# Zoom deslizante — 13/10/2026

Solo desarrollo. Se reutiliza el control vertical y el track activo del lector compartido; no se cambian reconocimiento EAN/QR, audio, vibración ni apertura de cámara.

## Implementación

`js/ean-scanner.js`: eventos input durante el arrastre, indicador inmediato y confirmación mediante getSettings(). Debounce60ms, un valor pendiente (el más reciente), una actualización serializada con flash/enfoque; evita repetir el valor ya aplicado. No desactiva el deslizador al esperar. Al cerrar cancela temporizador/pendientes. No modifica zoom por detección de códigos.

Rango real obtenido de getCapabilities().zoom, conservando la decisión anterior de no bajar de1×. Extremos alineados al step real, sin el antiguo límite artificial de3× para EAN. Si la cámara ofrece0,1 se usa0,1; si ofrece un paso mayor se respeta. Accesos1/2/3 o1/2/5 solo cuando pertenecen al rango/paso admitido. Indicador vuelve al nivel confirmado si rechaza un cambio.

`css/ean-scanner.css`: deslizador nativo vertical glass existente, touch-action:none/overscroll para evitar mover la página, indicador estable y hasta tres accesos táctiles44px dentro de la cámara. Posición según mano móvil reutiliza css/responsive.css; no hay nueva preferencia. No se bloquea el gesto nativo con preventDefault.

Recursos versionados en header/footer y precache SW shell-v11 para que el modo sin conexión actualice lector/estilos. No se cambian colas offline.

## Compatibilidad y pruebas

No se puede determinar el máximo de un teléfono sin abrir su cámara. Ejemplos simulados:1–8×/0,1 y1–4×/0,5. No son mediciones de hardware real. Si Safari/u otro navegador no expone el rango, el control no aparece; si rechaza el ajuste, se conserva nivel confirmado y feedback existente. No se simula zoom digital mediante recorte de CSS.

`tests/scanner-zoom.cjs`: input en vivo, agrupación de movimientos, indicador, una operación simultánea, rangos/pasos, accesos rápidos, flash conserva zoom, errores, cierre y falta de capacidades. Lector simulado en Chrome táctil390px. ean-scanner, Normal/Rápido y offline-start comprueban regresiones. Safari/iPhone/cámara real y gesto físico quedan pendientes.

API de capacidades y configuración: https://developer.mozilla.org/en-US/docs/Web/API/MediaStreamTrack/getCapabilities

## Reversión

Revertir el commit de esta entrega: lector, estilos, versiones header/footer y nombre de precache. Sin migraciones/datos/permisos afectados; conservar BETA exclusivo desarrollo.

## Zoom según mano — 13/10/2026

Slider vertical en el lateral de la mano configurada, con centro en la mitad inferior del visor. Flash y entrada manual al lado opuesto; sin reflejar iconos/textos. Preferencia existente body[data-hand], inmediata y válida también en horizontal para pantallas táctiles; escritorio conserva distribución derecha. En pantallas de hasta360px se muestra solo el acceso1× para evitar solapamientos; el rango completo sigue disponible en el slider. Sin cambios de reconocimiento, audio, vibración o control del track. Rango real y paso del dispositivo existentes, mínimo1× si compatible, fallback sin zoom cuando no se admite.

Archivos: css/ean-scanner.css, css/responsive.css, vistas/header.vista.php (versiones), sw.js (v14), tests/scanner-hand-layout.cjs y tests/responsive-workspaces.cjs. Validación: scanner-zoom y scanner-hand-layout pasan; PHP header válido. Safari/cámara físicos pendientes: confirmar rango real, deslizamiento y comodidad con ambas manos. Revertir el commit de esta entrega solo en desarrollo; no afectaMain.
