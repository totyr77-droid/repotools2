# Repotools

Escáner de códigos EAN para repositores, con registro opcional.

- Sin cuenta: escanear y guardar códigos en el navegador.
- Con cuenta: acceso a la gestión de palets en desarrollo. Los palets todavía se guardan localmente, separados por usuario; no están sincronizados con el servidor.
- Las cuentas se almacenan en SQLite, con contraseñas mediante hash, protección CSRF y límite de intentos.

## Ejecutar con XAMPP

Activa Apache y abre `http://localhost/repotolls2/index.php`. PHP requiere las extensiones PDO SQLite y mbstring. La base de cuentas se crea automáticamente en `data/accounts.sqlite`, y esa carpeta está bloqueada por Apache y excluida de Git. No subas bases de usuarios al repositorio.

En producción, configura `REPOTOOLS_DB_PATH` con una ruta persistente fuera de la carpeta pública y habilita HTTPS. Con otros servidores, bloquea el acceso web a `data/` explícitamente. Se necesita almacenamiento persistente y permiso de escritura para el proceso PHP.

## Probar desde el celular

GitHub guarda el código. GitHub Pages puede publicar únicamente la vista estática `index.html`, que permite probar el escáner sin cuentas. No ejecuta PHP ni el registro. Para probar todo, despliega este proyecto en un hosting con PHP y SQLite, HTTPS y almacenamiento persistente.

La cámara necesita HTTPS (o localhost en el mismo dispositivo). Abrir la IP de la computadora por HTTP desde el celular puede permitir navegar, pero no usar la cámara. No configures `DirectoryIndex index.html` en el hosting PHP: la entrada principal debe ser `index.php`.

Los escaneos permanecen en el dispositivo y se pierden si se borra el almacenamiento del navegador. La sesión de cuenta usa una cookie con HttpOnly y SameSite; no hay un banner de aceptación. El proyecto carga fuentes y bibliotecas desde proveedores externos.

## Verificación

Ejecuta `node tests/auth-smoke.cjs` con PHP disponible en `C:/xampp/php/php.exe` o en la variable `PHP_BIN`. La prueba usa una base temporal y verifica registro, login, logout, CSRF, restricción de palets y límite de intentos; no usa cuentas reales.
