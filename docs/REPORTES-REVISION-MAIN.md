# Reportes y nombres importados — main

La revisión en PC usa comparación y edición en dos columnas desde1024px, con acciones accesibles y desplazamiento independiente. Móvil conserva disposición vertical y preferencia de mano.

Un reporte aprobado permite al moderador abrir «Editar ficha actual» con el editor existente: modifica el catálogo actual, conserva el historial del reporte y no vuelve a otorgar recompensas. Permisos, CSRF y revisión optimista siguen validados por la API de productos.

Importaciones Excel sobre productos con nombre existente: opción «Cambiar el nombre actual por el del Excel», desmarcada por defecto. El campo conserva el nombre existente hasta marcarla. El servidor conserva ese nombre si falta confirmación explícita, aun ante un POST directo; otros datos se revisan normalmente. Productos nuevos mantienen la carga habitual. La propuesta original queda visible y no se modifica.

El control de cambios pendientes ignora formularios internos del lector EAN y campos de otros diálogos. No son borradores de edición del producto; se conserva la confirmación para formularios reales modificados.

Validación: reports-api con dos aprobaciones Excel (sin/con confirmación), cambios secundarios y permisos; report-approved-edit-ui en Chromium/WebKit con guardado real, historial/recompensas sin cambios, usuario común rechazado por servidor y selección del nombre; report-review-desktop-ui en ambos motores a1024/1366/1920 y ambas manos, acciones visibles y móvil vertical; dialog-dirty-ui Chromium, cierre X/fondo/Escape, seguir/salir/guardar y formulario del lector sin falso aviso. No equivale a prueba física de cámara/Safari. La antigua prueba dialog-edit-scroll-ui pasó WebKit, pero Chromium tuvo timeout de revelado con teclado simulado; no se declara aprobada.

Reversión: revertir el commit de esta entrega y publicar una versión de caché superior. Sin migración; las ediciones de catálogo hechas por personas no se revierten con el código. No mezclar funciones comerciales de beta.
