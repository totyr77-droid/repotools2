# Ediciones pendientes de Mi equipo

Entrega main v2.06 / release111, 2026-10-20.

## Comportamiento

- GPS retirado como requisito de ingreso/escaneo. La configuración muestra la duración del acceso; se conservan las coordenadas del directorio y los datos anteriores sin usarlos para autorizar. Tokens de un solo uso, destinatario, rangos, revocación y opciones 1/4/7/12 horas o sin vencimiento conservados.
- Escanear un palet de la misma sucursal mantiene la edición normal por rango.
- Sin sucursal o con otra sucursal: se crea una solicitud al propietario y una propuesta aislada para el equipo. El escaneo no cambia branch_id, cantidades ni vencimientos confirmados. Nunca permite editar el original utilizando el permiso pendiente.
- Operadores, encargados y administradores editan mercadería de la propuesta online. Encargados/administradores ven el original, el total propuesto y diferencias en Palets/Mercadería; pueden corregir o cancelar. Cada edición registra actor, antes/después y estado pendiente en Movimientos. Actualización reutiliza el polling existente.
- El propietario recibe una notificación interna no leída enlazada a Stock y encuentra Solicitudes de equipos. Revisión por producto/fecha, aceptar acceso y cambios o rechazar. Aprobar aplica una sola vez y conserva los IDs de lotes existentes; no vuelve a sumar la propuesta. Permiso exclusivo del propietario, revisión de solicitud y revisión base del palet verificadas dentro de transacción/mutex.
- Si el propietario modificó el original mientras tanto, no se sobrescribe: debe rechazar y solicitar un nuevo escaneo. Los cambios posteriores del equipo también obligan a revisar otra vez antes de aprobar.
- Aprobación cambia la sucursal y excluye el palet de enlaces públicos preexistentes de esa sucursal. Traslado desde otra sucursal requiere esta aceptación explícita; el equipo de origen pierde el acceso derivado del vínculo anterior. Los permisos personales explícitos siguen su contrato existente.
- No se cambia la propiedad ni se habilitan transferencias de mercadería a quienes no son propietarios. Pendientes no se guardan como stock confirmado en la copia offline. Datos de propietarios anteriores no se heredan al transferir un palet.

## Implementación y validación

Migración41 aditiva: team_pallet_requests, team_pallet_products, índices por pendientes/propietario/sucursal. No reinterpreta operaciones ni inventario anteriores. Nueva API autenticada con CSRF para revisión/decisión; editor stock.php recibe contexto explícito de sucursal validado en servidor. Proyección SQL solo en Mi equipo, paginación y filtros previos conservados; datos pendientes se consultan por lotes, sin una consulta por tarjeta. Stock personal sigue sobre tablas confirmadas.

Pruebas: team-pallet-requests.php (aislamiento, cantidades/cajas/fechas, altas/retiros/eliminación, historial restringido, aprobación única, revisión/conflictos, rechazo/cancelación, otra sucursal, roles/expiración y sin GPS). team-pallet-requests-ui.cjs en Chromium/WebKit (cuentas temporales, edición, idempotencia, denegación sin contexto autorizado, detalle/totales/lotes, revisión/aprobación, configuración sin GPS, móvil/PC ambas manos). manager-scan-ui WebKit una mano preserva prueba del modal invisible con palet ya vinculado. team-inventory PHP/UI, stock-goods, stock-api, branch-team, branch-team-security actualizado al retiro de GPS, manager-team-access, team-refinements, team-stock-transfer, team-pallet-labels, team-activity-source, service-worker y lint/diff.

Migración40→41 y contrato de solicitudes comprobados en una rama temporal de PostgreSQL clonada de producción, con datos sintéticos en esquema separado; expiración automática 2026-10-22. No se probaron aprobaciones sobre palets reales. Safari/PWA físico pendiente: WebKit automatizado no sustituye pruebas de campo. Las pruebas antiguas de mapas/obligatoriedad GPS ya no representan este contrato; la configuración nueva se cubre en la prueba de solicitudes.

## Reversión

Conservar migración41, propuestas, estados, notificaciones e historial: no borrar inventario ni restaurar una base anterior. Para desactivar la función, bloquear temporalmente el escáner de equipo ante palets de otra/sin sucursal, mantener la edición normal de palets de la misma sucursal y publicar versión superior. No restaurar el team_scan previo, que reasignaba automáticamente. Las propuestas quedan disponibles para reactivar o rechazar posteriormente; los cambios aprobados son inventario confirmado y no deben revertirse automáticamente.
