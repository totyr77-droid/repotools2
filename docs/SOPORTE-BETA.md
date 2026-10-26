# Ayuda y soporte — beta

Acceso: Ajustes → Ayuda y soporte. Disponible para Free y Pro. Cada cuenta crea consultas de Stock, cuenta, Pro/pagos u otro tema; puede responderlas, resolverlas y reabrirlas respondiendo. Manager ve la bandeja de todas las cuentas y responde desde la misma pantalla. No otorga acceso como usuario ni privilegios Pro.

Mensajes internos: no se envían correos, WhatsApp ni notificaciones push. Primera versión sin adjuntos ni SLA prometido. El estado se actualiza al abrir/recargar la consulta, sin sondeo periódico. Páginas de 20 consultas, conversación con los últimos 100 mensajes. El número de versión interna se registra al crear la consulta; no se recolectan URLs, ubicación, cookies, credenciales ni registros automáticos del navegador. Los mensajes pueden contener información personal escrita por el usuario: acceso exclusivo al autor y managers, no públicos ni en Git.

Seguridad: permisos y rol actual consultados en servidor, CSRF, texto escapado, máximo 3000 caracteres, 5 consultas/día y 30 mensajes/hora por usuario; transacción/lock e identificador de envío para evitar duplicados. No hay bloqueo por Pro. Usuario ajeno recibe404 incluso intentando responder/cerrar. POST→redirect→GET conserva feedback RepoToast; un fallo conserva el texto del formulario.

Migración48 añade support_tickets/support_messages e índices a la base beta existente; no crea servicios pagos ni afecta producción. Mantener respaldos con el resto de datos de la base. No pegar mensajes reales en documentación.

Validación: tests/help-desk-ui.cjs, Chromium/WebKit con SQLite y cuentas ficticias: invitado, CSRF, aislamiento, reenvío duplicado, manager responde/resuelve, usuario reabre, texto HTML tratado como texto y formulario responsive320/390/1366/ambas manos. Prueba simulada, no teléfono físico. No se envían solicitudes a usuarios reales.

Reversión: retirar acceso en cuenta.php, soporte.php, includes/help-desk.php, CSS/JS y carga condicional en header/footer. Restaurar código anterior con versión de caché superior; conservar tablas/migración48 para no perder consultas y poder restaurar la función. No borrar mensajes como parte del rollback. Planes comerciales, suscripciones y producción independientes.
