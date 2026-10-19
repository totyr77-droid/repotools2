# Revisión integral de UX — 1.11

Implementación sobre Main, conservando cámara, negocio, permisos y datos existentes.

## Cambios por solicitud

1. Inicio una mano: únicamente Escanear producto pasa de 52 a 104 px; Tu lista conserva su tamaño y el flujo reserva espacio para la navegación.
2. Calculadora: altura móvil máxima de 304 px, teclas de al menos 44 px, límite del viewport y scroll para alturas extraordinariamente reducidas. Misma lógica matemática y mano.
3. Controles Stock/Biblioteca: preferencia existente; posiciones/orden de acciones, sin invertir lectura. Se retiró alineación invertida de contenido en estilos compartidos.
4. Pendientes: seleccionar desde una etiqueta, Eliminar, confirmación, transacción por lote (máximo 100), validación de cada reserva y reintento con identificador estable. Si falla un permiso, se revierte todo.
5–9. Palet: mensajes separados de divisores, estado compacto, jerarquía de productos, encabezado y acciones fuera de la zona desplazable. Solo la lista de productos desplaza; validada con 100 productos.
6. Enlaces: acciones en una fila, con información en otra fila cuando falta ancho; orden editar cuando corresponde/ver/compartir/eliminar. Controles adaptados a la mano.
7. Público: menos padding/bordes, descripción vacía oculta, cantidades y fechas conservadas, estado legible y acceso secundario compacto. No se cambiaron datos públicos autorizados.
10. Sucursal: encabezado/cuerpo desplazable/footer separados; Guardar ocupa su propio espacio y no tapa campos. Mapa acotado; búsqueda y datos escritos se conservan en el formulario actual, sin otro asistente.
11. Actividad: tarjetas compactas con icono, actor/acción, palet/fecha y número de movimiento, scroll propio; lectura izquierda a derecha.
12. Estados: presentación reutilizada con SVG y variantes información/éxito/advertencia/error. Los mensajes siguen siendo texto accesible.
13. Accesos: email y envío alineados, envío ancho completo móvil y compacto PC, icono; miembros con nombre/email/estado en líneas claras y revocación existente.
14. Notificaciones: menor densidad vertical, icono/fecha/lectura conservados, diferencia leído/no leído; normalización UTC de fechas SQL sin zona.
15. Invitaciones: contador, palet/invitante, tarjeta accesible por teclado que abre el detalle autorizado, Aceptar/Rechazar independientes a la derecha y detención de propagación.
16. Alias p/s/b: ocho caracteres aleatorios de alfabeto 62, unicidad global y por destino, reintento de colisiones. Tabla aditiva `public_link_aliases`, migración 28. El controlador resuelve internamente al componente original, conservando URL corta, permisos y tokens anteriores. Compartir usa un helper común con fallback al enlace anterior si falla la creación. Dominio sin www en producción; beta/local conserva su origen.
17. Compartir: ventana con encabezado, lista con scroll y pie de configuración/crear separado; apertura reinicia scroll propio, nunca el del modal anterior. Pestañas y herramientas compactas.
18. Biblioteca: cuatro bloques rectangulares uniformes con icono/texto; distribución según mano, conservando acciones y espacio inferior.
19. Teclado global: `dialog-scroll.js` reutiliza VisualViewport para alto/offset reales, mantiene el campo activo visible, aplica scroll interno y limpia listeners/variables al navegar. Fuente móvil mínima 16 px. Sin desplazamientos fijos inventados para el teclado.
20. Colección compartida: nueva/agregada/no disponible; Ver en mi biblioteca selecciona la colección existente. No ofrece Agregar deshabilitado al estar importada, ni acción de importación en error. Singular/plural y lista compacta. Conservada transacción/índice único de importaciones.
21. General de moderación: métricas en dos columnas móviles, más columnas según espacio en escritorio, padding/tipografía compactos; permisos intactos.
22. Ajustes: Perfil → Accesos → Preferencias → Seguridad. Seguridad fuera de Perfil, último grupo, encabezado uniforme sin icono decorativo; botones de seguridad conservados.

## Validación

Datos exclusivamente temporales SQLite; sin modificación de inventario real.

- `tests/integral-ui.cjs`: orden de Ajustes, 100 productos con scroll/acciones fijas en 360/390/430/1366 y ambas manos; selección múltiple; alias y CSRF; viewport de teclado simulado de 350 px; colección ya agregada y cuatro bloques Biblioteca.
- `tests/public-link-aliases.php`: 302 alias, longitud/alfabeto/unicidad, colisión forzada, reutilización y tipo inválido.
- `tests/stock-api.cjs`: eliminación múltiple con rechazo atómico por permisos y reintento idempotente; regresiones de Stock/autenticación/cargas/invitaciones.
- `tests/share-links-ui.cjs`: ruta corta real mediante router equivalente, recursos públicos, cuatro botones en fila 320/390/1366 y ambas manos, compartir/copiar, revocación y enlace antiguo de 64 caracteres.
- `tests/stock-branches-ui.cjs`: creación/mapa/asignación, favoritos, edición, selección de palets y sucursal, exclusiones, permisos y revocación.
- `tests/pending-label-print.cjs`: selección, impresión, permisos y selección disponible para una etiqueta.
- `tests/one-hand-mode.cjs` y `tests/date-time.cjs`: cambio/restauración del modo y fechas PostgreSQL/SQLite con zona horaria.
- `tests/settings-ui.cjs`, `tests/list2-ui.cjs`, `tests/one-hand-workspaces.cjs`, `tests/responsive-workspaces.cjs`: preferencias, calculadora, navegación y siete secciones en diez tamaños, horizontal, móvil/escritorio.
- PHP: library-sharing, stock-branches, stock-notifications, schema-fastpath. Versionado: app-release.
- Capturas inspeccionadas: palet con 100 productos, Biblioteca una mano, acciones de enlace a 320 px, Ajustes, Reportes, General y vista pública. Las suites generan capturas adicionales de secciones y formularios.

No se dispone de iPhone/Android físicos: quedan pendientes Safari/PWA con teclado real, barras del navegador cambiantes, mapa con geolocalización real y uso prolongado táctil. Chrome emulado y VisualViewport simulado no equivalen a esas pruebas. La reescritura Apache se verifica además después del despliegue; el servidor PHP de pruebas usa un router equivalente.

## Archivos y reversión

UI compartida: css/ux-polish.css, css/hand-preferences.css, css/settings.css; js/dialog-scroll.js, js/one-hand-mode.js, js/ux-polish.js y js/notifications.js. Stock/Biblioteca: js/stock.js, js/stock-branches.js, js/biblioteca.js, stock.php, cuenta.php, vistas/stock-dialogs.vista.php, vistas/stock-branches.vista.php, includes/account-security-view.php. Enlaces: .htaccess, enlace.php, api/enlaces.php, includes/public-link-aliases.php, js/share-links.js, includes/database.php, includes/page-metadata.php, vistas/footer.vista.php y sw.js. APIs api/stock.php y api/biblioteca.php; pruebas y release.json.

Para revertir presentación, revertir los archivos UI de este commit y publicar un build superior. Conservar migración 28, tabla de alias y rutas/controlador si ya se compartieron enlaces cortos; eliminarlos rompería enlaces nuevos. No borrar colas offline ni tablas/datos. La eliminación confirmada de reservas es una operación real del usuario; no se revierte automáticamente con Git.
