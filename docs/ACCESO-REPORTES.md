# Acceso a reportes — 13/10/2026

Problema: reportes.php incluía el encabezado antes de exigir sesión. queue=1 sin permiso se interpretaba como vista propia, renderizando la estructura de revisión, aunque las operaciones de moderación ya tenían validaciones en la API. Además, la API permitía consultar reportes de invitados con un token de propietario. Se cambia esa política de consulta: Mis reportes requiere cuenta.

Ahora la página valida antes de renderizar: sin sesión redirige al login existente; conserva next=reportes o next=moderacion mediante lista blanca, sin aceptar URLs arbitrarias. Una cuenta sin permiso en queue=1 recibe 403 y no recibe el formulario. Se reutilizan require_account, current_user y can_edit_catalog. Cache-Control:no-store en la página y API.

## Matriz

| Ruta/acción | Sin cuenta | Usuario/supervisor | Moderador | Administrador/manager |
| --- | --- | --- | --- | --- |
| reportes.php | Login | Propios | Propios | Propios |
| reportes.php?queue=1 | Login | 403 | Cola | Cola |
| API GET listado/detalle/foto de reporte | 401 | Solo propietario de sesión; ajeno 404 | Según permisos existentes | Según permisos existentes |
| API GET queue=1 / contador | 401 | 403 | Permitido | Permitido |
| API GET history/logs | 401 | 403 | 403 | Permitido |
| API GET general | 401 | 403 | 403 | Solo manager |
| API POST review | 401 | 403 | Con CSRF; no propio | Con CSRF; administrador no propio, manager sí |
| API POST bloquear/desbloquear | 401 | 403 | Bloquear según jerarquía; no desbloquear | Reglas jerárquicas existentes |
| API GET config=1, único parámetro | Configuración mínima | Configuración mínima | Configuración mínima | Configuración mínima |
| API POST submit | CSRF y reglas de envío; confirmación simple | CSRF y reglas de envío | Igual | Igual |

El config público devuelve únicamente csrf, can_submit, photos_enabled y registered. No devuelve reportes, propuestas, nombres, emails, notas ni IDs. Combinar config con otro parámetro no abre acceso. El envío anónimo sigue permitido por el diseño previo, devuelve solo submitted:true; no permite consultar luego el reporte mediante token. La interfaz de envío usa config=1 y acepta el recibo simple. Los reportes anónimos anteriores se conservan para el staff, no se vinculan automáticamente a nuevas cuentas.

IDOR: report_owner siempre deriva user:ID de la sesión cuando hay cuenta; los IDs/user_id/token enviados no cambian esa identidad. report_detail exige propietario o permiso administrativo. Las fotos pasan por el mismo control. Aprobar/rechazar usan POST, sesión, rol, ID, CSRF y revisión del producto. GET con action de moderación devuelve 405. No se cambiaron decisiones, recompensas ni lógica de productos.

## Auditoría relacionada

Usuarios exige sesión y administrador/manager en servidor, salvo el token del perfil propio; Biblioteca exige sesión y dueño; Stock privado exige sesión y usa ID actual; invitaciones siguen asociadas a la sesión. Cuenta anónima muestra login/registro. Sucursales exige cuenta para funciones privadas/administrativas. Productos públicos se pueden consultar, editar requiere rol. Palets y stock compartido mantienen lectura pública de contenido autorizado como estaba definido; no se consideran una fuga equivalente. No se encontró otra exposición crítica equivalente en estas rutas revisadas. No es una auditoría exhaustiva de toda la aplicación.

Validación: tests/reports-access.cjs pasa redirecciones, 401,403, queue, propios/ajenos/fotos, manipulación user_id, CSRF, rechazo por moderador, aprobación por administrador, GET mutable 405, config limpio, envío anónimo sin datos y APIs privadas relacionadas. tests/reports.php conserva lógica de negocio y privacidad. La prueba antigua reports-api.cjs todavía contiene expectativas de consulta anónima que esta política elimina y un fixture conocido de sesiones PC; no se declara toda esa suite aprobada.

Archivos: reportes.php, api/reportes.php, includes/auth.php (lista blanca de retorno), cuenta.php (destino de retorno), js/product-reports.js (config/recibo), versión de caché del script y prueba nueva. Sin migraciones. Reversión técnica desde 7ba2ba6 en esos archivos; reabriría el acceso anterior, por lo que se recomienda revertir solo un ajuste puntual conservando las comprobaciones de seguridad.
