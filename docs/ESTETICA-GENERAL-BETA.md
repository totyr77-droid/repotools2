# Estética general de beta

25/10/2026. Usuario autoriza extender la estética más cuidada a toda la aplicación únicamente en beta. Rama desarrollo; no trasladar este tema a main sin pedido explícito.

## Diseño

Hoja única css/beta-theme.css, activada mediante data-ui-skin=beta en el body. Tokens de color, violeta profundo con degradado suave en cabecera y acciones principales, superficies claras, bordes coherentes y sombras discretas. Botones secundarios lavanda, radios uniformes, títulos más definidos, inputs y foco coherentes. No se reemplazan fuentes, SVG, pantallas, jerarquía funcional ni navegación. No se agregan tarjetas o pasos a flujos existentes.

Aplica sobre componentes existentes de Inicio, Stock, Biblioteca, Moderación, Ajustes y Supervisión, así como diálogos/formularios comunes. Colores de cantidades/vencimientos/errores permanecen en sus componentes; sin nueva regla de color general sobre metadatos de palets. Botones destructivos conservan su tratamiento.

Selección activa contrastada en pestañas y navegación: respeta el indicador deslizante existente motion-selector y sus estados ARIA, sin duplicarlo. Transiciones breves en color/borde/sombra, sin animaciones continuas ni transformaciones nuevas de pantalla. Reducción de movimiento respetada. El modal Pro conserva su presentación propia al cargar su hoja después del tema.

No se modifican anchuras/alturas de contenedores, posición de flotantes, preferencia de mano, cámara/escáner, lógica de teclado, datos, APIs, permisos ni cobros. Los bordes de campos no afectan inputs de búsqueda envueltos por herramientas de producto. Distintivo BETA conservado.

## Validación

Fixture local con usuario ficticio, stock con fechas/cantidades, biblioteca y equipo. beta-theme-ui comprueba seis pantallas a320/390/1366px, ambas manos, navegación inferior/escritorio sin desborde, modal del palet/aviso Pro y movimiento reducido. Capturas antes/después en tmp ignorado; se esperan cargas/animaciones existentes antes de evaluar el resultado. Chromium/WebKit según resultados de PENDIENTES. Ajustes conserva prueba funcional de preferencias/guardado/CSRF. No pruebas físicas, medición de rendimiento ni cobertura de todas las combinaciones de datos.

## Reversión

Retirar el link css/beta-theme.css y data-ui-skin=beta de vistas/header.vista.php, publicar desarrollo con versión/cache superior. La hoja puede permanecer sin cargarse. No tocar bases, stock, sesiones, cola offline, permisos o configuración de pagos. Producción no incorpora esta entrega.
