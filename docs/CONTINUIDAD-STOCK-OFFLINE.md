# Continuidad de Stock al perder conexión — v1.76

Se conserva en sessionStorage, por cuenta y pestaña, el texto de búsqueda, sucursal, etiqueta, vencimientos y el formulario de producto abierto (EAN, referencia, fecha, cantidad, presentación, unidades por caja/bulto y clave de operación). La vigencia es de dos horas desde la última actualización; cerrar o guardar el formulario elimina su borrador. El borrador no es una carga ni se encola/envía por recuperarlo. Cerrar sesión o cambiar de cuenta limpia el estado correspondiente. No se guardan credenciales ni CSRF.

Stock usa la copia preparada si la red falla o el dispositivo está offline, con los mismos filtros y proyección de movimientos pendientes. Distingue resultados locales de servidor y advierte datos incompletos; un resultado ausente en la copia no se presenta como ausencia global. Al cambiar la búsqueda se retiran los resultados anteriores y una respuesta tardía no puede reemplazar la consulta actual. Al recuperar conectividad se vuelve a consultar sin vaciar la búsqueda.

La página offline incorpora búsqueda y filtros compactos con despliegue progresivo y posición según mano. También recupera formularios de cargas nuevas cuando existe una copia editable del palet. Editar un producto existente requiere conexión: el borrador se conserva para volver a Stock. Al volver online se reconsulta el palet, se comprueban código/permiso y, para edición, producto/EAN/revisión; si cambió se conserva el borrador para revisión, sin sobreescribir automáticamente datos nuevos. Se conserva la clave de operación al recuperar una carga. Las validaciones y la idempotencia en servidor no cambiaron.

Las etiquetas personales se incluyen ahora en la copia privada de Stock y en su preparación. Si falta un detalle o metadato local, se conserva el filtro elegido y se limita la consulta a lo disponible, sin afirmar que está completo. No se añaden migraciones ni se modifica inventario o colas existentes.

## Validación

- stock-session-transition.cjs: Service Worker real, búsqueda local al cortar red, consultas sucesivas sin resultados viejos, recarga offline y retorno online con EAN/sucursal/etiqueta/vencimientos y formulario completos, cambio offline del borrador, vacío/cancelación autoritativos, guardar una vez y recargar sin duplicar, aislamiento de cuenta, 320/390/1366 y ambas manos.
- stock-session-storage.cjs: caducidad dos horas, limpieza, valores vacíos y almacenamiento no disponible.
- stock-offline-ui.cjs: cargas offline sincronizadas exactamente una vez, recarga, revisiones aditivas, idempotencia y separación de cuentas.
- offline-start.cjs: arranque offline desde cero, búsqueda, Biblioteca, carga 8×5 y privacidad.
- ean-list-offline-transition.cjs, offline-recovery.cjs, stock-search-summary.cjs y service-worker-offline.cjs aprobados.
- Sintaxis JS y PHP de footer aprobada. Inspección móvil en tmp/stock-session-offline-390.png.

Se actualizaron fixtures antiguos: mock de cámara incluye observe, se cierra el teclado opcional de fecha antes de usar cantidad, la fecha readonly se opera por su teclado, Listo cierra la calculadora y Biblioteca usa Agregar manualmente. No se alteró el comportamiento de esos controles para hacer pasar las pruebas. Safari físico y pérdida de señal en depósito siguen pendientes.

## Reversión

Restaurar los cambios selectivos en stock.js, offline-page.js, offline-cache.js, stock-preparation.js, el evento de opciones en stock-branches.js, la inclusión de stock-session.js en footer/offline.html y sus controles/CSS. Retirar el helper y su entrada ASSETS; publicar una versión superior para refrescar SW. No borrar ni restaurar inventario, claves de operaciones o colas: el estado temporal de sesión caduca y es independiente de las cargas guardadas.
