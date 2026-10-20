<?php
	if(mysqli_num_rows($q_fotos)){
		//Navegacion de fotos
		$navegar_primera="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&idfotos=".(int)$r_fotos['primera'];
		if($r_fotos['anterior'])
			$navegar_anterior="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&idfotos=".(int)$r_fotos['anterior'];
		if($r_fotos['siguiente'])
			$navegar_siguiente="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&idfotos=".(int)$r_fotos['siguiente'];
		$navegar_ultima="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&idfotos=".(int)$r_fotos['ultima'];
		?>
		<script>
			//Declarando variables
			<?php if((int)$ownerId === (int)$global_idusuarios){
				echo "propietario = true;";
			}else{
				echo "propietario = false;";
			} ?>
			
			iduser = <?php echo (int)$ownerId; ?>;
			idalbum = <?php echo (int)$r_fotos['albums_idalbums']; ?>;
			idfoto = <?php echo (int)$r_fotos['idfotos']; ?>;
			
			tecla_primera = <?php echo json_encode($navegar_primera); ?>;
			tecla_anterior = <?php echo json_encode(isset($navegar_anterior) ? $navegar_anterior : ''); ?>;
			tecla_siguiente = <?php echo json_encode(isset($navegar_siguiente) ? $navegar_siguiente : ''); ?>;
			tecla_ultima = <?php echo json_encode($navegar_ultima); ?>;
		</script>
		
		<div class="barra_izq_centro">
			<div class="marco">
		<?php
		###### TITULO FOTO
		print "<div id='titulo'>
			<div class='original'>" . e($r_fotos['titulo']) . "</div>
			<div class='input edicion'>
				<span>
					<input name='foto_titulo' type='text' value='" . e($r_fotos['titulo']) . "' autocomplete='off' placeholder='Titulo de la foto'>
				</span>
			</div>
		</div>\n";
		
			//echo "<div class='original'>".$r_fotos['titulo']."</div>";
			//echo "<input type='text' name='foto_titulo' value='".$r_fotos['titulo']."' class='edicion' size='80'>";
		
		
		####### FOTO	
		echo "<div id='foto_marco_padre'><div id='foto_marco_medio' style='width:700px;'><div id='foto_marco'>";
			echo "<img id='foto' style='max-width:700px;max-height:600px;cursor:pointer;' alt='" . e($r_fotos['titulo']) . "' src='" . e($r_fotos['archivo']) . "'";
				if($r_fotos['siguiente']){
					?>
					onclick="location.href=<?php echo e(json_encode($navegar_siguiente)); ?>"
					<?php
				}
			echo "/>";
			$etiquetados = db_query("SELECT idusuarios, nombre, apellidos, archivo, x, y
												FROM etiquetas, usuarios
												LEFT JOIN fotos ON idfotos_princi = idfotos
												WHERE fotos_idfotos = ?
												AND usuarios_idusuarios = idusuarios", array((int)$r_fotos['idfotos']), 'i');
			while($p = mysqli_fetch_assoc($etiquetados)){
				echo "<div class='etiquetado etiqueta_" . (int)$p['idusuarios'] . "' style='left:" . (int)$p['x'] . "px;top:" . (int)$p['y'] . "px;' id='etiqueta_" . (int)$p['idusuarios'] . "'>
					<div class='etiqueta_nombre'>" . e($p['nombre'] . " " . $p['apellidos']) . "</div>
				</div>";
			}
			
		####### BARRA NAVEGACION
		echo "<div id='barra_navegacion'>";
		if($r_fotos['actual']>1){
			echo "<img class='flecha_back_top' src='assets/images/flechas/flecha_left_top.JPG' onclick=\"location.href=" . e(json_encode($navegar_primera)) . "\">";
			echo "<img class='flecha_back' src='assets/images/flechas/flecha_left.JPG' onclick=\"location.href=" . e(json_encode($navegar_anterior)) . "\">";
		}
		echo "<div class='texto'>" . (int)$r_fotos['actual'] . " de " . (int)$r_fotos['totales'] . "</div>";
		if($r_fotos['actual']<$r_fotos['totales']){
			echo "<img class='flecha_next' src='assets/images/flechas/flecha_right.JPG' onclick=\"location.href=" . e(json_encode($navegar_siguiente)) . "\">";
			echo "<img class='flecha_next_top' src='assets/images/flechas/flecha_right_top.JPG' onclick=\"location.href=" . e(json_encode($navegar_ultima)) . "\">";
		}
		echo "</div>";
	}else{
		?>
		<div class="centrar">
		<div class="error_ajustable">
		<?php
		if($_GET['iduser']==$global_idusuarios){
			if($_GET['idalbum']=='subidas'){
				echo "No has subido ninguna foto";
			}elseif($_GET['idalbum']=='etiquetadas'){
				echo "No estas etiquetado en ninguna foto";
			}else{
				echo "Este album todavia no contiene ninguna fotografia";
			}
		}else{
			if($_GET['idalbums']=='subidas'){
				echo e($global_nombre)." no ha subido ninguna foto";
			}elseif($_GET['idalbums']=='etiquetadas'){
				echo e($global_nombre)." no esta etiquetado en ninguna foto";
			}else{
				echo "Este album todavia no contiene ninguna fotografia";
			}
		}
		echo "</div></div>";
		die();
	}
	?>
	</div>
	</div>
</div>
</div>
</div>
