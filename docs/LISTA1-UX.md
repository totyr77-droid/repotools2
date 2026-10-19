# Lista 1 — entrega en Main, 1.08

Trabajo publicado en tandas de dos puntos, con excepción del último punto impar. Por pedido del usuario, las ocho primeras tandas mantienen 1.07; solo la última incrementa a 1.08 (build interno 13). Avisos por cada push en el chat y en las notificaciones de la cuenta manager solicitante; no se enviaron correos.

## Cambios y causas

| Punto | Corrección | Causa y componentes principales |
|---|---|---|
| 1 | Perfil, Preferencias, Accesos y Seguridad comparten jerarquía, sin aspecto de botón. | Selectores distintos para encabezados; `css/settings.css`. |
| 2 | Icono de una mano de 20 px, texto e interruptor alineados. | El SVG no tenía dimensiones propias; `css/settings.css`. |
| 3 | Seguridad debajo de Perfil; botones verticales, tres columnas cuando hay espacio y adaptación móvil. | El include quedaba fuera de la grilla; `cuenta.php`, `css/settings.css`. |
| 4 | Mercadería con paquete +/−, colecciones con capas y linterna con estado encendida/apagada. | Símbolos ambiguos, título dinámico con icono informativo y foto que asumía activación sin confirmación; `includes/stock-icons.php`, vistas de palet/foto, `js/title-icons.js`, `js/biblioteca.js`, lectores/fotos. No biblioteca de iconos nueva. |
| 5 | Tu lista y Escanear producto integrados en el flujo, en ese orden antes del nav. | El grupo se trasladaba al body con posición fija; `js/one-hand-mode.js`, `css/ux-polish.css`. |
| 6 | Etiquetas con punta junto a los accesos circulares mientras el subnav está abierto. | Los botones solo tenían título accesible; texto dentro del mismo botón, sin otro manejador. Respeta mano y movimiento reducido. |
| 7 | Barra de Stock de 44 px; Vencimientos textual y panel expandible dentro del cuerpo. | El acceso se había reducido a icono y abría una hoja en una mano. `stock.php`, `js/stock.js`, estilos/una mano reutilizan los mismos resultados, estados y consultas. |
| 8 | Botón PC arrastrable, snap lateral, lateral recordado por cuenta/navegador y límites de header/nav/viewport/FABs. | Antes solo posición fija por mano. `js/connection-position.js`, `js/device-connection.js`, footer y estilos; umbral distingue clic de arrastre, limpieza al navegar. |
| 9 | Versión debajo del extremo derecho del logo. | El absolute se calculaba respecto del enlace completo; `.logo-mark` define el anclaje sin agrandar el logo. |
| 10 | Zoom −/valor/+ compacto, ajuste fino y pulsación prolongada; accesos 1×/2×/5× compatibles. | El slider vertical consumía espacio. Misma cola serial, snapping y capacidades del track; no reinicia cámara. Range interno oculto mantiene el pipeline existente y pinch. |
| 11 | Pitido breve más perceptible: seno 1600 Hz, pico 0.72 con envolvente. | El pico anterior era 0.45. Respeta OFF, una confirmación y vibración existente; no modifica volumen físico. |
| 12 | Mensajes encima del visor en una región estable y desplazable para texto largo. | Estado después del lector y altura dependiente del texto. Componente compartido incluye QR de perfil; Inicio tiene su propia fila estable. |
| 13 | “Tus QR están listos”, contador, previews por etiqueta, compartir todos los PNG, imprimir y generar más. | Solo se previsualizaba la primera imagen y se compartía una por fila. `js/label-files.js`, `js/stock.js`, vista/estilos; archivos se preparan antes de pulsar compartir. Fallback PNG o ZIP sin dependencias. |
| 14 | QR mayor, logo/cabecera compactos, aviso en menos líneas y número grande intacto. | Espaciados sobredimensionados en la composición. `js/pallet-label.js` comparte layout para PNG/SVG/PDF. Conserva cuatro módulos de silencio, A4 vertical, números, URL y reglas de vencimiento. |
| 15 | Header de tablet en dos filas sin superposiciones, márgenes de contenido coherentes; teclado, cámara local y teléfono. | Regla antigua de columna a 1207 px y bloqueo de cámara local por modo desktop. `css/responsive.css`, `js/device-connection.js`. `device_kind` clasifica iPad y Android sin Mobile como puesto desktop para conservar teléfono acompañante con el límite existente de una sesión de cada tipo. |
| 16 | Grupos de acciones según pulgar sin invertir lectura; Biblioteca no desborda; Moderación General/Reportes/Usuarios/Registros/Sucursales, scroll y activo visible. | Orden visual fijo, orden distinto entre menús y pestañas que se envolvían. Estilos/una mano, `js/reports-page.js`, `js/mobile-navigation.js`. Permisos siguen determinando opciones. |
| 17 | Normal y Rápido comparten marco y visor estables; solo resultados tienen scroll interno. | Ocultar paneles cambiaba altura y otra regla tardía imponía `height:fit-content`. `vistas/escaner.vista.php`, `js/script.js`, `css/scan-result.css`. Normal se detiene y conserva su ficha; Rápido mantiene cámara, contador, producto/EAN, estado, duplicados, acciones y Finalizar. |

