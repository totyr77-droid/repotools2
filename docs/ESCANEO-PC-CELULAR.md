# Escanear desde PC y celular

## Uso

1. Iniciar sesión con la misma cuenta en computadora y celular.
2. En la computadora, abrir escaneo: aparece un campo para teclado o lector de códigos (Enter).
3. Elegir Usar celular; Recordar este método es opcional y se guarda en este navegador por cuenta.
4. En el celular, aceptar Conectar. Desde el aviso verde, Escanear para PC abre la cámara compartida, con zoom, flash y entrada manual cuando corresponde.
5. Inicio incorpora los EAN a la lista. Biblioteca abre su formulario. Stock abre cantidad/vencimiento o el palet si se pidió QR. En una carga individual, el teléfono espera hasta abrir el siguiente escaneo.
6. Desconectar está disponible en ambos equipos. Recordar el método no acepta una conexión nueva por el usuario.

## Sesiones y seguridad

Una sesión de navegador de computadora y una de celular por cuenta; varias pestañas con la misma cookie son la misma sesión. Un nuevo ingreso del mismo tipo pide Cancelar/Entrar aquí. Cancelar conserva la sesión anterior; entrar reemplaza solamente ese tipo. No borra cargas locales pendientes. Los dispositivos se clasifican por User-Agent, no son una identificación física infalible ni un mecanismo de licencia anticopia.

Las sesiones existentes adoptan un cupo al usarse: si ya se ocupó, deben volver a ingresar y confirmar el reemplazo. Token aleatorio en sesión PHP, solo digest SHA-256 en la tabla de dispositivos. Validación de cupo y auth_version en servidor. Cambio de contraseña o cierre de otras sesiones conserva el cupo actual e invalida los restantes; recuperación de contraseña invalida todos. Salir libera únicamente el cupo cuyo token coincide.

Relay autenticado, misma cuenta, POST con CSRF, una lectura pendiente y secuencia de acuse. Un identificador por apertura evita que otra pestaña o un cierre anterior consuma/interrumpa la lectura del formulario actual. Cambiar sección o cerrar la carga pausa el flujo. QR limitado al código de palet o URL del dominio configurado por APP_URL; no admite perfiles ni URL arbitraria. La consulta de usuarios por QR conserva su flujo existente. La conexión vence tras 30 minutos sin acciones de conexión/escaneo; consultar su estado no prolonga esa reserva. Las sesiones tienen la duración existente de 30 días.

Migraciones aditivas 21 (account_devices) y 22 (scan_relays), PostgreSQL/SQLite. No cambios manuales a cuentas ni base productiva. Las consultas periódicas usan intervalos menores durante una conexión y mayores al esperar. Hace falta Internet en ambos equipos; no envía fotos ni sincroniza cargas offline.

## Validación y límites

Tests/account-devices.php: conflictos, reemplazo, liberación protegida, auth_version y clasificación. Tests/device-connection-ui.cjs: Chrome con PC/celular simulados, input/Enter, consentimiento, cámara con decodificador simulado, entrega a Inicio/Stock/Biblioteca, pausa, QR, aislamiento de flujo/cuentas, CSRF, desconexión y reemplazo de sesión. Geometría 320/390/1366. Regresiones de Stock, sucursales, reportes, navegación, lector EAN y seguridad de cuenta.

Queda probar cámara real, pistola USB/Bluetooth y Safari/Android con el despliegue. PostgreSQL aislado sigue pendiente por las credenciales de prueba anteriores rechazadas; las pruebas de DB realizadas son SQLite. No se afirma calidad de cámara ni rendimiento real a partir del lector simulado.

## Revertir

Revertir el commit completo de esta entrega (UI, autenticación, endpoint, seguridad y navegación de dispositivos), conservando las tablas y datos 21/22. Al quitar la validación de cupos vuelven a permitirse múltiples sesiones. No quitar solamente la actualización de account_devices en api/seguridad.php mientras esté activo el control de sesiones: cerraría también la sesión que cambia su contraseña. Las preferencias locales pueden quedar guardadas sin efecto.
