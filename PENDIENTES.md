# Continuidad de Repotools

## Rama de trabajo — 13/10/2026

IMPORTANTE: distintivo BETA junto al logo exclusivo desarrollo, css/development.css e integración en vistas/header.vista.php. Por pedido explícito NUNCA llevar este distintivo a main; excluir este commit y sus fragmentos al publicar o fusionar mejoras futuras. Servicio de beta confirmado: https://repotools-desarrollo.onrender.com/ . Portada y recursos públicos verificados; mantener el marcador exclusivamente en desarrollo.

Entorno de prueba preparado: nuevo proyecto Neon vacío repotools-desarrollo (`square-bonus-56273687`) y render-desarrollo.yaml, exclusivamente servicio nuevo Free en rama desarrollo. El original rechazó schema-only por límite de ramas; no se borraron ramas ni copiaron datos. La preparación de Blueprint y APP_URL descrita aquí es histórica; el servicio de beta ya existe. No inferir ni modificar variables privadas desde esa comprobación pública. Pasos y validación pendiente en docs/DESARROLLO-RENDER.md. No copiar DATABASE_URL/grupos privados de producción.

Por pedido del usuario, continuar en la rama `desarrollo`, creada desde `main` en `3014899`. Los nuevos commits y pushes deben ir a `desarrollo`. No fusionar ni publicar esos cambios en `main` hasta que el usuario lo indique explícitamente. El servicio público debe conservar su rama `main`; una prueba remota requerirá un servicio independiente, sin cambiar el despliegue actual ni usar datos de producción para pruebas destructivas.

Antes de continuar, leer [ROADMAP.md](ROADMAP.md) para los pendientes actuales y [ARCHITECTURE.md](ARCHITECTURE.md) para entender el proyecto.

Entregas y validaciones en [CHANGELOG.md](CHANGELOG.md). Las notas anteriores se conservaron completas en [docs/HISTORIAL-PROYECTO.md](docs/HISTORIAL-PROYECTO.md); son historial, no instrucciones actuales.

## Última entrega — 13/10/2026

Etiquetas nuevamente A4 vertical por pedido del usuario: logo/QR/número grandes, PDF210×297 y una hoja por palet. PNG/SVG/PDF comparten composición;1/2/6 páginas y seis QR renderizados pasan. Sustituye el horizontal; solo desarrollo. Archivos/pruebas/reversión docs/ETIQUETAS-VERTICAL.md; AirPrint/distancia físicos pendientes.

Escáner inteligentefd68948 publicado y verificado en beta (11 recursos/hash y OCR real sintético;56 regresiones validadas, Ajustes repetido con fixture corregido). QR + OCR de palets compartido en desarrollo: Stock y relayPC usan identificador normalizado; perfiles mantienen su validador y EAN conserva checksum/flujo. OCR local3 frames/worker, seguimiento, duplicados y cancelación; prueba física Safari/altura pendiente. Inventario, validación y reversión docs/ESCANER-INTELIGENTE.md. Main intacto.

Zoom de cámara ajustado a la mano hábil, abajo del visor; herramientas opuestas, horizontal táctil incluido y acceso1× compacto. Detalle/pruebas/reversión docs/ZOOM-DESLIZANTE.md. Solo desarrollo; cámara/Safari físicos pendientes.

Refinamiento etiquetas8b5cedc confirmado en beta (3 recursosHTTP200/SHA256 idénticos; Main intacto): A4 horizontal/PDF conservado, QR con quiet zone adaptable4 módulos y dígitos sin compresión. Pruebas1/2/6, QR y ceros iniciales; detalle/archivos/reversión docs/ETIQUETAS-HORIZONTAL.md. Safari físico/impresora/OCR a distancia pendientes. Solo desarrollo.

Cierre de lista de animaciones7e5cbf0 publicado y confirmado en beta (global2, HTTP200 y hashes iguales a Git): campos pequeños, salida de menús/notificaciones y confirmaciones sin demora lógica; global2/SW-v13. Pruebas/reversión docs/MOVIMIENTO-GLOBAL.md. Queda Safari/iPhone físico; no continuar funciones ajenas sin nuevo pedido.

