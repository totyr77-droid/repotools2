# Distribución responsive — 13/10/2026

Entrega exclusiva de desarrollo. Se conservan colores, logotipo, componentes, rutas, operaciones, preferencias y permisos. Sin migraciones ni cambios de reconocimiento de códigos.

## Cambios

- `css/responsive.css`: capa compartida al final de los estilos, variables para ancho/gutters, títulos compactos, Inicio en dos columnas desde769px y herramientas arriba, cuadrícula adaptable de Biblioteca, modales más amplios para gestión y con límites de altura/scroll. Formularios de acceso siguen limitados a560px.
- Ajustes aprovecha dos columnas intermedias y tres en escritorio; móvil mantiene una. Stock y palets reducen espacios y padding; los estados, skeletons y componentes existentes se conservan.
- Navegación superior conservada para PC; entre769 y1000px las etiquetas van debajo de los iconos. Móvil conserva menú inferior, safe areas y espacio reservado bajo el contenido. No se agrega una segunda navegación lateral.
- Preferencia existente `body[data-hand]`: controles compartidos de cámara y flash de fotos cambian de posición solo en móvil táctil. Zoom queda en el lado opuesto para separar controles. No se espejan textos, números ni iconos; la barra de cuenta conserva su disposición.
- `palet.php`: título con SVG existente y enlace Abrir desde Stock integrado como acción compacta con icono. IDs y operaciones existentes intactos.
- `vistas/header.vista.php`: responsive cargado después de los estilos de componentes, con versión nueva. Padding y variables del encabezado sticky del modal coinciden para evitar desbordamientos.

## Validación

Prueba nueva `tests/responsive-workspaces.cjs`: siete pantallas × diez tamaños (320,360,390,430,768,769,844 horizontal,1024,1366×768,1920×1080), ambas manos, límites de navegación, espacio inferior y posición de controles de cámara. Chrome con móvil táctil simulado y SQLite temporal, sin datos reales.

También: Stock/exportación, Ajustes (persistencia/sonido/vibración), sucursales/compartir, mapa Moderación, resumen público, demo visitante, escaneo Normal/Rápido, transiciones y PDF A4 de1/2/5 etiquetas. Capturas locales revisadas. PHP lint de los dos archivos de vista.

Safari/iPhone físico, cámara real y safe areas de hardware quedan pendientes. La simulación no acredita compatibilidad física. El usuario confirmó la URL de beta y pidió omitir comprobaciones remotas por ahora; no se hicieron cambios de producción.

## Reversión

Revertir el commit de esta entrega en desarrollo: vuelve el CSS responsive, su posición/versión y la presentación pública del palet. No afecta bases, datos ni permisos. Mantener BETA exclusivo de desarrollo.

Resultado de regresión completa:49/50 pruebas JS pasan en ejecución paralela. `stock-export-ui.cjs` detectó2 consultas de resumen en lugar de1; al repetirla aislada pasó. No se cambió lógica de sincronización/consultas en esta entrega; revisar ese comportamiento temporal si se reproduce en campo. Las dos vistas PHP pasan lint. La nueva matriz responsive pasó dentro de la regresión completa.
