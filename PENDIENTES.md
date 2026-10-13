# Continuidad de Repotools

Antes de continuar, leer [ROADMAP.md](ROADMAP.md) para los pendientes actuales y [ARCHITECTURE.md](ARCHITECTURE.md) para entender el proyecto.

Entregas y validaciones en [CHANGELOG.md](CHANGELOG.md). Las notas anteriores se conservaron completas en [docs/HISTORIAL-PROYECTO.md](docs/HISTORIAL-PROYECTO.md); son historial, no instrucciones actuales.

## Última entrega — 13/10/2026

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
