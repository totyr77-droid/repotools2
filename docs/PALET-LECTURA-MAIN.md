# Main v3.10 — lectura dentro del palet

Pedido: el listado dentro del palet se ve demasiado pequeño en móvil. Ajuste limitado a main: palets con al menos dos filas usan la altura visible menos32px; listado flexible con scroll propio y acciones debajo sin superposición. Nombres1rem y detalle0,9rem. Cabecera/búsqueda mantienen su tamaño y controles táctiles existentes. Palets vacíos/disponibles o de una sola fila conservan altura natural; escritorio y teclado abierto conservan layout previo. No cambios de datos, permisos, planes ni funcionalidades beta.

Validación: pallet-product-space-ui Chromium/WebKit,320/390/1366,600/844px y ambas manos: límites del viewport, espacio útil del listado móvil, scroll y separación de acciones, nombre legible. pallet-keyboard-layout-ui aprobado en ambos motores: producto completo, acciones alcanzables, búsqueda/cancelación y restauración del scroll al cerrar teclado. Captura revisada con productos ficticios; no prueba física iPhone.

Reversión: retirar el bloque final de css/ux-polish.css «En móvil, palets con varios productos…» y publicar caché superior. Sin migraciones ni datos a revertir. PENDIENTES.md/ROADMAP.md locales previos se conservan sin incluirlos en este commit.
