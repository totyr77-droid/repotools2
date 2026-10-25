# Preparación de versión paga — beta

Trabajo autorizado el25/10/2026, únicamente en desarrollo. Producción continúa en main. No contratar otra instancia ni activar cobros por esta preparación.

## Base y código

- Integrar main f6f0100/v3.09 en desarrollo, conservando distintivo BETA, favicon, sesiones móviles de manager propias de beta y numeración independiente.
- Se creó una base lógica beta y un rol exclusivo en la instancia Render existente. Estructura de79 tablas y45 migraciones, sin registros privados. El rol no tiene lectura/escritura ni creación de objetos en el esquema de producción. Comparte recursos de la instancia; no equivale a aislamiento de CPU o disponibilidad.
- Protección opcional de servidor mediante REPOTOOLS_EXPECTED_DATABASE y REPOTOOLS_EXPECTED_DATABASE_USER: una conexión de otro entorno se rechaza antes de abrir PDO. No guardar valores secretos en Git.
- Preservar configuración anterior y pruebas de aislamiento en carpeta privada externa. No volver a conectar beta a producción para revertir código.

## Decisiones pendientes de respuesta

1. Unidad de cobro: empresa, sucursal o usuario.
2. Primera entrega: activación manual con prueba gratuita; cobro automático; o planes/límites sin cobros.
3. Datos de beta: estructura/datos ficticios o copia de cuentas/stock sin sesiones, credenciales de proveedores ni envíos reales.

No inventar precios, períodos, límites ni restricciones mientras estas decisiones estén pendientes. El pedido actual autoriza preparar y aislar beta; no modificar condiciones ni acceso de usuarios de producción.

## Antes de activar beta aislada

- Completar el alcance de datos según la respuesta; evitar duplicar sesiones, dispositivos push, colas de envíos y secretos de producción.
- Cambiar solamente DATABASE_URL de desarrollo por su rol exclusivo; añadir guardas de base/usuario y APP_URL de beta. Mantener REPOTOOLS_PUSH_WORKER=0 durante preparación. No copiar claves de correo o almacenamiento de producción.
- Desplegar únicamente desarrollo, comprobar distintivo/versión, health.php y sesión nueva en la base beta, ausente en producción.
- Confirmar producción sin cambios y conservar IndexedDB/operaciones pendientes; no borrar almacenamiento de dispositivos como parte del corte.

## Validación de esta preparación

Stock API y CSRF/idempotencia/permisos, sesiones persistentes y revocación, supervisión/alcances/exportación, excepción móvil de manager de beta y servicio global de sonido: fixtures locales aprobados. beta-database-guard verifica base y rol esperados, rechazo de mezcla de entornos y TLS. Esquema de destino creado y permisos de producción denegados. No afirmar beta publicada/aislada en ejecución hasta verificar conexión y versión remotas.

## Reversión

Conservar base y datos beta. Revertir código dentro de desarrollo con contador superior y guardas/conexión de beta intactas. No borrar recursos ni restaurar URLs de producción automáticamente. Ante una copia fallida, conservar diagnósticos privados y comprobar estado antes de repetir; nunca usar --clean sobre una base con datos.
