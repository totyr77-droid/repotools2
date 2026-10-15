# RepoTools — mejoras unificadas

## Alcance y decisiones

Se amplía la aplicación PHP/PDO existente. No se reemplazan escáneres, autenticación, componentes de Stock ni la cola IndexedDB v4. Se conserva la barra compacta de Biblioteca y la distribución vigente de una mano. No se incluyen CSV, recompensas ni alertas automáticas de auditoría.

### Auditoría

- Migración29 crea audit_events y ranking_daily, índices por fecha/actor/palet/EAN/sucursal/categoría. Eventos sin FK a actor, destinatario o palet: identificadores y nombre/rango del responsable sobreviven a su eliminación.
- Evento confirmado y contador se escriben dentro de la transacción de negocio. El ID deriva del responsable, acción, operación y resultado; el índice primario impide duplicación. Los rechazos/conflictos se conservan sin sumar actividad ni unidades.
- Datos antes/después se limpian de claves de contraseña, tokens, credenciales, correo, señales IP e imágenes. Seguridad de cuenta registra solo el cambio de versión de acceso, nunca hashes ni contraseñas.
- Stock reutiliza los snapshots de actividad existentes. Cantidades se calculan por cambio neto de EAN: fusionar lotes o editar nombres/vencimientos no cuenta como mover las mismas unidades nuevamente.
- Registro de palets/mercadería/accesos, sucursales, enlaces Stock/Biblioteca, generación significativa de QR, catálogo, revisiones/bloqueos, rangos, cuenta, verificación y referidos. No se registran visitas, navegación, consultas o autenticaciones rutinarias.
- Se preservan y copian registros legados. Donde el rango histórico no existía, se informa “No registrado”; no se lo sustituye por el rango actual. El histórico antiguo no se borra ni se usa para inventar contadores que carecen de identificación de operación fiable.
- Moderadores: acciones propias y de rango inferior, comprobando también el rango actual del actor; no ven denegaciones ajenas. Administradores y managers: actividad completa. Permisos se evalúan en cada lectura de API, incluidos detalles. Usuarios comunes y visitantes no acceden.
- Filtros por categoría, acción, actor, rango, fechas, sucursal, palet y EAN; búsqueda de usuario/palet/EAN. Páginas de50; detalles largos se consultan solo al abrirlos. PC muestra un panel lateral; móvil despliega dentro del registro. Nuevas cuentas muestran invitador y verificación, sin correo privado.
- Con el volumen actual no hace falta particionar. No hay tarea de purga de eventos. Si crece el histórico, se podrá particionar por fecha manteniendo event_id e índices/consultas; no aplicar borrados arbitrarios para “limpiar” registros.

### TOPs

Tres categorías visibles: Referidos, Actividad y Sucursales. Top10, RANK para empates y posición/estadística personal aunque quede fuera del Top10. Se conservan funciones legadas para compatibilidad de llamadas internas.

Zona: America/Argentina/Buenos_Aires. Semana lunes00:00 a domingo23:59:59; mes calendario. Los límites se calculan al consultar y no eliminan históricos.

- Referidos: estado validado y prueba de correo, contabilizados por email_verified_at. Cambios de rango no alteran su atribución.
- Actividad: una operación confirmada de Stock/catálogo/revisión/gestión cuenta una vez.50 unidades no equivalen a50 operaciones. Consultas, escaneos sin operación, operaciones rechazadas y mantenimiento de cuenta no suman.
- Sucursales: suma absoluta del cambio neto de unidades por EAN al ingresar/retirar/eliminar mercadería activa. Mover el palet a otra sucursal cuenta sus unidades una sola vez en el destino. La asignación inicial con mercadería cuenta como entrada en esa sucursal; quitar ubicación no atribuye unidades a una sucursal ficticia. Ediciones de texto y fusiones sin cambio neto de unidades no suman.
- Actividad/Sucursales usan agregados diarios transaccionales, no recorren eventos en cada vista. Su histórico empieza con esta activación; la interfaz lo aclara. No se reconstruyen puntuaciones antiguas a partir de registros incompletos.

### Referidos y correo

