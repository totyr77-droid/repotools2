# Registros de Moderación

Desde v2.37, Registros separa Usuarios, Mercadería, Moderación y Gestión. La categoría se calcula para presentar los eventos existentes; no reescribe la auditoría.

- Usuarios: altas, verificación, perfil propio, seguridad de la cuenta y sesiones.
- Mercadería: operaciones habituales sobre palets, cargas, retiros, bajas y transferencias.
- Moderación: decisiones sobre reportes, catálogo, rangos, cambios de cuentas ajenas, intervención del personal sobre un palet ajeno y resultados denegados/en conflicto. Acciones históricas de pallet_admin_events también se presentan aquí.
- Gestión: sucursales, invitaciones, accesos y enlaces compartidos.

Cada fila abre un modal; no hay despliegue de registros en la página. Muestra persona/rango/fecha, sucursal, productos afectados, cantidades antes/después, diferencia y vencimiento cuando quedaron registrados. Transferencias nuevas incluyen origen/destino o destinatario y unidades en el mismo evento. Para eventos anteriores sin destino guardado se informa esa ausencia. Datos técnicos permanecen dentro de un desplegable opcional del modal, solo para los rangos ya habilitados.

La agrupación usa actor + identidad de operación + resultado + sección, antes de paginar. Las bajas múltiples nuevas guardan transaction_id en la instantánea de auditoría; bajas antiguas con UUID:palet se reconocen conservadoramente. No se agrupa por proximidad horaria ni se inventan asociaciones para eventos antiguos sin identidad común. El filtro selecciona una operación completa si cualquiera de sus eventos coincide. El detalle vuelve a validar permisos sobre todos sus miembros. Máximo 200 eventos por detalle, error explícito si lo supera; operaciones actuales de baja múltiple admiten hasta 100 palets.

Los datos completos se consultan al abrir el modal, no al listar. Se conserva API anterior sin grouped=1. API nueva: view=logs&grouped=1, section=users|goods|moderation|management, event para detalle; filtros y paginación existentes. Opciones de acciones/personas/sucursales se calculan bajo el ámbito permitido; la sucursal de pruebas sigue oculta en las etiquetas/opciones para rangos no Manager.

Inicios y cierres explícitos de sesión se registran desde esta entrega, sin reconstruir sesiones anteriores ni registrar cada recarga. No se guardan cookies, tokens, identificadores de sesión, IP ni credenciales. Fallos del registro de sesión no impiden entrar/salir. No suma actividad al ranking. Mantiene CSRF de salida e invitaciones/permisos de Stock.

Sin migración. Eventos inmutables existentes y ranking conservados. Revertir el commit de entrega y publicar versión/cache superior; no borrar audit_events. Código anterior ignora los metadatos nuevos y conserva su lectura individual.

Validación: tests/audit-records.php (ámbito, jerarquía, agrupación antes de paginar, filtro mantiene grupo completo, deltas, sucursal de prueba, sesiones); tests/audit-records-ui.cjs (API real, modal exclusivo, scroll conservado, filtros, 320/390/1366, ambas manos/modos, Chromium/WebKit); regresiones unified-core, Stock API, stock-transfer, account-devices, logout, user-profile-edit-ui y unified-presentation. Consulta PostgreSQL real generada con tests/audit-records-pg-query.php y ejecutada como CTE de datos sintéticos, solo SELECT en rama de prueba; no consulta ni modifica datos reales.


## Staff y presentación v2.42

- Moderador: catálogo/reportes y sus propios registros. No administra rangos, perfiles ajenos ni bloqueos de cuentas.
- Administrador: sus registros y los de moderadores; no registros globales de usuarios/sesiones, acciones de otros administradores/managers ni perfiles de esos rangos (salvo su propio perfil). Puede gestionar cuentas de rango menor según las acciones existentes, pero no asignar rangos.
- Manager: alcance global y único que asigna rangos. Se conserva la protección del último manager.

Los permisos se validan con el rol actual de DB en ambas variantes del API de auditoría, filtros/opciones, paginación y detalle. El ámbito de administrador usa el rango histórico del actor y su rol actual: ascender a alguien cierra también el acceso de administradores a su historial. Usuarios/sesiones quedan solo para manager. El historial de reportes resueltos y el de cambios en perfiles no ofrecen una vía alternativa para consultar actuaciones de managers/otros administradores. Se retiró la consulta legacy de logs que era inalcanzable tras el lector unificado.

Las opciones de rango en perfiles y escrituras siguen la misma regla de manager exclusivo. API de directorio y ficha limita perfiles superiores. Las funciones de empresa/sucursal y sus permisos independientes se conservan.

Registros tienen iconos SVG existentes por acción y tonos discretos: carga/aprobación verde, baja/eliminación rojo, retiro/advertencia ámbar e información/transferencia azul. Antes/Después se muestran en dos columnas compactas; diferencias de cantidades, referencias y fechas siguen disponibles. Palet y EAN se identifican, y el modal ofrece Ver palet cuando el destino sigue activo y se puede acceder; actor/destinatario enlazan a perfiles solo bajo sus permisos. Palets eliminados e IDs históricos que ya no corresponden a la etiqueta actual no ofrecen un enlace falso. No se concede acceso por seguir enlaces: las páginas/API de destino vuelven a validar permisos. El detalle siempre es modal y conserva scroll, ambas manos y modo de una mano.

Los filtros se acomodan hacia arriba o abajo según el espacio disponible para no quedar detrás del menú inferior. No se muestran datos crudos en la lista; los datos técnicos existentes siguen dentro del modal con su permiso previo.

Validación: staff-access.php (lista/agrupación/filtros/detalle directo, roles actuales/históricos, perfiles protegidos, bloqueos, promoción y navegación); audit-records.php, user-profiles.php, reports.php, unified-core/corrections-three-core; API de perfiles y reports-access; UI Chromium/WebKit320/390/1366, ambas manos/modos, comparaciones, vínculos, jerarquía y modal sin mover la página. Capturas móviles revisadas. El fixture ahora espera el fin de la animación para medir44px y acepta el prefijo de ruta del redirect de login. Sintaxis PHP/JS y diff comprobados. Pruebas aisladas con SQLite/API real; no cambian cuentas productivas ni sustituyen una prueba física.

Reversión: revertir esta entrega y publicar versión/cache superior. Sin migración ni cambios de roles reales, auditorías, stock, preferencias o sesiones. Revertir permisos volvería a habilitar los alcances anteriores; evaluar esa consecuencia antes de hacerlo.

Historial anterior sin rango exacto: las fichas de cuentas muestran al administrador solo sus propias intervenciones de rango/bloqueo/perfil; no se deduce una identidad histórica por fecha. Resueltos permite decisiones propias o de un moderador actual con evento de auditoría de revisión de rango1 asociado por report:id; sin evidencia histórica suficiente queda para manager. Se probó expresamente que degradar un manager no exponga sus decisiones anteriores.
