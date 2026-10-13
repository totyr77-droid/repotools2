# Cargas visuales — 13/10/2026

## Análisis y alcance

PHP entrega la estructura y datos de Cuenta y TOPs en la respuesta inicial. Un loader de JavaScript no puede representar la espera anterior a recibir ese HTML; no se añadió una pantalla artificial al entrar. La navegación AJAX conserva la página actual mientras pide la siguiente. Stock, Biblioteca, Moderación y páginas públicas consultan información después de recibir su estructura. Las fichas de producto ya tienen errores recuperables y Reintentar. Se respetan esos flujos, APIs, permisos y comportamiento offline.

## Archivos y presentación

- `js/loading.js`: `RepotoolsLoader.start(elemento, opciones)`, devuelve cierre con cancelación inmediata; estados independientes por componente, limpieza offline/pagehide y límite visual de 20 segundos. No intercepta fetch ni cambia respuestas.
- `css/loading.css`: barras SVG equivalentes mediante CSS, skeletons neutros, indicadores locales, botón sin cambiar su ancho y pantalla oscura con isotipo existente. El logo no rota. `css/navigation.css` retira el indicador inmediato anterior.
- Encabezado/pie: CSS/JS central antes de los consumidores y versiones de caché actualizadas.
- `js/navigation.js`: carga local sobre contenido existente; cambios de categoría TOPs usan skeleton sobre tabla/estado vacío y dejan pestañas visibles. Fullscreen queda disponible únicamente si no existe main útil; no se fuerza en páginas normales.
- `js/product-info.js`: indicador dentro de la ficha, conservando datos, errores y Reintentar. Aplica en Inicio/Biblioteca/Stock.
- `js/reports-page.js`: skeleton de reportes/registros, detalle local y botón al revisar. No cambia decisiones ni permisos.
- `js/stock.js`: skeleton de lista propia y botón en operaciones que ya bloqueaban doble envío. No altera reclamación, cantidades, búsqueda ni cámara.
- `js/public-pallet.js`, `js/stock-branches.js`: skeleton/local en palet público y stock compartido, conservando refresco, paginación y revocación.
- `js/biblioteca.js`: skeleton inicial en el bloque de colecciones.

## Reglas y accesibilidad

Demora 180 ms; si termina antes no aparece. Mínimo visual de 300 ms cuando sigue montado; los resultados y controles se habilitan inmediatamente, sin esperar ese mínimo. Si un render reemplaza el contenido, prima el resultado sobre mantener un skeleton artificial. Cancelación/navegación/offline limpian inmediatamente. Un indicador nunca atrapa el puntero; no bloquea menús ni escáner. Los botones conservan el bloqueo de operaciones existente. `aria-busy`, `role=status`, `aria-live=polite`; barras decorativas ocultas al lector. `prefers-reduced-motion` desactiva animaciones.

## Validación

`tests/loading.cjs` comprueba demora, mínimo visual, superposición/cancelación de peticiones, restauración de botones, error/finally, desconexión, limpieza, 390/1366 px y movimiento reducido. Pasan también product-info, navigation, navigation-timeouts, stock-branches-ui, share-links-ui y moderation-branch-map. PHP lint de vistas y sintaxis JS correctos. Las APIs y mapas externos están simulados en las pruebas que lo requieren. No implica cámara física, Safari ni rendimiento de Render.

## Observaciones y reversión

No se añade shimmer en contadores pequeños, ni loader de sesión ficticio, ni cambios de períodos TOPs inexistentes. Filtros de Stock, edición de cuentas y cámara conservan los estados existentes; no se les añade carga por defecto. Queda probar visualmente con red móvil real y Safari. El límite de 20 segundos retira el indicador visual; no cancela ni inventa el resultado de una petición: errores y reintentos siguen en sus flujos originales.

Revertir los archivos enumerados desde `17e9b98`, retirar loading.js/loading.css y su prueba. No hay migraciones, cambios de datos, service worker ni nuevas dependencias.
