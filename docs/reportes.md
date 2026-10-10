# Reportes de productos

En Inicio y Biblioteca, el botón de la bandera abre un reporte. Incluye una explicación, los datos propuestos y, opcionalmente, fotos del EAN y del producto. Crear el ticket no cambia el catálogo.

Para las fotos, toca **Tomar foto del EAN** o **Tomar foto del producto**. La cámara se abre dentro de la app; permite el acceso, encuadra y toca **Tomar foto**. Puedes revisar la imagen, volver a tomarla o quitarla antes de enviar. La cámara se cierra al capturar, cancelar o salir de la página.

Los invitados pueden reportar y consultar sus tickets desde el mismo navegador. Si borran los datos del navegador, pierden ese acceso. Las cuentas pueden ver sus reportes al iniciar sesión en cualquier dispositivo. Solo cuentas registradas participan en los TOPs de reportes enviados y aportes confirmados.

En **Mi cuenta → Mis reportes**, los administradores y moderadores ven **Pendientes de revisión**. Pueden ajustar los datos y aprobar la corrección o rechazarla con un motivo. Una aprobación actualiza el catálogo y otorga un punto al autor registrado en la misma transacción. No se duplican tickets por reintentos ni puntos por aprobaciones repetidas. Un moderador no puede aprobar su propio reporte. La asignación de permisos queda para la futura CMS; este cambio no convierte cuentas en administradores.

Administradores y moderadores también tienen la pestaña **Reportes** en la navegación principal y móvil. Abre directamente los pendientes y muestra su cantidad. El contador se oculta al llegar a cero y se actualiza al revisar un ticket, volver a la página o cada minuto mientras está visible. El servidor verifica el permiso vigente para mostrar la pestaña y entregar el contador.

La bandeja de moderación excluye los tickets del propio moderador, al igual que su contador. Otro administrador o moderador puede revisarlos. **Mis reportes** es una vista separada, accesible desde Mi cuenta; no aparece como pestaña dentro de la bandeja de moderación.

Los formularios separan **Marca** (Red Bull), **Producto** (Energizante Lata) y **Contenido neto** (250 ml, 500 g, 1 kg o 1 L). El contenido debe escribirse con su unidad, como aparece en el envase. Los datos existentes conservan su valor; no se convierten automáticamente unidades ni se cambia el nombre recibido de la API.

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
