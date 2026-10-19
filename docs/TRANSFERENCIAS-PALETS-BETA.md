# Transferencias de palets — beta

Solo desarrollo. No migraciones ni operaciones sobre datos reales durante la implementación.

## Flujo

En Stock, configuración del palet → Transferir → A otro palet / A un usuario.

Otro palet abre lectorQR existente; teclado permite ingresar ocho números. Revisión muestra origen, destino, lotes y unidades. Confirmar traslada todos los productos y descripción; origen queda vacío y conserva código/propietario/sucursal. Destino conserva dueño/sucursal; si no tiene sucursal hereda la del origen. Etiqueta libre puede activarse exactamente con ese código si no está reservada a otro usuario.

Lotes se agrupan por EAN y vencimiento según restricción existente. Un lote nuevo conserva nombre/presentación/envase. Coincidencias suman unidades sin cambiar vencimiento ni presentación del destino; referencias distintas se conservan combinadas. Los lotes originales completos quedan en historial de ambos palets. No se truncan referencias ni descripciones: si la combinación excede sus límites se rechaza para revisar. Cantidades se validan contra máximo existente.

A usuario requiere propietario actual (el rango global no sustituye esta condición), correo de cuenta existente y revisión del destinatario. Conserva código, productos, descripción y sucursal; cambia dueño, elimina accesos personales/invitaciones, etiquetas personales y asociaciones a enlaces de palets compartidos. Permisos de sucursal o rango global siguen vigentes por decisión del usuario. No se revoca la sucursal. Nuevo dueño recibe aviso interno. Eliminar asociaciones de enlace no elimina otros palets del mismo enlace; vistas de sucursal permanecen según permisos existentes.

## Integridad

POST Stock conserva autenticación/CSRF/transacción y bloqueo global de escritores existentes. Preview revalida permisos sobre ambos destinos y registra revisiones. Confirmación vuelve a calcular datos y compara origen/destino/usuario/revisiones. No confía en cantidades enviadas por cliente. Clave de operación idempotente con huella de transferencia: reintentar no duplica; reutilizar clave con otro destino se rechaza. Errores revierten todo. Auditoría existente y movimientos específicos preservan trazabilidad.

Solo online. Se impide transferir si este dispositivo tiene cargas pendientes de origen/destino; no se añade cola offline. Tras éxito se invalidan copias de ambos códigos y se refresca Stock. Modal descarta previews tardías al cerrarse. Registros de traslado se presentan con etiquetas/tonos y detalle de lotes en Movimientos de Mi equipo.

## Validación

- tests/stock-transfer.php: sumas exactas, fechas separadas, lotes/presentación/referencias, etiqueta libre, origen vacío, revisiones obsoletas de ambos extremos, rollback provocado, propiedad/sucursal y revocación de accesos.
- tests/stock-transfer-ui.cjs: lectorQR simulado, revisión/volver sin traslado, confirmación, suma/origen vacío, replay idempotente y huella distinta, correo, pérdida de acceso directo, CSRF.
- stock.php, stock-api.cjs, branch-team.php, branch-team-ui.cjs, service-worker-offline.cjs y sintaxis: aprobados.
- Captura móvil de confirmación inspeccionada. Pendientes: cámara/Safari físico y concurrencia/integración PostgreSQL. Pruebas SQLite aisladas, sin datos reales.

## Reversión

Restaurar archivos de esta entrega al padre y publicar versión superior deshabilita UI/acciones nuevas. No revierte transferencias confirmadas. Recuperar datos requiere un traslado inverso o intervención auditada con revisión de movimientos posteriores y cantidades; nunca borrar recibos/historial para repetir operaciones.
