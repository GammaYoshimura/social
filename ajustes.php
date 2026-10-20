<?php
require_once __DIR__ . '/app/verify_login.php';
head("Ajustes");
require APP_ROOT . '/estructura.inc.php';
?>
<div class="barra_izq">
	<div class="marco_small lista_enlaces" style="padding: 0;">
		<ul>
			<li><a href="ajustes.php?seccion=datos">Datos de la cuenta</a></li>
			<li><a href="ajustes.php?seccion=peticiones">Peticiones</a></li>
			<li><a href="ajustes.php?seccion=ip">Control de acceso</a></li>
		</ul>
	</div>
</div>
<div class="barra_centro_der">
	<div class="marco">
	<?php
	if ($_GET['seccion'] == "datos") {
		require APP_ROOT . '/ajustes/datos.php';
	} elseif ($_GET['seccion'] == "peticiones") {
		require APP_ROOT . '/ajustes/peticiones.php';
	} elseif ($_GET['seccion'] == "ip") {
		require APP_ROOT . '/ajustes/ip.php';
	} else {
		require APP_ROOT . '/ajustes/datos.php';
	}
	?></div>
</div>


<script>
	$(document).ready(function() {
		// Señala en el menu horizontal la pagina actual
		var url = location.href.match(/[a-z0-9_-]{1,}.php[/?seccion=a-z]{0,}/gi);
		$(".lista_enlaces").find("a").each(function() {
			if ($(this).attr("href") == url) {
				//alert(url);
				$(this).css({
					"color" : "white"
				});
				$(this).parent().css({
					"background-color" : "#3869A0"
				});
			}
		});
	});
</script>
