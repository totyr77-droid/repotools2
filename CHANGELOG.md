# Entregas y decisiones de Repotools

## 13/10/2026 — Tarjeta social y canonical

Preview PNG de marca 1200×630, helper central de metadatos, título/description/canonical y Twitter large image sin duplicados ni datos personales. Pruebas de imagen HTTP, formatos compartidos y privacidad pasan. Informe y reversión en docs/PREVIEW-SOCIAL.md. Audio sin cambios: iPhone en modo silencio explica el pitido ausente con ambient; prueba física pendiente.

## 13/10/2026 — Aviso offline flotante

Aviso de preparación fuera del flujo: posición fija superior, entrada/bajada y salida hacia arriba; confirmación dura 2,4 segundos en lugar de 6. Conserva colores, icono, role=status y reduce movimiento según preferencia. No modifica caché ni service worker. Validación: sintaxis JS, PHP lint y prueba offline-start. Revertir css/device-workspace.css, js/offline-cache.js y versión del pie desde 0d0b1c2.

## 13/10/2026 — Cargas visuales

Sistema central de indicadores, skeletons y botones con demora, cancelación y accesibilidad. Integración visual en navegación, fichas, listas y consultas públicas, sin cambiar negocio/APIs. Análisis, pruebas, limitaciones y reversión en [CARGAS-VISUALES](docs/CARGAS-VISUALES.md).

## 13/10/2026 — Continuación completada

- Fotos de reportes desde el celular vinculado a PC, evidencia EAN temporal, reemplazo de fotos y cancelación segura. Validación y reversión en [FOTOS-PC-CELULAR](docs/FOTOS-PC-CELULAR.md); migración aditiva 23 verificada en rama Neon sin datos.
- Enlaces cortos seguros compatibles con los anteriores, compartir con contexto y controles alineados en móvil/PC. Validación y reversión en [ENLACES-CORTOS](docs/ENLACES-CORTOS.md); sin migración para enlaces. Cámara física, Safari y despliegue pendientes de verificar.

Resumen del código actual y entregas registradas. El [historial anterior completo](docs/HISTORIAL-PROYECTO.md) conserva notas, validaciones y procedimientos de reversión; contiene propuestas y afirmaciones luego reemplazadas. Para pendientes vigentes usar exclusivamente [ROADMAP.md](ROADMAP.md).

## 13/10/2026

- Selector de sucursales: mapa como primera pestaña/opción en Stock y alta/edición de Moderación. Sugerencias de tres en tres, con otras conservadas en memoria y navegación sin repetir consulta. Confirmación separada del área desplazable, siempre visible. Centrar/quitar ubicación dentro del mapa mediante iconos; quitar no cierra ni destruye el mapa. Controles accesibles y preferencia de mano móvil; formulario más compacto. No cambia API, cuota ni permisos.
- Validación del selector: `tests/stock-branches-ui.cjs` y `tests/moderation-branch-map.cjs` pasan en Chrome/SQLite temporal: asignación/guardado/reapertura, mapa inicial, tres candidatos, paginación sin otra consulta, limpiar/centrar sin guardar coordenadas automáticamente, botón estable al desplazar320/390/1366 y controles de permisos. PHP/JS sintaxis y capturas móvil/PC revisadas. API/mapas externos simulados; prueba con locales reales en móvil pendiente. Revertir commit completo del selector conserva datos existentes, sin migraciones ni borrado local.
- Inicio presenta Herramientas para repositores y descripción rioplatense, conserva escaneo como acción principal y funciones existentes.
- Html5Qrcode2.3.8 pasa de unpkg a archivo local en vistas PHP y prueba estática; algoritmos intactos.
- Cabeceras nosniff, política de referencia y SAMEORIGIN en Apache; recuperación mantiene no-referrer y Docker habilita mod_headers.
- PWA con manifest, iconos normales/maskable192/512, Apple180, favicon y logo horizontal aportados por usuario. Usa el mismo service workerv9, sin cambios de sincronización.
- Documentación separada en arquitectura, pendientes e historial; copia completa de notas anteriores conservada.
- Mapa de Moderación muestra directorio común incluso con lista filtrada. Stock/Moderación identifican locales cercanos desde DB y Geoapify con confirmación y caché temporal. Permisos existentes conservados.

Reversión de presentación/PWA/documentación: revertir el commit completo de esta entrega; no requiere migraciones ni borrado de datos locales. Archivo histórico permite recuperar las notas previas. Instalaciones PWA existentes pueden tardar en actualizar iconos según navegador.

