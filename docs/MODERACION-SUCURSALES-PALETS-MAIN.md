# Moderación: palets vinculados a sucursales — main3.23

En la pestaña Sucursales de Moderación (reportes.php?queue=1&view=branches), cada fila incorpora Ver palets con icono ojo del sistema existente. Abre diálogo de consulta con nombre de sucursal, código/referencia/propietario/cantidad de productos. Cada fila enlaza a Stock por código. Carga30 por cursor, Ver más, vacío/error/reintento local; cierre devuelve foco, solicitudes tardías ignoradas tras cambiar/cerrar/salir. Sin activar equipo ni cambiar sucursal personal para consultar.

GET api/sucursales.php?manage=1&view=pallets&id=... exige autenticación, rol administrador/manager y visibilidad de sucursal en servidor. Lista solo p.branch_id seleccionado y released=0, conserva alcance del contador existente. Código/datos de salida limitados, nombres sin email. Abrir Stock sigue permisos de stock_detail existentes. No añade permisos a operadores/moderadores, no cambia mercadería ni estructura.

Validación: stock-branches PHP aprobado; moderation-branch-pallets-ui Chromium/WebKit comprueba navegación desde pestaña correcta, enlaces, paginación30+1, exclusión liberados, vacío,404/422 y401/403 para invitado/usuario, sin overflow320/390/1366. Captura móvil revisada. El prototipo inicial en Empresas y sucursales se retiró antes de commit: no forma parte del cambio final.

Reversión: revertir commit/publicar caché superior. Sin migración/datos que revertir. PENDIENTES.md/ROADMAP.md locales del usuario conservados fuera de commit. Solo main; beta no modificada.
