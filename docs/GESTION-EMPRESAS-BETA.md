# Gestión de empresas para managers — beta

Primera entrega del panel administrativo de Repotools. Acceso desde **Asignar empresa**, junto a TOPs, y desde **Ajustes → Empresas y sucursales**. El acceso continúa visible para el manager aunque también tenga un equipo propio. `Mi equipo` conserva los permisos de sucursal existentes.

## Comportamiento

- `gestion-empresas.php` y `api/gestion-empresas.php` requieren el rango global `manager`, consultado en el servidor. El administrador de una empresa y el administrador general de catálogo no reciben este permiso.
- Lista paginada de 20 empresas, búsqueda por empresa, nombre público/correo del responsable, sucursal o localidad. La lista no devuelve correos; se muestra el del responsable solo al consultar el detalle autorizado.
- Detalle paginado de 30 sucursales, con dirección y conteos de personas autorizadas y palets. No descarga productos ni otorga al manager acceso de trabajo sobre palets ajenos.
- Permite corregir nombre de empresa y datos de su sucursal. Reutiliza validación, revisión, coordenadas y auditoría del directorio. Rechaza una sucursal que ya no pertenezca a la empresa seleccionada y modificaciones basadas en datos desactualizados.
- Nueva empresa y añadir sucursal usan la API de asignación existente, la cuenta habitual del responsable y un formulario compartido con `Mi equipo`. Al guardar abre la empresa resultante. No se modifican rangos ni responsable en una corrección de nombre o dirección.
- Listado y detalle descartan respuestas tardías; errores de guardado conservan los campos. Los cambios no se almacenan en localStorage ni en una cola offline. La gestión necesita conexión.
- Interfaz móvil primero, acciones secundarias en texto/engranaje, formularios desplegables, SVG existentes y adaptación de acciones según mano. Escritorio muestra lista y detalle en columnas.

## Permisos y datos

POST requiere sesión, CSRF, rango vigente y transacción con bloqueo de escritura Stock. Nombre de empresa guarda auditoría en `branch_events`; sucursal usa `branch_save`. Sin migraciones. No se ejecutaron cambios sobre empresas reales para probar.

En esta revisión se detectó el logo horizontal repetido en la lista de instalación del service worker, que hacía fallar `cache.addAll`. Se retiró la entrada duplicada. La prueba de SW ahora ejecuta instalación y verifica claves únicas y archivos presentes; la prueba UI vuelve a preparar offline sin el aviso de error. Se cachean solo archivos estáticos del panel; la API envía `Cache-Control: no-store`.

## Validación

- `tests/branch-manager.php`: rango vigente, denegación de administrador/usuario, paginación de empresas/sucursales, búsqueda literal, conflicto, auditoría y permisos empresariales intactos.
- `tests/branch-manager-ui.cjs`: acceso desde Inicio, página/API restringidas, CSRF, entradas malformadas, 320/390/1366 px y ambas manos, asignación/alta/edición, borrador tras fallo y respuesta tardía de otra empresa.
- Regresiones `branch-team.php`, `branch-team-ui.cjs`, `stock-branches.php`, `stock-watch.php`, `service-worker-offline.cjs` y sintaxis PHP/JS.
- Inspección visual local móvil y escritorio; capturas bajo `tmp/branch-manager-*`, sin datos reales. Safari físico e integración PostgreSQL siguen pendientes.

## Próximos pasos para acordar

1. Probar el panel con los managers y ajustar nombres, orden y búsquedas según uso.
2. Definir el procedimiento para cambiar responsable de una empresa: qué accesos conserva o pierde y cómo se confirma y registra.
3. Definir baja/archivo de empresa y gestión administrativa de vínculos. La eliminación de una sucursal por su administrador sigue funcionando en `Mi equipo`; no se añadió borrado global desde este panel.
4. Mejorar selección del directorio libre si supera los 200 registros que entrega el selector existente.
5. Marcas y comparación Excel permanecen para una entrega posterior.

## Reversión

Restaurar al padre de esta entrega los enlaces en Inicio/Ajustes, los includes compartidos, header/footer y respuesta `workspace_id` de asignación; retirar la nueva vista, API, helper, JS y CSS. Publicar una versión superior para renovar caché. Mantener la corrección de entrada duplicada del SW. No revertir inventario, cuentas o vínculos: no hubo migración. Los nombres/datos guardados durante el uso real quedan persistidos y cualquier corrección se realiza por una nueva operación auditada.
