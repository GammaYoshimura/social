<?php
require_once __DIR__ . '/app/config.php';
start_secure_session();

if(isset($_SESSION['idsesion']) && empty($_GET['activacion']))
	header("Location: inicio.php");


if(isset($_POST['email'], $_POST['password']) && is_string($_POST['email']) && is_string($_POST['password'])){
	//TODO: blowfish
	//COMPROBAR DATOS LOGIN
	$login = db_query("SELECT * FROM usuarios WHERE email = ? AND activacion = 1", array($_POST['email']));
	$r_login = $login ? mysqli_fetch_assoc($login) : false;

	//LOGIN OK
	if($r_login && verify_password($_POST['password'], $r_login['password'])){
		$passwordInfo = password_get_info($r_login['password']);
		if (empty($passwordInfo['algo'])) {
			db_query("UPDATE usuarios SET password = ? WHERE idusuarios = ?", array(hash_password($_POST['password']), (int)$r_login['idusuarios']), 'si');
		}
		$codigo_temp = bin2hex(random_bytes(16));
		db_query("UPDATE usuarios SET idsesion = ? WHERE idusuarios = ?", array($codigo_temp, (int)$r_login['idusuarios']), 'si');
		
		// Log de acceso con IP & fecha
		db_query("INSERT INTO accesos (usuarios_idusuarios, ip, fecha) VALUES (?, ?, now())", array((int)$r_login['idusuarios'], isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 'is');
		session_regenerate_id(true);
		$_SESSION['idsesion']=$codigo_temp;
		
		header( "Location: inicio.php" );
		die();
		// TODO: funcionalidad de URL al loguear.
		// TODO: duracion de la sesion al cerrar la pagina

	//LOGIN FAIL
	}else{
		//COMPROBAR ACTIVACION CUENTA
		$q_activacion = db_query("SELECT idusuarios FROM usuarios WHERE email = ? AND activacion <> 1", array($_POST['email']));
	
		//CUENTA NO ACTIVADA
		if($q_activacion && mysqli_num_rows($q_activacion)==1){
			$error = "activacion";
		}else{
			$error = "datos";
		}
	}
	
}

head("Login");
echo "<script type='text/javascript' src='jscripts/login_js.php'></script>";
echo "<script type='text/javascript' src='jscripts/forms.js'></script>";
?>
<body id="seccion_login">
		<div class="centrar">
			<div id="titulo">
				<img src="css/logosocial.png">
				<h1><?php echo Sitio; ?></h1>
			</div>
			
			<?php
			if($error){
				echo "<div class='centrar'><div class='error_ajustable'>";
				if($error == "datos"){
					print "El email o la contraseña introducidos son incorrectos.<br>
							<a href='otros.php?restore_pass=1'>¿Quieres recuperar la contrase&ntilde;a?</a>
						";
				}elseif($error == "activacion"){
					echo "La cuenta no est&aacute; activada a&uacute;n, revisa tu cuenta de email";
				}
				echo "</div></div>";
			}
			
			if($_GET['activacion']){
				if($_GET['activacion'] == "ok"){
					echo "<div class='centrar'><div class='ok_ajustable'>La cuenta se ha activado correctamente, ahora puedes entrar con tus datos</div></div>";
				}elseif($_GET['activacion'] == "fail"){
					echo "<div class='centrar'><div class='error_ajustable'>La activacion de la cuenta ha fallado</div></div>";
				}elseif($_GET['activacion'] == "fail_email"){
					print "<div class='centrar'><div class='error_ajustable'>Se ha producido un error al enviar el correo</div></div>";
				}
			}
			?>	
				<form id='form_login' method='POST' action='login.php'>
					Email: 					
						<div class="input">
							<span>
								<input id="email" name="email" class="validable" type="text" value="<?php echo isset($_POST['email']) && is_string($_POST['email']) ? e($_POST['email']) : ''; ?>" maxlength='40' autofocus placeholder="ejemplo@mail.com">
							</span>
						</div><br>
					
					Clave:  					
						<div class="input">
							<span>
								<input id="password" name="password" class="validable" type="password" value="" maxlength='30' placeholder="Contrase&ntilde;a">
							</span>
						</div><br>
					<button type='button' name='registro' value='Registrarse' class="azul" onclick="validador('submit')"><span><b>Entrar</b></span></button>
					<button type='button' class="azul" onclick="location.href='registro.php';"><span><b>Registrarse</b></span></button>
				  </form>
			</div>
		</div>
		<div id="creditos">
			<div>
				<p>Social &copy; 2012 - 2013</p>
				<p>Javier González Rastrojo</p>
			</div>
		</div>
</body>
