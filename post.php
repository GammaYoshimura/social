<?php
require_once __DIR__ . '/app/auth/verify_login.php';

$csrfToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
	http_response_code(403);
	exit('Solicitud no válida');
}

########	GENERAL
if(isset($_POST['online_keep'])){
	db_query("UPDATE usuarios SET online = now() WHERE idusuarios = ?", array((int)$global_idusuarios), 'i');
	die();
}

########	PERFIL

if (isset($_POST['estado_cambiar']) && isset($_POST['estado']) && is_string($_POST['estado'])) {
	db_query("UPDATE usuarios SET estado = ? WHERE idusuarios = ?", array($_POST['estado'], (int)$global_idusuarios), 'si');
	
	// Novedad
	$novedad = array("propietario" => $global_idusuarios, "tipo" => 'estado', "datos" => $_POST['estado']);
	novedades($novedad);
	die();
}


if (isset($_POST['tablon_leer_comentarios'])) {
	$requestedUser = isset($_POST['idusuarios']) ? filter_var($_POST['idusuarios'], FILTER_VALIDATE_INT) : false;
	$page = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : 1;
	if (!$requestedUser || !$page || $page < 1) die('Solicitud no válida');
	if (!can_view_profile($requestedUser)) { http_response_code(404); exit('Perfil no disponible'); }
	$limit = ($page - 1) * 10;
	
	$q_comentarios = db_query("SELECT idusuarios, nombre, apellidos, comentario, archivo,
			DATE_FORMAT(tablon.fecha, '%d/%m/%Y %H:%i') AS fecha2
			FROM tablon, usuarios LEFT JOIN fotos ON idfotos = idfotos_princi
			WHERE receptor = ? AND emisor = idusuarios ORDER BY tablon.fecha DESC LIMIT ?, 10", array((int)$requestedUser, (int)$limit), 'ii');
	
	
	if((int)$requestedUser === (int)$global_idusuarios){
		//El visitante es == propietario del tablon
		db_query("UPDATE tablon SET leido = 1 WHERE receptor = ?", array((int)$global_idusuarios), 'i'); //TODO: marcar solo los comentarios visualizados como leidos
		
		// Notificacion
		$notificacion = array("propietario" => $global_idusuarios, "tipo" => 'tablon');
		notificacion($notificacion);
	}
	
	if (mysqli_num_rows($q_comentarios) > 0) {
		while ($r_comentarios = mysqli_fetch_assoc($q_comentarios)) {
			print "<div class='foto_comentario'> 
					 <img src='" . e($r_comentarios['archivo']) . "' class='imagen_perfil'> 
					 <div> 
						<div> 
							<div class='foto_come_titu'><a class='link' href='perfil.php?id=" . (int)$r_comentarios['idusuarios'] . "'>" . e($r_comentarios['nombre']) . " " . e($r_comentarios['apellidos']) . "</a></div> 
							<div class='foto_come_fecha'>" . e($r_comentarios['fecha2']) . "</div> 
						</div> 
						<div class='foto_come_men'>" . e($r_comentarios['comentario']) . "</div> 
					</div> 
				</div>";
		}

		//Numeracion paginas
		$q_numeracion = db_query("SELECT count(*) AS total FROM tablon WHERE receptor = ?", array((int)$requestedUser), 'i');
		$r_numeracion = mysqli_fetch_assoc($q_numeracion);

		$siguiente = $page + 1;
		$anterior = $page - 1;
		$ultima = ceil($r_numeracion['total'] / 10);

		//BARRA NAVEGACION
		echo "<div id='barra_navegacion'>";
			if ($page > 1) {
				echo "<img class='flecha_back_top' src='assets/images/flechas/flecha_left_top.JPG' onclick=\"tablon_leer_comentarios(idusuarios, 1);\">";
				echo "<img class='flecha_back' src='assets/images/flechas/flecha_left.JPG' onclick=\"tablon_leer_comentarios(idusuarios, {$anterior});\">";
			}
			echo "<div class='texto'>".$page." de ".$ultima."</div>";
			
		if ($page < $ultima) {
				echo "<img class='flecha_next' src='assets/images/flechas/flecha_right.JPG' onclick=\"tablon_leer_comentarios(idusuarios, {$siguiente});\">";
				echo "<img class='flecha_next_top' src='assets/images/flechas/flecha_right_top.JPG' onclick=\"tablon_leer_comentarios(idusuarios, " . $ultima . ");\">";
			}
		
	}
	die();
}


if (isset($_POST['tablon_enviar_comentario']) && isset($_POST['receptor'], $_POST['comentario']) && is_string($_POST['comentario'])) {
	$recipient = filter_var($_POST['receptor'], FILTER_VALIDATE_INT);
	if (!$recipient) die('Destinatario no válido');
	if (!can_view_profile($recipient)) { http_response_code(404); exit('Perfil no disponible'); }
	// Comentario
	db_query("INSERT INTO tablon (emisor,receptor,comentario,fecha) VALUES (?, ?, ?, now())", array((int)$global_idusuarios, (int)$recipient, $_POST['comentario']), 'iis');

	// Notificacion
	$notificacion = array("propietario" => (int)$recipient, "tipo" => 'tablon');
	notificacion($notificacion);

	// Novedad
	$novedad = array("propietario" => (int)$recipient, "visitante" => $global_idusuarios, "tipo" => 'tablon', "datos" => $_POST['comentario']);
	novedades($novedad);
	die();
}

########	AMISTAD

if (isset($_POST['peticion_amistad_enviar'])) {
	$targetUser = isset($_POST['idusuario']) ? filter_var($_POST['idusuario'], FILTER_VALIDATE_INT) : false;
	if (!$targetUser || (int)$targetUser === (int)$global_idusuarios) die('Usuario no válido');
		//Comprobar no amistad
		$query = db_query("SELECT COUNT(*) AS cuenta FROM amigos WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?)", array((int)$targetUser, (int)$global_idusuarios, (int)$global_idusuarios, (int)$targetUser), 'iiii');
		$row = mysqli_fetch_assoc($query);
		if($row['cuenta']>0)
			die("Ya es tu amigo!");
		
		//Comprobar no peticion
		$query = db_query("SELECT COUNT(*) AS cuenta FROM peticiones WHERE (emisor = ? AND receptor = ?) OR (emisor = ? AND receptor = ?)", array((int)$targetUser, (int)$global_idusuarios, (int)$global_idusuarios, (int)$targetUser), 'iiii');
		$row = mysqli_fetch_assoc($query);
		if($row['cuenta']>0)
			die("Ya le enviastes una peticion de amistad!");
		
		//Insertar peticion
		db_query("INSERT INTO peticiones (emisor, receptor, fecha) VALUES (?, ?, now())", array((int)$global_idusuarios, (int)$targetUser), 'ii');
		
		// Notificacion
		$notificacion = array("propietario" => (int)$targetUser, "tipo" => 'peticion');
		notificacion($notificacion);
		die();
}

if (isset($_POST['peticion_amistad_aceptar'])) {
	$sender = isset($_POST['emisor']) ? filter_var($_POST['emisor'], FILTER_VALIDATE_INT) : false;
	if (!$sender) die('Usuario no válido');
	$pending = db_query("SELECT emisor FROM peticiones WHERE emisor = ? AND receptor = ? AND ignorada = 0", array((int)$sender, (int)$global_idusuarios), 'ii');
	if (!$pending || mysqli_num_rows($pending) !== 1) die('No hay una solicitud pendiente');
	$query = db_query("SELECT COUNT(*) AS cuenta FROM amigos WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?)", array((int)$sender, (int)$global_idusuarios, (int)$global_idusuarios, (int)$sender), 'iiii');
	$row = mysqli_fetch_assoc($query);
	if($row['cuenta']>0)
			die("Ya es tu amigo!");
	
	db_query("INSERT INTO amigos (user1,user2) VALUES (?, ?)", array((int)$sender, (int)$global_idusuarios), 'ii');
	db_query("DELETE FROM peticiones WHERE emisor = ? AND receptor = ?", array((int)$sender, (int)$global_idusuarios), 'ii');
	
	
	// Notificacion
	$notificacion = array("propietario" => $global_idusuarios, "tipo" => 'peticion');
	notificacion($notificacion);
	
	// Novedad
	$novedad = array("propietario" => $global_idusuarios, "visitante" => (int)$sender, "tipo" => 'amistad');
	novedades($novedad);
	
	$novedad = array("visitante" => $global_idusuarios, "propietario" => (int)$sender, "tipo" => 'amistad');
	novedades($novedad);
	die();
}

if (isset($_POST['peticion_amistad_ignorar'])) {
	$sender = isset($_POST['emisor']) ? filter_var($_POST['emisor'], FILTER_VALIDATE_INT) : false;
	if (!$sender) die('Usuario no válido');
	db_query("UPDATE peticiones SET ignorada = 1 WHERE emisor = ? AND receptor = ?", array((int)$sender, (int)$global_idusuarios), 'ii');
	error_mysql();
	
	// Notificacion
	$notificacion = array("propietario" => $global_idusuarios, "tipo" => 'peticion');
	notificacion($notificacion);

	die();
}



########	MENSAJERIA PRIVADA

if (isset($_POST['mp_enviar']) && isset($_POST['receptor'], $_POST['mensaje']) && is_string($_POST['mensaje'])) {
	$recipient = filter_var($_POST['receptor'], FILTER_VALIDATE_INT);
	if (!$recipient) die('Destinatario no válido');
	// Insertar mensaje
	db_query("INSERT INTO mps (emisor,receptor,mp,fecha) VALUES (?, ?, ?, now())", array((int)$global_idusuarios, (int)$recipient, $_POST['mensaje']), 'iis');
	
	// Notificacion
	$notificacion = array("propietario" => (int)$recipient, "tipo" => 'mp');
	notificacion($notificacion);
	die();
}

if (isset($_POST['mp_leido'])) {
	// Marcar como leido
	$messageId = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : false;
	if (!$messageId) die('Mensaje no válido');
	db_query("UPDATE mps SET leido = 1 WHERE idmps = ? AND receptor = ?", array((int)$messageId, (int)$global_idusuarios), 'ii');
	
	// Notificacion
	$notificacion = array("propietario" => $global_idusuarios, "tipo" => 'mp');
	notificacion($notificacion);
	die();
}



########	ALBUM

	if (isset($_POST['album']) && is_string($_POST['album']) && $_POST['album'] !== '') {
	$result = db_query("SELECT * FROM albums WHERE usuarios_idusuarios = ? AND album = ?", array((int)$global_idusuarios, $_POST['album']), 'is');
	if (mysqli_num_rows($result) > 0) {
		echo "Ya existe un album con el mismo nombre.";
	} else {
		db_query("INSERT INTO albums (usuarios_idusuarios, album) VALUES (?, ?)", array((int)$global_idusuarios, $_POST['album']), 'is');
	}
	die();
}

if (isset($_POST['album_renombrar']) && is_string($_POST['album_renombrar'])) {
	$albumId = isset($_POST['album_id']) ? filter_var($_POST['album_id'], FILTER_VALIDATE_INT) : false;
	if (!$albumId) die('Album no válido');
	db_query("UPDATE albums SET album = ? WHERE idalbums = ? AND usuarios_idusuarios = ?", array($_POST['album_renombrar'], (int)$albumId, (int)$global_idusuarios), 'sii');
	die();
}

if (isset($_POST['album_borrar'])) {
	$albumId = filter_var($_POST['album_borrar'], FILTER_VALIDATE_INT);
	if (!$albumId) die('Album no válido');
	db_query("DELETE FROM albums WHERE idalbums = ? AND usuarios_idusuarios = ?", array((int)$albumId, (int)$global_idusuarios), 'ii');
	die();
}

########	FOTOS
if (isset($_POST['foto_edicion'])) {
	$photoId = isset($_POST['idfotos']) ? filter_var($_POST['idfotos'], FILTER_VALIDATE_INT) : false;
	$albumId = isset($_POST['idalbum']) && $_POST['idalbum'] !== '' ? filter_var($_POST['idalbum'], FILTER_VALIDATE_INT) : null;
	if (!$photoId || ($albumId === false)) die('Foto o album no válido');
	$ownedPhoto = db_query("SELECT idfotos FROM fotos WHERE idfotos = ? AND uploader = ?", array((int)$photoId, (int)$global_idusuarios), 'ii');
	if (!$ownedPhoto || mysqli_num_rows($ownedPhoto) !== 1) die('Foto no válida');
	if ($albumId !== null) {
		$ownedAlbum = db_query("SELECT idalbums FROM albums WHERE idalbums = ? AND usuarios_idusuarios = ?", array((int)$albumId, (int)$global_idusuarios), 'ii');
		if (!$ownedAlbum || mysqli_num_rows($ownedAlbum) !== 1) die('Album no válido');
	}
	db_query("DELETE FROM etiquetas WHERE fotos_idfotos = ?", array((int)$photoId), 'i');
	error_mysql();
	
	preg_match_all("/[0-9]{1,}/", $_POST['etiquetas'], $salida, PREG_PATTERN_ORDER);
	$i2 = 0;
	for ($i = 0; $i < count($salida[0]); $i++) {
		if ($i2 == 0) {
			$id = $salida[0][$i];
			//echo "ID: ".$salida[0][$i]." - ";
		} elseif ($i2 == 1) {
			//echo "X: ".$salida[0][$i]." - ";
			$x = $salida[0][$i];
		} elseif ($i2 == 2) {
			//echo "Y: ".$salida[0][$i]."<br>";
			$y = $salida[0][$i];
			db_query("INSERT INTO etiquetas (fotos_idfotos, usuarios_idusuarios, x, y) VALUES (?, ?, ?, ?)", array((int)$photoId, (int)$id, (int)$x, (int)$y), 'iiii');
			error_mysql();
			$i2 = -1;
		}
		$i2++;
	}

	db_query("UPDATE fotos SET titulo = ?, albums_idalbums = ? WHERE idfotos = ? AND uploader = ?", array(isset($_POST['titulo']) ? $_POST['titulo'] : '', $albumId === null ? null : (int)$albumId, (int)$photoId, (int)$global_idusuarios), 'siii');
	error_mysql();
	die();
}

if (isset($_POST['foto_principal'])) {
	$photoId = filter_var($_POST['foto_principal'], FILTER_VALIDATE_INT);
	$ownedPhoto = $photoId ? db_query("SELECT idfotos FROM fotos WHERE idfotos = ? AND uploader = ?", array((int)$photoId, (int)$global_idusuarios), 'ii') : false;
	if (!$ownedPhoto || mysqli_num_rows($ownedPhoto) !== 1) die('Foto no válida');
	db_query("UPDATE usuarios SET idfotos_princi = ? WHERE idusuarios = ?", array((int)$photoId, (int)$global_idusuarios), 'ii');
	
	// Novedad
	$novedad = array("propietario" => $global_idusuarios, "tipo" => 'foto_principal', "datos" => $_POST['foto_principal']);
	novedades($novedad);
	die();
}

if (isset($_POST['foto_borrar'])) {
	$photoId = filter_var($_POST['foto_borrar'], FILTER_VALIDATE_INT);
	if (!$photoId) die('Foto no válida');
	$result = db_query("SELECT * FROM fotos WHERE uploader = ? AND idfotos = ?", array((int)$global_idusuarios, (int)$photoId), 'ii');
	if (mysqli_num_rows($result) == 1) {
		db_query("UPDATE usuarios SET idfotos_princi = 1 WHERE idfotos_princi = ? AND sexo = 'h'", array((int)$photoId), 'i');
		db_query("UPDATE usuarios SET idfotos_princi = 2 WHERE idfotos_princi = ? AND sexo = 'm'", array((int)$photoId), 'i');
		$result = mysqli_fetch_assoc($result);
		unlink($result['archivo']);

		db_query("DELETE FROM fotos WHERE uploader = ? AND idfotos = ?", array((int)$global_idusuarios, (int)$photoId), 'ii');
	}
	die();
}

if (isset($_POST['foto_comentario']) && is_string($_POST['foto_comentario'])) {
	$photoId = isset($_POST['idfotos']) ? filter_var($_POST['idfotos'], FILTER_VALIDATE_INT) : false;
	if (!$photoId) die('Foto no válida');
	if (!can_view_photo($photoId)) { http_response_code(404); exit('Foto no disponible'); }
	db_query("INSERT INTO fotos_comentarios (fotos_idfotos,emisor,comentario,fecha) VALUES (?, ?, ?, now())", array((int)$photoId, (int)$global_idusuarios, $_POST['foto_comentario']), 'iis');
	//TODO: Novedad/Notificar comentario a dueño & etiquetados
	die();
}

if (isset($_POST['foto_leer_comentarios'])) {
	//TODO: Notificaciones, marcar comentarios como leidos
	$page = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : 1;
	$photoId = isset($_POST['idfoto']) ? filter_var($_POST['idfoto'], FILTER_VALIDATE_INT) : false;
	if (!$page || $page < 1 || !$photoId) die('Solicitud no válida');
	if (!can_view_photo($photoId)) { http_response_code(404); exit('Foto no disponible'); }
	$limit = ($page - 1) * 10;
	$result = db_query("
				SELECT idusuarios, nombre, apellidos, comentario,
				 DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha2,
				(SELECT archivo FROM fotos WHERE idfotos=idfotos_princi) AS img_princi
				 FROM fotos_comentarios, usuarios WHERE fotos_idfotos = ? AND emisor=idusuarios ORDER BY fecha DESC LIMIT ?, 10", array((int)$photoId, (int)$limit), 'ii');
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			print "<div class='foto_comentario'> 
					 <img src='" . e($row['img_princi']) . "' class='imagen_perfil'> 
					 <div> 
						<div> 
							<div class='foto_come_titu'><a href='perfil.php?id=" . (int)$row['idusuarios'] . "'>" . e($row['nombre']) . " " . e($row['apellidos']) . "</a></div> 
							<div class='foto_come_fecha'>" . e($row['fecha2']) . "</div> 
						</div> 
						<div class='foto_come_men'>" . e($row['comentario']) . "</div> 
					</div> 
				</div>";
		}

		//Numeracion paginas
		$q_numeracion = db_query("SELECT count(*) AS total FROM fotos_comentarios WHERE fotos_idfotos = ?", array((int)$photoId), 'i');
		$r_numeracion = mysqli_fetch_assoc($q_numeracion);

		$siguiente = $page + 1;
		$anterior = $page - 1;
		$ultima = ceil($r_numeracion['total'] / 10);

		//BARRA NAVEGACION
		echo "<div id='barra_navegacion'>";
			if ($page > 1) {
				echo "<img class='flecha_back_top' src='assets/images/flechas/flecha_left_top.JPG' onclick=\"foto_leer_comentarios(idfoto,1);\">";
				echo "<img class='flecha_back' src='assets/images/flechas/flecha_left.JPG' onclick=\"foto_leer_comentarios(idfoto,{$anterior});\">";
			}
			echo "<div class='texto'>".$page." de ".$ultima."</div>";
			
		if ($page < $ultima) {
				echo "<img class='flecha_next' src='assets/images/flechas/flecha_right.JPG' onclick=\"foto_leer_comentarios(idfoto,{$siguiente});\">";
				echo "<img class='flecha_next_top' src='assets/images/flechas/flecha_right_top.JPG' onclick=\"foto_leer_comentarios(idfoto," . $ultima . ");\">";
			}
		
	} else {
		echo "<div>Todavia nadie ha comentado esta foto</div>";
	}
	die();
}


