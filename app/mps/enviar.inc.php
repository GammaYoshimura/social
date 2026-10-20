<script>
	<?php
		require_once APP_ROOT . '/validador.class.php';
		$Validador = new Validador();
		if(!isset($_GET['receptor']) || $_GET['receptor'] === ''){
			$Validador->SetInput(array('name' => 'receptor', 'alias' => 'Destinatario', 'obligatorio' => 'si'));
		}
		$Validador->SetInput(array('name' => 'mensaje_mp', 'alias' => 'Mensaje', 'min' => '1'));
		$Validador->GeneraValidadorJS();
	?>
		function mp_enviar(){
			if(validador()=="form_ok"){
				receptor=$("input[name='receptor_id']").val();
				mensaje=$("textarea[name='mensaje_mp']").val();
				
				ajax_post({
					data : "mp_enviar=1&receptor=" + receptor + "&mensaje=" + mensaje,
					reload : true,
				});
			}
		}
	</script>
	
	<h2>Mensajeria Privada: Redactar</h2>
	<div id='mps_separador'></div>
<?php
	//Si hay un destinatario preseleccionado
	if(isset($_GET['receptor']) && $_GET['receptor'] !== ''){
		$recipientId = filter_var($_GET['receptor'], FILTER_VALIDATE_INT);
		$query = $recipientId ? db_query("SELECT * FROM usuarios LEFT JOIN fotos ON idfotos_princi = idfotos WHERE idusuarios = ?", array((int)$recipientId), 'i') : false;
		$usuario = $query ? mysqli_fetch_assoc($query) : false;
		if (!$usuario || !can_view_profile($recipientId)) { http_response_code(404); exit('Destinatario no disponible'); }
		
		print "<form action='#' onsubmit='return false'>
				Destinatario: <img id='receptor_icon' src='" . e($usuario['archivo']) . "'>
				<input id='receptor' name='receptor' class='' value='" . e($usuario['nombre'] . ' ' . $usuario['apellidos']) . "' disabled size='40'>
				<input id='receptor_id' name='receptor_id' style='display: none;' value='" . (int)$recipientId . "'>";
	}else{
		$query=db_query("
			SELECT idusuarios, nombre, apellidos, archivo
			FROM amigos, usuarios
			LEFT JOIN fotos
			ON idfotos_princi=idfotos
			WHERE (user1 = ? AND user2 = idusuarios) OR (user2 = ? AND user1 = idusuarios)
		", array((int)$global_idusuarios, (int)$global_idusuarios), 'ii');

		if(mysqli_num_rows($query)>0){
			?>
			<script src="assets/js/forms.js"></script>
			<form action="#" onsubmit="return false">
				<div class="ui-widget">
					<?php //TODO: al borrar letras, deseleccionar amigo ?>
				   Destinatario: <img id="receptor_icon">	
					<div class="input">
						<span>
							<input placeholder="Nombre de un amigo" id="receptor" name="receptor" class="validable" type="text" value="<?php echo isset($_POST['email']) && is_string($_POST['email']) ? e($_POST['email']) : ''; ?>" size='40' autofocus>
						</span>
					</div>
				    <input id="receptor_id" name="receptor_id" style="display: none;" /><br>
				</div>


					<script>
					var receptor_seleccionado=false;
				    $(function() {
						<?php
						$friends = array();
						while ($row = mysqli_fetch_assoc($query)) {
							$friends[] = array('value' => (int)$row['idusuarios'], 'label' => NombreApellido($row['nombre'] . ' ' . $row['apellidos']), 'icon' => $row['archivo']);
						}
						?>
						var amigos = <?php echo json_encode($friends, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
						
						$( "#receptor" ).autocomplete({
							source: amigos,
							minLength : 0,
							focus : function(event, ui) {
								return false;
							},
							select : function(event, ui) {
								//AL PULSAR UN RESULTADO
								receptor_seleccionado=true;
								$("#receptor").val("").addClass('input_ok').removeClass('input_error');
								$("#receptor").val(ui.item.label);
								$("#receptor_id").val(ui.item.value);
								$( "#receptor_icon" ).attr( "src",ui.item.icon );
								$("textarea[name='mensaje']").focus();
								return false;
							}
						}) .data( "ui-autocomplete" )._renderItem = function( ul, item ) {
							var link = $("<a>");
							$("<img>").attr("src", item.icon).addClass("autocomplete_img").appendTo(link);
							$("<div>").addClass("autocomplete_label").text(item.label).appendTo(link);
							return $("<li>").append(link).appendTo(ul);
						};
					});
					
					$(window).ready(function(){
						$( "#receptor" ).autocomplete("search");
						
						//Aseguramos la seleccion del autocompletado
						$("#receptor").blur(function(e){
							if(!receptor_seleccionado){
								$("#receptor").val("").addClass('input_error').removeClass('input_ok');
							}
						});
						$("#receptor").focus(function(e){
							$( "#receptor" ).autocomplete("search");
						});
					});
					</script>
		<?php
		}else{
			echo "No tienes amigos";
		}
	}
	?>
	<br>
	Mensaje:<br>
	<div class="input">
		<span>
			<textarea name="mensaje_mp" class="validable" placeholder="Escribele tu mensaje aqui"></textarea>
		</span>
	</div>
	<button type='button' class="azul" onclick="mp_enviar();"><span><b>Enviar</b></span></button>
</form>
