# Reportes de productos

En Inicio y Biblioteca, el botón de la bandera abre un reporte. Incluye una explicación, los datos propuestos y, opcionalmente, fotos del EAN y del producto. Crear el ticket no cambia el catálogo.

Los invitados pueden reportar y consultar sus tickets desde el mismo navegador. Si borran los datos del navegador, pierden ese acceso. Las cuentas pueden ver sus reportes al iniciar sesión en cualquier dispositivo. Solo cuentas registradas participan en los TOPs de reportes enviados y aportes confirmados.

En **Mi cuenta → Mis reportes**, los administradores y moderadores ven **Pendientes de revisión**. Pueden ajustar los datos y aprobar la corrección o rechazarla con un motivo. Una aprobación actualiza el catálogo y otorga un punto al autor registrado en la misma transacción. No se duplican tickets por reintentos ni puntos por aprobaciones repetidas. Un moderador no puede aprobar su propio reporte. La asignación de permisos queda para la futura CMS; este cambio no convierte cuentas en administradores.

## Habilitar las fotos en Render

El bucket privado `report-evidence` se declara en `neon.ts`. Ya fue creado en la rama `production`. Sus cuatro variables están en `.env.local` en la computadora; ese archivo no se sube a Git.

1. En Render abre el servicio **repotools2 → Environment → Add Environment Variable**.
2. Copia de `.env.local` cada nombre y su valor, por separado:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_ENDPOINT_URL_S3`
   - `AWS_REGION`
3. En **Key** pega solo el nombre. En **Value** pega el valor sin comillas. Conserva `DATABASE_URL`.
4. Guarda con **Save, rebuild, and deploy** y espera a **Live**.
5. Envía un reporte con ambas fotos. Comprueba que aparecen en **Mis reportes → Ver reporte**.

No pegues claves ni contraseñas en mensajes o GitHub. Mientras falten estas variables, se pueden enviar reportes sin fotos.

Las imágenes quedan en Neon, no en el disco temporal de Render. El navegador reduce las fotos a JPEG y el servidor vuelve a procesarlas, elimina metadatos y valida el formato. Cada foto guardada tiene como máximo 1280 píxeles de lado y 1 MB. El bucket exige firma S3 y la aplicación verifica el autor o el permiso de moderación antes de entregar una imagen.

## Pruebas

`php tests/reports.php` verifica permisos, privacidad, conflictos, rollback y puntos únicos con una base aislada. `node tests/reports-api.cjs` verifica el flujo HTTP, CSRF, rankings públicos y rechazo de archivos falsos. Si se proporcionan variables S3 de una rama de pruebas, también verifica ambas fotos privadas; nunca uses credenciales de producción para esa prueba.
