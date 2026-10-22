# Notificaciones push — v2.38

## Uso

Ajustes → Perfil → Avisos y respuesta → Avisos en este dispositivo → Activar avisos. El permiso se pide solo al tocar el botón; no se interpone con la introducción, ubicación o instalación. En iPhone/iPad se indica instalar en pantalla de inicio y abrir desde el icono (iOS/iPadOS16.4+). Si está bloqueado/no soportado, se explica el estado sin repetir solicitudes.

Incluye invitaciones a palets/equipos/supervisión/colecciones y solicitudes de edición de palets. Excluye insignias/referidos y demás avisos no autorizados para push. Vencimientos: un resumen diario por dispositivo desde las09:00 de Buenos Aires, sobre stock propio o acceso directo aceptado, con cantidades de lotes vencidos/hoy/próximos. No envía un aviso por producto. Si se activa después de las09:00, el resumen del día se prepara en la siguiente pasada.

Probar aviso prepara un envío al dispositivo actual, como máximo uno por minuto. Desactivar afecta solo ese dispositivo; la campana continúa. Recibir el push y abrir su destino no modifica read_at. Los destinos de solicitudes/invitaciones siguen revalidando permisos y requieren confirmación normal para aceptar.

## Servidor

Migración aditiva44: push_app_keys, push_subscriptions, push_jobs e índices. La clave VAPID se genera una sola vez al preparar la activación y permanece en PostgreSQL para sobrevivir a reinicios de Render. Solo se devuelve la clave pública. Claves privadas, endpoints y auth permanecen en DB; no se imprimen ni versionan.

Composer fija minishlink/web-push9.0.4 y dependencias en composer.lock, sin plugins/scripts al instalar. Docker instala vendor durante build; vendor se excluye de Git y del contexto de build y su acceso HTTP está bloqueado. El cifrado/firma Web Push los hace esa librería; no se implementa criptografía propia.

El mismo contenedor inicia scripts/push-loop.sh como www-data, una ejecución corta de scripts/push-worker.php cada60segundos. No necesita un segundo servicio pago ni una tarea de Codex. REPOTOOLS_PUSH_WORKER=0 permite detenerlo operativamente. Su ejecución CLI no es accesible por HTTP. No probar el worker con endpoints de terceros reales sin autorización.

Cada pasada prepara hasta100suscripciones autorizadas, procesa hasta50avisos nuevos por suscripción y hasta20envíos. No reenvía el historial previo a la activación. Cola con clave única por suscripción/evento, lease120s, reintentos espaciados hasta5intentos y TTL; respuestas404/410 deshabilitan endpoints vencidos. Un crash tras entregar y antes de confirmar puede reintentar; tag estable y renotify:false coalescen el aviso visible. No promete latencia exacta con backlog/red móvil.

Se verifica sesión vigente mediante hash del dispositivo en account_devices, expiración y auth_version. Salir, sustituir la sesión o revocar sesiones impide nuevos envíos de ese vínculo. Reasignar el endpoint al iniciar otra cuenta cancela pendientes anteriores. El worker también revalida que invitación/solicitud siga pendiente y que el aviso no se haya leído. Endpoints aceptados solo de servicios push conocidos por HTTPS, sin redirecciones; límites de tamaño/clave y CSRF en altas/bajas/prueba.

El service worker existente importa js/push-worker.js, verifica la cuenta/dispositivo mediante un recibo aleatorio antes de mostrar datos y descarta si la sesión no corresponde. Si esa verificación falla por red, solo muestra un aviso genérico sin datos de la otra cuenta. Navegación restringida al mismo origen/ámbito y destinos de la app. No hay segundo service worker ni caché de APIs privadas. El enlace de vencimientos abre esa vista aunque Stock recordara otros filtros, sin borrar borradores.

## Validación y límites

- PHP tests/push.php: migración repetible, claves persistentes, URL/SSRF, selección de tipos, cola/dedupe, resumen diario, nolectura, salida, cambio de cuenta, leído antes de enviar, endpoint410 y cifrado/firma reales con HTTP simulado.
- tests/push-worker.cjs: verificación, cuenta incorrecta, error de red genérico, navegación segura y ausencia de lectura automática.
- tests/push-ui.cjs Chromium/WebKit: activación voluntaria, APIreal/CSRF/SSRF, clave privada noexpuesta, prueba limitada, baja, guía iPhone y320–1366 ambasmanos/modos. Destino vencimientos desde vista previa distinta. APIs del dispositivo simuladas: no equivalen a recepción real de APNs/FCM.
- Migración PostgreSQL real y dedupe de jobs en tablas TEMP con ON COMMIT DROP, transacción en rama de pruebas; no modificación de tablas/datos reales. Generador: tests/push-migration-pg-query.php.
- Regresión settings-ui, Stock API, logout, stock-notifications, unified-core y audit-records. Composer audit sin avisos conocidos. Sintaxis shell del arranque/loop aprobada; no hubo Docker local disponible para construir la imagen.

Falta probar en iPhone/Android instalados con permiso real, pantalla bloqueada/app cerrada y envío desde Render tras despliegue. No se enviaron notificaciones reales durante estas pruebas. El sistema operativo puede demorar o bloquear avisos según sus ajustes/conectividad; no se garantiza recepción instantánea.

En Windows de desarrollo, ejecutar pruebas PHP con OPENSSL_CONF apuntando a la configuración válida de la instalación; en este entorno C:/xampp/php/extras/ssl/openssl.cnf. No cambia php.ini del usuario. Linux usa la configuración OpenSSL del contenedor.

## Reversión

Desactivar worker con REPOTOOLS_PUSH_WORKER=0 y revertir código publicando versión/cache superior. Mantener tablas aditivas44, claves VAPID, inventario y colas offline. No rotar/borrar las claves al desplegar ni al revertir, para no invalidar suscripciones existentes. No bajar REPOTOOLS_SCHEMA_VERSION ni borrar datos de usuarios.

Referencias primarias: [WebKit: Web Push iOS/iPadOS](https://webkit.org/blog/13878/web-push-for-web-apps-on-ios-and-ipados/), [MDN Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API), [librería PHP y requisitos](https://github.com/web-push-libs/web-push-php/tree/v9.0.4).


## Aviso dentro de la app (v2.39)

Para cuentas existentes y nuevas sin una suscripción activa en este dispositivo, ofrecer “Te avisamos por tu mercadería”. Activación con el mismo flujo autenticado/CSRF de Ajustes; permiso nativo exclusivamente por clic. No solicita permiso al entrar automáticamente. Ahora no/Escape posponen 30 días por usuario y navegador; desactivar voluntariamente desde Ajustes también pospone. Activación confirmada con toast. No cambia lectura de campana ni permisos del servidor.

Esperar introducción, carga, instalación/ubicación visibles y otros modales. No interrumpir cámara activa ni escritura en un campo. Excluir navegadores incompatibles, permiso bloqueado, iOS sin PWA instalada y dispositivo ya activado. Permanecen las instrucciones manuales en Ajustes. Sin migraciones ni dependencias nuevas.

Validación: PHP de vistas, sintaxis JS, push PHP y service worker; UI Chromium/WebKit con API real y permiso simulado, espera ante otro modal, ausencia de petición nativa automática, posposición persistente, activación desde el aviso y diseño 320/390/1366 para ambas manos. Captura móvil revisada. Recepción física sigue pendiente; estas pruebas no son envíos reales.

Revertir esta entrega y publicar una versión/cache superior. Conservar claves, suscripciones, trabajos push y datos del usuario; la clave local de posposición puede permanecer.
