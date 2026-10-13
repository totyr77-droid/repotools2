# Ajustes: preferencias y presentación

## Estructura y uso

Perfil contiene Mi QR y Nombre público, ambos plegables existentes. Preferencias contiene vencimientos, Sonido/Vibración y Comodidad de uso. Accesos mantiene TOPs, Biblioteca, Mis reportes, Palets, Escáner e introducción como acción discreta. Seguridad conserva sus acciones y comportamiento. Una columna en móvil; en escritorio Perfil/Accesos a la izquierda y Preferencias a la derecha. Sin tarjetas interiores, colores o tipografía nuevos. Preferencia de mano aplicada solo a móvil y espacio final para el menú inferior.

Los interruptores guardan al tocarlos; los días guardan tras600ms sin escribir, al salir del campo o presionar Enter. Valores válidos enteros0..365, como antes. No hay botón Guardar para preferencias. Cambios simultáneos son parciales y no pisan otros campos; sucesivas ediciones de días se serializan. Valor inválido o fallo de guardado revierte el control y muestra toast de error. POST keepalive y salida de página intentan completar la última edición válida; cerrar el navegador sin red no garantiza guardado. No se agrega cola offline ni otro almacén.

## Persistencia y defaults

Migración25 de includes/database.php agrega users.sound_enabled y users.vibration_enabled, INTEGER NOT NULL DEFAULT1 con restricción0/1. Cuentas existentes y nuevas comienzan activadas; lecturas ausentes/null se interpretan true. API api/preferencias.php devuelve sound/vibration como booleanos y acepta0/1. Se reutiliza esta API para mano, días y confirmaciones, con login, CSRF y propietario de sesión; no admite editar otra cuenta ni cambiar permisos.

Mano permanece en users.handedness; días permanecen en stock_preferences.expiry_days con default efectivo30 cuando no hay fila. La lógica de avisos Stock no cambia. GET conserva handedness/profile_pending/csrf y amplía la respuesta. POST valida todos los campos antes de transacción. No se modifica api/stock.php ni su operación legacy preferences.

includes/preferences.php centraliza lectura/escritura de estas estructuras. js/stock-preferences.js reutilizado controla las tres preferencias; footer deja de cargar el antiguo controlador hand-preferences para evitar doble guardado. No hay localStorage nuevo, tabla nueva, preferencia global paralela ni cambios de autenticación.

## Escáner

Header entrega las opciones vigentes de la cuenta en data-scan-sound y data-scan-vibration del body. Inicio pasa un callback feedbackPreferences al lector compartido; se consulta al confirmar tanto Normal como Rápido. Sound OFF omite preparación y pitido; Vibration OFF omite la llamada navigator.vibrate. Una opción no desactiva la otra. Invitados sin configuración mantienen true/true.

El beep existente conserva amplitud0,45, frecuencia, duración y política de audio iPhone; la vibración conserva60ms. No se implementaron otra vez ni cambian EAN, cámara, confirmaciones o algoritmos. Lectores que no proporcionan feedbackPreferences mantienen exactamente el comportamiento anterior.

El celular vinculado, únicamente cuando atiende Inicio Normal/Rápido, consulta las mismas preferencias al abrir el lector y las aplica al aviso que reproduce ese celular. Otros destinos remotos y escáneres Stock/Biblioteca/QR mantienen el comportamiento previo. Teclado/lector en PC conserva su flujo manual previo sin introducir otro pitido.

## Feedback

Reutiliza RepoToast con su popover manual en la capa superior y desaparición automática2,4s. Se agregó un parámetro opcional kind=error para errores rojos con icono correspondiente; llamadas existentes siguen iguales. No modifica layout ni abre alert/modal. Mensajes breves: Sonido activado/desactivado, Vibración activada/desactivada, Días de anticipación actualizados y Comodidad de uso actualizada.

## Archivos

- Backend: cuenta.php, api/preferencias.php, includes/preferences.php (nuevo), includes/database.php.
- Presentación: css/settings.css (nuevo), includes/hand-preference-view.php, vistas/public-name.vista.php, vistas/header.vista.php, vistas/footer.vista.php; index.html solo versión de scripts.
- Preferencias/feedback: js/stock-preferences.js, js/action-notices.js, css/action-notices.css.
- Integración: js/script.js, js/ean-scanner.js, js/device-connection.js.
- Pruebas: tests/settings-preferences.php y tests/settings-ui.cjs (nuevos), tests/ean-scanner.cjs ampliada.
- Continuidad: PENDIENTES.md, CHANGELOG.md y este documento.

## Validación

Pasan:

- PHP tests/settings-preferences.php: migración desde esquema anterior con cuenta existente, cuenta nueva, defaults, idempotencia, escrituras parciales, aislamiento, validación atómica y límites0/365. SQLite desechable y PostgreSQL en rama schema-only de pruebas br-mute-lab-b5ixjeph, sin copia de datos privados ni escrituras en producción. XAMPP local requiere pdo_pgsql y endpoint explícito para su libpq antiguo, solo en el test. Rama temporal vence14/10; runner con configuración temporal eliminado.
- node tests/settings-ui.cjs: usuario existente/nuevo, ocho combinaciones ON/OFF en Normal/Rápido, recarga/nueva sesión,25→20 y autosave, error/reversión, Zurdo/Diestro, CSRF/método/identidad, toast sobre modal,360/390/430 y escritorio real1366. Cámara/audio simulados; no se creó mercadería real.
- ean-scanner: opciones independientes con y sin captura continua, navegador sin navigator.vibrate, y regresiones EAN/QR/audio/ciclo.
- main-rapid-scanner, device-connection-ui, scan-result, ean-scan-photo, public-names-ui y toast-top-layer.
- Lint PHP y sintaxis JS de archivos afectados. Capturas móvil/escritorio revisadas; controles visibles y contenido final accesible sobre menú.

No se afirma prueba de sonido, vibración o cámara física. Safari/iPhone no ofrece Vibration API; activarla no puede agregar soporte. Render y migración de producción se verifican después del despliegue. No se declara toda la suite del repositorio aprobada ni se inicia TOPs2.0.

## Reversión

Revertir este commit o restaurar sus archivos a5a68b1b, retirando nuevos helper/CSS/tests de esta entrega y recuperando el controlador de mano en footer. Conservar columnas de migración25 y datos de preferencias: son aditivos, no afectan código anterior; no borrar usuarios ni cambiar mano/días. No eliminar la migración25 del registro ni ejecutar DROP en producción. Tono fuerte, vibración y modo rápido de entregas anteriores permanecen.
