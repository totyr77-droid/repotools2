# Escáner inteligente compartido — desarrollo

## Integración

El motor sigue en `js/ean-scanner.js`, detrás de `RepoEANScanner.create/rawCreate`. No se abrió otra cámara ni se duplicaron controles o callbacks de negocio. `identification:'pallet'` habilita QR + OCR únicamente al iniciar en modo `qr`. Devuelve el identificador de ocho dígitos a `onCode`; el consumidor decide qué hacer y las APIs existentes conservan sus permisos. `validateQR` permite aplicar la validación propia de perfiles u otros QR sin imponer ocho dígitos.

| Instancia | Archivo | Comportamiento revisado |
| --- | --- | --- |
| Inicio Normal/Rápido | js/script.js, contenedor | EAN8/13 y checksum existentes; sin OCR de palets |
| Stock: abrir/buscar/usar palet | js/stock.js, stockReader | QR de palet o número impreso; callback abre el estado/flujo existente |
| Stock: cargar/buscar producto | js/stock.js, stockReader | EAN, sin imponer ocho dígitos ni activar OCR |
| Celular como lector PC | js/device-connection.js, relayPhoneReader | QR/número de palet en contextoQR; envía el identificador al receptor de la PC; en contextoEAN conserva producto/filtro/formulario |
| Biblioteca | js/biblioteca.js, libraryReader | EAN8/13; controles y feedback compartidos, sin OCR de palets |
| Usuarios/perfiles | js/user-profiles.js, userReader | QR de perfil validado por tokenFromQR; no OCR ni aceptación de números de palet |
| Inicio/Stock/Biblioteca offline | js/offline-page.js, offlineReader | Lectores EAN existentes; sin añadir identificación de palets donde no existe ese flujo |
| Fotos de reportes/productos | js/product-reports.js y relay fotografía | Captura fotográfica, no lector de identificadores; sin OCR ni cambio de acción |

La búsqueda de constructores de cámara/lectores en archivos publicables encontró un único constructorHtml5Qrcode, dentro del motor compartido. No hay otro lectorQR específico que requiera sustituirse. No se añadieron operaciones de selección/registro que no existían.

## Reconocimiento y feedback

- QR: mismo Html5Qrcode local y confirmación existente. Palets normalizados a ocho dígitos, conservando ceros iniciales; acepta el origen actual y dominios oficiales antiguos/públicos sin navegar a ellos. Rechaza enlaces externos, credenciales, puertos externos y códigos incompletos. La existencia/estado del palet se comprueba por el flujo/API posterior.
- OCR: Tesseract.js6.0.1/core6.1.2/modeloFAST local, whitelist numérica y detección de texto disperso. Un candidato de exactamente ocho dígitos, confianza mínima80, rechazo de candidatos distintos en una misma imagen. No extrae ocho dígitos de un EAN13.
- Tres lecturas coincidentes de fotogramas distintos (`video.currentTime`), consecutivas y sin pausas de más de12s. Repetir el mismo fotograma no suma. QR y OCR convergen al mismo callback, evitando entregas simultáneas; el mismo palet no se reenvía durante escaneo continuo hasta entregar otro distinto.
- Recuadro amarillo sigue los límites detectados del número sobre el área realmente visible. QR conserva feedback del marco. Verde/pitido/vibración solo tras confirmar; se reutilizan feedback y preferencias existentes, sin volver a implementar audio.
- Zoom vertical, flash y entrada manual son los controles compartidos ya implementados, con la preferencia de mano existente y sin reiniciar la cámara.

## Rendimiento, cierre y Safari

OCR se carga después de abrir una cámara de palets, sin bloquearQR ni añadir scripts pesados a EAN. Un trabajo OCR a la vez, canvas limitado a1280px de ancho y pausa550ms después de cada lectura; WebWorker evita ejecutar el reconocimiento en el hilo de interfaz. Cierre/navegación cancela temporizadores, elimina el marcador y termina el worker; resultados tardíos no se entregan. Si OCR no está disponible, QR y entrada manual siguen funcionando con mensaje corto.

Archivos vendor locales (~10MB distribuidos; solo se descarga un core compatible), licencias y hashes en `js/vendor/ocr/README.md`. Sin claves/API externas ni envío de imágenes. Serviceworkerv15: cache OCR bajo demanda separado, sin inflar la instalación inicial offline. No garantiza OCR offline antes de su primera descarga; tampoco añade un flujo nuevo de palets offline. Core SIMD/noSIMD y Worker local ofrecen alternativa compatible; no simula capacidades inexistentes.

Pendiente prueba física Safari/iPhone/Android con cámara y etiquetas reales, poca luz, zoom y altura/distancia. El OCR no puede garantizar lectura si los ocho dígitos no tienen resolución suficiente; mantenerQR/entrada manual como alternativas. Safari sin VibrationsAPI no vibra; sonido sigue condicionado por políticas del navegador y preferencias.

## Validación y reversión

`tests/scanner-identification.cjs`: OCR WASM/modelo real local sobre video generado, tres frames, consenso, confianza/ambigüedad, ceros, URL ajena, callbacks de palet/perfil separados, duplicados, una cámara, cierre tardío y fallback QR si fallaOCR. No solicita cámara física ni usa datos privados.

Regresiones: EAN, fotoEAN, zoom, mano, Normal/Rápido, preferencias, navegación, APIsStock/perfiles, ediciónUsuarios, offline y conexióncelularPC. El test de relay se actualizó para el nuevo rechazo temprano de QR externo: cámara permanece activa y luego admite un QR válido, en lugar de enviarlo al servidor y forzar reabrirla.

Revertir el commit de esta entrega exclusivamente en desarrollo. MantenerMain y el distintivoBETA separados. No hay migraciones ni cambios de datos, permisos o reglas de Stock.

Documentación del proveedor: https://github.com/naptha/tesseract.js/blob/master/docs/api.md y https://github.com/tesseract-ocr/tessdata_fast.

Resultado de regresión:56 pruebasCJS ejecutadas;55 pasaron en la ejecución conjunta. Ajustes falló por bienvenida automática superpuesta en la preparación del usuarioexistente. Se agregó seen+version del onboarding al fixture (sin cambiar producción) y pasó su repetición completa. Lint PHP header/footer correcto.

Despliegue funcionalfd68948 confirmado en https://repotools-desarrollo.onrender.com/: portada y11 recursos públicosHTTP200, SHA256 iguales a los archivos publicados (incluido modelo gzip sin Content-Encoding). El mismo test de OCR real pasó usando JS/core/modelo de la beta, con página e imágenes sintéticas y sin acceder a cuentas ni modificar datos. Main sigue en f7c6003e7dcec8a38ec640c068260107538b769d.
