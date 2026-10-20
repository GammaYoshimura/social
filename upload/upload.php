<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

function upload_error($message, $status = 400) {
	http_response_code($status);
	echo json_encode(array('jsonrpc' => '2.0', 'error' => array('code' => $status, 'message' => $message), 'id' => 'id'));
	exit;
}

$ruta = '../';
require('../inc/config.php');
start_secure_session();

if (!isset($_SESSION['idsesion']) || !is_string($_SESSION['idsesion']) || $_SESSION['idsesion'] === '') {
	upload_error('Authentication required', 401);
}
if (!isset($_SESSION['csrf_token'], $_REQUEST['csrf_token']) || !is_string($_SESSION['csrf_token']) || !is_string($_REQUEST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_REQUEST['csrf_token'])) {
	upload_error('Invalid request token', 403);
}

$stmt = mysqli_prepare($link, 'SELECT idusuarios, nombre, apellidos FROM usuarios WHERE idsesion = ? AND activacion = 1');
if (!$stmt) {
	upload_error('Upload unavailable', 500);
}
mysqli_stmt_bind_param($stmt, 's', $_SESSION['idsesion']);
if (!mysqli_stmt_execute($stmt)) {
	mysqli_stmt_close($stmt);
	upload_error('Upload unavailable', 500);
}
mysqli_stmt_bind_result($stmt, $userId, $firstName, $lastName);
if (!mysqli_stmt_fetch($stmt)) {
	mysqli_stmt_close($stmt);
	upload_error('Authentication required', 401);
}
mysqli_stmt_close($stmt);

$maxBytes = 10 * 1024 * 1024;
$chunk = isset($_REQUEST['chunk']) ? filter_var($_REQUEST['chunk'], FILTER_VALIDATE_INT) : 0;
$chunks = isset($_REQUEST['chunks']) ? filter_var($_REQUEST['chunks'], FILTER_VALIDATE_INT) : 0;
if ($chunk === false || $chunks === false || $chunk < 0 || $chunks < 0 || $chunks > 10 || ($chunks > 0 && $chunk >= $chunks)) {
	upload_error('Invalid upload chunk');
}

$fileName = isset($_REQUEST['name']) && is_string($_REQUEST['name']) ? $_REQUEST['name'] : '';
if (!preg_match('/\A[A-Za-z0-9_-]{1,100}\.(?:jpe?g|png)\z/i', $fileName)) {
	upload_error('Only JPG and PNG images are accepted');
}

$targetDir = dirname(__DIR__) . '/user_fotos/' . (int)$userId . '-' . limpia_texto($firstName . ' ' . $lastName);
if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true)) {
	upload_error('Upload unavailable', 500);
}
if (!is_writable($targetDir)) {
	upload_error('Upload unavailable', 500);
}

$partPath = $targetDir . DIRECTORY_SEPARATOR . $fileName . '.part';
$chunkSize = 0;
if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
	$chunkSize = filesize($_FILES['file']['tmp_name']);
	$inputPath = $_FILES['file']['tmp_name'];
} else {
	$inputPath = 'php://input';
}
if ($chunkSize > $maxBytes) {
	@unlink($partPath);
	upload_error('Image exceeds the 10 MB limit', 413);
}

$mode = ($chunks === 0 || $chunk === 0) ? 'wb' : 'ab';
$out = @fopen($partPath, $mode);
$in = @fopen($inputPath, 'rb');
if (!$out || !$in) {
	if ($out) fclose($out);
	if ($in) fclose($in);
	upload_error('Could not receive image', 500);
}
$written = 0;
while (!feof($in)) {
	$data = fread($in, 8192);
	if ($data === false) break;
	$written += strlen($data);
	if ($written > $maxBytes || ftell($out) + strlen($data) > $maxBytes) {
		fclose($in);
		fclose($out);
		@unlink($partPath);
		upload_error('Image exceeds the 10 MB limit', 413);
	}
	if ($data !== '' && fwrite($out, $data) !== strlen($data)) {
		fclose($in);
		fclose($out);
		upload_error('Could not receive image', 500);
	}
}
fclose($in);
fclose($out);

if ($chunks > 0 && $chunk < $chunks - 1) {
	echo json_encode(array('jsonrpc' => '2.0', 'result' => null, 'id' => 'id'));
	exit;
}

if (!is_file($partPath) || filesize($partPath) < 1 || filesize($partPath) > $maxBytes || !function_exists('imagecreatefromstring') || !function_exists('imagejpeg') || !function_exists('imagepng') || !function_exists('finfo_open')) {
	@unlink($partPath);
	upload_error('Image processing is unavailable or the image is invalid', 415);
}

$imageInfo = @getimagesize($partPath);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $partPath) : false;
if ($finfo) finfo_close($finfo);
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$validType = $imageInfo && $imageInfo[0] > 0 && $imageInfo[1] > 0
	&& ($imageInfo[0] * $imageInfo[1]) <= 12000000
	&& (($extension === 'png' && $imageInfo[2] === IMAGETYPE_PNG && $mime === 'image/png')
		|| (in_array($extension, array('jpg', 'jpeg'), true) && $imageInfo[2] === IMAGETYPE_JPEG && $mime === 'image/jpeg'));
if (!$validType) {
	@unlink($partPath);
	upload_error('Only valid JPG and PNG images are accepted', 415);
}

$image = @imagecreatefromstring(file_get_contents($partPath));
if (!$image) {
	@unlink($partPath);
	upload_error('Invalid image data', 415);
}
$finalPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
if (file_exists($finalPath)) {
	@unlink($partPath);
	imagedestroy($image);
	upload_error('An image with this name already exists', 409);
}
$temporaryPath = tempnam($targetDir, '.image-');
if ($temporaryPath === false) {
	imagedestroy($image);
	@unlink($partPath);
	upload_error('Could not store image', 500);
}
$saved = $extension === 'png' ? @imagepng($image, $temporaryPath, 6) : @imagejpeg($image, $temporaryPath, 90);
imagedestroy($image);
@unlink($partPath);
if (!$saved || !rename($temporaryPath, $finalPath)) {
	@unlink($temporaryPath);
	upload_error('Could not store image', 500);
}
@chmod($finalPath, 0640);
echo json_encode(array('jsonrpc' => '2.0', 'result' => null, 'id' => 'id'));
?>