## 12/10/2026

- Conexión celular-PC de la misma cuenta, consentimiento, lectura contextual EAN/QR, espera/continuidad/desconexión y cupo de una sesión de cada tipo.
- Menús, tarjetas y ventanas adaptados a escritorio/móvil; controles de mano conservados.
- Correos de seguridad y aviso de contraseña actualizada alineados con identidad del sitio.
- Compartir Stock por palets o sucursal, exclusiones por enlace, favoritos/recientes de sucursales y calculadora opcional recordada.
- Registros con filtros de acción y autor; contenido compartido adaptable y actualización manual del informe.
- Biblioteca y Stock con cargas offline, catálogo local y notificación de sincronización; Inicio offline con lista local y EAN generado.
- Shellv9 amplía fallback de navegación; preparación offline informa activación/control. Safari físico sigue pendiente de verificación.
- Fichas QR y enlaces públicos suman unidades por EAN de todos los palets autorizados, conservan lotes/vencimientos y distinguen selección fija de sucursal dinámica.
- General de Moderación muestra conteos sin datos personales, exclusivo manager.

## 11/10/2026

- Stock administrativo entra con palets propios/compartidos y permite búsqueda autorizada por código, sin descargar listado global.
- Directorio común de sucursales, descripción/ubicación opcionales y asignación al preparar palets; bloques de sucursales/enlaces retirados del inicio de Stock.

## Entregas anteriores sin fecha individual confirmada

- Sesiones persistentes en DB y seguridad de cuentas; recuperación de contraseña Resend, cierre de otras sesiones y edición de usuarios según rango.
- Reportes con evidencias, comparativa de cambios, notas de revisión y bloqueos; referencias personales de Biblioteca/Stock separadas del catálogo.
- Navegación AJAX, preferencias de mano móvil, notificaciones y validación de rangos en servidor.
- Decisión: supervisión como permiso adicional por vínculo; Empresa aplazada. Son decisiones históricas, no funcionalidades implementadas.

## Validación de presentación/PWA del 13/10/2026

- PHP lint:67 archivos, sin errores. Manifest JSON válido, dimensiones PNG verificadas, rutas existentes y una sola etiqueta manifest por página PHP.
- `tests/pwa-presentation.cjs`: Chrome, encabezado/logo, manifest interpretado sin errores, Html5Qrcode local una sola vez, inicio/ambas manos320/390/1366, apertura del lector PC y único registro sw.js. Capturas móvil/PC revisadas.
- `tests/security-headers.cjs`: Apache local aislado con .htaccess real, nosniff/SAMEORIGIN/referencia, excepción de recuperación, MIME manifest, DirectoryIndex, redirección308 y protección de archivos. No usa servidor/base de producción.
- Regresiones: `tests/ean-scanner.cjs`, `tests/navigation.cjs`, `tests/navigation-timeouts.cjs`, `tests/device-connection-ui.cjs` y `tests/offline-start.cjs` pasan. No quedan cargas externas de Html5Qrcode en código publicable. sw.js, IndexedDB, APIs y algoritmos no cambiaron.
- No se ejecutó instalación física ni cámara real en Safari/iPhone/Android; verificar tras despliegue. Tampoco se confirma el fin del despliegue Render por publicar código.
- El historial anterior se preservó completo con sus bytes originales; algunas notas históricas tienen codificación mixta. Los documentos actuales son UTF-8 y no repiten como pendientes funciones ya implementadas.

## 13/10/2026 ? Cr?ditos discretos y altura del mapa

- Cuerpo desplazable de sucursales usa flex-basis auto para evitar colapso en el tama?o intr?nseco del dialog. Guardar permanece fuera del scroll. Atribuciones mapa/Geoapify peque?as y legibles; informaci?n de producto muestra procedencia OFF/licencia si fuente o foto corresponde. No se ocultan atribuciones ni se alteran mapas, permisos o esc?ner.
- Validaci?n: Stock y Moderaci?n pasan; Moderaci?n a?ade control de altura >200 px del cuerpo y tama?os 320/390 con alto600 y PC1366/900. PHP lint y JS syntax pasan. Capturas Chrome revisadas; Safari f?sico pendiente. Mapas externos simulados en las pruebas.
- Perfil inicial local en docs/RENDIMIENTO.md y dos pruebas reproducibles. No se aplicaron optimizaciones de DB ni se toc? producci?n.
- Revertir esta entrega restaurando CSS, product-info, cache versions header/footer y pruebas desde la entrega anterior 0a2a8f2. No borrar datos ni modificar esquema.
