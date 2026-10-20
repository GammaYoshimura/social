<?php
	echo "<h2>Mensajeria Privada: Enviados</h2>";
	echo "<div id='mps_separador'></div>";

	$q_mensajes = db_query("SELECT *, DATE_FORMAT(mps.fecha, '%W %e de %M a las %H:%i') AS fechaf FROM mps, usuarios LEFT JOIN fotos ON idfotos = idfotos_princi WHERE emisor = ? AND idusuarios = receptor ORDER BY idmps DESC", array((int)$global_idusuarios), 'i');
	
	while($r_mensajes=mysqli_fetch_assoc($q_mensajes)){
		$nombre_apellido = $r_mensajes['nombre']." ".$r_mensajes['apellidos'];
		
		if(strlen($r_mensajes['mp'])>20){
			$resumen_mp = substr($r_mensajes['mp'], 0, 20)."...";
		}else{
			$resumen_mp = $r_mensajes['mp'];
		}
		
		 print "<div class='mensaje' id='" . (int)$r_mensajes['idmps'] . "'>
					<div onclick='MP_toggle(this)' class='encabezado'>
						<div class='img'>
							<a href='perfil.php?id=" . (int)$r_mensajes['receptor'] . "'>
								<img src='" . e($r_mensajes['archivo']) . "'>
							</a>
						</div>
						<div class='datos'>
							<div class='nombre'><a href='perfil.php?id=" . (int)$r_mensajes['receptor'] . "'>" . e($nombre_apellido) . "</a></div>
							<div class='fecha'>" . e(utf8_encode($r_mensajes['fechaf'])) . "</div><br>
							<div class='resumen'>" . e($resumen_mp) . "</div>
						</div>
					</div>
					<div class='texto'>" . e($r_mensajes['mp']) . "</div>
				</div>";
	}
	echo "</div>";
?>


	<script>
		function MP_toggle(t){
			//Mostrar u ocultar
			$(t).parent().find(".texto").toggle();
			
			//Marcar como leido
			id=$(t).parent().attr("id");
			ajax_post({
				data : "mp_leido=1&id=" + id,
				retrieve: true
			});
			
		}
	</script>