########	CHAT
if (isset($_POST['chat_estado']) && $_POST['chat_estado'] !== "") {
	if (!in_array((string)$_POST['chat_estado'], array('0', '1'), true)) die('Estado no válido');
	$_SESSION['chat_estado'] = $_POST['chat_estado'];
	db_query("UPDATE usuarios SET chat_estado = ? WHERE idusuarios = ?", array((int)$_POST['chat_estado'], (int)$global_idusuarios), 'ii');
	error_mysql();
	die();
}

if (isset($_POST['chat_enviar']) && isset($_POST['receptor'], $_POST['mensaje']) && is_string($_POST['mensaje'])) {
	$recipient = filter_var($_POST['receptor'], FILTER_VALIDATE_INT);
	if (!$recipient) die('Destinatario no válido');
	db_query("INSERT INTO chat (emisor,receptor,mensaje,fecha) VALUES (?, ?, ?, now())", array((int)$global_idusuarios, (int)$recipient, $_POST['mensaje']), 'iis');
	error_mysql();
	$idchat = mysqli_insert_id($link);
	echo $idchat;
	die();
}

if (isset($_POST['chat_leer'])) {
	$result = db_query("SELECT *, archivo, chat.fecha AS fecha, DATE_FORMAT(chat.fecha, '%H:%i') AS fecha_corta, DATE_FORMAT(chat.fecha, '%d/%m %H:%i') AS fecha_larga FROM chat, usuarios LEFT JOIN fotos ON idfotos = idfotos_princi WHERE idusuarios=emisor AND receptor = ? AND chat.leido = 0 ORDER BY idchat", array((int)$global_idusuarios), 'i');

	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			//print_r($row);
			$intervalo = fecha_intervalo($row['fecha']);
			$segundos = fecha_a_segundos($intervalo);
			echo $segundos;
			if($segundos > 86400){
				$fecha = $row['fecha_larga'];
			}else{
				$fecha = $row['fecha_corta'];
			}
			echo "<div iduser='" . (int)$row['emisor'] . "' idchat='" . (int)$row['idchat'] . "' nombre='" . e($row['nombre'] . ' ' . $row['apellidos']) . "' img='" . e($row['archivo']) . "' fecha='" . e($fecha) . "' >" . e($row['mensaje']) . "</div>";
		}
	}
	db_query("UPDATE chat SET leido = 1 WHERE receptor = ?", array((int)$global_idusuarios), 'i');
	error_mysql();
	die();
}

