<?php
	require_once __DIR__ . '/app/verify_login.php';
	head("Gente");
	echo "<body id='seccion_gente'>";
	require APP_ROOT . '/estructura.inc.php';
?>
<div class='barra_izq_centro' style="width: 710px;">
	<div class="marco">
				<script>
					function peticion_enviar(idusuario){
						ajax_post({
							data: "peticion_amistad_enviar=1&idusuario="+idusuario
						});
						$("#resultado"+idusuario).find(".estado_amistad button").attr({"disabled":"true", onclick:""});
						$("#resultado"+idusuario).find(".estado_amistad button b").text("Peticion enviada");
					}
					
					function cambiar_pagina(number){
						$("input[name='page']").val(number);
						$("form[name='busqueda']").submit();
					}
					
					$(document).ready(function(){
						// Marcar la opcion del select elegido
						$("select[name='provincia']").find("option[value='<?php echo isset($_POST['provincia']) ? (int)$_POST['provincia'] : 0; ?>']").attr("selected","true");
					});
				</script>
				
				<?php			
				$params = array((int)$global_idusuarios, (int)$global_idusuarios, (int)$global_idusuarios, (int)$global_idusuarios, (int)$global_idusuarios);
				$types = 'iiiii';
				$where = " WHERE idusuarios <> ?";
				if (isset($_POST['nombre']) && is_string($_POST['nombre']) && $_POST['nombre'] !== '') {
					$where .= " AND nombre LIKE ?";
					$params[] = '%' . $_POST['nombre'] . '%'; $types .= 's';
				}
				if (isset($_POST['apellidos']) && is_string($_POST['apellidos']) && $_POST['apellidos'] !== '') {
					$where .= " AND apellidos LIKE ?";
					$params[] = '%' . $_POST['apellidos'] . '%'; $types .= 's';
				}
				foreach (array('edad_menor', 'edad_mayor') as $ageField) {
					$age = isset($_POST[$ageField]) ? filter_var($_POST[$ageField], FILTER_VALIDATE_INT) : false;
					if ($age && $age >= 18 && $age <= 100) {
						$where .= $ageField === 'edad_menor' ? " AND fnac <= DATE_SUB(CURDATE(), INTERVAL ? YEAR)" : " AND fnac >= DATE_SUB(CURDATE(), INTERVAL ? YEAR)";
						$params[] = (int)$age; $types .= 'i';
					}
				}
				$province = isset($_POST['provincia']) && $_POST['provincia'] !== '' ? filter_var($_POST['provincia'], FILTER_VALIDATE_INT) : false;
				if ($province !== false) { $where .= " AND provincia = ?"; $params[] = (int)$province; $types .= 'i'; }
				if (isset($_POST['sexo']) && in_array($_POST['sexo'], array('h', 'm'), true)) { $where .= " AND sexo = ?"; $params[] = $_POST['sexo']; $types .= 's'; }
				
				$page = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : 1;
				if (!$page || $page < 1) $page = 1;
				$_POST['page'] = $page;
				$limit = ($page - 1) * 5;
				$sql = "SELECT *, FLOOR(DATEDIFF(CURDATE(),fnac)/365) AS edad,
					(SELECT count(*) FROM amigos WHERE user1 = ? AND user2 = idusuarios OR user2 = ? AND user1 = idusuarios) AS amigo,
					(SELECT count(*) FROM peticiones WHERE emisor = ? AND receptor = idusuarios OR receptor = ? AND emisor = idusuarios) AS enviada
					FROM usuarios LEFT JOIN fotos ON idfotos_princi = idfotos" . $where . " LIMIT ?, 5";
				$searchParams = array_merge(array_slice($params, 0, 4), array_slice($params, 4), array((int)$limit));
				$searchTypes = 'iiii' . substr($types, 4) . 'i';
				
				//TODO: Debug
				/*echo "<pre>";
				print_r($_POST);
				echo "</pre>";
				echo "$sql<br>";*/
				
				echo "<div id='resultados'>";
				$q_search = db_query($sql, $searchParams, $searchTypes);
				if(mysqli_num_rows($q_search)){
					error_mysql();
					while($r_search = mysqli_fetch_assoc($q_search)){
						$userId = (int)$r_search['idusuarios'];
						$safeName = e($r_search['nombre'] . " " . $r_search['apellidos']);
						//print_r($r_search);
						
						//Estado de la amistad
						if($r_search['amigo']){
							$estado_amistad="";
							$nombre = "<a href='perfil.php?id=" . $userId . "' class='link'>" . $safeName . "</a>";
						}elseif($r_search['enviada']){
							$estado_amistad="<button type='button' disabled class='verde'><span><b>Peticion enviada</b></span></button>";
							$nombre = $safeName;
						}else{
							$estado_amistad="<button type='button' class='verde' onclick=\"peticion_enviar(" . $userId . ")\"><span><b>Agregar</b></span></button>";
							$nombre = $safeName;
						}
						
						// Imprimimos los resultados
						//TODO: Añadir estado a los resultados
						print "
							<div class='resultado' id='resultado{$r_search['idusuarios']}'>
								<div class='img'>
									<img alt='foto principal' src='" . e($r_search['archivo']) . "' />
								</div>
								<div class='datos'>
									<div class='nombre'>{$nombre}</div>
									<div class='info'>
										{$r_search['edad']} Años<br>
										".e(IdProvincia($r_search['provincia']))."<br>
									</div>
								</div>
								<div class='right'>
									<div class='estado_amistad'>
										{$estado_amistad}
									</div>
									<div class='mp'>
										<button type='button' class='azul' onclick=\"location.href='mp.php?modo=enviar&amp;receptor=" . $userId . "'\"><span><b>Enviar MP</b></span></button>
									</div>
								</div>
							</div>
						";
					}
					
					//Numeracion paginas
					$countParams = array_slice($params, 4);
					$countTypes = substr($types, 4);
					$q_search_nums = db_query("SELECT COUNT(*) AS total FROM usuarios" . $where, $countParams, $countTypes);
					$r_search_nums = mysqli_fetch_assoc($q_search_nums);
			
					$siguiente = $_POST['page'] + 1;
					$anterior = $_POST['page'] - 1;
					$ultima = ceil($r_search_nums['total'] / 5);
			
					//BARRA NAVEGACION
					echo "<div id='barra_navegacion'>";
						if ($_POST['page'] > 1) {
							echo "<img class='flecha_back_top' src='css/flechas/flecha_left_top.jpg' onclick=\"cambiar_pagina(1);\">";
							echo "<img class='flecha_back' src='css/flechas/flecha_left.jpg' onclick=\"cambiar_pagina($anterior);\">";
						}
						echo "<div class='texto'>" . (int)$_POST['page'] . " de " . (int)$ultima . "</div>";
						
					if ($_POST['page'] < $ultima) {
							echo "<img class='flecha_next' src='css/flechas/flecha_right.jpg' onclick=\"cambiar_pagina($siguiente);\">";
							echo "<img class='flecha_next_top' src='css/flechas/flecha_right_top.jpg' onclick=\"cambiar_pagina($ultima);\">";
						}
					echo "</div>";
				}else{
					print "<div class='resultado'>No se han encontrado resultados</div>";
				}
				echo "</div>";
		?>
	</div>
