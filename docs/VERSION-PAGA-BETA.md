# Preparación de versión paga — beta

Trabajo autorizado el25/10/2026, únicamente en desarrollo. Producción continúa en main. No contratar otra instancia ni activar cobros por esta preparación.

## Base y código

- Integrar main f6f0100/v3.09 en desarrollo, conservando distintivo BETA, favicon, sesiones móviles de manager propias de beta y numeración independiente.
- Se creó una base lógica beta y un rol exclusivo en la instancia Render existente. Estructura inicial79 tablas/45 migraciones. Por autorización posterior se copiaron cuentas/stock y datos de negocio desde una instantánea consistente:48 tablas/5.267 filas verificadas con hashes de contenido. El rol no tiene lectura/escritura ni creación de objetos en el esquema de producción. Comparte recursos de la instancia; no equivale a aislamiento de CPU o disponibilidad.
- Protección opcional de servidor mediante REPOTOOLS_EXPECTED_DATABASE y REPOTOOLS_EXPECTED_DATABASE_USER: una conexión de otro entorno se rechaza antes de abrir PDO. No guardar valores secretos en Git.
- Preservar configuración anterior y pruebas de aislamiento en carpeta privada externa. No volver a conectar beta a producción para revertir código.

## Decisiones confirmadas

1. Preparar los tres alcances: usuario, sucursal y empresa.
2. Solo planes y límites por ahora: sin precios inventados, pruebas gratuitas predeterminadas, cobros automáticos ni bloqueos de acceso.
3. Copia de cuentas y stock actuales, sin sesiones ni envíos reales. Excluidos sesiones/dispositivos, credenciales push, colas de envíos, recuperación/verificación de cuentas y enlaces privados compartidos. Las fotos externas conservan sus referencias, no se copian claves de almacenamiento ni archivos del proveedor.

Los precios, cuotas concretas, cálculo de consumo, asignación de planes, activación y restricciones quedan para otra decisión. No modificar condiciones ni acceso de usuarios de producción.

## Editor preparado en beta1.73

Moderación → Sistema → Planes y límites, visible y permitido solo a managers. Tres configuraciones iniciales (Usuario/Sucursal/Empresa), nombre y límites opcionales de usuarios/sucursales según alcance, palets activos y productos distintos. Campo vacío significa sin límite definido. Todos los límites son borradores: guardarlos no cobra, asigna planes ni bloquea cargas o accesos. En esa entrega todavía no había integración de pasarela ni suscripciones. La preparación privada posterior está en MERCADO-PAGO-BETA.md; continúa sin cobros reales ni aplicación de restricciones.

Migración46 aditiva con commercial_plans y configuraciones sin cuotas predeterminadas. API valida rol actual en servidor, CSRF, rangos/alcance y revisión optimista; registra antes/después en auditoría dentro de la transacción. UI conserva valores ante error y pide confirmación para cambiar de alcance con cambios sin guardar. Sin caché API ni nuevas dependencias. Reutiliza modal, SVG, alertas y campos existentes.

## Antes de activar beta aislada

- Copia de datos finalizada con pg_dump bajo snapshot exportado; origen activo y de solo lectura. Sesión del snapshot cerrada al terminar. El proceso auxiliar emitió error de cierre de stream después de guardar el resultado correcto; se corrigió el cierre y no se repitió la restauración sobre la base poblada.
- DATABASE_URL de desarrollo y guardas de base/usuario/APP_URL configurados para el próximo despliegue, con REPOTOOLS_PUSH_WORKER=0. Variables ajenas conservadas, sin claves de correo o almacenamiento de producción. Migración46 aplicada en beta usando su rol propio y el SQL del código probado.
- Desplegar únicamente desarrollo, comprobar distintivo/versión, health.php y sesión nueva en la base beta, ausente en producción.
- Confirmar producción sin cambios y conservar IndexedDB/operaciones pendientes; no borrar almacenamiento de dispositivos como parte del corte.

## Validación de esta preparación

Stock API y CSRF/idempotencia/permisos, sesiones persistentes y revocación, supervisión/alcances/exportación, excepción móvil de manager de beta y servicio global de sonido: fixtures locales aprobados. beta-database-guard verifica base y rol esperados, rechazo de mezcla de entornos y TLS. Esquema de destino creado y permisos de producción denegados. No afirmar beta publicada/aislada en ejecución hasta verificar conexión y versión remotas.

commercial-plans-ui aprobado en Chromium y WebKit: migración/idempotencia, manager/CSRF, scopes, validación de límites/nulos, revisión obsoleta409 sin auditoría duplicada, cambio de rol bloqueado, Stock accesible con planes en borrador, guardado/errores conservan datos y UI320/390/1366/ambas manos. Capturas revisadas y formulario compactado. No prueba física de teléfono ni cobros.

## Reversión

Conservar base y datos beta. Revertir código dentro de desarrollo con contador superior y guardas/conexión de beta intactas. No borrar recursos ni restaurar URLs de producción automáticamente. Ante una copia fallida, conservar diagnósticos privados y comprobar estado antes de repetir; nunca usar --clean sobre una base con datos.