Movimiento global0c15647 en desarrollo, desplegado: portada/JS/CSS betaHTTP200 y hashes correctos verificados por el pedido actual. indicadores deslizantes, microinteracciones, paneles/menús/diálogos/navegación y offline; reducido/hand/cleanup. Cámara y mapas excluidos. Pruebas/reversión docs/MOVIMIENTO-GLOBAL.md. Safari físico pendiente.

Etiquetas A4 horizontal y PDF directo iPhone en desarrollo: una hoja por palet, QR/número/logo negros, avisos completos. HTML/PDF1/2/6 y seis QR renderizados validados; Safari/AirPrint/distancia físicos pendientes. Detalle/reversión docs/ETIQUETAS-HORIZONTAL.md.

Zoom continuo en desarrollo: rango/paso real, input en vivo, cola último valor y accesos compatibles; mínimo1× conservado. Pruebas/reversión docs/ZOOM-DESLIZANTE.md. Safari/cámara real pendiente.

Responsive profesional en desarrollo: capa compartida al final, Inicio/Stock/Biblioteca/Ajustes/modales adaptados, controles de cámara según mano y palet público compacto. Pruebas y reversión en docs/RESPONSIVE-2026-10-13.md. Safari físico pendiente; funciones/permisos intactos.

Nueva entrega puntos4–8 en desarrollo: sincronización383453d, CSVfa06e57, resumenStockec57fb9; pushes separados confirmados. Regresión49JS+20PHP+PostgreSQL temporal pasa. Auditoría midió producción pública y Neon; corrigió loader exteriorcdf70e6 y versiones migraciones5ba72e2 (27→1 consulta, ~84% menor comprobación remota). Informe, archivos, reversión y checklist físico: docs/AUDITORIA-2026-10-13.md. Main intacto, BETA exclusivo desarrollo. URL beta confirmada por el usuario; omitir comprobaciones remotas por ahora. Falta medición autenticada dentro Render, Safari/Android/Excel/cámara reales. Esta autorización reemplaza los límites antiguos No implementar puntos4/5/7.


Punto3 Actividad del palet en desarrollo: captura backend/transacción/stock_operations, migración27 tabla actor/metadata mínima e índice palet+id, GET privado25+cursor, lista plegable sin modal. Creación/productos/cantidades/vencimientos/colaboradores/descripción; nombres públicos dinámicos, sin IDs personales/email. UI/permisos/retry offline/pruebas de regresión pasan; PostgreSQL esquema temporal validó26/27, fechas, FK SET NULL y rollback. docs/ACTIVIDAD-PALETS.md. No implementar Puntos4/5/7. Pendiente campo Safari/beta real.

Punto2 TOPs2.0 autorizado en desarrollo: Semana/Mes/Histórico, Códigos distintos/Escaneos totales/Reportes aprobados, public_name seguro sin IDs/nombre privado, actualización local, top50+posición propia, migración26 índices por fecha. Pruebas tops.php/tops-ui pasan; docs/TOPS-2.md. Aportes pospuesto por duplicar métrica de aprobaciones. Antes de Punto3 confirmar push de Punto2. PostgreSQL/Safari beta pendientes.

Solo desarrollo: número del palet ampliado a2040px de ancho disponible, fuente360px y ajuste SVG, centrado entre título y texto inferior. PDF1/2/5 páginas, límites del número sin superposición y lectura QR pasan; impresión múltiple pasa. Mismos SVG/PNG; no cambia QR ni A4. Revertir commit de esta entrega, conservando configuración de desarrollo.

Resumen de ejemplo de Stock más compacto: números18px, etiquetas12,8px, menor padding y separación; ancho máximo640px en PC, dos columnas móviles conservadas. Solo CSS de demo y versión del recurso; guest-demo pasa320/375/390/430/1366 y revisión visual390/1366. Revertir css/guest-demo.css y versión header a e857d9c; no cambia datos ni Stock real.

Etiquetas de palets ajustadas a una sola hoja A4: margen de impresión8mm y alto280mm evitan desbordamiento por redondeo; salto solo entre etiquetas. Logo original06_horizontal_blanco_transparente integrado y filtrado a negro, QR vectorial grande y código200px; texto cercano legible. PNG para compartir reutiliza la misma composición. PDF Chrome con1/2/5 etiquetas produce1/2/5 páginas y QR decodificado correcto; selección y permisos anteriores pasan. Probar impresora/Safari reales; detalle y reversión en docs/IMPRESION-PENDIENTES.md.