Se reutilizan referral_codes/referrals/referral_decisions y rutas /r/CODIGO. El invitador se fija al registrar, con unicidad por invitado y prohibición de autorreferido. La sesión conserva atribución durante la navegación. Compartir conexión no bloquea acreditación. Una señal IP opcional se guarda solo con clave privada suficiente; nunca identifica por sí sola un fraude.

El correo de verificación se ofrece a todas las cuentas, invitadas o no. Registro y uso funcionan aunque falle Resend. Tokens aleatorios256bits, hashes almacenados, duración24h, uso único transaccional y reenvío60s/5porhora serializado. Nuevos enlaces llevan el token en el fragmento, que no viaja en el GET ni en su access log; la página lo consume y confirma por POST con CSRF. Enlaces antiguos siguen admitidos. Se distinguen vencido, utilizado e inválido.

Correo de bienvenida: logo real, colores existentes, saludo escapado, cuatro ilustraciones PNG propias a partir de iconos SVG del proyecto, HTML de tablas/estilos inline y texto plano. Las imágenes no son necesarias para comprender el mensaje.

Perfil muestra verificación, reenvío y cambio de dirección. El correo anterior sigue vigente hasta confirmar el nuevo; al cambiarlo se invalidan recuperaciones pendientes del anterior. Respuestas de solicitud de nueva dirección no revelan si otra cuenta la usa. Mis referidos muestra únicamente confirmados, total real y los últimos100 nombres públicos, sin direcciones privadas.

Accesos mantiene exactamente las seis celdas: TOPs/Biblioteca; Mis reportes/Palets; Escáner/Compartir app. El nuevo acceso comparte el enlace personal real mediante Web Share; ofrece portapapeles y selección manual si el navegador no permite copiar.

### Instalación PWA

Tarjeta antes de Tu lista en modo normal y después de Tu lista en una mano; la mueve el sistema existente junto con la lista, sin cambiar escáner/nav. Sin X ni descarte propio. Usa beforeinstallprompt cuando existe; iPhone recibe instrucciones de Safari. Tras cancelar el diálogo nativo, la tarjeta permanece y ofrece instrucciones. Se oculta en standalone/navigator.standalone o al recibir appinstalled. No existe detección universal de una instalación iOS cuando se visita desde una pestaña normal: ahí puede seguir apareciendo la ayuda.

### Retiros offline

Mismos controles de retiro del detalle, y retiro desde cada lote de la pantalla offline. Solo se ofrece sobre copia autorizada de un lote concreto. IndexedDB guarda usuario, palet interno/código, producto/lote, cantidad, fecha y UUID. La reserva local y comprobación de saldo se hacen en una transacción de loads+palets, también entre pestañas. No se cambian snapshots confirmados: la vista proyecta retiros pendientes y no permite sobregiro local.

El motor existente sincroniza en orden, reutilizando exclusión mutua/leases, backoff, recuperación al volver al frente y confirmación atómica loads/sent/palets. Contrato3 confirma retiros: UUID, usuario, palet interno/código, lote, EAN y ecuación antes−retiro=después. Solo se quita la cola tras committed=true y coincidencia del contrato. Altas mantienen contrato2 y fingerprints compatibles con operaciones anteriores. Una respuesta perdida se reintenta con el mismo UUID y recupera el comprobante, sin volver a descontar.

El servidor comprueba permisos y existencias actuales dentro de la transacción. La revisión offline puede ser antigua, pero el lote y su EAN/vencimiento deben coincidir; un cambio ajeno no autoriza stock negativo. Conflictos/rechazos permanecen visibles con el motivo y acceso a revisión o descarte explícito. Descartar restaura la proyección local; no modifica el servidor.

La fecha original se recibe del dispositivo y se valida contra el reloj del servidor: hasta30días atrás/5minutos adelante. No hay forma de certificar el reloj de un equipo desconectado; se conserva también la fecha de sincronización. Fuera del rango queda rechazado para revisión, nunca se borra ni se redatan silenciosamente los movimientos. El servidor determina las unidades/operaciones que suman; no acepta puntuaciones del cliente.

## Base y configuración

