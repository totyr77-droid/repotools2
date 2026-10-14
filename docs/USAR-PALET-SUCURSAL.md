# Usar palet y sucursal opcional (beta)

- Usar este palet reclama directamente el código, sin abrir el selector y sin sucursal. Conserva reservas, reubicación por concurrencia y operación idempotente.
- Sin sucursal abre el selector opcional; Buscar primero, Mapa segundo. Cambiar sucursal vuelve a Buscar.
- Al asignar una sucursal con enlaces propios activos, consultar No compartir / Sí, compartir. Escape cancela sin guardar. La elección se aplica a todos los enlaces activos de esa sucursal mediante stock_share_exclusions; otras exclusiones se conservan.
- El servidor exige la elección al cambiar a una sucursal compartida, incluso si el enlace se creó después de abrir el formulario. Valida propietario/revisión dentro de la transacción existente. Reintentos de asignación verifican payload y permiso vigente.
- Sin migraciones ni cambios en QR, vencimientos o permisos de consulta. Las selecciones individuales de palets no cambian.

Validación: PHP/JS syntax, tests/branch-assignment-choice.php, tests/branch-assignment-choice-ui.cjs, tests/stock-branches-ui.cjs y tests/stock-api.cjs. Pruebas con SQLite aislado y Chrome móvil; Safari físico pendiente.

Reversión: revertir el commit de esta entrega en desarrollo. No requiere revertir esquema; las exclusiones elegidas permanecen y pueden cambiarse desde Enlaces compartidos. Main no se modifica.
