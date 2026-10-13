# Enlaces de stock compartido — 13/10/2026

Nuevos: `/s/<token>`, 22 caracteres base64url con 16 bytes aleatorios (128 bits). Anteriores: `stock-compartido.php?token=<token>`, 64 caracteres hexadecimales (256 bits); siguen funcionando. No se recortan tokens ni se exponen IDs. Las colisiones se reintentan hasta cinco veces. La comparación respeta mayúsculas.

Apache resuelve la ruta corta internamente, sin redirección. Se mantienen recursos, reglas de seguridad, dominio Render y revocación. No se incorpora vencimiento: se conserva la desactivación del enlace. Compartir añade título y explicación formal de acceso de solo lectura; copiar entrega únicamente la URL. La tarjeta utiliza la imagen negra y contexto público sin datos personales. Cuatro controles alineados, filas completas en PC y preferencia de mano móvil.

Archivos: `.htaccess`, `includes/share-links.php`, `includes/stock-branches.php`, `stock-compartido.php`, `palet.php`, encabezado/pie de vistas, `js/stock-branches.js` y `css/device-workspace.css`. La columna TEXT UNIQUE existente se conserva: enlaces no requieren migración. La migración 23 corresponde a fotos PC–celular, documentadas en FOTOS-PC-CELULAR.md. No existe generador QR de stock compartido; las etiquetas de palets no cambiaron.

Validación local: stock-branches.php, stock-branches-ui.cjs, share-links-ui.cjs y security-headers.cjs pasan. Cubren creación, colisiones, permisos, formatos nuevo/antiguo, recursos, copiar/compartir, revocación, mayúsculas y cuatro botones a 320/390/1366 px con ambas manos. PHP lint y sintaxis JS correctos. Apache aislado; sin modificar producción. Tarjetas WhatsApp y despliegue Render pendientes.

Reversión: presentación anterior en `8784842`. Si ya se emitieron enlaces nuevos, conservar el validador de ambos formatos y la ruta `/s/` para no romperlos. Se puede restaurar el generador y presentación anteriores manteniendo esas compatibilidades. No borrar datos ni tablas.
