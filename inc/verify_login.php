<?php
	require('inc/config.php');
	start_secure_session();
	
	if (!isset($_SESSION['idsesion']) || !is_string($_SESSION['idsesion']) || $_SESSION['idsesion'] === '') {
		header("Location: login.php", true, 302);
		exit;
	}

	$stmt = mysqli_prepare($link, "SELECT idusuarios, nombre, apellidos, idsesion, chat_estado FROM usuarios WHERE idsesion = ? AND activacion = 1");
	if (!$stmt) {
		http_response_code(500);
		exit;
	}

	mysqli_stmt_bind_param($stmt, "s", $_SESSION['idsesion']);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);
	if (mysqli_stmt_num_rows($stmt) !== 1) {
		mysqli_stmt_close($stmt);
		session_unset();
		session_destroy();
		header("Location: login.php?auth_error", true, 302);
		exit;
	}

	mysqli_stmt_bind_result($stmt, $userId, $firstName, $lastName, $sessionToken, $chatState);
	mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);

	$global_idusuarios = $userId;
	$global_nombre = $firstName;
	$global_apellidos = $lastName;
	$global_nombrefull = $firstName . " " . $lastName;
	$global_idsesion = $sessionToken;
	$_SESSION['chat_estado'] = $chatState;
	if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) !== 64) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
?>
