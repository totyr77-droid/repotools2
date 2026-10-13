# Vista previa social — 13/10/2026

## Archivos

- `includes/page-metadata.php`: título, descripción, imagen y URL pública centrales. Origen HTTPS de Repotools; omite parámetros innecesarios y privados. Conserva token de enlace compartido, código público de palet y categoría válida de TOPs únicamente cuando corresponden. `/s/token` mantiene esa ruta pública en canonical/og:url.
- `vistas/header.vista.php`: actualiza los metadatos existentes sin duplicarlos, añade canonical y usa título/descripción centrales. Stock compartido conserva su contexto genérico sin propietario, correo, alias ni cantidades.
- `img/repotools-og-preview.png`: PNG público 1200×630, fondo negro, logo horizontal original blanco, «Herramientas para repositores» y «Escaneá · Controlá stock · Organizá». Composición de presentación con el recurso existente; sin modificar el logo original ni añadir dependencias a la app.
- `tests/page-metadata.php`, `tests/share-links-ui.cjs`: origen, filtrado de parámetros, privacidad, duplicados, URL corta y acceso HTTP/dimensiones del PNG.

## Metadatos

Título general: Repotools - Herramientas para repositores. Descripción: Escaneá productos, controlá stock y organizá tu trabajo con Repotools. `og:title`, `og:description`, `og:type=website`, `og:image`, dimensiones/tipo/alt y `og:url`; `twitter:card=summary_large_image`, título, descripción, imagen y alt. Imagen absoluta bajo el dominio configurado de Repotools. Canonical usa la URL pública depurada; nunca localhost ni host recibido sin validar. Las URLs públicas conservan su identificador de acceso, que no se incluye en el texto visible de la tarjeta.

## Validación y límites

Pruebas metadata y share-links-ui pasan; PHP lint del encabezado correcto. PNG 1200×630 revisado visualmente y servido con HTTP 200 en el servidor local. Enlaces nuevos, antiguos y revocación siguen pasando. No se modificaron APIs, datos, escaneo, offline ni permisos. Verificar PNG público y tarjeta real después del despliegue; WhatsApp/Facebook/Telegram/Discord pueden conservar previews antiguas. No se añaden parámetros artificiales para forzar su caché. Metadatos se entregan en la respuesta PHP que consultan los robots.

Reversión: restaurar encabezado y prueba share-links-ui desde `454a852`, retirar helper, PNG y prueba metadata. No hay migración ni cambios de rutas funcionales.

## Consulta de audio del mismo turno

El usuario confirmó iPhone con modo silencio. El escáner ya prepara Web Audio en la apertura y usa sesión ambient para mezclar audio. Ese modo respeta silencio en iPhone, por lo que se conserva la configuración actual solicitada. Falta probar en teléfono real con silencio desactivado y música activa; no se promete mezcla universal. La prueba ean-scanner pasa, pero simula el audio y no demuestra sonido físico. Referencias: [Apple ambient](https://developer.apple.com/documentation/avfaudio/avaudiosession/category-swift.struct/ambient) y [MDN AudioSession](https://developer.mozilla.org/en-US/docs/Web/API/AudioSession/type). No se cambió el escáner.
