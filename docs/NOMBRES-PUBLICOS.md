# Nombres públicos — 13/10/2026

## Base y migración

Migración aditiva **24**, dentro de la transacción de initialize_database existente: agrega users.public_name TEXT NOT NULL DEFAULT '' si falta, completa los valores vacíos y registra la versión. También admite una columna ya presente sin sobrescribir nombres elegidos. No cambia users.name, IDs, relaciones, sesiones, contraseñas ni permisos.

Helper central includes/public-names.php: primera palabra más inicial de la segunda y punto; no interpreta apellidos. Conserva UTF-8, normaliza espacios; una palabra se conserva; vacío/valor inválido devuelve Usuario #ID. Un public_name válido existente tiene prioridad. Edición: 3–24 caracteres, letras Unicode/marcas, números, espacio, punto, guion y guion bajo. Backend rechaza HTML, saltos de línea, controles y entradas que no sean texto. HTML se escapa y vista previa JS usa textContent.

public_name no es UNIQUE ni identificador. Colisiones permitidas: propietarios, miembros y ranking siguen distinguiéndose por ID. No se introduce username. El usuario puede elegir compartir su nombre completo expresamente; el fallback nunca publica automáticamente varias palabras completas del nombre real.

Auditoría de producción de solo lectura previa al despliegue: **6 cuentas**, **6 pendientes de completar**, **0 grupos duplicados** con la regla propuesta. No se guardaron nombres, correos ni credenciales. Esto no es una confirmación de migración productiva: Render aplicará la versión 24 al ejecutar la nueva app. Pruebas aisladas: **4 cuentas completadas, 1 nombre elegido conservado**, dos nombres públicos iguales tratados sin error.

Rama temporal de Neon solo con esquema y datos ficticios: br-mute-lab-b5ixjeph, expira 14/10/2026 12:00 UTC. PostgreSQL y SQLite pasan la migración y repetición idempotente. El PHP local requiere extensión pdo_pgsql y PGOPTIONS de endpoint por su libpq antiguo; esto se configuró únicamente en el proceso de prueba, sin cambiar conexión de producción.

## Auditoría de presentación

| Uso | Clasificación | Resultado |
| --- | --- | --- |
| TOPs, tres categorías | Público | Helper actual por cuenta; estadísticas conservan ID |
| Palet público, lista y detalle Stock | Público/compartido | Propietario y último movimiento públicos; campos auxiliares de nombre real se eliminan antes del JSON |
| Reservas de etiquetas | Público | Nombre público de quien reserva, resuelto desde usuarios |
| Stock compartido, por sucursal o selección | Público | Propietario y actualizador públicos, lectura dinámica |
| Colaboradores e invitaciones de palet | Compartido | Nombre público; IDs y permisos originales |
| Notificación de invitación, incluida una anterior | Compartido | Se reconstruye desde propietario actual del palet; evita nombre real almacenado en mensajes anteriores |
| Biblioteca compartida | Compartido | Payload contiene nombres de colección/productos, no identidad del dueño; sin cambios |
| Mi cuenta, saludo del propio usuario, correo y seguridad | Privado | Conserva nombre real; nuevo bloque plegable Nombre público con edición/vista previa |
| Usuarios, revisión de reportes, registros/actividad, historial de moderación | Administrativo | Conserva nombre real bajo permisos existentes en servidor |
| Emails de recuperación y avisos privados | Privado | Conservan tratamiento privado existente |

No hay historial de personas público independiente hoy. Los registros administrativos ya relacionan actividad por ID y siguen privados. Si se añade uno público, debe resolver el helper desde el usuario, no copiar el nombre al evento. No se reescriben datos de palets ni actividad histórica. Las invitaciones muestran el propietario actual dinámicamente. Los datos antiguos guardados localmente en un dispositivo privado no se reescriben: offline queda fuera del alcance y se actualizarán con la consulta online habitual.

## Cuenta y registro

Registro genera el valor sin añadir campos ni pasos. Actualizar nombre real posteriormente no sobrescribe el público elegido. Mi cuenta usa una sección plegable con SVG y flecha, campo y Guardar en la misma fila, vista previa y confirmación. En móvil zurdo se aproxima la acción a la izquierda; PC conserva orientación normal. api/cuenta.php acepta action=public_name solo para la cuenta actual, con sesión vigente y CSRF. Lectura fresca incluye el nuevo campo sin cambiar lógica de autenticación.

## Pruebas

- tests/public-names.php: ejemplos solicitados, tildes/ñ, vacío, fallback, HTML/control, validación, migración repetida, nombres existentes conservados, colisiones, miembros, palets y stock compartido dinámicos. Pasa en SQLite y PostgreSQL aislado.
- tests/public-names-ui.cjs: edición/vista previa, guardado, validación backend, 401/403, registro nuevo, nombre real privado conservado, TOPs/palet/stock compartido sin nombre real, 320/390/1366 y ambas manos. Captura móvil revisada; se corrigió ancho del campo frente a estilos generales.
- tests/stock.php, stock-branches.php y user-profiles.php: pasan; propiedad, permisos, rangos y relaciones conservados.
- tests/share-links-ui.cjs: pasa con URLs cortas, enlace antiguo y desactivación.
- Lint PHP de archivos principales y sintaxis JS correctos. Safari real y despliegue/migración de Render pendientes de confirmar.

## Archivos y reversión

Nuevos: includes/public-names.php, vistas/public-name.vista.php, js/public-name.js, css/public-name.css, dos pruebas y este informe. Modificados: includes/database.php, includes/auth.php (solo proyección de campo), includes/stock.php, includes/stock-branches.php, api/cuenta.php, api/stock.php, api/notificaciones.php, cuenta.php, tops.php, header/footer y notas de continuidad.

Revertir únicamente estos cambios desde 8a74e30. Conservar columna y registro de migración 24 al revertir; no borrar nombres elegidos ni datos. Una reversión completa del renderizado volvería a exponer nombres reales: para una reversión funcional puntual conservar helper y presentación pública. No hubo cambios de service worker, escáner, offline, links, cantidades ni APIs externas.
