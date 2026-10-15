# RepoTools — trece mejoras y correcciones

Entrega sobre Main. Se conserva la arquitectura PHP/PDO, IndexedDB v4, el motor de cámara y los contratos offline 2/3. El color de las nuevas selecciones y de todas las plantillas de correo es `#27197F`.

## Resumen por punto

1. **PWA en Inicio:** el sistema existente de una mano mueve instalación → Tu lista → Escanear producto. Restaurar el modo normal devuelve los nodos a sus posiciones originales. No se cambia detección, instrucciones o descarte. Archivo: `js/one-hand-mode.js`.
2. **Selección de Stock:** Mis palets incorpora Seleccionar; al activarlo aparecen Todos, Ninguno, Cancelar, Imprimir con cantidad exacta y Eliminar. Se seleccionan hasta100, sin cambiar tamaño de tarjetas. Pendientes también tiene Todos/Ninguno independientes. Barras de una fila; acciones destructivas al lado opuesto de la mano en grupos horizontales de Stock, Biblioteca, enlaces, sucursales y confirmaciones. Se reserva espacio para los flotantes y las casillas. Archivos: `stock.php`, `js/stock.js`, `api/stock.php`, `css/ux-polish.css`.
3. **Teclado:** se usa Visual Viewport compartido, sin sumar un segundo desplazamiento en los sheets de una mano. Los modales se dimensionan/centran dentro del área visible; el campo se desplaza en su contenedor interno, no mediante scroll de toda la página. Se limpia el estado al perder foco/cambiar sección. Entradas móviles de texto de al menos16px. Archivos: `js/dialog-scroll.js`, `js/one-hand-mode.js`, `css/ux-polish.css`, `offline.html`.
4. **Detalle privado:** altura intrínseca con `fit-content`, espaciados compactos y zona de productos que puede contraerse y desplazarse. Encabezado y acciones conservan acceso con listas largas. No se cambian datos privados ni controles. Archivos: `css/ux-polish.css`, `js/stock.js`.
5. **Historial:** color del icono derivado de `action` y, para cantidades, de valores anterior/nuevo: entrada verde, salida/eliminación roja, información azul, ubicación ámbar y administración violeta. No se buscan palabras en descripciones. Archivos: `js/pallet-activity.js`, `js/stock-overview.js`, `css/ux-polish.css`.
6. **Sucursal:** Enter y el envío implícito buscan/filtran; seleccionar solo prepara el formulario. Únicamente Guardar asigna. Se retira “Opcional”. El guardado ya no depende de refrescar el directorio: null se envía directamente por el mecanismo existente. Se distinguen errores de directorio y de guardado. El servidor sigue validando revisión, propiedad y política de enlaces. Archivos: `js/stock-branches.js`, `vistas/stock-branches.vista.php`.
7. **Público:** Stock compartido deja de consultar/devolver propietario, descripción interna y última modificación. El QR individual utiliza una respuesta pública reducida incluso cuando lo abre su propietario; visitantes tampoco reciben esos campos por la consulta normal. Información de sucursal y mercadería permanece. Se mantienen reservas de etiquetas y acceso a Stock según permisos existentes. Archivos: `includes/stock-branches.php`, `includes/stock.php`, `api/stock.php`, `js/stock-branches.js`, `js/public-pallet.js`, `css/ux-polish.css`.
8. **Propietario/gestor:** se reutilizan las comprobaciones de propietario existentes para eliminar/invitar/revocar. El lote completo se autoriza antes de modificar nada y se confirma en una transacción idempotente, con historial y auditoría por palet. Gestores conservan alta/retiro/edición autorizada y pueden Desvincularme; solo se borra su acceso. La interfaz advierte sobre pérdida de acceso y movimientos pendientes, retira la copia de consulta propia sin borrar la cola y permite reinvitación. Los privilegios administrativos preexistentes se conservan. Archivos: `api/stock.php`, `js/stock.js`, `vistas/stock-dialogs.vista.php` (reutiliza `removeCached` de la cola existente).
9. **Mi lista:** alias aleatorio de8 caracteres `/l/XXXXXXXX`, usando el generador seguro existente. Payload únicamente EANs, validado y en orden; deduplicación por hash, colisiones controladas e índice único. POST con CSRF y límite60/h por sesión. La lista era pública antes y sigue siéndolo: no se guardan cuentas, sesiones ni secretos en el payload. No se fija vencimiento. Los enlaces comprimidos antiguos continúan funcionando; sin conexión o si no se puede crear el alias se conserva ese formato. LZString1.4.4, ya utilizado, pasa del CDN a archivo local con su licencia. Archivos: `includes/list-sharing.php`, `includes/public-link-aliases.php`, `api/listas.php`, `lista-compartida.php`, `.htaccess`, `includes/database.php`, `includes/page-metadata.php`, `index.php`, `js/script.js`, `vistas/footer.vista.php`, `sw.js`, archivos LZString.
10. **Escáner de Inicio:** ayuda breve antes de leer, resultado real o Código detectado con EAN después. Ventana ajustada al contenido, cámara anclada de forma estable al cambiar Normal/Rápido y misma instancia de reconocimiento. Zoom, linterna, manual, contador y protección de duplicados conservados. Archivos: `vistas/escaner.vista.php`, `js/script.js`, `css/ux-polish.css`.
11. **Biblioteca:** etiquetas compactas encima de Agregar producto/Opciones/Compartir, únicamente mientras existe el grupo flotante desplegado. No se etiqueta Nueva colección y se conserva su forma rectangular. Anchura limitada y texto en dos líneas cuando hace falta para evitar superposición. Archivos: `js/one-hand-mode.js`, `css/ux-polish.css`.
12. **Correo:** plantilla base compartida por bienvenida/verificación, recuperación, contraseña actualizada y verificación antigua de referidos. Logo PNG real, ahora versionado en una ruta pública estable, más cuatro ilustraciones existentes. HTML con tablas, colores de fondo explícitos, alt, texto plano conservado y comprensión sin imágenes. No hay otros envíos de invitaciones/notificaciones por correo en el proyecto: los de palets son notificaciones internas; no se inventan plantillas ni envíos nuevos. Archivos: `includes/email-layout.php`, `includes/email-verification.php`, `includes/password-email.php`, `includes/referrals.php`, `img/email/logo.png`.
13. **Ajustes/TOPs:** Cambiar correo pasa a Seguridad y exige contraseña actual validada en servidor; límites5 solicitudes/15min para reautenticación, además de límites existentes de correo. La dirección anterior sigue vigente hasta verificar la nueva. Perfil conserva estado/Reenviar y retira Mis referidos. TOPs agrega enlace personal, copiar, compartir y cantidad confirmada; tres radios compactos reemplazan el select de categoría, manteniendo períodos y fórmulas. Archivos: `cuenta.php`, `includes/account-security-view.php`, `api/verificacion.php`, `js/account-sharing.js`, `tops.php`, `js/tops.js`, `css/ux-polish.css`.

