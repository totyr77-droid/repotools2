# Confirmación y cancelación celular–PC — 13/10/2026

Aviso flotante verde con SVG de confirmación durante 2,4 segundos después de recibir respuesta exitosa del servidor: «Foto enviada con éxito», «EAN enviado con éxito» o «QR enviado con éxito». PC muestra «Foto recibida con éxito» al incorporar la imagen. Se coloca en la ventana activa para verse también en escaneo continuo; no desplaza contenido ni intercepta toques. Respeta reduced motion.

La solicitud en PC es una notificación amarilla con icono, mensaje «Enviamos una solicitud a tu celular para tomar la foto» y botón Cancelar foto compacto. En el celular la solicitud usa presentación amarilla, acción Tomar foto con icono y oculta Desconectar en ese contexto. Se conserva la posibilidad de desconectar desde las herramientas normales.

Cerrar/cancelar desde PC limpia la solicitud y el celular cierra su alerta/cámara en la siguiente actualización. Cerrar la alerta de foto o cancelar la cámara en el celular cancela únicamente esa fotografía y libera el formulario de PC. Se preserva la conexión entre equipos y una foto anterior del reporte. No se reutilizan envíos de una solicitud cancelada. La sincronización depende del sondeo existente; normalmente tarda unos segundos, no es instantánea sin red.

API añade `cancel_photo`: valida sesión vigente, CSRF, dispositivo vinculado, identidad, flujo exacto y modo foto; elimina evidencia temporal, código/contexto y disponibilidad de captura, sin borrar el vínculo. No cancela solicitudes ajenas ni de escaneo normal. No hay migración.

Archivos: `js/action-notices.js` (RepoToast), `js/device-connection.js`, `js/product-reports.js`, `api/scan-relay.php`, `css/device-workspace.css`, versiones de caché del encabezado/pie y dos pruebas de integración. Los tests de fotos usan upload_tmp_dir válido para evitar avisos de configuración local dentro del JSON; no se silencian errores de producción. Sus visitantes ya vieron la introducción para aislar estas pruebas.

Validación: device-report-photos.cjs y device-connection-ui.cjs cubren envío, notificación, captura guiada/luz, cancelación desde PC/celular, rechazo de envíos cancelados, foto previa conservada, misma cuenta y conexión conservada. Se simula cámara en Chrome y se usa SQLite temporal; Safari y dispositivos físicos pendientes. Sintaxis JS y PHP lint correctos. No se cambia el detector EAN/QR.

Reversión: restaurar archivos modificados y pruebas desde `dd13ebf`. Mantener la migración 23 de fotos anterior; esta entrega no modifica datos de servidor fuera de las solicitudes temporales normales.
