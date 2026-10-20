<?php
require ("inc/verify_login.php");

if (!$_GET['idalbum']) 
	header("inicio.php?noidalbum");

head("Fotos");
echo "<script type='text/javascript' src='jscripts/foto_edicion.js'></script>";
echo "<script type='text/javascript' src='jscripts/foto_visualizador.js'></script>";
echo "<body id='seccion_fotos'>";
require ("inc/estructura.inc.php");
require ("inc/chat.php");

$ownerId = isset($_GET['iduser']) ? filter_var($_GET['iduser'], FILTER_VALIDATE_INT) : (int)$global_idusuarios;
$photoId = isset($_GET['idfotos']) ? filter_var($_GET['idfotos'], FILTER_VALIDATE_INT) : 999999999;
$albumType = isset($_GET['idalbum']) && is_string($_GET['idalbum']) ? $_GET['idalbum'] : '';
if (!$ownerId || !$photoId || !can_view_profile($ownerId)) { http_response_code(404); exit('Foto no disponible'); }
if ($albumType === 'subidas') {
	$query = "SELECT *,
		(SELECT MIN(idfotos) FROM fotos WHERE uploader = ?) AS ultima,
		(SELECT MAX(idfotos) FROM fotos WHERE uploader = ?) AS primera,
		(SELECT MIN(idfotos) FROM fotos WHERE uploader = ? AND idfotos > ?) AS anterior,
		(SELECT MAX(idfotos) FROM fotos WHERE uploader = ? AND idfotos < ?) AS siguiente,
		(SELECT COUNT(idfotos) FROM fotos WHERE uploader = ?) AS totales,
		((SELECT COUNT(idfotos) FROM fotos WHERE uploader = ?) - (SELECT COUNT(idfotos) FROM fotos WHERE uploader = ? AND idfotos < ?)) AS actual
		FROM fotos LEFT JOIN albums ON albums_idalbums = idalbums LEFT JOIN usuarios ON uploader = idusuarios WHERE idfotos = ? AND uploader = ?";
	$params = array((int)$ownerId, (int)$ownerId, (int)$ownerId, (int)$photoId, (int)$ownerId, (int)$photoId, (int)$ownerId, (int)$ownerId, (int)$ownerId, (int)$photoId, (int)$photoId, (int)$ownerId);
	$q_fotos = db_query($query, $params, str_repeat('i', count($params)));
} elseif ($albumType === 'etiquetadas') {
	$query = "SELECT *,
		(SELECT MIN(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos) AS ultima,
		(SELECT MAX(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos) AS primera,
		(SELECT MIN(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos AND idfotos > ?) AS anterior,
		(SELECT MAX(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos AND idfotos < ?) AS siguiente,
		(SELECT COUNT(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos) AS totales,
		((SELECT COUNT(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ?) - (SELECT COUNT(idfotos) FROM fotos, etiquetas WHERE usuarios_idusuarios = ? AND idfotos = fotos_idfotos AND idfotos < ?)) AS actual
		FROM fotos LEFT JOIN albums ON albums_idalbums = idalbums LEFT JOIN usuarios ON uploader = idusuarios WHERE idfotos = ? AND EXISTS (SELECT 1 FROM etiquetas WHERE fotos_idfotos = fotos.idfotos AND usuarios_idusuarios = ?)";
	$params = array((int)$ownerId, (int)$ownerId, (int)$ownerId, (int)$photoId, (int)$ownerId, (int)$photoId, (int)$ownerId, (int)$ownerId, (int)$ownerId, (int)$photoId, (int)$photoId, (int)$ownerId);
	$q_fotos = db_query($query, $params, str_repeat('i', count($params)));
} else {
	$albumId = filter_var($albumType, FILTER_VALIDATE_INT);
	$albumOwner = $albumId ? db_query("SELECT usuarios_idusuarios FROM albums WHERE idalbums = ?", array((int)$albumId), 'i') : false;
	$albumOwnerRow = $albumOwner ? mysqli_fetch_assoc($albumOwner) : false;
	if (!$albumOwnerRow || (int)$albumOwnerRow['usuarios_idusuarios'] !== (int)$ownerId) { http_response_code(404); exit('Album no disponible'); }
	$query = "SELECT *,
		(SELECT MIN(idfotos) FROM fotos WHERE albums_idalbums = ?) AS ultima,
		(SELECT MAX(idfotos) FROM fotos WHERE albums_idalbums = ?) AS primera,
		(SELECT MIN(idfotos) FROM fotos WHERE albums_idalbums = ? AND idfotos > ?) AS anterior,
		(SELECT MAX(idfotos) FROM fotos WHERE albums_idalbums = ? AND idfotos < ?) AS siguiente,
		(SELECT COUNT(idfotos) FROM fotos WHERE albums_idalbums = ?) AS totales,
		((SELECT COUNT(idfotos) FROM fotos WHERE albums_idalbums = ?) - (SELECT COUNT(idfotos) FROM fotos WHERE albums_idalbums = ? AND idfotos < ?)) AS actual
		FROM fotos LEFT JOIN albums ON albums_idalbums = idalbums LEFT JOIN usuarios ON uploader = idusuarios WHERE idfotos = ? AND albums_idalbums = ?";
	$params = array((int)$albumId, (int)$albumId, (int)$albumId, (int)$photoId, (int)$albumId, (int)$photoId, (int)$albumId, (int)$albumId, (int)$albumId, (int)$photoId, (int)$photoId, (int)$albumId);
	$q_fotos = db_query($query, $params, str_repeat('i', count($params)));
}
$r_fotos = $q_fotos ? mysqli_fetch_assoc($q_fotos) : false;
if (!$r_fotos) { http_response_code(404); exit('Foto no disponible'); }
require ("inc/fotos/main.inc.php");
require ("inc/fotos/barra.inc.php");
require ("inc/fotos/comentarios.inc.php");
?>
