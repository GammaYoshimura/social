<script>
	function peticion_aceptar(idusuario){		
		ajax_post({
			data : "peticion_amistad_aceptar=1&emisor="+idusuario,
			reload : true
		});
	}
	function peticion_ignorar(idusuario){		
		ajax_post({
			data : "peticion_amistad_ignorar=1&emisor="+idusuario,
			reload : true
		});
	}
	
</script>
<?php
$q_peticiones = db_query("SELECT *, archivo, DATE_FORMAT(peticiones.fecha, '%d/%m/%Y %H:%i') AS fechaf
		FROM peticiones, usuarios
		LEFT JOIN fotos
		ON idfotos=idfotos_princi
		WHERE receptor = ? AND idusuarios = emisor", array((int)$global_idusuarios), 'i');
if (mysqli_num_rows($q_peticiones) > 0) {
	echo "Tienes " . mysqli_num_rows($q_peticiones) . " peticion(es) de amistad:<br>";
	echo "<div id='peticiones_amistad'>";
	while ($r_peticiones = mysqli_fetch_assoc($q_peticiones)) {
		//print_r($row);
		print "
			<div class='peticion_amistad'>
					<img src='" . e($r_peticiones['archivo']) . "'>
					<div class='nombre'><a class='link' href='perfil.php?id=" . (int)$r_peticiones['idusuarios'] . "'>" . e($r_peticiones['nombre'] . ' ' . $r_peticiones['apellidos']) . "</a> te ha enviado una solicitud de amistad</div>
					<div class='fecha'>" . e($r_peticiones['fechaf']) . "</div><br>
					<div class='acciones'>
						<div class='texto' onclick=''>
							<button type='button' class='azul' onclick=\"peticion_aceptar(" . (int)$r_peticiones['idusuarios'] . ")\"><span><b>Aceptar</b></span></button>
						</div>
						<div class='texto'>
							<button type='button' class='rojo' onclick=\"peticion_ignorar(" . (int)$r_peticiones['idusuarios'] . ")\"><span><b>Ignorar</b></span></button>
						</div>
					</div>
			</div>
		";
	}
	echo "</div>";
}
?>
