# Iconografía local — 13/10/2026

Anterior: kit `kit.fontawesome.com/d9b377fc7f.js`, configuración consultada con versión **6.7.2**, método CSS, licencia Free, origen de estilos/fuentes `ka-f.fontawesome.com`. El kit cargaba estilos después de ejecutar JavaScript. No había copia compatible local. Se sustituye en el encabezado compartido y en la prueba estática index.html.

Nuevo: paquete oficial `@fortawesome/fontawesome-free@6.7.2` descargado del registro npm. CSS oficiales sin modificar en `css/vendor/fontawesome-6.7.2/css/`: fontawesome.min.css, solid.min.css y brands.min.css. Fuentes Solid 900 y Brands 400 en webfonts, WOFF2 y TTF oficial como fallback ya declarado por CSS. Regular no se usa ni se incluye. Solo Solid WOFF2 se precarga. Las rutas relativas mantienen instalaciones en subdirectorio y el base existente de enlaces públicos. Se conserva LICENSE.txt y font-display:block oficial. No se cargan kits, shims ni scripts nuevos; se conservan las clases y glifos.

## Caché y carga

sw.js pasa de shell-v9 a shell-v10, con los mismos procedimientos de instalación/activación. Se agregan tres CSS, dos WOFF2, logo del encabezado y logo de bienvenida. Solo estos recursos usan cache-first; las páginas, datos y recursos offline anteriores conservan su estrategia. Los TTF de respaldo online no se precachean. La carpeta de versión fija incluye .htaccess con max-age de un año e immutable; actualizar una librería futura requiere otra carpeta/versionado. No se aplica caché larga al resto de CSS ni logos. Cabeceras Apache en Render pendientes de comprobar; PHP integrado no interpreta .htaccess.

No se modifica el loader: no espera document.fonts.ready ni bloquea operaciones por una fuente fallida. Los botones conservan sus textos y aria-labels. No se migra a SVG.

## Mediciones

Una ejecución Chrome local, servidor PHP y SQLite temporales, sin latencia móvil simulada. Las otras dependencias externas se neutralizaron para aislar iconografía. Visita cacheada bajo control del service worker. La señal ICONS_VISUALLY_READY_MS espera carga de Solid, regla del pseudo-elemento y dos cuadros de renderizado; es una aproximación reproducible a disponibilidad, no una medición de percepción humana. APP_FIRST_CONTENTFUL_PAINT_MS no equivale a toda la aplicación lista.

| Escenario | CSS base / Solid / Brands ms | Solid WOFF2 ms | Iconos listos ms | FCP ms |
| --- | --- | --- | --- | --- |
| PC primera | 40,4 / 42,1 / 43,3 | 24,9 | 287,0 | No disponible |
| PC cacheada | 9,1 / 9,2 / 9,1 | 5,7 | 199,4 | 152 |
| Móvil simulado primera | 24,6 / 25,6 / 26,2 | 24,2 | 240,4 | 176 |
| Móvil simulado cacheada | 3,8 / 4,1 / 4,3 | 2,8 | 166,1 | 116 |

Antes: el navegador del entorno bloqueó el kit con ERR_NETWORK_ACCESS_DENIED; fuentes/iconos no quedaron disponibles. Los 9173 ms de solicitud fallida no constituyen una referencia válida de latencia del CDN. No se afirma porcentaje de mejora ni se atribuye toda lentitud al CDN. Falta medir producción con primera visita/caché vacío y Safari físico.

## Inventario

24 nombres distintos en código de aplicación, incluyendo prueba estática y nombres dinámicos de rangos. Principales por apariciones de código: house, user, box-open, circle-info y barcode (4 cada uno); chevron-down, qrcode y shield-halved (3); address-book, angle-left, copy, trash, book y gear (2). Restantes: magnifying-glass, circle-check, whatsapp, telegram, envelope, users, crown, user-gear, user-tie, building. Estas cifras cuentan código, no tráfico ni visibilidad real.

Uso: header/footer comunes, Inicio, reportes.php, includes/user-profile-view.php y js/script.js (WhatsApp/Telegram y correo); js/title-icons.js reconoce una clase existente. El resto de la iconografía ya es SVG/código propio. Futuros candidatos a SVG: navegación house/qrcode/book/shield-halved/gear/user y barcode/copy/trash/info. No implementado.

Dependencias externas restantes: Adobe Typekit para texto; mapas/tiles y sus proveedores; JsBarcode y lz-string en páginas existentes. No son nuevas dependencias ni se migran aquí. Font Awesome no tiene solicitudes externas después del cambio.

## Validación y archivos

`node tests/local-icons.cjs`: fuentes decodifican, glifos y familias disponibles, sin CDN Font Awesome, primera/cacheada, móvil/PC, Inicio, Stock, Biblioteca, TOPs, Cuenta, Moderación, palet público y stock compartido; recarga offline usa el fallback anterior y obtiene CSS/WOFF2 locales desde caché. El fallback offline usa sus propios iconos; se verifica además renderización Solid en ese documento. No se publican cuentas ni bases de prueba. Revisadas capturas de Inicio móvil y Moderación PC. `node tests/pwa-presentation.cjs` pasa, incluido lector modal PC y tamaños/manos 320/390/1366. PHP lint del encabezado y sintaxis sw.js correctos. Sin fallos de fuente/CORS/OTS detectados. No se declara verificación de todas las combinaciones de modales ni Safari físico.

Modificados: vistas/header.vista.php, index.html, sw.js, CHANGELOG.md, PENDIENTES.md. Creados: carpeta CSS/fontawesome de versión con tres CSS, cuatro fuentes, licencia y .htaccess; tests/local-icons.cjs y este informe.

Reversión: restaurar encabezado, index.html y lista/estrategia SW desde e093864, retirar carpeta local. Al revertir, usar una nueva versión de caché (por ejemplo shell-v11) para instalar el SW restaurado sin reutilizar un nombre antiguo. No hay migraciones ni cambios de datos.
