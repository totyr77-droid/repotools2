# Corrección detectada en auditoría

El loader de `load()` en Stock se cerraba dentro del callback de una invitación, en lugar del `finally` de la consulta inicial. Sin invitaciones el skeleton podía tapar los palets indefinidamente. Ahora se cierra al terminar o fallar la consulta, sin cambiar las invitaciones ni permisos.

Archivos: js/stock.js, versión en vistas/footer.vista.php, prueba stock-export-ui.cjs. Validación: la tarjeta del palet aparece, el resumen y las descargas funcionan en360/390/430/1366, sin errores JS. Revertir este commit para volver al comportamiento anterior; no hay cambios de datos.
