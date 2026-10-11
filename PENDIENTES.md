# Próximos pasos de Repotools

Moderación: menú principal en una fila a ancho completo, estilo de palets; Pendientes/Resueltos debajo con dos columnas iguales. Validado a 320 y 1366 px, con contadores y cambio de sección. Archivos: `css/reports.css`, versión de estilos en `vistas/header.vista.php`. Revertir mediante el commit «Align moderation tabs with pallet navigation».

Registro del 11 de octubre de 2026. Estas notas definen trabajo pendiente; no implican que las funciones estén implementadas.

## Uso sin conexión

Alcance inicial acordado: guardar nuevas cargas temporalmente en el dispositivo para evitar perder el trabajo cuando falte señal, cobertura o Wi-Fi.

- Escanear EAN y guardar producto, cantidad y vencimiento como cargas pendientes.
- Permitir un nombre de referencia si el producto no está disponible localmente; consultar la base al recuperar conexión.
- Consultar productos y palets abiertos anteriormente, indicando la fecha de la última actualización.
- Mostrar un aviso con icono y contador: «Sin conexión · 3 cambios por enviar».
- Sincronizar al volver la conexión con identificadores de operación para evitar duplicados, revalidando sesión y permisos.
- Si hubo modificaciones simultáneas, mostrar el conflicto antes de aplicar un cambio.
- Reclamar o crear palets, gestionar permisos y moderar reportes requerirá conexión en la primera versión.

Pendiente de diseño técnico: almacenamiento local, límites y limpieza de datos por cuenta, recuperación de cargas pendientes y tratamiento de cambios de sesión. No confundir «guardado en este dispositivo» con «sincronizado».

## Supervisores

Dirección acordada en conversaciones anteriores, pendiente de implementar:

- Supervisión como permiso adicional, independiente del rango principal.
- Desde Mi cuenta, un usuario podrá compartir un enlace para vincular a su superior registrado; al aceptarlo se habilitará la supervisión automáticamente.
- Un usuario podrá vincular varios supervisores y desvincularlos.
- Los supervisores podrán consultar los palets de sus usuarios vinculados.
- Para editar, deberán solicitar acceso; solo el propietario del palet podrá aprobarlo.
- El rango Manager no debe convertirse automáticamente en supervisor.
- La propuesta inicial de tickets para obtener supervisión fue reemplazada por la vinculación mediante enlace.
- Antes de implementar, concretar aceptación del enlace, revocación y efectos sobre los accesos ya concedidos.

## Empresas

Idea aplazada por el usuario para pensarla mejor. No crear todavía un rango o permiso EMPRESA.

Propuesta por definir: una empresa podría consultar los palets de los usuarios que incorpore a su lista sin solicitar acceso a cada palet. No se acordaron aún las reglas de vinculación, autorización, alcance de lectura ni edición.

El campo opcional «Empresa o agencia» del perfil es información descriptiva; no concede permisos de empresa.

## Stock para administradores y managers

Aplicado el 11 de octubre: mantener acceso global, pero mostrar solo sus palets y los compartidos al entrar. La pestaña «Buscar palet» permite abrir otros por código o QR. La API ya no descarga el listado global en cada carga de Stock. Pruebas: `node tests/stock-api.cjs`.

## Acceso desde dispositivos

El usuario consultó la posibilidad de limitar una cuenta a un dispositivo. No está aprobado ni implementado.

Recomendación pendiente de decisión: permitir varios dispositivos personales y ofrecer gestión de sesiones y cierre remoto. Si se decide restringir, preferir una sola sesión activa con aviso al iniciar en otro dispositivo; no identificar dispositivos mediante IP, ya que cambia y puede compartirse.

## Otras ideas discutidas

- Registrar cambios de cantidades con responsable y fecha, sin exigir motivo.
- Transferencia de palets: propuesta de requerir aceptación del nuevo propietario; aún no confirmada.
- Búsqueda de productos o EAN dentro de Stock, vista reunida de vencimientos y recuperación de contraseña.

## Última entrega y continuidad para mañana

El usuario pidió mejorar estabilidad y UX con trabajo acotado por su cuota restante. No se completaron todas las mejoras ni se atribuye un 9/10 a la app.

Problema corregido: el resumen de Stock descargaba todos los palets para administradores y managers, incluso al consultar preferencias. Solución: retirar esa consulta global del resumen y reemplazar «Todos los palets» por búsqueda mediante los lectores y formularios existentes. Se conservan permisos de administración y gestión del palet abierto.