Ajustes terminado: Perfil/Preferencias/Accesos y Seguridad conservada, sonido/vibración con switches y días con autosave. API preferencias existente ampliada, users.sound_enabled/vibration_enabled default1 (migración25); días siguen en stock_preferences, mano en users. Principal Normal/Rápido y su lector remoto respetan opciones, tono/háptica existentes sin reimplementación; otros lectores intactos. Pruebas SQLite/PostgreSQL aislado, UI360/390/430/PC, sesiones, ON/OFF, ausencia de Vibration API y regresiones pasan. Validación física/Render pendientes; archivos y reversión en docs/AJUSTES-PREFERENCIAS.md. No continuar TOPs2.0.

Impresión múltiple en Pendientes cuando hay más de una etiqueta: selección inline, Todos/Ninguno, Cancelar e Imprimir con contador. Reutiliza etiqueta.php y A4 existente, hasta100 por lote; permisos de cada etiqueta siguen en servidor. Prueba pending-label-print pasa con dos hojas, impresión simulada,403 ajeno,320/390/1366 y fallback de una etiqueta. Reversión y validación física pendiente en docs/IMPRESION-PENDIENTES.md.

Inicio unifica Normal/Rápido con el celular remoto: transmite el contexto al lector, oculta el segundo control continuo y mantiene/cierra la cámara según el modo de PC. Cada apertura principal empieza en Normal. Resultados rápidos con altura reservada y acciones compactas; Normal al alcance de la mano elegida solo en móvil. Pruebas main-rapid-scanner, device-connection-ui, scan-result y ean-scanner pasan; reversión y límites en docs/ESCANEO-RAPIDO.md.

Punto6: Normal/Rápido solo en Inicio, misma lista y stream, contador de sesión, filtro repetido configurable exclusivo principal1500ms y Finalizar. Pruebas20 EAN/cámara única/duplicados/Normal/otros lectores y revisión320zurdo/390/1366: docs/ESCANEO-RAPIDO.md. Probar Safari/cámara reales y Render. No avanzar Punto7 sin nuevo pedido.

Pitido con mayor amplitud0,45 y vibración60ms al confirmar si navigator.vibrate está disponible; Safari/iPhone sin soporte de esa API. ean-scanner pasa. Probar altavoz, música y háptica en dispositivos reales; docs/PITIDO-IPHONE.md.

Registro pide Nombre y apellido en un único campo, ejemplo Juan Pérez, con al menos dos palabras y máximo80 caracteres validados en servidor. No obliga a modificar cuentas existentes. public-names-ui y lint pasan. Revertir cuenta.php y extensión de prueba a96110df; sin migraciones ni cambios de nombre público.

Demo de visitantes explícita con aviso amarillo, etiqueta DEMO y palets/lista de ejemplo. Pitido: playback solo durante el tono y restauración posterior; ambient silenciaba iPhone. Probar iPhone real en silencio y con música; no garantizar mezcla. Pruebas/reversión en docs/DEMO-Y-AVISOS.md y docs/PITIDO-IPHONE.md.

Transición visual RT desde el head, cobertura continua AJAX, espera de fuentes/UI y salida220ms con límite5s. Se conserva carga local de datos. Causa, pruebas, excepción en red muy lenta y reversión: docs/TRANSICIONES.md. Confirmar Safari/BFCache real y Render.

Acceso a reportes protegido en servidor (login/403, APIs401/403/IDOR/CSRF), configuración pública mínima para enviar reportes sin cuenta. Demo independiente Stock/Biblioteca para visitantes, sin datos reales ni operaciones. Avisos EAN/QR/fotos en capa superior y confirmación en PC. Pruebas y reversión: docs/ACCESO-REPORTES.md y docs/DEMO-Y-AVISOS.md. Safari/Render pendientes; suite antigua reports-api usa políticas anónimas anteriores.

Nombre público separado, migración 24, edición en Mi cuenta y presentación dinámica en TOPs/palets/stock compartido/colaboradores/invitaciones. Auditoría previa: 6 cuentas pendientes, sin colisiones; validación PostgreSQL aislado y SQLite en docs/NOMBRES-PUBLICOS.md. Confirmar migración en Render y Safari real. Mantener name privado y relaciones por ID.

