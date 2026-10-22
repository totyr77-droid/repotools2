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
