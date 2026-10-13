# Transición visual RT — 13/10/2026

## Causa comprobada

navigation.js sustituía el body y añadía app-page-enter antes de ejecutar los scripts de la nueva página. El título ya podía pintarse durante esa inicialización. También usaba un loader local con demora180ms, cancelado al sustituir el body: no había cobertura continua. Los inicializadores locales podían mostrar después otra carga. No se esperaba a fuentes/iconos. No se encontró un segundo render del h1: eran estados visibles sucesivos del contenido.

## Archivos y funcionamiento

- header.vista.php comienza con html.is-loading e incluye page-loader-head.vista.php después del charset, antes de hojas externas.
- Ese parcial incorpora el CSS crítico de css/page-loader.css y js/page-loader.js directamente al HTML. Crea el overlay durante el parseo del head, antes de DOMContentLoaded; el fondo crítico cubre también ese intervalo. No depende de otra descarga de JS/CSS.
- RT blanco de img/onboarding-logo.png, copia optimizada128px del isotipo existente,2546bytes. Data URI sin red/CDN. Pulso sutil de opacidad, sin giro/zoom/rediseño; reduced-motion desactiva animación y transición.
- navigation.js muestra el overlay antes del fetch, lo conserva fuera del body sustituido y elimina la animación prematura del contenido. Ya no usa el loader local para cambiar de página.
- onboarding.js espera también repotools:page-ready, sin cambiar sus pasos ni persistencia. footer.vista.php actualiza versiones de scripts afectados.

Un único RepotoolsPageLoader controla visible/hiding/hidden, con generaciones que descartan finalizaciones antiguas. Se bloquean clics repetidos durante la navegación. Mínimo250ms visible; fade220ms y después no captura eventos. Popover manual como capa superior cuando está disponible; overlay fijo como respaldo. No se usa display:none para toda la app.

## Recursos críticos y enlaces

Documento inicial: DOMContentLoaded, window.load y document.fonts.ready; solicitud explícita del font local Solid de Font Awesome6.7.2. AJAX: hojas de sección, ejecución de scripts, fuentes y dos frames. No espera promesas de APIs: consultas Stock/Biblioteca/reportes conservan skeletons e indicadores locales.

Watchdog libera la pantalla a los5segundos desde show aunque un recurso siga pendiente. Por esa excepción, en una conexión muy lenta puede faltar algún recurso al liberarse: no se promete interfaz completa a cualquier velocidad dentro de5s. En simulaciones frías Slow4G/Fast3G se activó el watchdog y las fuentes terminaron después. No se optimizó servidor ni dependencias en esta entrega.

Solo enlaces internos de las rutas existentes. Descargas, target externo, Ctrl/Meta/Shift/Alt, anchors, mailto/tel y otros orígenes conservan comportamiento. data-full-navigation muestra overlay y conserva navegación nativa. Formularios y acciones no se interceptan. El menú móvil queda cubierto inmediatamente; el nuevo body restablece su menú cerrado.

Atrás/adelante AJAX conserva historial y scroll. pageshow.persisted limpia el overlay en restauraciones BFCache. Debug ligero, sin envío ni datos personales, en RepotoolsPageLoader.timings: NAVIGATION_CLICK_MS, NEW_DOCUMENT_MS, LOADER_SHOWN_MS, FONTS_READY_MS, UI_READY_MS, LOADER_HIDDEN_MS, TIMEOUT_MS; valores relativos a performance.now del documento.

## Pruebas y límites

tests/page-transitions.cjs:390/1366px, clic real interno, scripts demorados650ms, frames muestreados, una instancia del overlay, RT128px decodificado. Secuencia autenticada manager Inicio→Stock→Biblioteca→TOPs→Cuenta→Stock→Moderación→Inicio. Atrás/adelante, pageshow persistido simulado, reduced-motion y fuente eternamente pendiente. Red fría CDP Slow4G150ms/200kBps y Fast3G400ms/100kBps. Chrome y SQLite temporal, no producción.

También pasan navigation, navigation-timeouts, loading, guest-demo y onboarding, más lint PHP/JS. Capturas temporales PC/móvil revisadas: RT centrado sobre fondo oscuro sin título detrás. Safari real, BFCache físico y Render pendientes; evento pageshow sintético no demuestra BFCache real de Safari. No se declara toda la suite del repositorio aprobada.

Antes: título visible, carga tardía, otro cambio durante inicialización. Después: cobertura RT inmediata, inicialización detrás y revelado suave único, salvo liberación de seguridad documentada. Loaders locales siguen representando datos secundarios.

## Reversión

Revertir solo esta entrega o restaurar header/footer/navigation/onboarding a fd4a88a y retirar css/page-loader.css, js/page-loader.js y vistas/page-loader-head.vista.php. Conservar cambios posteriores del usuario. No hay migraciones ni cambios de APIs, Stock/palets, escáner/cámara/EAN, cuentas/permisos, URLs, Open Graph, service worker o datos que revertir.