Font Awesome Free 6.7.2 local, Solid/Brands y precache shell-v10. Mediciones locales, inventario, validaciones y reversión en docs/ICONOGRAFIA-LOCAL.md. CDN anterior bloqueado en el entorno: no hay comparación fiable de latencia; medir Render/Safari real y cabeceras Apache.

Avisos flotantes de foto/EAN/QR enviados y solicitud de foto compacta. Cancelación desde ambos dispositivos limpia solo esa petición y conserva el vínculo. Validación/reversión en docs/AVISOS-ENVIO.md; cámara física/Safari pendientes.

Introducción opcional de cuatro pasos en Inicio y replay en Configuración, localStorage por identidad/navegador. Pruebas, limitaciones y reversión en docs/INTRODUCCION.md; Safari real pendiente. No hay migraciones ni cambios funcionales de escáner/Stock.

Preview social 1200×630, metadatos centrales y canonical depurado: docs/PREVIEW-SOCIAL.md. Pruebas locales pasan; verificar acceso público tras Render y caché de plataformas. Pitido conservado: usuario confirmó iPhone en silencio; probar sonido con silencio desactivado y música, sin prometer mezcla física.

Aviso «Modo sin conexión preparado» flotante superior durante 2,4 segundos; entra y sale sin desplazar contenido. Detalles y reversión en CHANGELOG.

Cargas visuales centralizadas: demora 180 ms, skeletons/locales en consultas y estados de botón; navegación conserva contenido útil. Pruebas y alcance en docs/CARGAS-VISUALES.md. Pendiente validación visual con Safari y red móvil reales; no se optimizaron tiempos de servidor ni se modificaron APIs.

Mapa de sucursales primero en Stock/Moderación, sugerencias de tres en tres y confirmación siempre visible. Centrar/quitar ubicación con iconos dentro del mapa; quitar conserva el mapa abierto. Pruebas Stock/Moderación, permisos, guardado y320/390/1366 pasan con Chrome/SQLite temporal. Reversión en CHANGELOG; no hay migraciones. Se conserva la entrega anterior de presentación/PWA y su validación.

Al retomar: probar instalación y modo avión en teléfono real, verificar despliegue/cabeceras Render y configurar Geoapify. Detalles y demás pendientes en ROADMAP. No afirmar puntuación objetiva ni compatibilidad física sin pruebas.

Mantener minimalismo, SVG, comodidad móvil, preferencia de mano y adaptación PC. Validar permisos en servidor, conservar cambios locales y documentar validación/reversión. No publicar credenciales, datos privados ni bases.

Actualización posterior: créditos discretos y cuerpo del mapa sin colapso; Safari real pendiente. Continuar la medición Render/Neon de docs/RENDIMIENTO.md antes de optimizar.

Entrega retomada: fotos desde celular vinculado a PC, evidencia EAN temporal y cancelación segura (migración aditiva 23; docs/FOTOS-PC-CELULAR.md). Enlaces nuevos /s/token, compatibilidad anterior, contexto formal y controles alineados (docs/ENLACES-CORTOS.md). Pruebas locales pasan; cámara física, Safari y despliegue Render por verificar. La prueba antigua de reportes conserva un fallo de fixture por el límite de una sesión PC; no se declara toda la suite aprobada.

Punto4 autorizado por el nuevo pedido: indicador de sincronización Stock/Biblioteca, mismas colas e idempotencia. Pruebas offline/retry/actividad pasan; docs/SINCRONIZACION-VISIBLE.md. Continuar puntos5–8 en desarrollo, uno por commit y push; Safari físico pendiente.
Punto5: CSV Stock/Palets/Vencimientos, permisos servidor, BOM/semicolon, protección de fórmulas y generación incremental. Pruebas backend3001filas/UI360–1366 pasan; docs/EXPORTACIONES.md. Excel/Safari reales pendientes. Detectado loader inicial de palets sin cierre en flujo sin invitaciones: revisar en auditoría punto8.
Punto6: resumen Stock4 métricas + últimos5 movimientos plegados, alcance propio/aceptado, una petición, helper de actividad reutilizado; docs/RESUMEN-STOCK.md. Pruebas ceros/300palets/fronteras/privacidad/UI/error pasan. Seguir regresión7 y auditoría8 sin nuevas funciones.

