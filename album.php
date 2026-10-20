<?php
require ("inc/verify_login.php");
head("Albums");
require ("inc/estructura.inc.php");
require ("inc/chat.php");
?>
<div class="barra_full">
	<div class="marco">
		<?php

		##### CONSULTA SQL

		$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
		if (!$page || $page < 1 || !isset($_GET['idalbum']) || !is_string($_GET['idalbum'])) die("Solicitud no válida");
		$limit_min = ($page - 1) * 10;
		$albumType = $_GET['idalbum'];
		if ($albumType === 'subidas' || $albumType === 'etiquetadas') {
			$ownerId = isset($_GET['iduser']) ? filter_var($_GET['iduser'], FILTER_VALIDATE_INT) : false;
			if (!$ownerId || !can_view_profile($ownerId)) { http_response_code(404); exit('Album no disponible'); }
			if ($albumType === 'subidas') {
				$fromWhere = "FROM usuarios, fotos WHERE idusuarios = uploader AND uploader = ?";
				$params = array((int)$ownerId);
				$types = 'i';
			} else {
				$fromWhere = "FROM usuarios, fotos, etiquetas WHERE usuarios_idusuarios = ? AND usuarios_idusuarios = idusuarios AND idfotos = fotos_idfotos";
				$params = array((int)$ownerId);
				$types = 'i';
			}
		} else {
			$albumId = filter_var($albumType, FILTER_VALIDATE_INT);
			if (!$albumId) die('Album no válido');
			$albumOwner = db_query("SELECT usuarios_idusuarios FROM albums WHERE idalbums = ?", array((int)$albumId), 'i');
			$ownerRow = $albumOwner ? mysqli_fetch_assoc($albumOwner) : false;
			if (!$ownerRow || !can_view_profile($ownerRow['usuarios_idusuarios'])) { http_response_code(404); exit('Album no disponible'); }
			$ownerId = (int)$ownerRow['usuarios_idusuarios'];
			$fromWhere = "FROM usuarios, albums, fotos WHERE idusuarios = usuarios_idusuarios AND idalbums = ? AND idalbums = albums_idalbums AND idfotos <= 999999999";
			$params = array((int)$albumId);
			$types = 'i';
		}
		$sql_old = "SELECT * " . $fromWhere . " ORDER BY idfotos DESC";
		$fotos = db_query($sql_old . " LIMIT ?, 10", array_merge($params, array((int)$limit_min)), $types . 'i');

		if (mysqli_num_rows($fotos)) {
			$row = mysqli_fetch_assoc($fotos);

			##### ENCABEZADO
			if ($_GET['idalbum'] == "subidas") {
				echo "<h2 style='margin-bottom:0px;'>Fotos subidas por " . e($row['nombre']) . "</h2>";
			} elseif ($_GET['idalbum'] == "etiquetadas") {
				echo "<h2 style='margin-bottom:0px;'>Fotos etiquetadas de " . e($row['nombre']) . "</h2>";
			} else {
				echo "<h2 style='margin-bottom:0px;'>Fotos del album " . e($row['album']) . " de " . e($row['nombre']) . "</h2>";
			}
			
			##### MOSTRAR FOTOS
			mysqli_data_seek($fotos, 0);
			while ($row = mysqli_fetch_assoc($fotos)) {
				echo "<a href='fotos.php?iduser=" . (int)$row['idusuarios'] . "&amp;idalbum=" . rawurlencode($albumType) . "&amp;idfotos=" . (int)$row['idfotos'] . "' class='album_contenido'><img class='album_contenido' alt='' src='" . e($row['archivo']) . "' /><br></a>";
			}
		} else {
			echo "No has subido ninguna foto";
			die();
		}


		###### BARRA NAVEGACION
		$result = db_query("SELECT COUNT(*) AS total " . $fromWhere, $params, $types);
		$countRow = mysqli_fetch_assoc($result);
		$npage_max = ceil($countRow['total'] / 10);
		$npage_initial = $page;
		
		if($npage_initial>1)
			$anterior=$npage_initial-1;
		
		if($npage_initial<$npage_max)
			$siguiente=$npage_initial+1;
		
		$navegar_primera="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&page=1";
		if($anterior)
			$navegar_anterior="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&page=".$anterior;
		if($siguiente)
			$navegar_siguiente="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&page=".$siguiente;
		$navegar_ultima="?iduser=".(int)$ownerId."&idalbum=".rawurlencode($albumType)."&page=".$npage_max;
	
				?>
		<script>
			//Declarando variables
			iduser = <?php echo (int)$ownerId; ?>;
			idalbum = <?php echo json_encode($albumType, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
			page = <?php echo (int)$page; ?>;
		</script>
		
		<?php
		echo "<div id='barra_navegacion'>";
		if ($npage_initial > 1) {
			echo "<img class='flecha_back_top' src='css/flechas/flecha_left_top.jpg' onclick=\"location.href=" . e(json_encode($navegar_primera)) . "\">";
			echo "<img class='flecha_back' src='css/flechas/flecha_left.jpg' onclick=\"location.href=" . e(json_encode($navegar_anterior)) . "\">";
		}
		echo "<div class='texto'>" . $npage_initial . " de " . $npage_max . "</div>";
		if ($npage_initial < $npage_max) {
			echo "<img class='flecha_next' src='css/flechas/flecha_right.jpg' onclick=\"location.href=" . e(json_encode($navegar_siguiente)) . "\">";
			echo "<img class='flecha_next_top' src='css/flechas/flecha_right_top.jpg' onclick=\"location.href=" . e(json_encode($navegar_ultima)) . "\">";
		}
		echo "</div>";
		?>
		</div>
		</div>
	</div>
