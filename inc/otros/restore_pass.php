<?php
start_secure_session();
if (!isset($_SESSION['chat_estado'])) $_SESSION['chat_estado'] = 0;
if (!isset($_SESSION['reset_csrf']) || !is_string($_SESSION['reset_csrf'])) {
	$_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
header('Referrer-Policy: no-referrer');
head("Recuperar la contraseña");
?>
<body id="seccion_restore_pass">
	<ul id="menudrop">
		<li><a href="login.php">Login</a></li>
		<li><a href="inicio.php">Inicio</a></li>
		<li><a href="registro.php">Registro</a></li>
		<li><a href="otros.php?contacto=1">Contacto</a></li>
	</ul>
	<br>
	<div class="barra_full">
		<div class="marco">
			<?php
			$genericMessage = "<div class='centrar'><div class='ok_ajustable'>Si la cuenta existe, recibirás un correo con instrucciones para recuperar la contraseña.</div></div>";

			if (isset($_POST['restore_pass']) && isset($_POST['email'])) {
				if (!isset($_POST['reset_csrf']) || !is_string($_POST['reset_csrf']) || !hash_equals($_SESSION['reset_csrf'], $_POST['reset_csrf'])) {
					http_response_code(403);
					exit('Solicitud no válida');
				}
				$email = is_string($_POST['email']) ? trim($_POST['email']) : '';
				if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$query = db_query("SELECT idusuarios, nombre, apellidos, email FROM usuarios WHERE email = ? AND activacion = 1", array($email), 's');
					$user = $query ? mysqli_fetch_assoc($query) : false;
					if ($user) {
						$token = bin2hex(random_bytes(32));
						$tokenHash = hash('sha256', $token);
						db_query("UPDATE usuarios SET recuperacion = ?, recuperacion_expira = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE idusuarios = ?", array($tokenHash, (int)$user['idusuarios']), 'si');
						$baseUrl = 'https://' . trim(Sitio_direccion, '/');
						$resetUrl = $baseUrl . '/otros.php?restore_pass=1&codigo=' . rawurlencode($token);
						$name = htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8');
						$message = "<html><body>Hola " . $name . ".<br>Abre el enlace para restablecer tu contraseña. Caduca en 30 minutos:<br><a href=\"" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "\">Restablecer contraseña</a><br>Si no lo solicitaste, ignora este correo.</body></html>";
						email_send($user['nombre'] . ' ' . $user['apellidos'], $user['email'], "Recuperar la contraseña - " . Sitio, $message);
					}
				}
				echo $genericMessage;
				die();
			}

			$rawToken = isset($_POST['codigo']) ? $_POST['codigo'] : (isset($_GET['codigo']) ? $_GET['codigo'] : '');
			$validToken = is_string($rawToken) && preg_match('/\A[a-f0-9]{64}\z/', $rawToken) === 1;
			$tokenHash = $validToken ? hash('sha256', $rawToken) : '';
			$validReset = false;
			if ($validToken) {
				$tokenQuery = db_query("SELECT idusuarios FROM usuarios WHERE recuperacion = ? AND recuperacion_expira > NOW() AND activacion = 1", array($tokenHash), 's');
				$validReset = $tokenQuery && mysqli_num_rows($tokenQuery) === 1;
			}

			if (isset($_POST['restore_pass']) && isset($_POST['codigo'], $_POST['pass'])) {
				if (!isset($_POST['reset_csrf']) || !is_string($_POST['reset_csrf']) || !hash_equals($_SESSION['reset_csrf'], $_POST['reset_csrf'])) {
					http_response_code(403);
					exit('Solicitud no válida');
				}
				if (!$validReset || !is_string($_POST['pass']) || strlen($_POST['pass']) < 12 || strlen($_POST['pass']) > 200) {
					http_response_code(400);
					echo "<div class='centrar'><div class='error_ajustable'>El enlace no es válido o ha caducado. Solicita uno nuevo y usa una contraseña de al menos 12 caracteres.</div></div>";
					die();
				}
				$updated = db_query("UPDATE usuarios SET password = ?, recuperacion = '0', recuperacion_expira = NULL, idsesion = '' WHERE recuperacion = ? AND recuperacion_expira > NOW()", array(hash_password($_POST['pass']), $tokenHash), 'ss');
				if ($updated && mysqli_affected_rows($link) === 1) {
					unset($_SESSION['idsesion']);
					echo "<div class='centrar'><div class='ok_ajustable'>La contraseña se ha cambiado. Ya puedes iniciar sesión.</div></div>";
				} else {
					http_response_code(400);
					echo "<div class='centrar'><div class='error_ajustable'>El enlace no es válido o ha caducado. Solicita uno nuevo.</div></div>";
				}
				die();
			}

			if ($validReset) {
				?>
				<script>if (window.history && window.history.replaceState) window.history.replaceState(null, document.title, "otros.php?restore_pass=1");</script>
				<p>Introduce una contraseña nueva de al menos 12 caracteres:</p>
				<form method="POST" action="otros.php">
					<div class="input"><span><input name="pass" type="password" minlength="12" maxlength="200" required autofocus placeholder="Contraseña nueva"></span></div>
					<button type="submit" class="azul"><span><b>Guardar</b></span></button>
					<input type="hidden" name="restore_pass" value="1">
					<input type="hidden" name="codigo" value="<?php echo htmlspecialchars($rawToken, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="reset_csrf" value="<?php echo htmlspecialchars($_SESSION['reset_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
				</form>
				<?php
				die();
			} elseif (isset($_GET['codigo'])) {
				http_response_code(400);
				echo "<div class='centrar'><div class='error_ajustable'>El enlace no es válido o ha caducado. Solicita uno nuevo.</div></div>";
			}
			?>
			<p>Introduce tu correo y, si la cuenta existe, te enviaremos instrucciones para recuperar la contraseña.</p>
			<form method="POST" action="otros.php">
				<div class="input"><span><input name="email" type="email" maxlength="254" required autofocus placeholder="ejemplo@mail.com"></span></div>
				<button type="submit" class="azul"><span><b>Enviar email</b></span></button>
				<input type="hidden" name="restore_pass" value="1">
				<input type="hidden" name="reset_csrf" value="<?php echo htmlspecialchars($_SESSION['reset_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
			</form>
		</div>
	</div>
</body>
</html>
