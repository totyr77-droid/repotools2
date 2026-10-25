# Acceso como usuario

Desde Moderación → Usuarios, el manager puede buscar por nombre, nombre público, correo o empresa y pulsar Ingresar junto a una cuenta. La búsqueda parcial se combina con rango y paginación; los caracteres % y _ se buscan literalmente. Seleccionar la fila sigue abriendo el perfil, sin ingresar a la cuenta.

El botón pide confirmar que los cambios son reales. Se crea una sesión de soporte separada, con credencial HttpOnly/SameSite propia, CSRF independiente y vencimiento de 30 minutos, sin sustituir la sesión principal ni reclamar, renovar o liberar los dispositivos del usuario. El contexto de URL no autentica: exige su cookie. El servidor revalida en cada solicitud el rango y dispositivo del manager y la versión de autenticación del usuario. Cambiar el rango del manager, revocar su dispositivo, cambiar la seguridad del usuario o vencer el plazo corta este acceso.

La identidad efectiva y los permisos son los del usuario. El aviso permanente identifica la cuenta y permite Volver a mi cuenta. Salir desde Ajustes también termina solo el acceso de soporte. No hay accesos anidados. Seguridad, credenciales, vinculación de dispositivos, push y asignación de rangos se gestionan desde la cuenta principal, no desde este acceso.

Las operaciones del registro unificado conservan su antes/después, pero identifican al manager real y la cuenta utilizada. Inicio, fin y solicitudes POST operativas se registran adicionalmente en auditoría; las acciones operativas y sus permisos siguen usando la cuenta efectiva. Los historiales operativos anteriores que muestran al propietario no se reescriben: el registro unificado permite identificar al manager. No se guardan credenciales ni contextos de acceso en auditoría.

La sesión de soporte tiene almacenamiento de navegador y canales separados. No usa la cola de cargas sin conexión: las cargas nuevas se confirman contra el servidor. La búsqueda puede usar datos temporales propios de esa vista, pero guardar requiere conexión. El service worker no devuelve el modo offline normal ante una caída de red, incluso si la navegación hereda el contexto desde el referer. Salir limpia el almacenamiento temporal y solicita eliminar sus bases locales. No se replican datos privados ni el almacenamiento del dispositivo físico del usuario: la mano y los permisos provienen de su cuenta; las preferencias exclusivamente locales pertenecen a esta vista separada.

## Validación

- tests/support-session-ui.cjs, Chromium y WebKit con API PHP y SQLite temporal: permiso exclusivo de manager, CSRF, coincidencias parciales/rango/literales, vistas 320/390/1366 y ambas manos; entrada desde la lista, edición por API y formulario, suma exacta y lectura simultánea desde el usuario, dispositivos del usuario intactos, datos/sesión principal separados, identidad real en auditoría, bloqueo de seguridad y accesos anidados, contexto sin cookie, salida, degradación del manager y vencimiento.
- La carga sin internet conserva el formulario y no genera cola; el aviso no tapa los controles del modo de una mano. Capturas locales revisadas.
- tests/support-worker.cjs: caídas de red en navegación/API, contexto heredado y bloqueo del fallback offline normal.
- Regresiones de sesiones, dispositivos, perfiles, jerarquía de staff y registros; sintaxis PHP/JS y diff.

No se modificaron cuentas ni datos de producción durante las pruebas. Pendiente probar en teléfono físico y PWA instalada. No hay migración de esquema.

## Reversión

Revertir esta entrega y publicar una versión de caché superior. Las sesiones principales y los dispositivos del usuario no cambian. Los contextos de soporte vencen a los 30 minutos; el código anterior ignora sus cookies separadas. Conservar la auditoría. Los cambios reales hechos por managers en cuentas de usuarios no se deshacen al revertir el código y requieren su corrección operativa habitual.
