# Inventario de Mi equipo — beta v1.63

Mi equipo reúne Palets, Mercadería, Movimientos y Cuentas para administrador y encargado. Palets y movimientos consultan toda la sucursal con filtros en servidor y páginas de 30 resultados. Mercadería agrupa EAN y vencimiento, suma unidades y permite desplegar los palets con su presentación en cajas/bultos. Los filtros aplicados se muestran y se pueden limpiar.

Movimientos separa mercadería y administración. Los nuevos cambios de rango y seguridad guardan datos anteriores y posteriores; los registros históricos conservan únicamente lo registrado. Migración 39 aditiva: metadata en branch_team_events. No guarda tokens, correos ni coordenadas personales en esta metadata. Los permisos y vencimientos se verifican en servidor; operadores no acceden al inventario administrativo. Las etiquetas personales respetan la autorización existente.

La sucursal del encabezado usa una etiqueta compacta con icono de sucursal, sin casita. Una sola queda fija; varias permiten cambiar. Conserva ambas preferencias de mano y la sección seleccionada al recargar.

Validación: tests/team-inventory.php y tests/team-inventory-ui.cjs aprobados, incluyendo más de 30 palets, totales, lotes, búsquedas históricas, filtros, permisos, tamaños 320/390/1366 y ambas manos. Integración PostgreSQL y pruebas físicas Safari/cámara/GPS pendientes. No se usaron datos reales ni se modificó main.

Reversión: restaurar código de v1.62 y publicar una versión superior. Conservar la columna metadata y sus registros; no deshacer cambios de permisos ni operaciones confirmadas mediante borrado de datos.

## Mi equipo — beta v1.68
Rol junto al título, sucursal separada como etiqueta. Mercadería usa iconos del sistema para EAN, palets, cantidad y fecha; vencimientos en etiquetas con estado/color existentes. Cada lote conserva la apertura del resumen local y agrega Ver en Stock con el código correspondiente.

Buscadores con cámara integrada y lupa que pasa a X para borrar solo la consulta. Se reutiliza RepoEANScanner con modos Producto (EAN) y Palet (QR), controles y guía existentes; el lector de búsqueda no asigna sucursal ni traslada inventario ni acepta invitaciones. Cierre/ingreso manual/navegación liberan cámara; ingreso manual vuelve al campo. Cámara física/Safari pendientes; prueba de integración de controles con lector simulado y motor compartido EAN aprobado.

Filtros: input/change/focusout, Enter y cierre del desplegable aplican valores; tocar fuera lo cierra. Se elimina el guard que solo atendía SELECT. Claves de consulta evitan repeticiones y versiones invalidan respuestas anteriores incluso durante el debounce. Etiquetas de texto discretas y Limpiar filtros en la misma fila; X conserva los demás filtros. Campos sin resultados mantienen estado y opciones de limpieza.

