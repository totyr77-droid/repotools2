# Lector y cámara dentro del buscador de Supervisión

Entrega: main3.24 y beta2.21 (28/10/2026).

La página Supervisión cargaba el código del lector, pero no ean-scanner.css. El video podía conservar su altura nativa y desplazar los controles. header.vista.php carga ahora la misma hoja compartida usada por los demás lectores; no se cambia el motor, recorte visible, permisos, cámara, sonido ni reconocimiento. No se agregan alturas alternativas por página.

Supervisión reutiliza team-inventory-query: lupa, texto y cámara dentro de un único borde, sin segundo borde en el input. Palets, Stock y equipos conservan sus componentes equivalentes; controles de cámara44px, foco común y orden según mano elegida. No se agregan cámaras a campos que no tenían lectura de códigos.

Validación: supervision-scanner-layout-ui sobre la página/API/sesión locales reales, con video vertical1080×1920 simulado. Verifica carga única de estilos, video contenido, EAN/QR,320×568/390×660/844×390/1366×844 y ambas manos; cámara dentro del campo, borde único, cierre/manual alcanzables, detención y código aceptado en búsqueda. WebKit/Chromium en main y beta. pallet-keyboard-layout-ui WebKit verifica buscador y acciones del palet con teclado simulado. Captura móvil revisada. No prueba física de cámara/iPhone ni medición de precisión.

Reversión: revertir cambios de header.vista.php, clase del buscador en supervision.php y bloque final de ux-polish.css; publicar caché superior. Sin migración, datos ni permisos modificados. Main conserva sus cambios locales de PENDIENTES.md/ROADMAP.md; no incorporar funciones comerciales de beta.
