social
======

Proyecto Red Social

## Configuración local

La aplicación requiere PHP con MySQLi, mysqlnd, Fileinfo y GD. Antes de abrir el sitio, configura la conexión de base de datos mediante las variables de entorno SOCIAL_DB_HOST, SOCIAL_DB_USER, SOCIAL_DB_PASSWORD y SOCIAL_DB_NAME. El usuario de base de datos debe tener solo los permisos que necesita la aplicación; no uses root.

## Estructura

- Los archivos PHP de la raíz son las rutas HTTP existentes; se mantienen ahí para conservar sus URLs.
- `app/` contiene la configuración y los módulos internos, organizados por función; `app/auth/` contiene la autenticación y el manejador de registro.
- `assets/css/`, `assets/js/` y `assets/images/` contienen los recursos propios; `assets/vendor/` contiene las bibliotecas de terceros.
- `database/schema/` contiene los modelos de Workbench y `database/migrations/` las migraciones SQL.
- `docs/` contiene la hoja de ruta del proyecto.