Validación: team-inventory PHP/UI ampliado con EAN/QR, X, ingreso manual, fecha cambiada al perder foco, cierre exterior, respuesta antigua demorada, enlaces por palet, etiquetas y rol; paginación, agrupación, administración, permisos, URL y 320/390/1366 ambas manos. branch-team-ui completo, EAN, SW y sintaxis aprobados; capturas de Mercadería y filtros revisadas. Sin migraciones ni datos reales. Reversión: vistas, módulos, iconos y estilos a v1.67 con versión superior; conservar inventario. Solo beta.
`nQR de Inicio: proporción cuadrada en una mano, contraste invertido conservando paleta y separación4px. Validado tests/home-scan-pair-ui.cjs a320/390/531/1366 y ambasmanos. Reversión: restaurar reglas añadidas al final de branch-team.css y prueba al padre; publicar versión superior.

## Beta 1.69 — revisión de controles y movimientos

QR de Inicio mantiene su proporción cuadrada, con fondo más claro y borde. Filtros de Palets/Mercadería incorporan orden por vencimiento cercano o lejano en servidor y paginación, fechas ausentes al final. Las nuevas aceptaciones guardan al invitador como actor y al receptor como destino, con rango; no se reescriben eventos históricos. Detalles de movimientos muestran comparación para alta/baja y unidades antes/después, además del formato old/new existente.

Miembros activos del equipo pueden retirar únicamente la sucursal actual del palet, tras confirmar posible pérdida de acceso. Servidor verifica vínculo y revisión; no permite asignar otra sucursal ni actuar a ajenos. Herramientas de palet ocupan bordes opuestos según mano. Acceso lateral a Mi equipo exclusivo para administradores/encargados activos, fuera de Inicio y Mi equipo, arrastrable verticalmente sin activar navegación; posición local por usuario.

Validación: tests/team-refinements.php (orden, permisos, pérdida de acceso e invitador), tests/team-inventory-ui.cjs (modales y visibilidad), tests/branch-team-ui.cjs, tests/team-shortcut-ui.cjs (bordes, ambas manos, arrastre), tests/home-scan-pair-ui.cjs, SW y sintaxis. Bases aisladas; sin escritura real ni migración. Safari/cámara física y revisión del usuario pendientes. Main intacta.

Reversión: revertir el commit de v1.69 y publicar un número superior; no borrar eventos ni modificar datos. Restaurar permiso previo de asignación y retirar vista/CSS/JS de lengüeta junto con sus referencias y caché.

Beta1.70: Mercadería muestra chevron con estado abierto/cerrado y marca lateral tenue para identificar producto desplegado. LengüetaMiEquipo usa etiquetaempresa y admite ambosbordes porarrastre/teclado con persistencialocal porusuario; reglasdevisibilidad/permisos de1.69 intactas. Ver validación/reversión en PERFIL-INSIGNIAS-BETA.md.

## Beta 1.71 — detalle local y etiquetas de propietario

Los accesos Ver en Stock de Palets y Mercadería abren el editor existente como modal dentro de Mi equipo. No cambian URL/sección/filtros ni consumen el estado de Stock personal. Al pasar desde el resumen, se cierra el resumen para evitar dos pasos al regresar. El editor comprueba que el palet siga editable y en la sucursal activa; el polling de equipo espera mientras el editor esté abierto. Sucursal y etiqueta aparecen en el encabezado del detalle y como badges en el resumen.

Usuario confirmó compartir la etiqueta del propietario con encargados/administradores y usarla como filtro. Solo esa etiqueta se incorpora al listado y a las opciones de Palets/Mercadería de la sucursal; las etiquetas personales de terceros no se comparten. El filtro se aplica en servidor antes de paginar/agrupar y pasa también al desglose de lotes, manteniendo los totales coherentes. La edición de etiquetas sigue siendo personal; se actualizó el texto explicativo. Se acotó el render de etiquetas de Stock a su propio contenedor para que no borre las de Mi equipo.

Se aplica aclaración de rangos del usuario: retirar sucursal desde equipo solo para administrador/encargado; operador sin control ni autorización en servidor. Propietario mantiene su permiso habitual. Retirar sucursal puede quitar acceso del equipo y conserva confirmación existente.

Validación: tests/team-pallet-labels.php (propietario frente a etiquetas ajenas, filtros/opciones/totales/lotes, límites por rango y público), team-refinements.php, team-inventory.php, stock-tags.php; team-inventory-ui.cjs comprueba apertura local desde Palets/Mercadería, sucursal/etiqueta visibles, cierre con filtros/grupo abierto conservados y persistencia de etiquetas; branch-team-ui.cjs verifica ambos botones locales; stock-tags-ui.cjs conserva gestión personal. SW, PHP/JS y revisión visual aprobados. Datos aislados, sin migración ni cambios en main; Safari físico pendiente.

Reversión: revertir entrega1.71 sobre6cf1dcc y publicar número superior. No borrar etiquetas. Conservar la restricción de retirada para admin/encargado si se revierte únicamente el diseño; revertir a1.69/70 completo volvería a habilitar operadores y requiere conservar ese guard de servidor.