## Offline inteligente — beta, 2026-10-13
Detección real red/servidor/sesión, badge del logo y sincronizador compartido. Colas IndexedDB v3 conservadas por usuario, revisión de conflictos e idempotencia Stock/Biblioteca. Copias sanitizadas con fecha, QR/OCR global preparado y palets desconocidos requieren conexión. Sin migración SQL. Alcance, pruebas y reversión segura (no bajar IndexedDB a2): docs/OFFLINE-INTELIGENTE.md. Safari y cámara reales pendientes; listas EAN online/offline siguen separadas.

Entrega offline verificada en beta el2026-10-14:1392b3d + ff6592d, hashes públicos/plantilla/health correctos y shell reabierto sin red en Chrome390px. Regresión57:54 iniciales y3 fallos corregidos/repetidos; OCR real cacheado sobre video sintético, privacidad, conflictos e idempotencia. Continuar Safari físico/modo avión/red intermitente/almacenamiento; listas EAN online-offline separadas. Main intacto. Detalles y reversión IndexedDBv3: docs/OFFLINE-INTELIGENTE.md.

Sincronización silenciosa (beta,2026-10-14): carga/comprobación normal sin avisos; éxito automático sin toast; historial consultable con botón neutro; estado sincronizando solo con pendientes procesables. Colas y servidor intactos. Pruebas y reversión en docs/OFFLINE-INTELIGENTE.md. Mantener pruebas físicas Safari pendientes.

Historial general Stock (beta,2026-10-14): resumen encima de Escanear palet; Últimos movimientos abre ventana paginada25con ficha individual Antes/Después. Alcance propio/compartido aceptado, incluyendo eliminados, sin listado indiscriminado admin. Permisos revalidados en cada ficha; misma tabla, sin migración. Validación/reversión: docs/HISTORIAL-STOCK.md. Safari físico pendiente.

Confirmaciones reales refinadas (beta,2026-10-14): toast verde de2,4s únicamente tras cargas confirmadas Stock/Biblioteca, también al reconectar; no se repite al cargar/navegar ni por historial. Sustituye el silencio de éxito de la entrega anterior por pedido del usuario. Pruebas/reversión en docs/OFFLINE-INTELIGENTE.md; colas/servidor intactos.

Palet compacto/restas (beta,2026-10-14): metadatos arriba de productos, acciones abajo según mano, Actividad/Último movimiento abren visor por palet; edición de sucursal solo desde Sucursal. Alta+vencimiento inicial unificados; pares históricos coincidentes agrupados sin borrar registros. Restar por EAN/lote/unidades/cajas/bultos, online con permisos/revisión/idempotencia/límites; agotado elimina solo ese lote, no el palet. Pruebas/reversión en docs/PALET-COMPACTO-Y-RESTAS.md. Safari/cámara/PostgreSQL físicos pendientes.

Usar palet/sucursal opcional (beta, 2026-10-14): activar sin selector ni sucursal; botón Sin sucursal, Buscar primero/Mapa segundo. Al asignar sucursal compartida consultar inclusión, persistida en todos los enlaces activos propios mediante exclusiones existentes. Servidor exige elección, revisión/propiedad/transacción y reintento consistente. Sin migraciones. Validación y reversión: docs/USAR-PALET-SUCURSAL.md; Safari físico pendiente.

Cámara compartida QR/EAN (beta, 2026-10-14): cameraViewport y cameraControls reutilizados en Inicio Normal/Rápido, Biblioteca, Stock QR/OCR/EAN, Usuarios QR, celular-PC y offline. Cola/propietario global impide cámaras simultáneas; dimensiones comunes, guías por modo, seguimiento solo con coordenadas reales, cleanup de tracks/controles/observador. Motores y callbacks conservados. SW shell-v19. Pruebas/reversión y límites físicos: docs/CAMARA-COMPARTIDA.md. Probar Safari/Android cámara real, foco/zoom/linterna y códigos inclinados.

Barra del palet refinada (beta, 2026-10-14): editar/restar/borrar por producto, resta selecciona lote exacto; +EAN/−EAN juntos a izquierda según pedido, tuerca/sucursal/historial a derecha; menú abre arriba y metadatos compactos sobre separación de mercadería. Misma API de resta/historial, sin cambios de permisos ni SQL. Validación/reversión: docs/PALET-COMPACTO-Y-RESTAS.md. Safari físico pendiente.

