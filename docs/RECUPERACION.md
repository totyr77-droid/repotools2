# Recuperación de contraseña

Flujo preparado con Resend. El envío queda deshabilitado hasta configurar en el servicio de Render:

- `RESEND_API_KEY`: clave de envío creada en Resend. Guardarla únicamente como variable privada.
- `MAIL_FROM`: remitente de un dominio verificado, por ejemplo `Repotools <acceso@tu-dominio.com>`.
- `APP_URL`: URL pública HTTPS de la aplicación, sin barra final.

No alcanza con crear un Environment Group: vincularlo al servicio y desplegar. No guardar claves en Git ni enviarlas por chat.

Desde Ingresar → Olvidé mi contraseña se solicita el enlace. Caduca a los 30 minutos, admite un solo uso, invalida otros enlaces del usuario y cierra las sesiones anteriores al cambiar la contraseña. El formulario no confirma si existe una cuenta. Máximo cinco solicitudes por IP en quince minutos; token aleatorio de 32 bytes y solo su digest en la base.

Sin las variables aparece un aviso de indisponibilidad. Las pruebas locales no envían correos reales. Antes de habilitarlo, probar entrega real con un destinatario autorizado. Si el proveedor falla, no se registra una entrega exitosa ni se conserva el token fallido.
