# Entregas y decisiones de Repotools

Resumen del código actual y entregas registradas. El [historial anterior completo](docs/HISTORIAL-PROYECTO.md) conserva notas, validaciones y procedimientos de reversión; contiene propuestas y afirmaciones luego reemplazadas. Para pendientes vigentes usar exclusivamente [ROADMAP.md](ROADMAP.md).

## 13/10/2026

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
