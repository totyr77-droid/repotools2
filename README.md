# Repotools

Escáner de códigos EAN para repositores, con registro opcional.

El visor de Inicio muestra el código de barras, el nombre y el contenido del producto. Permite pasar con botones, deslizar en móvil o usar las flechas del teclado. **Ver información** abre los detalles por separado. **Compartir este EAN** envía solo el código visible y **Compartir lista completa** incluye todo el grupo; sin compartir nativo, copia un enlace.

- Sin cuenta: escanear y guardar códigos en el navegador.
- Con cuenta: Stock guarda palets en la base de datos, con propietario e invitados que aceptan una notificación. El contenido se puede consultar sin cuenta mediante el QR; la edición requiere permisos.
- Las cuentas usan PostgreSQL cuando se configura `DATABASE_URL`, o SQLite para desarrollo local. Incluyen contraseñas mediante hash, protección CSRF y límite de intentos.
- Biblioteca permite crear y renombrar colecciones privadas, escanear o escribir EAN, guardar nombres y recorrer códigos de barras. Cada EAN aparece una vez por colección. Con al menos un producto, el dueño puede compartir una copia mediante un enlace aleatorio. El destinatario inicia sesión, revisa los productos y confirma la importación; la misma colección de origen se importa una sola vez por cuenta mientras conserve su copia. Recompartir actualiza el contenido del enlace, sin sobrescribir copias ya importadas. Los datos se guardan en la misma base de cuentas.
- TOPs es público y su acceso está en Mi cuenta. Solo los escaneos con cuenta suman. Ordena por códigos únicos acumulados. Cada lectura repetida suma al total, pero no al ranking. Los reintentos de envío con el mismo identificador no duplican una lectura. Escribir productos, importar listas o borrarlas no afecta al TOP. Se contabilizan las lecturas de cámara en Inicio, Biblioteca y Stock.
- Las cuentas tienen un campo `role`, cuyo valor inicial es `usuario`. Moderación permite gestionar rangos según los límites de administrador/manager; el registro público no permite elegir un rol. Supervisión por vínculos y Empresa como permiso adicional siguen pendientes.
- Se pueden reportar datos de productos, proponer correcciones y adjuntar fotos privadas. Administradores y moderadores revisan los tickets. Los TOPs públicos incluyen reportes enviados y aportes confirmados, exclusivamente de cuentas registradas. Consulta [reportes y configuración de fotos en Render](docs/reportes.md).
- Las cuentas tienen una campana de notificaciones en el encabezado. Aprobar o rechazar un reporte genera un aviso privado para su autor, dentro de la misma transacción; repetir una revisión no duplica el aviso. La lectura se guarda en la base. El panel consulta novedades cada minuto mientras la página está visible y permite abrir el reporte o marcar todos los avisos como leídos.

## Ejecutar con XAMPP

Estado técnico actual en [ARCHITECTURE.md](ARCHITECTURE.md), tareas futuras en [ROADMAP.md](ROADMAP.md) y entregas/historial en [CHANGELOG.md](CHANGELOG.md). La PWA usa `manifest.webmanifest`, los iconos aportados en `img/` y el único service worker existente.

Inicio, Biblioteca y Stock comparten el lector de `js/ean-scanner.js`, la interfaz, la confirmación de tres lecturas y los controles compatibles de zoom y luz. Stock cambia al modo QR para los palets. El análisis oculto del canvas estima posición y orientación de las barras para corregir diagonales, conserva resolución y dispone de Quagga2 como localizador alternativo y de rotaciones de respaldo. Los escaneos EAN confirmados en Stock también consultan el catálogo común y suman en TOPs; escribir un código o leer el QR del palet no suma.

Activa Apache y abre `http://localhost/repotolls2/index.php`. PHP requiere las extensiones PDO SQLite y mbstring. La base de cuentas se crea automáticamente en `data/accounts.sqlite`, y esa carpeta está bloqueada por Apache y excluida de Git. No subas bases de usuarios al repositorio.

En Render Free configura `DATABASE_URL` con una base externa PostgreSQL. La imagen Docker requiere esa variable y no crea una base SQLite temporal si falta. Sigue la [guía Render + Neon](docs/render-neon.md). Para otro hosting con SQLite, configura `REPOTOOLS_DB_PATH` fuera de la carpeta pública y con almacenamiento persistente; bloquea el acceso web a `data/`.

## Probar desde el celular

GitHub guarda el código. GitHub Pages puede publicar únicamente la vista estática `index.html`, que permite probar el escáner sin cuentas. No ejecuta PHP ni el registro. Para probar todo, despliega este proyecto en un hosting con PHP y SQLite, HTTPS y almacenamiento persistente.

