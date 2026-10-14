# Cámara y visor compartidos QR/EAN — beta

## Arquitectura
`js/ean-scanner.js` conserva su API create/rawCreate/start/stop. La función cameraViewport monta un solo componente reutilizable de visor, guía y seguimiento; cameraControls sigue siendo la única implementación de zoom/linterna/entrada manual. Una cola y un propietario de cámara compartidos serializan aperturas y cierres entre instancias. Abrir otra instancia libera la anterior. El tamaño se establece antes de abrir el lector; ResizeObserver adapta las guías sin reiniciar la cámara.

No se creó un segundo lector EAN ni se cambiaron callbacks, permisos o validaciones. El motor QR + OCR sigue condicionado al contexto pallet; los perfiles no activan OCR numérico. Producto conserva EAN13/EAN8 y checksum. Los canvas auxiliares de análisis permanecen ocultos; son necesarios para reconocimiento y fotografías, no interfaces adicionales.

## Instancias
- Inicio: Normal y Rápido.
- Biblioteca: alta por EAN.
- Stock: abrir palet QR/OCR, agregar/restar mercadería y buscar EAN.
- Moderación/Usuarios: QR de perfil, sin OCR de palet.
- Celular vinculado a PC: EAN y QR, mismas acciones de recepción.
- Shell offline: EAN y consulta autorizada QR/OCR.

La cámara para sacar fotografías de reportes no es un lector de códigos y mantiene su flujo existente.

## Visual y ciclo de vida
Mismas dimensiones, bordes, video cover y controles; guía QR cuadrada y EAN horizontal. Blanco / detección amarilla / confirmado verde. No hay seguimiento inventado: OCR usa cajas del motor; EAN muestra cajas Quagga cuando existen y son válidas. El decoder sin coordenadas solo cambia el estado de la guía. Un timeout retira cajas antiguas. El marco rojo de espera celular-PC permanece encima del blur.

Zoom vertical según mano, rango real, actualizaciones serializadas, linterna opcional y preferencias de sonido/vibración se reutilizan. Al cerrar se detienen tracks, análisis, OCR y controles, se desconecta ResizeObserver y se retiran guías. Caché SW shell-v19 para actualizar la interfaz offline.

## Validación
- tests/shared-camera-ui.cjs: visor idéntico QR/EAN, EAN13/EAN8 y contexto, una cámara incluso en aperturas concurrentes, cambio de modos, manos y limpieza.
- tests/ean-scanner.cjs, scanner-zoom.cjs, scanner-hand-layout.cjs, scanner-identification.cjs: checksum, ciclo, zoom real/rechazos, linterna, 44px y OCR WASM real sobre video generado con tres fotogramas distintos.
- main-rapid-scanner.cjs, ean-scan-photo.cjs, device-connection-ui.cjs: rápido/normal, foto temporal y recepción PC.
- offline-intelligent.cjs: escenario offline; fixture adaptado para que los ocho dígitos entren completos en el visor cover compartido.

Pendiente físico: Safari iPhone y Chrome Android con cámara real, foco, zoom/torch expuestos por dispositivo, QR inclinado, EAN girado, distancia y luz. Las pruebas automatizadas no sustituyen esas comprobaciones.

## Revertir
Revertir el commit de esta entrega en desarrollo. Mantener/incrementar la versión SW al revertir para que los clientes actualicen los recursos; no bajar IndexedDB ni tocar datos. Main no se modifica.

EAN: desde 2026-10-14 la guía fija se oculta cuando existe una caja de detección válida. Vuelve si no hay coordenadas válidas, se pierde la detección (700ms) o se reinicia el estado. QR/OCR no cambia. Regresión shared-camera-ui/ean-scanner/scanner-zoom aprobada. SW shell-v20. Reversión: revertir esta entrega conservando/incrementando versión SW.
