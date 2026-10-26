# Main v3.12 — pitido al volver a escanear un palet

Usuario informa falta de pitido en el recorrido primer QR→detalle→Escanear otro palet. El adaptador de métodos iniciaba la cámara móvil después de esperar phoneReader.stop (incluso sin lector enlazado), dejando la preparación del audio detrás de una espera. Ahora raw expone prepareFeedback, que respeta preferencias y reutiliza el servicio global; el adaptador lo llama sin esperar dentro del gesto al abrir móvil o cámara recordada. El start real conserva su preparación posterior, necesaria para usos directos. No cambia cámara, flujo de códigos, tiempos del tono ni permisos.

Regresión nueva pallet-rescan-audio-ui usa adaptador y lector reales con motor/cámara/audio simulados: primer QR y dos reaperturas, AudioContext interrumpido que solo admite resume dentro del gesto. Chromium/WebKit: tres códigos y tres tonos programados. ean-scanner/scan-feedback conservan preferencias, audio global y recuperación; scanner-lazy ambos motores, tablet-scan-methods y main-rapid-scanner. No demuestra audición física ni que toda interrupción de iOS tenga esa causa.

Reversión: restaurar js/ean-scanner.js y js/device-connection.js previos con caché superior. Sin cambios de datos/configuración ni beta. La comprobación final del pitido corresponde al recorrido físico informado por el usuario.
