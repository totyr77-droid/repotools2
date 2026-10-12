# Sucursales y stock compartido — 11/10/2026

## Decisiones acordadas

- Sucursal opcional, con directorio común para todas las cuentas registradas. Todos pueden crear y seleccionar; solo administradores y managers pueden editar o eliminar.
- Se carga por lista: nombre, calle/altura, localidad, provincia y descripción opcional.
- Búsqueda local por nombre o dirección y selección desde los palets. El mapa se deja para el final; no se contrató proveedor ni se añadieron claves.
- Solo el propietario asigna sus palets a una sucursal del directorio común. Los permisos anteriores para editar productos y consultar palets siguen vigentes.
- La sucursal se puede elegir al usar un QR disponible y también en un palet propio todavía sin productos. Compartir una selección sigue requiriendo palets activos; las etiquetas pendientes no se comparten.
- Compartir una sucursal incluye solo los palets del creador del enlace, actuales y los que asigne después. Nunca incluye los de otras cuentas de la misma sucursal. Compartir una selección conserva los IDs de los palets elegidos, con contenido actualizado.
- Los enlaces son públicos, de solo lectura y no requieren cuenta. Quien tenga el enlace puede ver nombres, empresa, descripciones, productos, cantidades, fechas y último movimiento; no obtiene correos, IP, IDs de cuentas ni permisos de edición.
- Se pueden desactivar los enlaces. Solo admin/manager pueden eliminar una sucursal: desactiva los enlaces asociados de todas las cuentas y conserva todos sus palets, sin sucursal. La confirmación explica ese alcance.
- Un código de palet reutilizado no incorpora el nuevo palet a una selección anterior.

## Implementación

Migración 16 en `includes/database.php`: `stock_branches`, columna nullable `pallets.branch_id`, `stock_shares` y `stock_share_pallets`, con índices. Es aditiva; los palets anteriores quedan sin sucursal.

`includes/stock-branches.php` valida propietario del palet, campos, revisión de formularios y permisos por rango. Los duplicados de nombre/dirección/localidad/provincia se comparan sin distinguir mayúsculas, acentos habituales ni espacios repetidos; crear un duplicado reutiliza el registro existente, sin cambiar sus datos. El mismo nombre con otra dirección/localidad/provincia puede coexistir. `api/sucursales.php` usa sesión, CSRF, operaciones idempotentes separadas de Stock y el mismo lock transaccional de palets. Todos los cambios se confirman juntos o se revierten. No utiliza APIs externas.

Stock incluye sección plegable Sucursales, búsqueda, agregar/editar/eliminar, filtro de mis palets por sucursal o sin sucursal, selección para compartir y lista de enlaces activos. La tuerca de cada palet propio ofrece Sucursal del palet; Usar palet abre la misma selección opcional antes del primer producto. Crear una nueva despliega el formulario dentro del mismo diálogo, sin apilar otro editor. Preparar QR y asignación se confirman juntos, conservando reserva, conflictos y reubicación. El palet muestra ubicación y descripción de sucursal; su descripción propia sigue separada.

`stock-compartido.php` y GET `api/sucursales.php?token=…` muestran el stock actualizado, con 20 palets por carga y botón Ver más. El token se genera con 32 bytes aleatorios. No hay caché offline nueva de sucursales, tokens ni vistas públicas; administrar sucursales requiere conexión. La navegación AJAX se integra sin listeners de páginas anteriores. CSS e iconos siguen el diseño existente y la preferencia de mano solo en móvil con entrada táctil.

## Validación y cómo probar

- `php tests/stock-branches.php`: migración idempotente, directorio común entre cuentas, aislamiento del stock, duplicados normalizados, permisos admin/manager y propietario del palet, revisión obsoleta, sucursal dinámica, selección fija, descripciones, eliminación, paginación y reutilización del QR. Disponible para SQLite en memoria o PostgreSQL con `REPOTOOLS_TEST_DATABASE_URL`; datos de prueba dentro de una transacción con rollback.
- `node tests/stock-branches-ui.cjs`: Chrome real, crear/asignar/filtrar/compartir, público sin cuenta, CSRF, reintentos idempotentes, desactivar enlace, navegación AJAX, móvil zurdo y PC, geometría sin desbordamiento a 320/390/1366.
- Regresión `tests/stock.php`, `tests/stock-api.cjs` y `tests/stock-notifications.php`.
- PostgreSQL se valida en la rama de pruebas existente, nunca con cuentas de producción. Solo la migración aditiva permanece en esa rama; los datos de prueba se revierten.

Prueba manual: Stock → Sucursales → +; abrir un palet con productos → tuerca → Sucursal del palet. Volver a Mis palets y filtrar por sucursal. Compartir sucursal con el icono de compartir o Compartir selección para elegir palets; abrir el enlace en incógnito. Agregar otro palet a esa sucursal y actualizar el enlace para comprobar que aparece. Desactivar el enlace desde Enlaces compartidos.

## Continuidad y reversión

Revertir el commit `Add private stock branches and revocable public sharing` revierte interfaz/API y consultas; mantener las tablas y la columna de migración 16 para conservar datos y facilitar retomar. No borrar ni revertir filas de producción. Se puede restaurar el código sin quitar el esquema aditivo.

Pendiente: prueba del usuario en iPhone/Android y confirmar despliegue de Render. La selección mediante mapa es una etapa posterior; primero decidir proveedor y condiciones de costos/almacenamiento. No añadir Google Places ni facturación sin ese acuerdo. No hay integración de supervisores, empresas anexas ni nueva suscripción en esta entrega. Esos proyectos siguen pendientes.

Al incorporar nuevas acciones al registro unificado, considerar auditoría específica de sucursales y enlaces: el estado ya se guarda, pero esta entrega no crea una nueva categoría de eventos para sus cambios. Mantener la restricción anterior de notas y permisos de moderación.

Aclaración del 11/10: la columna stock_branches.owner_id conserva al creador como metadato interno; ya no restringe el directorio ni los permisos de edición. No requiere otra migración. Se preservan registros anteriores; no se fusionaron ni borraron posibles duplicados históricos de distintas cuentas. Los nuevos duplicados se reutilizan y los históricos pueden ser revisados por admin/manager.


## Stock: retirar bloques del inicio — 11/10/2026

El usuario confirmó quitar solo los bloques Sucursales y Enlaces compartidos del inicio de Stock. Permanecen elegir/crear sucursal dentro del palet, filtro y Compartir selección. No se eliminan datos ni se revocan enlaces existentes. Los avisos de sucursales quedan fuera del panel oculto para seguir visibles. Se actualiza la prueba UI para comprobar que ambos paneles permanecen ocultos después de compartir y navegar por AJAX; mantiene alta inline y asignación desde el palet. Para revertir, restaurar interfaz/JS y versión del recurso del commit de este ajuste, sin cambios de esquema.
