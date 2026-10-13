# Continuidad de Repotools

## Rama de trabajo — 13/10/2026

Por pedido del usuario, continuar en la rama `desarrollo`, creada desde `main` en `3014899`. Los nuevos commits y pushes deben ir a `desarrollo`. No fusionar ni publicar esos cambios en `main` hasta que el usuario lo indique explícitamente. El servicio público debe conservar su rama `main`; una prueba remota requerirá un servicio independiente, sin cambiar el despliegue actual ni usar datos de producción para pruebas destructivas.

Antes de continuar, leer [ROADMAP.md](ROADMAP.md) para los pendientes actuales y [ARCHITECTURE.md](ARCHITECTURE.md) para entender el proyecto.

Entregas y validaciones en [CHANGELOG.md](CHANGELOG.md). Las notas anteriores se conservaron completas en [docs/HISTORIAL-PROYECTO.md](docs/HISTORIAL-PROYECTO.md); son historial, no instrucciones actuales.

## Última entrega — 13/10/2026

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
