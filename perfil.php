<?php
require_once __DIR__ . '/app/auth/verify_login.php';

if (!isset($_GET['id']) || $_GET['id'] === '' || (int)$_GET['id'] === (int)$global_idusuarios) $_GET['id']=NULL; //Si el id es el mio, se ve la pagina en modo normal

if ($_GET['id']) {
	$profileId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
	if (!$profileId || !can_view_profile($profileId)) {
		header("Location: inicio.php?nosoisamigos");
		die();
	}
	//Comprueba amistad
	$perfil = "ajeno";
} else {
	$_GET['id'] = $global_idusuarios;
	$perfil = "propio";
}

$q_user = db_query("SELECT *,
			FLOOR(DATEDIFF(CURDATE(),fnac)/365) AS edad,
			DATE_FORMAT(fecha_reg, '%d/%m/%Y') AS fecha_reg,
			TIME_TO_SEC(TIMEDIFF(now(),online)) AS segundos_off
		FROM `usuarios` LEFT JOIN fotos ON idfotos = idfotos_princi WHERE idusuarios = ?", array((int)$_GET['id']), 'i');
$r_user = mysqli_fetch_assoc($q_user);

if ($perfil == "ajeno") {
	head(NombreApellido($r_user['nombre'] . " " . $r_user['apellidos']));
} else {
	head("Perfil");
}
echo "<script type='text/javascript' src='assets/js/perfil.js'></script>";
echo "<body id='seccion_perfil'>";
require APP_ROOT . '/estructura.inc.php';
?>

<div class="barra_izq">
	<?php
	require APP_ROOT . '/perfil/datos.php';
	?>
</div>

<div class="barra_centro_der" >
	<?php
	require APP_ROOT . '/perfil/tablon.php';
	require APP_ROOT . '/perfil/albums.php';
	require APP_ROOT . '/perfil/comentarios.php';
	?>
</div>

<?php
require APP_ROOT . '/chat.php';
?>