La cámara necesita HTTPS (o localhost en el mismo dispositivo). Abrir la IP de la computadora por HTTP desde el celular puede permitir navegar, pero no usar la cámara. No configures `DirectoryIndex index.html` en el hosting PHP: la entrada principal debe ser `index.php`.

Los escaneos permanecen en el dispositivo y se pierden si se borra el almacenamiento del navegador. La sesión de cuenta usa una cookie con HttpOnly y SameSite; no hay un banner de aceptación. El proyecto carga fuentes y bibliotecas desde proveedores externos.

## Verificación

Ejecuta `php tests/library-sharing.php` para comprobar la migración y el intercambio de colecciones, permisos, nombres repetidos y prevención de importaciones duplicadas. Usa SQLite en memoria o `REPOTOOLS_TEST_DATABASE_URL` en una rama PostgreSQL de prueba.

El zoom vertical y el botón circular de flash se muestran dentro del recuadro de cámara con efecto vidrio. La imagen llena el recuadro y el análisis oculto sigue la porción visible. El pitido usa Web Audio, preparado durante el toque que abre el lector y reactivado al confirmar; no hay botón ni preferencia de silencio. Solicita una sesión `ambient` cuando está disponible para mezclarlo con otros audios. En iPhone esta sesión respeta el modo silencio; la interacción con música depende del navegador y del sistema operativo.

Ejecuta `node tests/auth-smoke.cjs` con PHP disponible en `C:/xampp/php/php.exe` o en la variable `PHP_BIN`. La prueba usa una base temporal y verifica registro, login, logout, CSRF, restricción de palets y límite de intentos; no usa cuentas reales.

La navegación entre Inicio, Biblioteca, Stock, TOPs, Moderación y Mi cuenta usa AJAX, conserva el historial y carga los permisos desde el servidor. Al cambiar de sección se detienen cámaras, consultas pendientes y temporizadores de notificaciones. Si falla la carga, el enlace abre la página normalmente. Los formularios de ingreso, registro y cierre de sesión mantienen su envío al servidor. Ejecuta `node tests/navigation.cjs` para comprobar la limpieza de recursos entre secciones.

Ejecuta `php tests/database-config.php` para verificar el formato PostgreSQL y que una configuración incompleta no use almacenamiento temporal. Las pruebas locales de integración usan SQLite; la conexión con Neon debe verificarse tras configurar Render siguiendo la guía.

## Stock y etiquetas

Stock permite generar de 1 a 100 etiquetas A4 en blanco y negro, con QR, marca REPOTOOLS y código de 8 dígitos (incluidos ceros iniciales). La reserva dura 72 horas desde la generación. Se activa desde Stock; si no recibe su primer producto antes del plazo, se libera. Un palet que ya recibió productos no vence al vaciarlo. Eliminarlo libera su código y mantiene separado el historial anterior. Las reservas vencidas se limpian al consultar Stock, el QR o las etiquetas.

La ficha pública es `palet.php?codigo=00123456`. El QR utiliza el dominio desde el que se genera. Propietario e invitados aceptados pueden reimprimir o compartir la imagen PNG. La impresión usa imágenes SVG para conservar calidad y reducir el consumo de memoria al preparar varias hojas. Generación QR local: QRCode.js, licencia MIT en `js/vendor/qrcode-LICENSE.txt`.

Los lotes se identifican por EAN y vencimiento opcional. Coincidencias suman unidades; cajas y bultos incluyen unidades por envase. La calculadora acepta operaciones aritméticas sin ejecutar código. Al editar, la revisión impide sobrescribir cambios ajenos. Si otro usuario ocupa un código durante una activación, el guardado crea otro palet y conserva los datos enviados. Los reintentos usan una clave de operación para evitar duplicar cantidades.

Solo el propietario elimina y gestiona accesos. Los invitados pueden modificar el contenido y dejar de participar. La descripción, el propietario y la fecha/persona del último movimiento aparecen en la ficha. Los palets locales anteriores tienen una opción de importación, sin borrar el original hasta confirmar el guardado.

En Mi cuenta se configura el aviso entre 0 y 365 días antes del vencimiento. Las notificaciones se generan cuando la web consulta la campana; no son correo ni notificaciones push con la web cerrada.

Pruebas: `php tests/stock.php`, `node tests/stock-utils.cjs` y `node tests/stock-api.cjs`. Para PostgreSQL, usar `REPOTOOLS_TEST_DATABASE_URL` exclusivamente con una rama de prueba. La migración 6 conserva las tablas y cuentas existentes.