## Validación

Pruebas con SQLite temporal, cámaras simuladas y contextos de navegador aislados; sin escrituras de prueba en inventario ni cuentas reales. Única escritura dirigida en producción: notificaciones solicitadas para la cuenta manager.

- Ajustes: `settings-ui` (existente/nuevo, ON/OFF, sesión/recarga, autosave, mano y seguridad).
- Interfaz: `ux-polish`, `one-hand-workspaces`, `user-moderation-state`, `responsive-workspaces`; pantallas de 320 a 1920, incluyendo 768/769, 1043, 1207/1208 y 1241; ambas manos y modos. Comprobación adicional de intersecciones de controles de header en tablet.
- Conexión: `connection-position` (clic/arrastre, snap, límites, colisiones, resize, cleanup), `device-connection-ui`, `tablet-scan-methods` y `account-devices.php`.
- Cámara: `scanner-zoom` (pasos finos/gruesos, pulsación prolongada y detención, rechazo/rollback, cancelación y serialización), `shared-camera-ui`, `scanner-hand-layout`, `ean-scanner`, `device-report-photos`, `scanner-opening-notice`.
- Etiquetas: `label-files` (1/2/6 archivos y ZIP), `pallet-label-pdf`, `pallet-label-layout`, `pending-label-print`. QR decodificados, 1/2/6 páginas, logo negro, cifras dentro de márgenes. Prueba adicional de generación de dos etiquetas en la UI y compartir sus dos PNG.
- Escáner de Inicio: `main-rapid-scanner` (20 EAN, no duplicados al mantener código, pausa por reporte, Normal/Finalizar/reset), `scanner-frame` (dimensiones y mensajes largos en móvil/tablet/PC y 844×390).
- Sintaxis PHP/JS de archivos modificados y `app-release`.

## Límites reales y pruebas físicas

- Safari/iPhone, PWA, iPad/Android reales, cámara/linterna/zoom dependen del hardware y permisos. Los controles no simulan capacidades ausentes.
- Volumen, modo silencio, mezcla con música y vibración dependen del navegador/dispositivo. Falta comprobar la percepción del nuevo tono físicamente.
- Web Share puede limitar cantidad/tamaño de imágenes o destinos. Si rechaza compartir, queda descarga; cancelar voluntariamente no dispara una descarga. El ZIP conserva cada PNG con su número.
- AirPrint, impresora y lectura a distancia necesitan comprobación física. El PDF sigue garantizando el tamaño de página; no se garantiza alcance de seis metros con todas las cámaras/impresoras.
- Las sesiones de tablet abiertas anteriormente mantienen su tipo hasta el siguiente ingreso. La preferencia del lateral del botón PC y el modo de una mano siguen siendo locales por cuenta/navegador.
- No quedan puntos de implementación deliberadamente aplazados de esta lista. Las validaciones físicas anteriores no se presentan como realizadas.

## Continuidad y reversión

Seguir trabajando en Main por pedido del usuario. No migraciones de datos, cambios de IDs/QR ni modificación de permisos de moderación. Referidos continúa con su protección de entorno anterior.

Para deshacer, revertir las tandas necesarias desde `d623a4a`, conservando movimientos/colas IndexedDB y datos del servidor. Publicar una versión/build superior para invalidar cachés; no restaurar un contador antiguo ni borrar datos locales. La etiqueta de versión y Service Worker se incrementaron únicamente en la última tanda por excepción expresa del usuario.
