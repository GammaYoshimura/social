<?php
require_once __DIR__ . '/app/config.php';

########	Contacto
if($_GET['contacto']){
	require APP_ROOT . '/otros/contacto.php';
	die();
}

########	TERMINOS DE USO
if($_GET['tos']){
	require APP_ROOT . '/otros/tos.php';
	die();
}

########	RECUPERAR CONTRASEÑA
if($_GET['restore_pass'] OR $_POST['restore_pass']){
	require APP_ROOT . '/otros/restore_pass.php';
	die();
}

########	ACTIVAR CUENTA
if($_GET['activar_cuenta']){
	require APP_ROOT . '/otros/activacion.php';
	die();
}
header("location: index.php");
?>
