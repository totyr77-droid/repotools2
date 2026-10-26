# Mercado Pago — preparación privada en beta

25/10/2026. Solo rama desarrollo. Autorización: preparar Pro mensual con renovación automática, probar antes de cobrar y mostrar distintivos PRO sin romper acciones actuales. No hay autorización para cargos reales, precios definitivos ni cambios en producción. Sucursal/Empresa fuera de esta etapa.

## Implementado

Migración aditiva47: pro_subscriptions y pro_payments; referencias aleatorias sin datos de tarjetas. Configuración por secretos de entorno, sin SDK/librería nueva. ProSubscriptions usa HTTPS con tiempo limitado, sin redirecciones ni errores privados en respuestas. Interfaz en Ajustes → Mi suscripción (solo manager durante pruebas), importe mensual cuando se configure y cancelación con confirmación. Regreso desde el proveedor solo consulta estado, no concede Pro.

Configuración falla cerrada: modo test, APP_URL exacta de beta, vendedor configurado y verificado como test_user antes de crear/cancelar, comprador de prueba, importe válido y secreto de firma. No confiar en prefijos de credenciales para decidir si una cuenta es de prueba. Los pagos deben indicar live_mode=false. Modo live no está implementado; no basta con sustituir el token.

Webhook sin sesión, HMAC del data.id original + request-id + ts, sin parámetros duplicados y tolerancia5min. Se consulta recurso a la API, nunca se acepta estado/precio del navegador o del cuerpo como prueba de pago. Verifica suscripción/referencia, vendedor, moneda ARS, periodicidad y precio guardado. Cada pago tiene clave única y período según debit_date con mes calendario (31enero→28febrero). Repetir aviso no agrega otro mes. Estados rechazado/reembolsado no conceden período; cancelación confirmada conserva tiempo pagado. La autorización de recurrencia por sí sola no concede Pro.

Persistencia de intento antes de crear; después de timeout no se repite POST automáticamente: busca por referencia. Si no puede confirmar un único resultado, queda pendiente para revisar, sin crear otra suscripción a ciegas. Webhooks tempranos recuperan por referencia verificada. Reconsultas de proveedor bajo bloqueo de fila evitan aplicar estados antiguos por avisos desordenados.

Los privilegios comerciales NO se aplican todavía (enforced=false). El cálculo Pro es un estado de prueba; no modifica roles/permisos/inventario. Distintivos son informativos. No ofrecer contratación a clientes hasta cerrar pendientes.

## Configuración pendiente del usuario

Crear aplicación y cuentas vendedor/comprador de prueba de Argentina desde Mercado Pago Developers. Crear/configurar la aplicación según la guía de Suscripciones de la cuenta vendedora de prueba. Verificar el vendedor con /users/me; las credenciales de suscripciones deben corresponder al flujo de cuentas de prueba del proveedor, no asumir que cualquier token TEST sirve.

Guardar únicamente como secretos del servicio beta (no Git, documentación ni mensajes): REPOTOOLS_MP_ACCESS_TOKEN y REPOTOOLS_MP_WEBHOOK_SECRET. Configurar REPOTOOLS_MP_TEST_SELLER_ID, REPOTOOLS_MP_TEST_BUYER_EMAIL y REPOTOOLS_MP_TEST_AMOUNT_CENTS; este importe es de prueba, no define precio comercial. Activar REPOTOOLS_BILLING_MODE=test solo después de comprobar origen de cuentas. APP_URL conserva URL beta.

En la aplicación del proveedor configurar Webhooks de subscription_preapproval y subscription_authorized_payment hacia /api/mercadopago-webhook.php de beta. Se requiere data.id en query y firma válida. No cambiar claves/env de producción. No guardar tarjeta ni CVV en RepoTools.

## Validación y límites de lo comprobado

PHP tests/pro-subscriptions.php: migración idempotente, prueba privada, bloqueo vendedor real/modo live, referencia persistente/recuperación timeout, pago vs autorización, avisos duplicados/tempranos, fin de mes, rechazo/reembolso, importe/vendedor/live_mode inválidos, cancelación confirmada y período preservado, HMAC/manipulación/replay. Transporte simulado, sin llamadas ni cargos al proveedor.

pro-subscriptions-ui: API auth/rol/CSRF, cobro deshabilitado sin filas nuevas, controles ocultos hasta configurar, página y etiquetas móviles320/390/escritorio1366, ambas manos, apertura del acceso al palet conservada. commercial-plans-ui conserva borradores y acceso a Stock. Chromium/WebKit según resultados registrados en PENDIENTES. Capturas locales con datos ficticios.

Falta prueba real del entorno de prueba de Mercado Pago: autorización, primer débito, renovación, rechazo, cancelación, recuperación de timeouts y recepción de webhooks firmados. Antes de producción completar reconciliación programada de notificaciones perdidas, recontratación tras cancelación, historial/soporte, auditoría comercial, condiciones/precio/gracia, devoluciones/contracargos y aplicación de privilegios con transición explícita. Las pruebas simuladas no acreditan cobros ni renovación real.

## Reversión

Mantener cobro sin configurar o retirar REPOTOOLS_BILLING_MODE; revocar únicamente credenciales de prueba si hace falta. Volver código de desarrollo con versión/cache superior; conservar tablas47, referencias y pagos, sin borrar datos ni tocar producción. Cancelar en Mercado Pago cualquier suscripción de prueba antes de retirar integración. No restaurar la conexión de producción en beta.

## Referencias oficiales consultadas

- https://www.mercadopago.com.ar/developers/es/docs/subscriptions/integration-configuration/subscription-no-associated-plan/pending-payments
- https://www.mercadopago.com.ar/developers/es/docs/subscriptions/additional-content/your-integrations/test/accounts
- https://www.mercadopago.com.ar/developers/es/docs/subscriptions/integration-test/payment-approval
- https://www.mercadopago.com.ar/developers/en/docs/subscriptions/additional-content/your-integrations/notifications/webhooks

## Actualización: bloqueo Pro autorizado y cobro pospuesto

El usuario pospone Mercado Pago. Token verificado como vendedor de prueba argentino, identificador guardado y secreto de Webhooks presente; sin correo de comprador ni importe/modo habilitado. Consulta del comprador existente no devolvió correo. Usuario autorizó crear otro comprador por API, pero el proveedor rechazó con403; no se creó cuenta ni pago. No insistir ni pedir credenciales hasta retomar. No exponer secretos/IDs/filas de cuentas en documentación.

El usuario ahora sí autoriza aplicar privilegios PRO solo en beta y mostrar una comparación Free/Pro al intentar usarlos. Esto reemplaza la indicación anterior de enforced=false para privilegios personales; cuotas de capacidad y configuraciones de sucursal/empresa siguen en borrador.

## Retoma de pruebas26/10

Usuario desde PC confirma acceso al comprador de prueba y aporta su correo, guardado únicamente en entorno beta. Vendedor revalidado por API como test_user argentino y coincide con configurado. Importe ficticio100ARS, modo test y comprador configurados; no pagos ni suscripciones creados por esta preparación. Secretos existentes preservados, producción intacta.

Compatibilidad con concesión beta_trial del manager: checkout puede iniciar si aún no tiene proveedor/enlace, toma el importe configurado y conserva paid_until. Interfaz muestra botón de prueba y estado de acceso manual. Prueba unitaria de transición/persistencia de vigencia y UI Chromium/WebKit; corrige expectativa antigua de esquema47 para usar constante actual48. Falta autorización/primerpago/notificación con proveedor: no considerar integración certificada por estas pruebas simuladas.