if (isset($_POST['chat_leer_prev'])) {
	//Sacamos los msg antiguos en orden inverso a proposito
	$otherUser = isset($_POST['iduser']) ? filter_var($_POST['iduser'], FILTER_VALIDATE_INT) : false;
	$lastChatId = isset($_POST['idchat']) && $_POST['idchat'] !== '' ? filter_var($_POST['idchat'], FILTER_VALIDATE_INT) : null;
	if (!$otherUser || $lastChatId === false) die('Usuario no válido');
	$friend = db_query("SELECT user1 FROM amigos WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?)", array((int)$otherUser, (int)$global_idusuarios, (int)$global_idusuarios, (int)$otherUser), 'iiii');
	if (!$friend || mysqli_num_rows($friend) !== 1) die('Usuario no válido');
	if ($lastChatId !== null) {
		$result = db_query("SELECT *, DATE_FORMAT(fecha, '%H:%i') AS fecha_corta, DATE_FORMAT(fecha, '%d/%m %H:%i') AS fecha_larga FROM chat WHERE ((emisor = ? AND receptor = ?) OR (receptor = ? AND emisor = ?)) AND idchat < ? ORDER BY idchat DESC LIMIT 30", array((int)$otherUser, (int)$global_idusuarios, (int)$otherUser, (int)$global_idusuarios, (int)$lastChatId), 'iiiii');
	} else {
		$result = db_query("SELECT *, DATE_FORMAT(fecha, '%H:%i') AS fecha_corta, DATE_FORMAT(fecha, '%d/%m %H:%i') AS fecha_larga FROM chat WHERE ((emisor = ? AND receptor = ?) OR (receptor = ? AND emisor = ?)) ORDER BY idchat DESC LIMIT 30", array((int)$otherUser, (int)$global_idusuarios, (int)$otherUser, (int)$global_idusuarios), 'iiii');
	}
	if (mysqli_num_rows($result) > 0) {
		$mensajes = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$intervalo = fecha_intervalo($row['fecha']);
			$segundos = fecha_a_segundos($intervalo);
			
			if($segundos > 86400){
				$fecha = $row['fecha_larga'];
			}else{
				$fecha = $row['fecha_corta'];
			}
			
			if($row['emisor']!=$global_idusuarios){
				$class = "mensaje_ajeno";
			}else{
				$class = "mensaje_propio";
			}
			
			$msg = "<div idchat='" . (int)$row['idchat'] . "' class='" . e($class) . "'><div class='texto'>" . e($row['mensaje']) . "<div class='fecha'>" . e($fecha) . "</div></div></div>";
			array_unshift($mensajes, $msg); //con esto el orden se corrige
		}
		
		foreach ($mensajes as $val) {
		    echo  $val;
		}
	}
	error_mysql();
	die();
}

