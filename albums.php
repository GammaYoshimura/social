<?php
require_once __DIR__ . '/app/verify_login.php';
head("Albums");
echo "<body id='seccion_albums'>";
require APP_ROOT . '/estructura.inc.php';
?>
<div class="barra_full">
	<div class="marco">
	<?php

	//Albums de fotos de ¿?
	if (!isset($_GET['iduser']) || $_GET['iduser'] === '') {
		$_GET['iduser'] = $global_idusuarios;
		$user = 'yo';
		echo "<h2 style='margin-bottom:0px;'>Mis Albums de imagenes</h2>";
	} else {
		$requestedId = filter_var($_GET['iduser'], FILTER_VALIDATE_INT);
		if (!$requestedId || !can_view_profile($requestedId)) { http_response_code(404); exit('Album no disponible'); }
		$_GET['iduser'] = (int)$requestedId;
		$usuario = db_query("SELECT nombre FROM usuarios WHERE idusuarios = ?", array((int)$requestedId), 'i');
		$row = $usuario ? mysqli_fetch_assoc($usuario) : false;
		if (!$row) { http_response_code(404); exit('Usuario no disponible'); }
		echo "<h2 style='margin-bottom:0px;'>Albums de imagenes de " . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . "</h2>";
	}

	//Subidas
	echo "<div class='album'><div class='album_titulo'><a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=subidas'>Fotos subidas</a></div>";
	$fotos = db_query("SELECT * FROM fotos WHERE uploader = ? ORDER BY idfotos DESC LIMIT 3", array((int)$_GET['iduser']), 'i');
	if (mysqli_num_rows($fotos)) {
		$bottom = 0;
		$left = 0;
		while ($row = mysqli_fetch_assoc($fotos)) {
			echo "<a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=subidas'><img class='album_cubierta' style='bottom:" . $bottom . "px;left:" . $left . "px;max-width:" . $width . "px;max-height:" . $height . "px;' alt='cubierta album' src='" . e($row['archivo']) . "' /><br></a><br>";

			$bottom = $bottom + 90;
			$left = $left + 90;
		}
	} else {
		echo "No has subido ninguna foto";
	}
	echo "</div>";

	//Etiquetadas
	echo "<div class='album'><div class='album_titulo'><a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=etiquetadas'>Fotos etiquetadas</a></div>";
	$fotos = db_query("SELECT * FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos ORDER BY idfotos DESC LIMIT 3", array((int)$_GET['iduser']), 'i');
	if (mysqli_num_rows($fotos)) {
		$bottom = 0;
		$left = 0;
		while ($row = mysqli_fetch_assoc($fotos)) {
			echo "<a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=etiquetadas'><img class='album_cubierta' style='bottom:" . $bottom . "px;left:" . $left . "px;max-width:" . $width . "px;max-height:" . $height . "px;' alt='cubierta album' src='" . e($row['archivo']) . "' /></a><br>";
			$bottom = $bottom + 90;
			$left = $left + 90;
		}
	} else {
		echo "No estas etiquetado en ninguna foto";
	}
	echo "</div>";

	//Personalizados
	echo "<h2 style='clear: both;margin-bottom:0px;'>Albums personales</h2>";

	$personalizados = db_query("SELECT * FROM albums WHERE usuarios_idusuarios = ?", array((int)$_GET['iduser']), 'i');
	if (mysqli_num_rows($personalizados) > 0) {
		while ($row = mysqli_fetch_assoc($personalizados)) {
			print("<div class='album'>
					<div class='album_titulo'>
						<a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=" . (int)$row['idalbums'] . "'>" . e($row['album']) . "</a>
						<div class='album_renombrar' onclick=\"album_renombrar(" . e(json_encode((int)$row['idalbums'])) . "," . e(json_encode($row['album'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"></div>
						<div class='album_borrar' onclick=\"album_borrar(" . e(json_encode((int)$row['idalbums'])) . "," . e(json_encode($row['album'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"></div>
					</div>");
			$fotos = db_query("SELECT * FROM fotos WHERE albums_idalbums = ? ORDER BY idfotos LIMIT 3", array((int)$row['idalbums']), 'i');
			if (mysqli_num_rows($fotos)) {
				$bottom = 0;
				$left = 0;
				while ($row2 = mysqli_fetch_assoc($fotos)) {
					echo "<a href='album.php?iduser=" . (int)$_GET['iduser'] . "&amp;idalbum=" . (int)$row['idalbums'] . "'><img class='album_cubierta' style='bottom:" . $bottom . "px;left:" . $left . "px;max-width:" . $width . "px;max-height:" . $height . "px;' alt='cubierta album' src='" . e($row2['archivo']) . "' /></a><br>";
					$bottom = $bottom + 90;
					$left = $left + 90;
				}
			} else {
				echo "No hay ninguna foto en este album";
			}
			echo "</div>";
		}
	}

	//Formulario creacion de albumes
	?>
	<div style='display: inline-block;margin: 25px;'>

	Crea un album personalizado
	<hr>
	<div class="input">
		<span>
			<input name="album" id="album_id" type="text" placeholder="Nombre del album">
		</span>
	</div>
	<button type='button' class="azul" onclick="album_crear()"><span><b>Crear album</b></span></button>
	</div>
	</div>

	<script>
		$(document).ready(function() {
			// Muestra y oculta los menús
			$('.album').hover(function(e) {
				$(this).find('.album_renombrar,.album_borrar').css("visibility", "visible");
			}, function(e) {
				$(this).find('.album_renombrar,.album_borrar').css("visibility", "hidden");
			});
		});

		function album_crear() {
			var name = $("input[name='album']").val();
			if (name != null && name != "") {
				ajax_post({
					data:'album='+name,
					reload:true
				});
			}

		}
		
		function album_renombrar(id, name) {
			var name = prompt("Escribe el nombre del album", name);
			if (name != null && name != "") {
				ajax_post({data:'album_renombrar=' + name + '&album_id=' + id,reload: true});
			}
		}

		function album_borrar(id, name) {
			var r = confirm("¿Estás seguro de borrar el album \"" + name + "\" ?");
			if (r == true && id != "") {
				ajax_post({data:'album_borrar=' + id,reload: true});
			}
		}
	</script>
</div>

<?php
require APP_ROOT . '/chat.php';
?>
