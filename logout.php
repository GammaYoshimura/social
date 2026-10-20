<?php
require_once __DIR__ . '/app/config.php';
start_secure_session();
if (isset($_SESSION['idsesion']) && is_string($_SESSION['idsesion']) && $_SESSION['idsesion'] !== '') {
	db_query("UPDATE usuarios SET idsesion = '' WHERE idsesion = ?", array($_SESSION['idsesion']), 's');
}
$_SESSION = array();
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header("Location: index.php", true, 302);
exit;
//<a href="index.php">Entrar</a>
?>
