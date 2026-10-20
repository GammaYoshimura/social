<?php
//CONSTANTES DE CONFIGURACION GLOBALES

//Titulo corto del sitio
define("Sitio", "Social");

//Titulo largo del sitio
define("Sitio_Largo", "Social Networks");

//Direccion de la web
define("Sitio_direccion", "127.0.0.1/social");

if (!defined('APP_ROOT')) {
	define('APP_ROOT', __DIR__);
}

// Las credenciales se proporcionan por entorno o en app/config.local.php.
$localConfig = __DIR__ . '/config.local.php';
if (!is_file($localConfig)) {
	$localConfig = dirname(__DIR__) . '/inc/config.local.php';
}
if (is_file($localConfig)) {
	require_once $localConfig;
}

$mysqlHost = getenv('SOCIAL_DB_HOST');
$mysqlUser = getenv('SOCIAL_DB_USER');
$mysqlPassword = getenv('SOCIAL_DB_PASSWORD');
$mysqlDatabase = getenv('SOCIAL_DB_NAME');
if ($mysqlHost === false && defined('MySQL_IP')) $mysqlHost = MySQL_IP;
if ($mysqlUser === false && defined('MySQL_USER')) $mysqlUser = MySQL_USER;
if ($mysqlPassword === false && defined('MySQL_PASS')) $mysqlPassword = MySQL_PASS;
if ($mysqlDatabase === false && defined('MySQL_BD')) $mysqlDatabase = MySQL_BD;
if (!$mysqlHost || !$mysqlUser || !$mysqlPassword || !$mysqlDatabase) {
	http_response_code(500);
	error_log('Database configuration is missing or incomplete.');
	exit('Servicio no disponible');
}

//Email que se usara como emisor de los correos
define("Email_Address", "javi.and.friends@gmail.com");



//CONEXION BBDD
$link = mysqli_connect($mysqlHost, $mysqlUser, $mysqlPassword, $mysqlDatabase);
if (!$link) {
	http_response_code(500);
	error_log('Database connection failed: ' . mysqli_connect_error());
	exit('Servicio no disponible');
}
if (!mysqli_set_charset($link, 'utf8mb4')) {
	http_response_code(500);
	error_log('Could not set database character set.');
	exit('Servicio no disponible');
}

// CONTADOR DE TIEMPO DE EJECUCION
/*$tiempo_inicio = microtime(true);

//CONTADOR DE CONSULTAS
$q_querys = mysqli_query($link, "SHOW SESSION STATUS LIKE 'Questions'");
$r_querys = mysqli_fetch_array($q_querys);
define("START_QUERIES", $r_querys['Value']);*/

// Configuracion idioma de PHP para fechas (usar con strftime)
setlocale(LC_ALL,"es_ES@euro","es_ES","esp");

// Configuracion idioma de MySql para fechas
mysqli_query($link, "SET lc_time_names = 'es_ES'");

// $ruta se usa para meter ../ cuando la peticion procede de ficheros en subdirectorios
require_once __DIR__ . '/functions.php';
?>
