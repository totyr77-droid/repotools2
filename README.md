# Repotools

Escáner de códigos EAN para repositores, con registro opcional.

El visor de Inicio muestra el código de barras, el nombre y el contenido del producto. Permite pasar con botones, deslizar en móvil o usar las flechas del teclado. **Ver información** abre los detalles por separado. **Compartir este EAN** envía solo el código visible y **Compartir lista completa** incluye todo el grupo; sin compartir nativo, copia un enlace.

- Sin cuenta: escanear y guardar códigos en el navegador.
- Con cuenta: acceso a la gestión de palets en desarrollo. Los palets todavía se guardan localmente, separados por usuario; no están sincronizados con el servidor.
- Las cuentas usan PostgreSQL cuando se configura `DATABASE_URL`, o SQLite para desarrollo local. Incluyen contraseñas mediante hash, protección CSRF y límite de intentos.
- Biblioteca permite crear y renombrar colecciones privadas, escanear o escribir EAN, guardar nombres y recorrer códigos de barras. Cada EAN aparece una vez por colección. Solo su dueño puede consultar o editar una colección. Los datos se guardan en la misma base de cuentas.
- TOPs es público y su acceso está en Mi cuenta. Solo los escaneos con cuenta suman. Ordena por códigos únicos acumulados. Cada lectura repetida suma al total, pero no al ranking. Los reintentos de envío con el mismo identificador no duplican una lectura. Escribir productos, importar listas o borrarlas no afecta al TOP. Se contabilizan las lecturas de cámara en Inicio y Biblioteca.
- Las cuentas tienen un campo `role`, cuyo valor inicial es `usuario`. La asignación de otros permisos y su administración quedan pendientes; el registro público no permite elegir un rol.
- Se pueden reportar datos de productos, proponer correcciones y adjuntar fotos privadas. Administradores y moderadores revisan los tickets. Los TOPs públicos incluyen reportes enviados y aportes confirmados, exclusivamente de cuentas registradas. Consulta [reportes y configuración de fotos en Render](docs/reportes.md).

## Ejecutar con XAMPP

Activa Apache y abre `http://localhost/repotolls2/index.php`. PHP requiere las extensiones PDO SQLite y mbstring. La base de cuentas se crea automáticamente en `data/accounts.sqlite`, y esa carpeta está bloqueada por Apache y excluida de Git. No subas bases de usuarios al repositorio.

En Render Free configura `DATABASE_URL` con una base externa PostgreSQL. La imagen Docker requiere esa variable y no crea una base SQLite temporal si falta. Sigue la [guía Render + Neon](docs/render-neon.md). Para otro hosting con SQLite, configura `REPOTOOLS_DB_PATH` fuera de la carpeta pública y con almacenamiento persistente; bloquea el acceso web a `data/`.

## Probar desde el celular

GitHub guarda el código. GitHub Pages puede publicar únicamente la vista estática `index.html`, que permite probar el escáner sin cuentas. No ejecuta PHP ni el registro. Para probar todo, despliega este proyecto en un hosting con PHP y SQLite, HTTPS y almacenamiento persistente.

La cámara necesita HTTPS (o localhost en el mismo dispositivo). Abrir la IP de la computadora por HTTP desde el celular puede permitir navegar, pero no usar la cámara. No configures `DirectoryIndex index.html` en el hosting PHP: la entrada principal debe ser `index.php`.

Los escaneos permanecen en el dispositivo y se pierden si se borra el almacenamiento del navegador. La sesión de cuenta usa una cookie con HttpOnly y SameSite; no hay un banner de aceptación. El proyecto carga fuentes y bibliotecas desde proveedores externos.

## Verificación

Ejecuta `node tests/auth-smoke.cjs` con PHP disponible en `C:/xampp/php/php.exe` o en la variable `PHP_BIN`. La prueba usa una base temporal y verifica registro, login, logout, CSRF, restricción de palets y límite de intentos; no usa cuentas reales.

Ejecuta `php tests/database-config.php` para verificar el formato PostgreSQL y que una configuración incompleta no use almacenamiento temporal. Las pruebas locales de integración usan SQLite; la conexión con Neon debe verificarse tras configurar Render siguiendo la guía.