Guía EAN (beta, 2026-10-14): ocultar recuadro fijo mientras exista seguimiento con coordenadas válidas; restaurarlo al perder detección/reset. Sin cambios en reconocimiento/zoom/QR. SW shell-v20; regresiones de cámara/EAN/zoom aprobadas. docs/CAMARA-COMPARTIDA.md.

Avisos/cargas recuperadas (beta, 2026-10-14): RepoToast global con cola/dedupe/prioridad/3–6s/X/capa superior; estados transitorios flotantes, validaciones/prompts conservados. Icono reservado junto a campana solo recuperadas Stock/Biblioteca, 10min sin abrir/3s tras cerrar, sin bloque fijo. Exportar y Buscar palet redundante ocultos; métricas una fila. Historial de todos los actores autorizado, invitaciones pendientes y sucursal añadidos, alias públicos. Sin migraciones; SW shell-v21. Pruebas/reversión: docs/TOASTS-Y-CARGAS-RECUPERADAS.md; Safari/PWA físico pendiente.


Integración completada por autorización explícita del usuario (2026-10-14): Main5c7430ade921e61aef60f435384b1edb30e5e351 integra desarrollo5678d58 con correcciones de expectativas de tests; 63 CJS aprobadas tras repetición secuencial, 23 PHP funcionales y 99 PHP lint. Producción repotools.online verificada HTTP200/6 assets coincidentes/sin BETA; beta HTTP200 conserva BETA. Worktree de integración tmp/release-main; checkout habitual tmp/upstream sigue desarrollo. Continuar cambios nuevos en desarrollo salvo nueva autorización de publicar Main. Reversión: docs/INTEGRACION-MAIN-2026-10-14.md. Safari/impresora/cámara físicos pendientes.

## Carga y resta compactas — beta, 2026-10-14
Vencimiento opcional primero tras EAN fijo; búsqueda visible arriba con estados buscando/encontrado/sin información/error. Referencia personal sigue en pallet_products.name, no reemplaza catálogo compartido. Icono SVG sustituye prefijo EAN en textos independientes de información. Calculadora compartida agregar/restar con botón44px junto a cantidad y popover sin desplazar formulario, mano izquierda en móvil y derecha por defecto PC; presentación alineada. No cambios SQL/permisos/reconocimiento. Validación: PHP/JS sintaxis, pallet-deduct-ui (cálculo agregar/restar, búsqueda pendiente/sin información, referencia conservada, icono,360–1366, permisos/límites/idempotencia), global-toast-ui. Safari/cámara/teclado reales pendientes. Revertir commit de esta entrega en desarrollo; conservar cola local y esquema. Main intacto.

## Altas offline automáticas — beta, 2026-10-14
Las altas de mercadería pendientes se suman automáticamente aunque cambie la revisión general del palet: operación aditiva bajo mutex/transacción, conserva nombre existente y demás lotes. Mantiene identidad ID+código, cuenta original, permisos actuales, validación e idempotencia; edición/resta no se habilitan offline. Reintenta conflictos antiguos solo si coinciden con el aviso histórico de revisión general; otros conflictos/permisos siguen requiriendo atención y no se descartan. Resultado reutiliza recibos recuperados del icono del header. Campana al extremo de mano dominante en móvil; escritorio conserva orden. Pruebas stock-api, offline-add-auto, sync-receipts-ui y sintaxis PHP/JS aprobadas. No migraciones. Revertir commit de esta entrega en desarrollo; conservar IndexedDB. Safari físico pendiente. Main intacto.

## Producto pendiente visible — beta, 2026-10-14
Guardar alta offline cierra formulario tras IndexedDB, muestra fila amarilla Pendiente de enviar con referencia/EAN/cantidad/vencimiento dentro del palet y libera botón sin esperar conectividad. Envío online en segundo plano; fila desaparece al confirmar/discardar y vuelve desde cola al abrir copia del palet, sin alterar snapshot de servidor ni duplicar cantidades. Sin botones de edición/resta sobre filas pendientes. Catálogo compartido intacto: nombre offline solo referencia del palet, no crea ni actualiza products; sincronizar mercadería sigue guardando unidades/referencia en pallet_products. Pruebas pallet-deduct-ui ampliadas con carga offline real3unidades, cierre/botón liberado/fila visible/cola/envío/no entrada de catálogo; regresión stock-offline-ui. Sintaxis JS aprobada. Revertir commit en desarrollo sin borrar IndexedDB. Main intacto; Safari físico pendiente.

