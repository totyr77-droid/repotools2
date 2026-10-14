# Integración de desarrollo en Main — 2026-10-14

Autorizada explícitamente por el usuario. Se integra desarrollo5678d58 completo, excluyendo únicamente la marca BETA y css/development.css. Desarrollo mantiene ambos. El directorio de trabajo original permanece en desarrollo; integración preparada en un worktree aislado.

Conflictos resueltos: etiquetas y sus pruebas usan la última composición A4 vertical de desarrollo, con PDF y una etiqueta por página; footer usa versiones recientes. CHANGELOG/PENDIENTES incorporan entregas nuevas y esta autorización; documentación de impresión anterior de Main se conserva. Identificadores, enlaces QR y datos no se modifican manualmente.

Validación: 99 PHP sin errores de sintaxis; 23 pruebas funcionales PHP con SQLite aislado, actualizando la elección explícita de compartir en stock-branches; 63 suites CJS, con repetición secuencial de9 casos tras la corrida concurrente. Se actualizan expectativas de icono accesible, toast en lugar de texto inline y medición síncrona del loader, sin cambiar la lógica de la app. Smoke de la versión final sin BETA360/390/1366, sin overflow e historial anónimo401. Safari/AirPrint/cámara reales siguen pendientes según la documentación de cada entrega.

Reversión: git revert -m1 del commit de integración sobre Main y push normal. No borrar tablas, datos ni IndexedDB. Conservar o incrementar el número de caché Service Worker al revertir para actualizar clientes. La base anterior de Main es f7c6003e7dcec8a38ec640c068260107538b769d.