Opcionalmente puede prepararse con php scripts/migrate-unified.php en el servidor con sus variables habituales; no requiere copiar claves al código. Migración29 es aditiva, automática e idempotente desde includes/database.php. Además del ledger/agregados, agrega users.email_verified_at/pending_email, email_verifications y email_delivery_limits, e instala las tablas existentes de referidos si faltan. Importa auditoría previa y pruebas de correo válidas existentes sin fabricar rangos históricos. No vacía IndexedDB ni colas. Se probó sobre SQLite y copias PostgreSQL de producción en ramas Neon separadas.

Variables de correo: RESEND_API_KEY, MAIL_FROM (remitente autorizado, por ejemplo RepoTools <acceso@repotools.online>) y APP_URL (origen real HTTPS). Sin credenciales, la cuenta se crea y muestra cómo pedir otro correo. No se enviaron correos reales de prueba en esta entrega. Verificar llegada a bandeja/spam con una cuenta de prueba propia tras desplegar.

Referidos está preparado para producción; ya no exige una base beta ni una lista fija de hosts. REPOTOOLS_REFERRALS_ENABLED=0 lo desactiva explícitamente; los indicadores antiguos de beta dejan de ser requisitos. REFERRALS_SIGNAL_KEY es opcional para señales anónimas contra abuso, no para acreditar usuarios; sin clave suficiente no se almacena la señal IP.

## Validación

Ver lista final de ejecuciones debajo. Pruebas usan SQLite temporal/fixtures o ramas Neon aisladas. No se modificó inventario real para probar ni se enviaron correos a terceros. Capturas de Ajustes, TOPs, auditoría y bienvenida móvil revisadas. No afirmar pruebas físicas de Safari/iPhone, Android, instalación o cámara que no se hayan realizado.

## Reversión

Preferir revertir presentación o desactivar Referidos con su variable, conservando migración29, ledger y contratos2/3 mientras haya movimientos pendientes. No borrar tablas/columnas, comprobantes, IndexedDB o caches para “resolver” conflictos. Si se revierte código completo, mantener el soporte servidor/sincronización de retiros ya encolados y publicar una versión nueva para renovar assets; no reducir el número de versión. Credenciales nunca forman parte de Git o documentación.

## Archivos de la entrega

### Creados

- includes/audit.php
- includes/audit-read.php
- includes/email-verification.php
- api/verificacion.php
- verificar-correo.php
- js/account-sharing.js
- js/pwa-install.js
- scripts/migrate-unified.php
- tests/unified-core.php
- tests/unified-ux.cjs
- tests/unified-presentation.cjs
- docs/MEJORAS-UNIFICADAS.md
- img/email/pallets.png
- img/email/pallets.svg
- img/email/ean.png
- img/email/ean.svg
- img/email/stock.png
- img/email/stock.svg
- img/email/qr.png
- img/email/qr.svg

### Modificados

- api/cuenta.php
- api/referrals.php
- api/reportes.php
- api/stock.php
- api/sucursales.php
- api/usuarios.php
- css/ux-polish.css
- cuenta.php
- includes/account-security.php
- includes/catalog.php
- includes/database.php
- includes/library-sharing.php
- includes/pallet-activity.php
- includes/password-reset.php
- includes/referrals.php
- includes/report-log-filters.php
- includes/reports.php
- includes/stock.php
- includes/tops.php
- includes/user-profile-view.php
- includes/user-profiles.php
- index.php
- js/offline-page.js
- js/offline-sync.js
- js/one-hand-mode.js
- js/referrals.js
- js/reports-page.js
- js/stock-offline.js
- js/stock.js
- referidos.php
- reportes.php
- scripts/migrate-referrals.php
- sw.js
- tests/referrals-ui.cjs
- tests/referrals.php
- tests/reports-api.cjs
- tests/tops-ui.cjs
- tests/user-profile-edit-ui.cjs
- tests/user-profiles-api.cjs
- tops.php
- vistas/footer.vista.php
- vistas/tops-ranking.vista.php
- PENDIENTES.md

release.json se actualiza únicamente al cerrar esta entrega. No se incluyen los cambios locales previos en CHANGELOG.md, css/mobile-navigation.css, docs/IMPRESION-PENDIENTES.md, docs/render-neon.md ni img/logo.png.


