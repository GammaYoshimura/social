<?php

//TODO: Pulir estetica
require ("inc/verify_login.php");
head("Subir fotos");
require ("upload/subida.html");
?>
</head>
<body>
	<?php
	require ("inc/estructura.inc.php");
	?>
	<div class="barra_full">
	<div class="marco">
		<?php
		if (isset($_POST['subir_fotos'])) {
			if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !is_string($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
				http_response_code(403);
				exit('Solicitud no válida');
			}
			$count = 0;
			$albumId = null;
			if (isset($_POST['idalbums']) && $_POST['idalbums'] !== '' && $_POST['idalbums'] !== 'NULL') {
				$albumId = filter_var($_POST['idalbums'], FILTER_VALIDATE_INT);
				$ownedAlbum = $albumId ? db_query("SELECT idalbums FROM albums WHERE idalbums = ? AND usuarios_idusuarios = ?", array((int)$albumId, (int)$global_idusuarios), 'ii') : false;
				if (!$ownedAlbum || mysqli_num_rows($ownedAlbum) !== 1) { http_response_code(400); exit('Album no válido'); }
			}
			$uploadDirectory = dirname(__FILE__) . '/user_fotos/' . (int)$global_idusuarios . '-' . limpia_texto($global_nombrefull);
			foreach ($_POST as $name => $value) {
				if (preg_match("/tmpname/", $name) === 1 && is_string($value) && preg_match('/\A[A-Za-z0-9_-]{1,100}\.(?:jpe?g|png)\z/i', $value) === 1) {
					$filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $value;
					if (!is_file($filePath) || filesize($filePath) < 1 || filesize($filePath) > 10 * 1024 * 1024) continue;
					$title = isset($_POST['titulo']) && is_string($_POST['titulo']) ? $_POST['titulo'] : '';
					$relativePath = 'user_fotos/' . (int)$global_idusuarios . '-' . limpia_texto($global_nombrefull) . '/' . $value;
					if (db_query("INSERT INTO fotos (titulo, archivo, uploader, fecha, albums_idalbums) VALUES (?, ?, ?, now(), ?)", array($title, $relativePath, (int)$global_idusuarios, $albumId), 'ssii')) $count++;
				}
			}

			if ($count > 0) {
				//Si ha subido fotos recientemente hacemos update sino insert
				$q_novedades = db_query("SELECT idnovedades, datos FROM novedades WHERE propietario = ? AND tipo = 'subida_fotos' AND fecha > ADDTIME(now(), '-7 0:0:0') LIMIT 1", array((int)$global_idusuarios), 'i');
				if(mysqli_num_rows($q_novedades)==1){
					$r_novedades=mysqli_fetch_assoc($q_novedades);
					$suma = (int)$r_novedades['datos'] + (int)$count;
					db_query("UPDATE novedades SET datos = ? WHERE idnovedades = ? AND propietario = ?", array($suma, (int)$r_novedades['idnovedades'], (int)$global_idusuarios), 'iii');
				}else{
					db_query("INSERT INTO novedades (fecha,tipo, propietario, datos) VALUES (now(), 'subida_fotos', ?, ?)", array((int)$global_idusuarios, (int)$count), 'ii');
				}
				?>
				
				<div class='centrar'>
					<div class="ok_ajustable">
					Fotos subidas con exito, puedes verlas en tus <a href='albums.php' class='link'>albums</a>
					</div>
				</div>
				
				<?php
			} else {
				echo "<div class='centrar'>
					<div class='error_ajustable'>
						Faltan las imagenes
					</div>
				</div>";
			}
		}
		?>
		<form method="post" action="subir_fotos.php" enctype="multipart/form-data">
			<b>Titulo(s):</b>			
				<div class="input">
					<span>
						<input type="text" name="titulo" placeholder="Escribe un titulo para las fotos" size='55'>
					</span>
				</div>
			<br />
			<b>Album:</b>
			<?php
			$albums = db_query("SELECT * FROM albums WHERE usuarios_idusuarios = ?", array((int)$global_idusuarios), 'i');
			if (mysqli_num_rows($albums) > 0) {
				print "<div class='input'>
						<span class='select'>
							<select name='idalbums'><option value='NULL'>Ninguno</option>";
				while ($row = mysqli_fetch_assoc($albums)) {
					echo "<option value='" . (int)$row['idalbums'] . "'>" . e($row['album']) . "</option>";
				}
				echo "</select></span></div>";
			} else {
				echo "<span class='ayuda' title='Primero debes crear un album'>Ninguno</span>";
			}
			?>
			</select>
			<br />
			<b>Imagenes:</b>
			<br />
			<div id="uploader"></div>
			<input type="hidden" name="subir_fotos" value="1" />
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
			<button type='submit' class="azul"><span><b>Subir fotos</b></span></button>
		</form>

<?php
require ("inc/chat.php");
?>
</body>
</html>
