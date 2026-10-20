# Correo de verificación — 2026-10-20

## Diagnóstico y alcance

El usuario confirmó que botón y enlace no abren nada en el mensaje recibido como spam. No mostró error del servidor ni token inválido. No se abrió ni consumió el token de las fotos. La imagen oficial pública devuelve HTTP200/image/png y es un PNG válido1024×175. La página sin token devuelve HTTP200. Esto no reproduce la política del cliente Gmail ni prueba bandeja de entrada.

En los DNS públicos de repotools.online se encontró DKIM en resend._domainkey y SPF en send.repotools.online; no se encontró _dmarc.repotools.online. No se inspeccionaron encabezados privados del mensaje ni se comprobó alineación del remitente real. No atribuir spam exclusivamente a DMARC.

## Cambios

Correo de bienvenida incorpora el PNG oficial como adjunto inline CID con bytes base64 y MIME image/png. No depende de descargar externamente el logo. Dimensiones180×31 explícitas en plantilla compartida; conserva proporción e identidad. URLs del botón y respaldo siguen idénticas, con token en fragmento, sin cambiar vencimiento/uso único ni consumir mediante GET. Agrega ayuda para desbloquear mensaje como No es spam o copiar enlace al navegador. Construcción de URL usa email_origin validado. No modifica correos ya recibidos.

## Pendiente externo

En el DNS del dominio remitente real, configurar DMARC inicialmente en modo observación (TXT _dmarc, valor v=DMARC1; p=none; adkim=r; aspf=r), verificando primero que SPF/DKIM estén alineados con Resend. No reemplazar registros existentes sin revisar su estado actual. Revisar seguimiento de enlaces/aperturas en Resend para correo transaccional. Sin acceso DNS/proveedor en esta entrega; no se cambiaron configuraciones ni se enviaron correos de prueba. No se garantiza bandeja principal.

Fuentes: https://support.google.com/mail/answer/145919?hl=es (Gmail puede bloquear imágenes de remitentes sospechosos); https://resend.com/changelog/embed-images-using-cid (formato inline); https://www.resend.com/blog/top-10-email-deliverability-tips (alineación de dominio y seguimiento transaccional).

## Validación y reversión

email-inline-logo.php valida bytes PNG/CID/MIME/tamaño, URL exacta en botón/respaldo y origen inválido. unified-core.php valida verificación/repetición/vencimiento y crédito referido inmutable. email-colors.cjs prueba logo/colores/desbordamiento móvil en navegador claro/oscuro; no simula Gmail. unified-presentation.cjs recorre confirmación con token aislado. Campo Gmail/Android pendiente.

Revertir cambios de esta entrega en email-layout/email-verification y publicar versión superior. No borrar tokens ni modificar usuarios. Sin migración ni datos reales.
