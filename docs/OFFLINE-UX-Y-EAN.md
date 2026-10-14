# Mejoras de interfaz offline — beta

Inicio, Stock y Biblioteca del shell offline comparten acciones44px+, tarjetas compactas, navegación inferior fija con área segura móvil y navegación superior en PC. Agregar escaneando es la acción principal, Agregar manualmente secundaria; orden según mano existente. El modo una mano lee la misma preferencia local por usuario y coloca controles de colección/palet después de los productos; no nueva configuración.

Biblioteca descargada muestra botón Ver código EAN en cada producto y también pendientes. Visor gráfico reutilizado con anterior/siguiente y compartir código. EAN13/EAN8 validados antes de generar. JsBarcode3.11.6 ean-upc, la misma versión usada online, ahora local en js/vendor/JsBarcode.ean-upc-3.11.6.min.js; footer online y shell offline usan ese archivo. Se eliminó el codificador duplicado de Inicio offline. Márgenes blancos16px y barras negras; número separado legible. SW shellv32 incluye librería local junto con recursos existentes de cámara/OCR/estilos. No consulta API para generar barras.

Información disponible: copia autorizada reciente guardada anteriormente, con fecha visible; colecciones sin descarga necesitan conexión. No se generan códigos incompletos ni se inventan datos. Indicador existente del logo y resumen de cola: pendiente/enviando/sincronizada/revisión/reintento. Sin avisos de éxito en comprobación normal. No cambios en esquema IndexedDB, sincronización, reconocimiento, permisos ni base compartida. Acceso de borrado de consultas oculto del recorrido habitual, sin vaciar datos automáticamente.

CSS específico del shell desactiva transición discreta de cierre de modales para soltar top-layer entre acciones; repetición de prueba amplia offline completa ahora aprobada. No reconstrucción de cámara ni cambios del lector.

Archivos: offline.html, css/offline.css, js/offline-page.js, js/vendor/JsBarcode.ean-upc-3.11.6.min.js, vistas/footer.vista.php, sw.js, tests/offline-library-visual.cjs, este documento, PENDIENTES.md.

Pruebas: offline-library-visual (Service Worker preparado→sin red→recarga, barrasEAN13/EAN8, visor/reapertura/navegación, ambos modos/manos320/390/430/1366, botones44px/sin overflow/acciones accesibles); offline-intelligent completo (QR/OCR cacheados, sesión, privacidad, Biblioteca lost-ack y leases); stock-sync-integrity (dos84 y fallos de red sin duplicación); sintaxis JS y PHPfooter. Capturas móvil/PC revisadas. Repetir Safari/PWA real y leer barra generada con segundo dispositivo; no se afirma validación física ni cámara real.

Reversión: revertir commit exclusivamente en desarrollo y conservar colas/IndexedDBv3. No modificar Main ni migraciones. Verificar despliegue por archivos antes de probar beta en campo.
