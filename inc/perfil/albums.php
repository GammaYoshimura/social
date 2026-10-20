<div class="marco_small">
	<h2>Albums de imagenes de <?php echo e($r_user['nombre']); ?></h2>
	<?php
	
	//Subidas
	echo "<div class='album'><div class='album_titulo'><a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=subidas'>Fotos subidas</a></div>";
	$fotos = db_query("SELECT * FROM fotos WHERE uploader = ? ORDER BY idfotos DESC LIMIT 3", array((int)$_GET['id']), 'i');
	if (mysqli_num_rows($fotos)) {
		while ($row = mysqli_fetch_assoc($fotos)) {
			echo "<div class='foto'><a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=subidas'><img class='album_cubierta' alt='cubierta album' src='" . e($row['archivo']) . "' /></a></div>";
		}
	} else {
		echo "No ha subido nignuna foto";
	}
	echo "</div>";

	//Etiquetadas
	echo "<div class='album'><div class='album_titulo'><a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=etiquetadas'>Fotos etiquetadas</a></div>";
	$fotos = db_query("SELECT * FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos ORDER BY idfotos DESC LIMIT 3", array((int)$_GET['id']), 'i');
	if (mysqli_num_rows($fotos)) {
		while ($row = mysqli_fetch_assoc($fotos)) {
			echo "<div class='foto'><a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=etiquetadas'><img class='album_cubierta' alt='cubierta album' src='" . e($row['archivo']) . "' /></a></div>";
		}
	} else {
		echo "No esta etiquetado en ninguna foto";
	}
	echo "</div>";

	//Personalizados
	$personalizados = db_query("SELECT * FROM albums WHERE usuarios_idusuarios = ?", array((int)$_GET['id']), 'i');
	if (mysqli_num_rows($personalizados) > 0) {
		echo "<h2 style='clear: both;margin-bottom:0px;'>Albums personales</h2>";
		while ($row = mysqli_fetch_assoc($personalizados)) {
			print("<div class='album'>
					<div class='album_titulo'>
						<a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=" . (int)$row['idalbums'] . "'>" . e($row['album']) . "</a>
						<div class='album_renombrar' onclick=\"album_renombrar(" . e(json_encode((int)$row['idalbums'])) . "," . e(json_encode($row['album'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"></div>
						<div class='album_borrar' onclick=\"album_borrar(" . e(json_encode((int)$row['idalbums'])) . "," . e(json_encode($row['album'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"></div>
					</div>");
			$fotos = db_query("SELECT * FROM fotos WHERE albums_idalbums = ? ORDER BY idfotos LIMIT 3", array((int)$row['idalbums']), 'i');
			if (mysqli_num_rows($fotos)) {
				while ($row2 = mysqli_fetch_assoc($fotos)) {
					echo "<div class='foto'><a href='album.php?iduser=" . (int)$_GET['id'] . "&amp;idalbum=" . (int)$row['idalbums'] . "'><img class='album_cubierta' style='bottom:" . $bottom . "px;left:" . $left . "px;max-width:" . $width . "px;max-height:" . $height . "px;' alt='cubierta album' src='" . e($row2['archivo']) . "' /></a></div>";
				}
			} else {
				echo "No tiene ninguna foto en este album";
			}
			echo "</div>";
		}
	}
	?>
	
	
	
</div>
