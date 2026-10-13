# Sistema global de movimiento — 13/10/2026

Exclusivo desarrollo. Se mantienen identidad, rutas, preferencias, permisos, autenticación y operaciones. No se añaden librerías ni se cambia reconocimiento/cámara/audio/vibración.

## Componentes

- `css/motion.css`: tokens130ms micro,230ms selectores,220ms paneles/menús y260ms navegación; curvas compartidas. Botones, switches, formularios, disclosures, menús y diálogos, con soporte progresivo.
- `js/motion.js`: un indicador CSS por grupo (sin botones nuevos), vinculado a aria-selected/pressed/current o radio.checked. Normal/Rápido, mano, Stock, Moderación/pendientes/resueltos, colecciones, filtros de sucursal/compartir, períodos TOPs, navegación y offline. Se conserva el color seleccionado existente. Recalcula por cambios relevantes, resize y fuentes; geometría real, incluidas distribuciones según mano y scroll interno.
- Paneles/listados: entrada discreta del contenedor al actualizar datos o mostrar campos/estados; no anima todas las filas. Limita repeticiones a una por300ms. Excluye vídeo/canvas/lector/mapas y estado de detección. Aperturas/cierres de detalles con altura nativa interpolada solo donde el navegador lo admite; no fuerza alturas de listados grandes.
- Diálogos: transición nativa de opacity/translate/display/overlay en navegadores compatibles; cierre lógico inmediato y sin interceptar clicks cuando open es falso. En otros se conserva apertura existente; los elementos retirados inmediatamente del DOM no esperan una animación.
- `js/navigation.js` y `js/page-loader.js`: una petición nueva reemplaza la anterior; la cola de navegación se libera al iniciar el fundido, sin esperar220ms adicionales. Conserva preparación de fuentes/UI y fallback/timeout. Corrige el riesgo de una promesa de fundido pendiente si otra navegación cancela su temporizador.
- Header/footer cargan recursos compartidos. Offline utiliza el mismo sistema; SW shell-v12 incluye ambos archivos. Las etiquetas impresas quedan sin efectos.

## Rendimiento, accesibilidad y compatibilidad

Sin bucle continuo ni animaciones por producto. MutationObserver acotado a atributos de selección/visibilidad; ResizeObserver agrupa mediciones en requestAnimationFrame. Se desconectan eventos/observadores y se cancelan animaciones al salir. prefers-reduced-motion quita transiciones y animaciones agregadas, incluso al cambiar la preferencia mientras la app está abierta. Los estados nativos, foco, áreas táctiles y uso de mano permanecen disponibles.

Las capacidades CSS avanzadas se protegen con @supports. Safari/iPhone físicos pendientes; no se garantiza que versiones antiguas animen altura/cierre. Las operaciones funcionan con su fallback inmediato.

Referencias primarias de soporte: https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/dialog y https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/interpolate-size .

## Pruebas

`motion-ui.cjs`: único indicador deslizante, selección/radios reales,360/430/1366, cierre síncrono, reveal, cámara excluida, movimiento reducido dinámico, limpieza y navegación liberada antes del final del fundido; reinicio de loader sin promesa bloqueada.

Regresión JS completa y lint de header/footer. Fixtures ajustados: onboarding incluye versión de introducción al marcarla vista; tests de modales distinguen open de la salida visual y esperan el palet abierto actual; zoom agrupa una ráfaga con primera respuesta retenida de forma determinista; altura del resultado permite menos de0,5px de redondeo, no deformación. Detalles del resultado final se anotan al cerrar entrega.

Capturas móviles/PC de Ajustes y workspaces revisadas. Queda prueba de gesto físico y Safari; no se usa base de producción.

## Reversión

Revertir el commit: quitar recursos/integación, devolver espera/guard de navegación y caché anterior. Sin migraciones ni datos a restaurar. Mantener BETA exclusivo desarrollo.

Resultado final: regresión completa53 suites JS ejecutada:51 pasan en el lote; Normal/Rápido falló solo por redondeo0,00006px (fixture corregido) y mapa de sucursales agotó una espera de renderizado en paralelo. Ambas suites repetidas tras finalizar el lote pasan; quedan53 suites verificadas, sin afirmar que todas pasaron en una única ejecución paralela. Lint PHP y comprobación sintáctica JS pasan. Ningún fallo conocido de operación permanece reproducible en estas pruebas.

Despliegue comprobado13/10/2026 después del push0c15647: portada pública de betaHTTP200, integra motion.css/motion.js con la versión correcta y mantiene BETA. Ambos recursos devuelven200 y su hash SHA256 coincide con los archivos publicados. El pedido actual de verificar despliegue reemplaza la indicación anterior de omitirlo; la revisión automática aceptó la comprobación al presentarse esa autorización. No se iniciaron sesiones ni se consultaron/modificaron datos privados. No sustituye una prueba física Safari.

## Cierre de la lista — 13/10/2026

Se completan salida de menús/notificaciones, expansión/contracción de campos pequeños y salida visual de confirmaciones creadas dinámicamente. CSS nativo con guardas: hidden/open cambian inmediatamente y los controles ocultos dejan de interceptar toques; valores y FormData intactos. `motion-field` se limita a unidades por caja/EAN, email administrativo, campos offline y avisos, excluyendo estados del lector. En navegadores sin interpolación/discrete se conserva feedback de entrada y cierre inmediato.

`RepoMotion.retire` conserva únicamente la carcasa cerrada de la confirmación mientras termina la transición, con inert y sin IDs duplicados; devuelve la decisión de inmediato. El fallback quita la carcasa al momento. Temporizador de seguridad280ms, sin retrasar operaciones ni una segunda decisión. No hay clones de campos ni cambios de datos/validación/permisos.

Recursos global2 y SW shell-v13. Pruebas motion-completion y motion-ui: alturas, menús, decisiones rápidas, valores de formulario, eliminación y movimiento reducido. Regresiones específicas Ajustes, sucursales/compartir, offline, edición administrativa y Normal/Rápido. Lint/syntax de archivos afectados. De la lista queda validación física Safari/iPhone; no se agregan funciones fuera del sistema de animaciones.

Reversión de este complemento mediante su propio commit, conservando el sistema base0c15647 y la documentación941a6ff.
