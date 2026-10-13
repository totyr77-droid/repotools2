# Continuidad del proyecto

Antes de continuar, leer `PENDIENTES.md`, especialmente la última entrega y sus prioridades. No afirmar que la app alcanzó una puntuación objetiva sin pruebas de campo.

Mantener el diseño minimalista existente, iconos SVG, controles cómodos en móvil y adaptación a PC. Respetar la preferencia de mano del usuario. Los permisos deben validarse en el servidor.

Conservar los cambios locales del usuario y documentar qué se modificó, su validación y cómo revertirlo. No guardar credenciales ni datos privados en documentación o Git.

## Diseño y UX obligatorios

Repotools debe sentirse rápido, simple, limpio y predecible. Aplicar estos criterios a todos los cambios:

- Mobile first: herramienta de trabajo, lectura inmediata, controles táctiles cómodos y acciones accesibles con una mano según preferencia. Adaptar después a escritorio.
- Minimalismo: pocos elementos visibles, sin tarjetas innecesarias, decoraciones sin función, textos largos ni pantallas saturadas.
- Reutilizar componentes y el sistema de iconos existente antes de crear botones, cards, modales, tablas o iconos. Iconografía simple y consistente que apoye el significado; sin nuevas librerías ni estilos mezclados.
- Preferir menos pasos, menor fricción y carga cognitiva, comportamiento predecible y opciones comprensibles sin explicación. No elegir por rapidez de programación.
- Cada pantalla debe tener una acción principal clara, acciones secundarias menos destacadas, estados importantes visibles e información secundaria discreta. No destacar todos los botones por igual.
- Mostrar opciones avanzadas solo cuando hagan falta, mediante despliegue progresivo.
- Reservar modales para decisiones importantes, confirmaciones necesarias, riesgo de pérdida de datos o tareas que requieran foco. Para acciones simples, feedback inline, toast o actualización directa.
- Feedback inmediato en cada acción y errores claros: Guardado, Producto agregado, Sincronizado, Sin conexión, No se pudo guardar.
- Estados vacíos con mensaje corto, explicación mínima y acción clara; nunca una pantalla vacía.
- Mantener el sistema de carga: fullscreen solo cuando corresponda, skeletons de contenido, loaders locales y botones con estado de carga. No bloquear toda la interfaz innecesariamente.
- Conservar colores, tipografía, espaciados generales, estructura y navegación salvo que el punto solicitado requiera cambiarlos.
- Textos breves, naturales y directos en español rioplatense consistente.

Antes de cada commit revisar elementos innecesarios, pasos evitables, claridad de la acción principal, comodidad móvil, minimalismo, consistencia de iconos y posibles confusiones. Corregir cualquier problema encontrado antes de publicar. Integrar cada función como parte natural de la aplicación.
