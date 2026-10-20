social
======

Proyecto Red Social

## Configuración local

La aplicación requiere PHP con MySQLi, mysqlnd, Fileinfo y GD. Antes de abrir el sitio, configura la conexión de base de datos mediante las variables de entorno SOCIAL_DB_HOST, SOCIAL_DB_USER, SOCIAL_DB_PASSWORD y SOCIAL_DB_NAME. El usuario de base de datos debe tener solo los permisos que necesita la aplicación; no uses root.

También puedes copiar inc/config.local.php.example a inc/config.local.php y completar los valores. El archivo local está excluido de Git. No se usa Composer.