</div>
<div class="barra_der" style="width: 270px;">
	<div class="marco_small">
		<h3>Busqueda</h3>
		<form name="busqueda" method='post' action='gente.php'>
				<div class="input">
					<span>
						<input name="nombre" type="text" value="<?php echo isset($_POST['nombre']) && is_string($_POST['nombre']) ? e($_POST['nombre']) : ''; ?>" placeholder="Nombre">
					</span>
				</div><br>
				
				<div class="input">
					<span>
						<input name="apellidos" type="text" value="<?php echo isset($_POST['apellidos']) && is_string($_POST['apellidos']) ? e($_POST['apellidos']) : ''; ?>" placeholder="Apellidos">
					</span>
				</div><br>
				
				Edad entre:
				<div class="input">
					<span class="select">
						<select name="edad_menor">
							<option value="">-</option>
							<?php
								for($i=18;$i<100;$i++){
									echo "<option";
									if($_POST['edad_menor']==$i){ echo " selected";}
										echo ">{$i}</option>";
								}
							?>
						</select>
					</span>
				</div>y<div class="input">
					<span class="select">
						<select name="edad_mayor">
							<option value="">-</option>
							<?php
								for($i=18;$i<100;$i++){
									echo "<option";
									if($_POST['edad_mayor']==$i){ echo " selected";}
										echo ">{$i}</option>";
								}
							?>
						</select>
					</span>
				</div><br>
				
				<div style="word-spacing: 30px;">Sexo: 
					<input type="radio" name="sexo" value="h" id="sexo_hombre" <?php if($_POST['sexo'] == "h") echo "checked"; ?>/>
					<label for="sexo_hombre" class="label_radio label_Sexo"> </label><label for="sexo_hombre">Hombre</label>
					<input type="radio" name="sexo" value="m" id="sexo_mujer" <?php if($_POST['sexo'] == "m") echo "checked"; ?>/>
					<label for="sexo_mujer" class="label_radio label_Sexo"></label><label for="sexo_mujer">Mujer</label>
				</div><br>
				
				
				
				<?php //TODO: Amigo, Amigo de amigos, Todos ?>
				
				Provincia: 
				<div class="input">
					<span class="select">
						<select name="provincia">
							<?php require APP_ROOT . '/select_provincias.html'; ?>
						</select>
					</span>
				</div>
				<input type="hidden" name='page' value="1">
				<input type="hidden" name='busqueda' value="true">
				<button type='submit' class="azul"><span><b>Buscar</b></span></button>
			</form>
	</div>
</div>

<?php
require APP_ROOT . '/chat.php';
?>