## Base de datos y configuración

Migración **30 aditiva**: `scan_list_shares(code PRIMARY KEY, payload_hash UNIQUE, payload, created_at)`. No altera cuentas, permisos, palets, auditoría o colas; no cambia la tabla ni los alias anteriores de p/s/b. Se ejecuta por el migrador habitual; `scripts/migrate-unified.php` permite ejecutarla por CLI si hace falta.

No hacen falta nuevas credenciales. Se conservan `APP_URL` HTTPS público, `RESEND_API_KEY` y `MAIL_FROM`. El logo se publica en `/img/email/logo.png`; las cuatro ilustraciones permanecen en `/img/email/`. No se enviaron correos reales de prueba.

## Validación

- `tests/unified-13.cjs`: permisos directos de gestor, alta/retiro, bloqueo de eliminación/invitación/revocación/compartición ajena, desvinculación y reinvitación, eliminación atómica y repetición sin duplicación, selección móvil en ambas manos, privacidad pública, búsqueda versus guardado de sucursal, Sin sucursal con directorio fallido, teclado simulado con offset, alias y enlaces largos, reautenticación, TOPs y orden PWA.
- `tests/unified13-core.php`: migración29→30 con conservación, alias en orden, deduplicación/colisión, checksum/límites y diseño común de correos con cinco imágenes existentes.
- `tests/unified-core.php`: verificación/expiración/uso único, cambio de correo pendiente, acreditación idempotente, auditoría y reglas de los tres rankings.
- `tests/stock-api.cjs`: autenticación, CSRF, idempotencia, permisos, invitaciones, impresión, desvinculación y reutilización de códigos.
- `tests/stock-branches.php` y `tests/stock-branches-ui.cjs`: directorio, asignación, conflictos, compartir/revocar, paginación y responsive.
- `tests/main-rapid-scanner.cjs`:20 lecturas, una cámara, duplicados, producto desconocido, reporte y alternancia Normal/Rápido. Se actualiza la expectativa de alto fijo a alto de contenido, conservando posición/tamaño del visor.
- `tests/integral-ui.cjs`: detalle con100 productos y scroll interno, footer y teclado, selección, enlaces y Biblioteca.
- `tests/one-hand-workspaces.cjs`: cinco secciones, ambas manos,320/390/1366 y espacio para navegación.
- `tests/tops-ui.cjs`: botones de categoría, períodos, actualización sin recarga y estados vacíos.
- `tests/unified-presentation.cjs`: Ajustes/TOPs/auditoría,320/390/768/1366, correo360px con cinco imágenes y confirmación por fragmento.
- `tests/unified-ux.cjs`: retiros offline, reapertura, saldo local, respuesta perdida, reintento sin duplicación, conflicto retenido, comprobantes, compartir y PWA. Las expectativas reflejan el nuevo orden solicitado del aviso.
- `tests/password-reset.php` y `tests/account-security-api.cjs`: seguridad de recuperación, contraseña actual, revocación de sesiones y tokens conservadas.
- `tests/settings-ui.cjs`: preferencias de sonido/vibración, vencimiento, mano, recarga y sesiones.
- `tests/public-link-aliases.php` y `tests/public-stock-summary.cjs`: alias anteriores, colisiones y resumen público por EAN/páginas con revocación.
-45 verificaciones de sintaxis PHP/JS sin errores.
- Migración30 ejecutada en PostgreSQL **aislado**:226 eventos históricos conservados, lecturas de auditoría/TOPs y eventos/contadores transaccionales idempotentes aprobados; fixtures revertidas. No se usó inventario de producción para probar.