## Resultados ejecutados

Aprobados en SQLite temporal:

- tests/unified-core.php: migración, acreditación/verificación/caducidad/reutilización, cambio de correo, límites, autorreferido, Top10/empates/posición externa, privacidad, permisos/filtros y detalle diferido.
- tests/referrals.php, tests/referrals-ui.cjs: registro con atribución persistente, usuarios normales/invalid code, verificación idempotente, red compartida sin bloqueo, revisión autorizada, nombres públicos y compartir/copiar.
- tests/tops.php, tests/tops-ui.cjs: límites argentinos, compatibilidad de estadísticas previas, nuevas categorías y períodos, empates, estados vacíos y320–1366px.
- tests/stock-api.cjs y tests/unified-ux.cjs: permisos/CSRF, UUID/comprobantes, retiros consecutivos, saldo local, reapertura offline real vía Service Worker, respuesta perdida tras guardar y repetición sin doble descuento, conflicto retenido sin saldo negativo, auditoría y unidades de sucursal contadas solo en destino.
- tests/unified-presentation.cjs: Ajustes con seis celdas exactas, Tops, auditoría con detalle bajo demanda y ambos lados a320/390/768/1366; bienvenida360px con logo+4PNG cargados; enlace de correo por fragmento consumido y verificación real sobre fixture.
- tests/offline-intelligent.cjs: estado de conexión/servidor/autenticación, cola anterior de Biblioteca, reintentos, fallback entre pestañas, shell y OCR/QR locales, rechazo de palet desconocido, sesión vencida, privacidad y conservación de pendientes.
- tests/stock-branches-ui.cjs y tests/stock-branches.php: sucursales, permisos, asignación, exclusiones dinámicas, enlaces y revocación, CSRF y responsive.
- tests/pallet-history-all-actors.php: historial existente de propietario/miembros, eliminaciones y accesos.
- tests/user-profiles-api.cjs, tests/user-profile-edit-ui.cjs, tests/user-profiles.php: jerarquía, edición/privacidad, destinatarios, baja inmediata de permisos y correo nuevo pendiente.
- tests/reports-access.cjs, tests/reports-api.cjs, tests/reports.php: invitados, IDOR/fotos, permisos, aprobación/rechazo/idempotencia, bloqueo y auditoría. Se actualizó la expectativa de Registros para moderadores según la nueva política; administración de usuarios permanece exclusiva de administradores/managers.
- tests/settings-ui.cjs y tests/one-hand-workspaces.cjs: sonido/vibración Normal/Rápido, preferencias, recarga/sesiones, lado hábil, las cinco secciones, navegación inferior y despeje de contenido.
- tests/integral-ui.cjs: detalle de100productos, scroll/footer/teclado, selección múltiple, enlaces y barra compacta de Biblioteca.
- tests/password-reset.php y tests/account-security-api.cjs: caducidad/uso único, contraseñas, sesiones, revocación y nuevos eventos sin secretos.

PostgreSQL: migración desde producción28 a29 completada en dos ramas aisladas, con218/226registros históricos preservados en sus respectivas capturas. Consultas de los tres rankings, auditoría paginada y la inserción atómica/idempotente evento+contadores aprobadas. Fixtures de escritura transaccionales revertidas; no se cambió inventario de producción para probar.

Ramas conservadas para diagnóstico: verify-unified-modules-20261015 y verify-unified-final-20261015. Son copias de prueba, no servicios Render ni cambios en la rama productiva. Su limpieza posterior es opcional y no afecta la entrega.

No ejecutado físicamente: Safari/iPhone/PWA instalada, Android/cámara real, menús nativos de instalación/compartición en esos equipos, clientes de correo Outlook/Gmail/iOS Mail y recepción real por Resend. El HTML, las imágenes, las rutas y estados se probaron localmente; no se afirma entrega de correos reales.

Revisión final: sintaxis de52archivos PHP/JS aprobada. La prueba de fusión de lotes cuenta solo el cambio neto de30unidades, no las70unidades que resultarían de sumar eliminación+incorporación. Un guardado sin cambio de datos de negocio no suma actividad. Cancelar la instalación nativa no oculta la tarjeta.
