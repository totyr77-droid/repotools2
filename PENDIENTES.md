# Próximos pasos de Repotools

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

1. Validar visualmente la nueva búsqueda administrativa en móvil y PC, y confirmar carga y edición de un palet ajeno con permisos.
2. Auditar sesiones en Render: actualmente PHP conserva sesiones en archivos locales, que pueden perderse al reiniciar/desplegar. Diseñar almacenamiento persistente y seguro; no confundir cuenta borrada con sesión perdida. Probar cierre, revocación y caducidad antes de publicar.
3. Implementar recuperación de contraseña con tokens de un solo uso, caducidad, límites y proveedor de correo definido. Falta elegir/configurar el servicio de correo; no inventar entregas.
4. Implementar el alcance inicial sin conexión descrito arriba, con cola local por cuenta, identificadores idempotentes y resolución explícita de conflictos. No simular éxito cuando algo no se sincronizó.
5. Agregar búsqueda de productos/EAN en Stock y vista reunida de vencimientos.
6. Retomar supervisores; empresas sigue pendiente de definición. La restricción de dispositivos para suscripciones está discutida, no aprobada ni implementada.

Pruebas ya disponibles: `tests/stock-api.cjs`, `tests/reports-api.cjs`, `tests/user-profiles-api.cjs`, `tests/navigation.cjs`, `tests/navigation-timeouts.cjs` y `tests/date-time.cjs`. Los archivos `tmp/*` son apoyos locales de pruebas y no deben publicarse.

Publicación utilizada: checkout Git en `tmp/upstream`, repositorio `totyr77-droid/repotools2`, rama `main`, despliegue automático en Render. Copiar y versionar solo los archivos de la entrega. La raíz del workspace no es un repositorio Git.
