<?php
require('inc/config.php');
start_secure_session();
if(isset($_SESSION['idsesion'])) {
	header("Location: inicio.php", true, 302);
} else {
	header("Location: login.php", true, 302);
}
exit;
?>
