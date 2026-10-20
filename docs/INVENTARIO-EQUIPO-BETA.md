# Inventario de Mi equipo — beta v1.63

Mi equipo reúne Palets, Mercadería, Movimientos y Cuentas para administrador y encargado. Palets y movimientos consultan toda la sucursal con filtros en servidor y páginas de 30 resultados. Mercadería agrupa EAN y vencimiento, suma unidades y permite desplegar los palets con su presentación en cajas/bultos. Los filtros aplicados se muestran y se pueden limpiar.

Movimientos separa mercadería y administración. Los nuevos cambios de rango y seguridad guardan datos anteriores y posteriores; los registros históricos conservan únicamente lo registrado. Migración 39 aditiva: metadata en branch_team_events. No guarda tokens, correos ni coordenadas personales en esta metadata. Los permisos y vencimientos se verifican en servidor; operadores no acceden al inventario administrativo. Las etiquetas personales respetan la autorización existente.

La sucursal del encabezado usa una etiqueta compacta con icono de sucursal, sin casita. Una sola queda fija; varias permiten cambiar. Conserva ambas preferencias de mano y la sección seleccionada al recargar.

Validación: tests/team-inventory.php y tests/team-inventory-ui.cjs aprobados, incluyendo más de 30 palets, totales, lotes, búsquedas históricas, filtros, permisos, tamaños 320/390/1366 y ambas manos. Integración PostgreSQL y pruebas físicas Safari/cámara/GPS pendientes. No se usaron datos reales ni se modificó main.

Reversión: restaurar código de v1.62 y publicar una versión superior. Conservar la columna metadata y sus registros; no deshacer cambios de permisos ni operaciones confirmadas mediante borrado de datos.