Últimos movimientos discreto (beta,2026-10-14): botón solo texto, sin iconos/flecha, fondo transparente y área táctil44px. Mismo acceso al historial/mano/permisos. PHP lint aprobado. Revertir commit de esta entrega; Main intacto.

## Sincronización estable al cerrar — beta,2026-10-14
Corta ciclo offline: marcar deferred solo una vez evita queue-changed/comprobación/escritura repetidas. Filas pendientes se conservan si sus datos/errores no cambiaron, sin reconstrucción constante. Actualización automática del palet no llama showModal; cerrar invalida respuestas en curso para impedir reapertura. Colas/reintentos/permisos/idempotencia conservados. Pruebas offline-add-auto (una escritura offline, sin ciclo), pallet-deduct-ui (cerrar durante respuesta demorada no reabre), stock-offline-ui y JS syntax. SWv25. Revertir commit en desarrollo sin borrar IndexedDB. Main intacto; Safari físico pendiente.

Calculadora amplia móvil (beta,2026-10-14): popover usa alto útil del visualViewport con márgenes/áreas seguras, teclas distribuidas en4filas, cantidad visible y Listo abajo44px+. Posición según mano móvil, actualizada al cambiar preferencia; PC compacto. Teclado se oculta al abrir; viewport reajusta sin reiniciar lógica. Agregar/restar comparten control. JS syntax y pallet-deduct-ui aprobadas (alto800+ en390, ancho sin overflow, ambos lados, cálculo8×5/2×2, offline y cierre). Revertir commit; sin SQL ni Main. Teclado/Safari reales pendientes.

Metadatos del palet compactos (beta,2026-10-14): último movimiento recibe dos tercios del ancho, menos padding y separación. Nombres visibles máximo9caracteres Unicode: nombre hasta6+espacio+inicial+punto, nombre único largo8+elipsis; empresa conservada, nombre completo en title. Aplica propietario/último actor/reserva dentro de ficha, no cambia identidad almacenada ni datos públicos. JS syntax/regresión pallet-deduct-ui móvil360–430 y PC1366. Revertir commit; Main intacto.

Publicación autorizada completada: Main79a814099b45e4d8bac541b13cab29715858354a integra desarrollo3ca504f; conflicto solo documentación resuelto conservando entregas. Código funcional comparado con beta probada; PHP lint4 y stock-api/offline-add-auto aprobadas. Indicador BETA excluido de Main. Checkout habitual desarrollo conservado; próximos cambios solo beta. Revertir merge con git revert -m 1, conservar colas/datos. Verificar Render y Safari físico.

Navegación móvil/subnav (beta,2026-10-14): barra fija68px+safe-area, panel92% fuera del flujo conectado al activo, animaciones/cierre4s/hand/limpieza AJAX. Misma navegación y controles, opciones según visibilidad/permisos; búsqueda visual en colección. Desktop intacto. Pruebas geometría/interacción/navigation/timeouts/responsive7páginas10anchos aprobadas; Safari/PWA/teclado/VoiceOver físicos pendientes. Alcance/reversión: docs/NAV-MOVIL-SUBNAV.md. SWv27; publicar solo desarrollo, no Main.

Detalle del palet en filas (beta,2026-10-14): propietario y último movimiento al100% en dos filas compactas, iconos/tipos/colores existentes; nombres completos permiten varias líneas. Sucursal debajo del título, usa short_name si llega definido y name como respaldo; hoy no existe campo abreviado persistido, no se inventa. Sin sucursal línea hidden sin espacio. Descripción/acciones/permisos intactos. Regla nav limitada a pestañas principales Stock/Reportes para no ocultar pestañas del selector de sucursal. Prueba pallet-deduct-ui:360–1366 ambos lados, nombre largo, filas/ancho, con/sin sucursal y fallback nombre completo/corto; sintaxis PHP/JS. Safari físico pendiente. SWv28. Revertir commit de esta entrega en desarrollo; no SQL ni Main.
