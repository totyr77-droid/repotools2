# Correcciones de Registros, correos y calculadora

## Alcance

Tres correcciones sobre componentes existentes. Sin migraciones, cambios de permisos de negocio, inventario, colas offline, destinatarios, tokens ni reglas de vencimiento.

### Registros

- La interfaz consulta exclusivamente `audit_events`. La revisión de producción fue de lectura: no se encontraron grupos repetidos por responsable + identificador de operación + resultado. No se fusionaron eventos por similitud, fecha o valores. Los eventos históricos de rango tienen referencias explícitas a su tabla original; una operación con otra clave se conserva independiente.
- Faltaban títulos para acciones como `account_updated`; el texto genérico daba la impresión de duplicación. Los títulos específicos ahora vienen del servidor. Se mantiene la idempotencia existente de `audit_event` y la protección contra cambios sin efecto.
- `audit-presentation.php` compara los estados y productos por identidad, sin depender del orden de las filas. Omite revisiones y campos técnicos en la vista normal. Muestra cantidades/diferencias, rangos, sucursales y campos modificados con etiquetas legibles. No inventa diferencias si no hay estados comparables.
- Los originales se conservan íntegros en la base. Administradores/manager autorizados reciben instantáneas sanitizadas para «Auditoría técnica». Los moderadores reciben únicamente diferencias legibles: el servidor no entrega `before`/`after` ni JSON original. La autorización jerárquica se aplica antes de preparar el detalle, también con acceso directo por identificador.
- Sucursales eliminadas y usuarios ausentes conservan su identificador; no se inventan sus nombres históricos.

### Correos

La plantilla ya contenía #27197F; la alteración observada corresponde al cliente de correo. Se añadió `color-scheme: light only`, fondos explícitos y gradientes del mismo color, reglas de respaldo para Apple Mail/Outlook y protección de texto blanco de botones para Gmail antiguo. Todo reutiliza `email_layout`/`email_button`: bienvenida/verificación, recuperación, aviso de cambio y verificación heredada de referidos.

Fuentes primarias consultadas:
- [Email Markup Consortium: actualización Gmail, septiembre 2026](https://emailmarkup.org/en/blog/2026/gmail-app-dark-mode-update): `light only` en versiones recientes y cuentas Google; no es garantía para cuentas externas dentro de Gmail.
- [Rémi Parmentier: técnica de mezcla para Gmail iOS](https://www.hteumeuleu.com/2021/fixing-gmail-dark-mode-css-blend-modes/): respaldo para botones claros sobre fondo de marca.

No se enviaron correos reales. La emulación de esquema oscuro de un navegador no reproduce el algoritmo de Gmail, Outlook o Apple Mail. Queda pendiente revisar recepción en esos clientes, especialmente Gmail en iPhone y cuentas de terceros/versiones antiguas; no existe garantía universal.

### Calculadora

La implementación anterior insertaba un bloque de 254px en el flujo. Ahora es un panel flotante anclado sobre la etiqueta/cantidad, sin segundo visor ni desplazamiento del input al abrir. Usa Popover API manual para evitar recortes y alternativa fija cuando no existe. Dimensiones máximas 280×254px; teclas44px. En poco espacio tiene scroll propio, sin cubrir la cantidad o Guardar. Ajusta coordenadas a VisualViewport, scroll y cambios de preferencia.

Normal/derecha: icono y panel a la derecha. Una mano izquierda: ambos a la izquierda. Sin reflejar números. El input original conserva expresión/resultado; se mantiene readonly mientras se usa el panel para evitar teclado innecesario. Listo/Escape cierran únicamente la calculadora. Se reutilizan U.calculate, callbacks, límites y guardado de alta/retiro.

## Archivos

- `includes/audit-presentation.php` (nuevo): títulos y diferencias legibles.
- `includes/audit-read.php`: presentación y autorización del JSON técnico.
- `js/reports-page.js`, `css/ux-polish.css`: detalle legible y avanzado discreto.
- `includes/email-layout.php`: protección de identidad de correo compartida.
- `js/stock.js`, `css/stock.css`: panel anclado y preferencias existentes.
- `tests/corrections-three-core.php`, `tests/email-colors.cjs` (nuevos); actualizados `tests/quantity-calculator.cjs`, `tests/list2-ui.cjs`, `tests/unified-presentation.cjs`.
- `PENDIENTES.md`, `release.json`, `sw.js`: continuidad y actualización de recursos.

## Validación

Aprobadas: núcleo de las tres correcciones; jerarquía y detalle directo; diferencias anidadas, orden de productos, operaciones independientes y reintentos; color/links de cuatro rutas de correo en claro/oscuro emulado, imágenes locales; calculadora normal/izquierda/derecha en cinco anchos con API nativa y alternativa; formulario real y lados dinámicos sin desplazar cantidad; retiro real, regresión de modales/teclado/100 productos; presentación de registros/configuración/TOPs; recuperación/verificación de contraseña. Revisadas capturas de calculadora izquierda, detalle390 y correo360.

Pendientes físicos: iPhone Safari/PWA, Android Chrome, teclado real y recepción de correos en Gmail/Outlook/Apple Mail. No se afirma haber realizado esas pruebas.

## Reversión

Revertir únicamente los archivos de esta entrega mediante un commit inverso. No borrar auditoría, tablas, IndexedDB ni colas. No se requiere deshacer migraciones. Conservar los cambios locales ajenos al alcance.