Validación: sintaxis PHP/JS y suite de API de Stock, incluyendo permisos, CSRF, reintentos idempotentes, conflictos de reclamación, invitaciones y acceso global por código. El resumen administrativo se verifica sin palets ajenos.

Reversión: esta entrega se publica en un commit separado titulado «Keep administrative Stock focused on own pallets and lookup». Usar `git revert <hash de ese commit>` en un checkout limpio; no resetear ni borrar las mejoras anteriores. Punto anterior: commit `b499245`, que contiene el registro de pendientes.

Prioridades al retomar:

1. Completado al retomar: búsqueda administrativa y edición autorizada de un palet ajeno comprobadas en Chrome a 320, 390 y 1366 px, sin desbordamiento horizontal ni errores JavaScript. Prueba local: `tmp/stock-lookup-ui.cjs`. Commit publicado: `13615e7`; la publicación no confirma por sí sola que Render terminó el despliegue.
2. Implementado: sesiones persistentes en la base (migración 13), duración fija de 30 días, cookie HttpOnly/SameSite Lax/Secure en Render, identificador renovado al ingresar, cierre y caducidad verificados. El primer despliegue invalida las antiguas sesiones de archivos: iniciar sesión nuevamente una vez. Confirmar el despliegue de Render y hacer seguimiento en uso real.
3. Implementar recuperación de contraseña con tokens de un solo uso, caducidad, límites y proveedor de correo definido. Falta elegir/configurar el servicio de correo; no inventar entregas.
4. Implementar el alcance inicial sin conexión descrito arriba, con cola local por cuenta, identificadores idempotentes y resolución explícita de conflictos. No simular éxito cuando algo no se sincronizó.
5. Agregar búsqueda de productos/EAN en Stock y vista reunida de vencimientos.
6. Retomar supervisores; empresas sigue pendiente de definición. La restricción de dispositivos para suscripciones está discutida, no aprobada ni implementada.

Pruebas ya disponibles: `tests/stock-api.cjs`, `tests/reports-api.cjs`, `tests/user-profiles-api.cjs`, `tests/navigation.cjs`, `tests/navigation-timeouts.cjs` y `tests/date-time.cjs`. Los archivos `tmp/*` son apoyos locales de pruebas y no deben publicarse.

Publicación utilizada: checkout Git en `tmp/upstream`, repositorio `totyr77-droid/repotools2`, rama `main`, despliegue automático en Render. Copiar y versionar solo los archivos de la entrega. La raíz del workspace no es un repositorio Git.

### Auditoría de sesiones al retomar

Confirmado en `includes/auth.php` y `Dockerfile`: se usa el almacenamiento de sesiones predeterminado de PHP, sin un manejador persistente ni configuración de duración propia. Los archivos del contenedor pueden desaparecer al desplegar o reiniciar; las cuentas en PostgreSQL no se eliminan por ello. La cookie dura la sesión del navegador y la limpieza de PHP puede caducar los archivos independientemente de esa cookie. La duración efectiva en Render requiere comprobar su configuración en ejecución.

Auditoría histórica anterior al cambio. La solución ya se implementó en `includes/sessions.php` y `includes/auth.php`: credenciales opacas con digest en la base, caducidad fija de 30 días y bloqueo temporal por sesión, sin mantener abierta una transacción durante consultas de la aplicación. Una petición atrasada no puede sobrescribir otra ni restaurar una sesión cerrada. Si falla el inicio del almacenamiento, se devuelve 503; no se recurre a archivos temporales ni se interpreta como cuenta inexistente. La IP no identifica dispositivos.

Validación de sesiones: `tests/sessions.php` en SQLite y en la rama aislada de Neon `br-empty-dawn-b5xa8l24` con conexión directa; `tests/sessions-api.cjs` comprueba reinicio real del servidor conservando la base, rotación al ingresar, rechazo del identificador anterior, cierre y caducidad; `tests/sessions-concurrency.cjs` comprueba tres procesos simultáneos sin pérdida de cambios. Pasaron además autenticación, cierre, Stock, reportes y perfiles. No cambia los permisos ni limita la cantidad de dispositivos. No incluye recuperación de contraseña ni gestión visual de sesiones.

Reversión: revertir el commit «Persist PHP sessions in the application database». La tabla nueva puede quedarse sin uso; no borrar usuarios ni deshacer migraciones anteriores. Volver a archivos implica otro inicio de sesión y recupera el problema de reinicios de Render.

Al retomar se repitieron satisfactoriamente `tests/stock-api.cjs`, `tests/navigation-timeouts.cjs` y `tests/date-time.cjs`, además de la prueba visual mencionada. Para revertir Stock sin perder otros cambios: `git revert 13615e7`.