Una ejecución con varios navegadores simultáneos tuvo un timeout local y otra se interrumpió; se repitieron secuencialmente. Esos intentos no se consideran pruebas aprobadas. Las expectativas antiguas sobre descripción pública, categorías nativas o altura fija se actualizaron por los cambios expresamente solicitados.

## Límites y comprobaciones físicas pendientes

- Se probó Chrome automatizado con viewport móvil, táctil y escritorio; simular Visual Viewport no reemplaza el teclado físico de Safari/iPhone, Android o una PWA instalada.
- Pendiente verificar Web Share, menús nativos de instalación y cámaras en dispositivos reales. El reconocimiento de cámara no se reemplazó.
- Pendiente recepción real y aspecto en Gmail, Outlook y Apple Mail, incluido modo oscuro. El HTML renderizado y los archivos de imagen sí se verificaron.
- La detección universal de una PWA ya instalada desde una pestaña común de iOS sigue siendo una limitación del navegador, sin cambios en esta entrega.
- Un retiro pendiente al desvincularse conserva su movimiento, pero el servidor exigirá acceso válido para confirmarlo; la interfaz lo advierte. No se fuerza ni se descarta ese retiro.

## Reversión y continuidad

Conservar migración30, payloads y ruta de lectura `/l/` para no romper enlaces ya enviados. Revertir selectivamente estilos/controles si fuera necesario; no borrar IndexedDB, historial, operaciones pendientes ni tablas de producción. Los cambios locales previos ajenos a esta tarea (navegación móvil, logo general y documentación anterior) no forman parte de la publicación. Ver `PENDIENTES.md` para las comprobaciones físicas pendientes.

## Archivos de esta entrega

- `.htaccess`
- `PENDIENTES.md`
- `api/listas.php`
- `api/stock.php`
- `api/verificacion.php`
- `css/ux-polish.css`
- `cuenta.php`
- `docs/MEJORAS-UNIFICADAS-13.md`
- `img/email/logo.png`
- `includes/account-security-view.php`
- `includes/database.php`
- `includes/email-layout.php`
- `includes/email-verification.php`
- `includes/list-sharing.php`
- `includes/page-metadata.php`
- `includes/password-email.php`
- `includes/public-link-aliases.php`
- `includes/referrals.php`
- `includes/stock-branches.php`
- `includes/stock.php`
- `index.php`
- `js/account-sharing.js`
- `js/dialog-scroll.js`
- `js/one-hand-mode.js`
- `js/pallet-activity.js`
- `js/public-pallet.js`
- `js/script.js`
- `js/stock-branches.js`
- `js/stock-overview.js`
- `js/stock.js`
- `js/tops.js`
- `js/vendor/LICENSE-lz-string.txt`
- `js/vendor/lz-string-1.4.4.min.js`
- `lista-compartida.php`
- `offline.html`
- `release.json`
- `stock.php`
- `sw.js`
- `tests/integral-ui.cjs`
- `tests/main-rapid-scanner.cjs`
- `tests/stock-api.cjs`
- `tests/stock-branches.php`
- `tests/tops-ui.cjs`
- `tests/unified-13.cjs`
- `tests/unified-core.php`
- `tests/unified-presentation.cjs`
- `tests/unified-ux.cjs`
- `tests/unified13-core.php`
- `tops.php`
- `vistas/escaner.vista.php`
- `vistas/footer.vista.php`
- `vistas/stock-branches.vista.php`
- `vistas/stock-dialogs.vista.php`