if (isset($_POST['chat_contactos'])) {
	if ($_SESSION['chat_estado'] == "1") {
		//CONECTADOS
		$result = db_query("
								SELECT *,(@tiempo:=TIME_TO_SEC(TIMEDIFF(now(),online))) AS segundos_off,
								CASE
								WHEN @tiempo<60 THEN 'conectado'
								WHEN @tiempo<86000 THEN TIME_FORMAT(TIMEDIFF(now(),online), '%H:%i:%s')
								ELSE DATE_FORMAT(online, '%d/%m/%Y %H:%i') END AS online,
								(SELECT archivo FROM fotos WHERE idfotos=idfotos_princi) AS archivo
								FROM amigos, usuarios
								WHERE TIME_TO_SEC(TIMEDIFF(now(),online))<95 AND chat_estado='1' AND
				(user1=? AND user2=idusuarios OR user2=? AND user1=idusuarios)
								ORDER BY nombre
			", array((int)$global_idusuarios, (int)$global_idusuarios), 'ii');
		if(mysqli_num_rows($result)>0){
			echo "<ul>";
			while ($row = mysqli_fetch_assoc($result)) {
				echo "<li onclick=\"chat_conv_init.apply(null," . e(json_encode(array((int)$row['idusuarios'], $row['nombre'] . ' ' . $row['apellidos'], $row['archivo'], 'normal'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"><div class='conectado'></div>" . e(NombreApellido($row['nombre'] . " " . $row['apellidos'])) . "</li>";
			}
			echo "</ul>";
		}
		//	NO CONECTADOS
		$result = db_query("
								SELECT *,(@tiempo:=TIME_TO_SEC(TIMEDIFF(now(),online))) AS segundos_off,
								CASE
								WHEN @tiempo<60 THEN 'conectado'
								WHEN @tiempo<86000 THEN TIME_FORMAT(TIMEDIFF(now(),online), '%H:%i:%s')
								ELSE DATE_FORMAT(online, '%d/%m/%Y %H:%i') END AS online,
								(SELECT archivo FROM fotos WHERE idfotos=idfotos_princi) AS archivo
								FROM amigos, usuarios
								WHERE (TIME_TO_SEC(TIMEDIFF(now(),online))>95 OR chat_estado='0') AND
				(user1=? AND user2=idusuarios OR user2=? AND user1=idusuarios)
								ORDER BY nombre
			", array((int)$global_idusuarios, (int)$global_idusuarios), 'ii');
		echo "<ul>";
		if(mysqli_num_rows($result)>0){
			while ($row = mysqli_fetch_assoc($result)) {
				echo "<li onclick=\"chat_conv_init.apply(null," . e(json_encode(array((int)$row['idusuarios'], $row['nombre'] . ' ' . $row['apellidos'], $row['archivo'], 'normal'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . ")\"><div class='desconectado'></div>" . e(NombreApellido($row['nombre'] . " " . $row['apellidos'])) . "</li>";
			}
			echo "</ul>";
		}
		//echo "<p style='cursor:pointer;' onclick=\"chat_turn('off')\">Desactivar Chat</p>";
	} else {
		//echo "<p style='cursor:pointer;' onclick=\"chat_turn('on')\">Activar Chat</p>";
	}
	error_mysql();
	die();
}



########	AJUSTES -> DATOS
if ($_POST['cambio_email']) {
	//print_r($_POST);
	if($_POST['email'] AND $_POST['email_new'] AND $_POST['pass']){
		$patron = '/^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/';
		if (preg_match($patron, $_POST['email_new'])) {
			$current = db_query("SELECT password FROM usuarios WHERE idusuarios = ? AND email = ?", array((int)$global_idusuarios, $_POST['email']), 'is');
			$currentUser = $current ? mysqli_fetch_assoc($current) : false;
			if (!$currentUser || !verify_password($_POST['pass'], $currentUser['password'])) {
				echo "Los datos introducidos no son correctos";
			} else {
				db_query("UPDATE usuarios SET email = ? WHERE idusuarios = ? AND email = ?", array($_POST['email_new'], (int)$global_idusuarios, $_POST['email']), 'sis');
				if(mysqli_affected_rows($link) != 1) echo "No se pudo actualizar el email";
			}
		}else{
			echo "El email nuevo no tiene un formato correcto";
		}
	}else{
		echo "Rellena todos los campos";
	}
	die();
}

if ($_POST['cambio_pass']) {
	//print_r($_POST);
	if($_POST['pass'] AND $_POST['pass_new'] AND $_POST['pass_new2']){
		if($_POST['pass_new'] == $_POST['pass_new2']){
			$current = db_query("SELECT password FROM usuarios WHERE idusuarios = ?", array((int)$global_idusuarios), 'i');
			$currentUser = $current ? mysqli_fetch_assoc($current) : false;
			if (!$currentUser || !verify_password($_POST['pass'], $currentUser['password'])) {
				echo "La contraseña actual no es correcta";
			} else {
				db_query("UPDATE usuarios SET password = ? WHERE idusuarios = ?", array(hash_password($_POST['pass_new']), (int)$global_idusuarios), 'si');
			}
		}else{
			echo "Las contraseñas nuevas no coinciden";
		}
	}else{
		echo "Rellena todos los campos";
	}
	die();
}

if ($_POST['cambio_datos']) {
	//print_r($_POST);
	foreach($_POST as $nombre_campo => $valor)
	{
	  if(!$valor){
		echo "Rellena todos los campos, incluido ".$nombre_campo;
		die();
		break;
	  }
	}
	
	$query = db_query("UPDATE usuarios SET nombre = ?, apellidos = ?, sexo = ?, provincia = ?, fnac = ? WHERE idusuarios = ?", array($_POST['nombre'], $_POST['apellidos'], $_POST['sexo'], $_POST['provincia'], $_POST['fecha_hidden'], (int)$global_idusuarios), 'sssssi');
	
	if($query!=1){
		echo "Se ha producido un error";
	}
	die();
}
http_response_code(404);
echo "Solicitud no válida";
?>
