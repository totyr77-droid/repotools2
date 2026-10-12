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


## Favoritos y sucursales recientes — 12/10/2026

Selector con Todas/Favoritos/Recientes y estrella independiente de la selección. Sin sucursal usa ubicación tachada. Favoritos por cuenta, guardados en DB, disponibles entre dispositivos. Recientes muestra hasta8 sucursales ordenadas por último uso, con frecuencia como desempate; se registra solo al guardar asignación o preparar palet con sucursal, no al tocar resultados. Todas prioriza favoritos y último uso. Quitar favorito conserva recientes. No otorga permisos ni cambia directorio común. Migración18 aditiva user_branch_preferences con clave user_id/branch_id y borrado en cascada; endpoint autenticado/CSRF/idempotencia, usuario tomado de sesión. Sin cambios manuales a producción.

Validado PHP sintaxis y tests/stock-branches.php en SQLite (migración repetible, aislamiento, favorito explícito repetido, validación, reciente al asignar y conservación al quitar estrella); Chrome tests/stock-branches-ui.cjs (estrella persistente al reabrir, filtros, vacío, asignación, AJAX y tamaños320/390/1366). PostgreSQL aislado pendiente por credenciales de prueba previas rechazadas; no afirmar validación Neon. Probar físicamente en móvil, confirmar Render y favoritos en otro dispositivo. Revertir el commit de favoritos conservando tabla18 y datos.


## Moderación: Sucursales — 12/10/2026

Nueva pestaña solo admin/manager junto a Reportes/Registros/Usuarios, filas con separadores, acciones SVG44px y búsqueda por nombre/dirección/localidad/provincia.20 resultados por página, navegación anterior/siguiente; conserva view=branches al recargar y cancela respuestas al cambiar sección. Formulario modal de alta/edición con descripción y mapa vectorial para corregir coordenadas o quitarlas. Coincidencias por nombre al escribir muestran dirección y botón Revisar con confirmación antes de descartar formulario; exactos reutilizan registro mediante reglas existentes. Mismo nombre con otra dirección permitido. Eliminar usa RepoConfirm, conserva palets sin sucursal y desactiva enlaces comunes; permisos de servidor frescos.

Migración19 aditiva branch_events, actor/acción/nombre y valores antes/después, guarda alta/edición/borrado junto con operación; no crea evento al reutilizar duplicado. Registros unificados incluyen categoría Gestión de sucursales, iconos y etiquetas de acción. No publica datos del creador en directorio; gestión GET autenticada restringida a admin/manager. Favoritos/recientes y permisos de palets conservados.

Validación: tests/stock-branches.php SQLite (migración repetible, permisos, filtro, paginación20, auditoría), tests/reports.php y sintaxis PHP/JS. tests/stock-branches-ui.cjs Chrome: pestaña conservada tras recarga, editar desde moderación, punto en mapa, sugerencias similares, registro filtrado, rechazo usuario normal, regresiones de Stock y geometría320/390/1366; proveedor simulado sin tráfico externo. Capturas móvil revisadas; ajuste extra a pestañas320 para evitar texto pegado. PostgreSQL aislado sigue pendiente por credenciales previas rechazadas; no validado ni alterado manualmente. Falta prueba física y confirmar Render. Revertir commit de panel, conservando tablas18/19 y datos.


## Compartir Stock desde barra de escaneo — 12/10/2026

Barra Escanear palet / teclado / compartir / ojo enlaces compartidos. Compartir visible con al menos un palet propio activado; pendientes e invitados no se seleccionan. Ojo visible también si quedan enlaces activos aunque no haya palets, para poder desactivarlos. No restaura bloques ocultos del inicio. Modal Palets/Sucursal, búsqueda código/descripción/sucursal, selección persistente al filtrar, contador, Seleccionar visibles y Limpiar; inicialmente sin selección, crear deshabilitado hasta elegir, máximo200 por selección. Sucursal ofrece solo locales con palets propios activados, explica actuales y futuros; servidor rechaza compartir sucursal sin stock propio. Preferencia de mano móvil conserva escanear primero, sin invertir SVG.

Modal Enlaces compartidos: nombre/tipo/palets disponibles actuales, abrir en nueva pestaña, compartir y desactivar con RepoConfirm. Copiar muestra aviso dentro del modal; errores también. GET incluye contador de palets por enlace sin datos privados. No migración nueva, enlaces anteriores siguen funcionando. No publica QR pendientes ni palets ajenos. Vista pública y permiso de solo lectura existentes se conservan.

Validación: sintaxis PHP/JS, tests/stock-branches.php SQLite (reglas previas, sucursal sin stock rechazada), tests/stock-branches-ui.cjs Chrome (selección sin perder al filtrar, seleccionar visibles/limpiar, compartir1, vista pública sin cuenta, sucursal, listar enlaces, desactivar mediante modal devuelve404, regresiones AJAX/permisos/mapa y tamaños320/390/1366). Captura móvil del selector revisada; estilos afinados para pestaña activa y barra compacta. Falta prueba física y confirmar Render. Revertir commit de compartir conserva DB/enlaces y devuelve controles anteriores.


Aclaración enlaces compartidos12/10: botón del ojo visible únicamente con al menos1 enlace activo propio. No basta tener palets cargados. Al crear el primero aparece y al desactivar el último se oculta; el modal abierto conserva aviso de éxito. Compartir mantiene condición de palet activado. Prueba Chrome comprueba estados antes/después de crear/desactivar. Revertir este ajuste JS para visibilidad anterior; sin DB nueva ni cambios a enlaces.


## Sucursales compartidas con exclusiones — 12/10/2026

En compartir por sucursal, despliegue Ver palets incluidos con contador, chevron giratorio y casillas inicialmente marcadas. Desmarcar excluye del enlace sin convertirlo en lista fija: futuros palets propios activados de esa sucursal se incorporan automáticamente. Puede excluir todos los actuales; próximos siguen incluidos. En Enlaces compartidos, lápiz Elegir palets incluidos permite cambiar exclusiones manteniendo token/URL; sucursal y título bloqueados al editar. Excluidos guardados por ID interno del palet, no código reutilizable.

Migración20 aditiva stock_share_exclusions, claves share/pallet y cascada. API autenticada/CSRF/idempotencia valida propiedad del enlace activo y de cada ID, límite200 exclusiones; el público y contador aplican NOT EXISTS antes de paginar. Cuentas ajenas no pueden editar ni excluir palets ajenos. Enlaces anteriores sin exclusiones mantienen comportamiento. Valores excluidos se conservan por enlace y no modifican palets ni permisos.

Validado sintaxisPHP/JS, tests/stock-branches.php SQLite: ocultar excluido, incorporar nuevo, restaurar en mismaURL y rechazo de cuenta/ID ajenos; tests/stock-branches-ui.cjs Chrome: despliegue/desmarcado, público vacío, lápiz/estado persistido y restauración sin cambiarURL, regresiones previas. PostgreSQL aislado pendiente por credenciales previas; no cambio manual producción. Falta prueba física y despliegue Render. Reversión segura: restaurar solo UI manteniendo filtro de exclusiones en público/API. Revertir todo el backend haría reaparecer excluidos: antes habría que desactivar enlaces de sucursal con exclusiones. Conservar tabla20 y datos.
