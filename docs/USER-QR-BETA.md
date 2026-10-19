# QR de usuario — beta

El encabezado autenticado incorpora una flecha discreta con el QR personal, generado al abrirlo mediante el token opaco de perfil existente. No contiene una contraseña ni una sesión. Se informa que quien lo escanee podrá completar el correo de la cuenta.

Cámaras en correo de accesos al palet, transferencia y responsable de empresa. Reutilizan RepoEANScanner en modo perfil, sin zoom ni guías de palet/OCR. El lector de perfiles existente también desactiva zoom/guías. No se añaden cámaras a inicio de sesión ni edición del correo propio.

Solo admite URL del perfil de esta instalación y token de 64 caracteres hexadecimales. El servidor exige autenticación y CSRF para resolver el QR y devuelve únicamente nombre público/correo. Mantiene los permisos previos de consulta de perfiles y de cada operación. Escanear rellena el campo, sin enviar formularios ni otorgar accesos. Cancelar/cambiar página detiene cámara y descarta consultas tardías. Resolver requiere conexión.

Validación local con SQLite y cuentas temporales: user-qr-ui (QR renderizado, 320/390/1366 ambas manos, destinatario completado sin envío, rechazo de URL externa/palet, errores de token/CSRF/perfil), stock-transfer-ui, user-profiles-api, scanner-zoom (incluye perfil con capacidad de zoom desactivada), service-worker-offline, sintaxis PHP/JS y captura móvil revisada. Sin escrituras de datos reales. Cámara y Safari físicos pendientes.

Reversión: restaurar los archivos de esta entrega al padre y publicar una versión superior del caché; no requiere migración ni elimina tokens existentes. Main permanece intacta.

## Perfil público e invitación (v1.47)

El QR nuevo apunta a perfil.php?user=<token opaco existente>. Los anteriores de reportes.php?queue=1&user=... redirigen al mismo perfil antes de exigir cuenta o permisos de moderación. Los lectores internos admiten ambas rutas exclusivamente de la instalación actual: completan correo con resolver autenticado, sin navegación ni invitación automática.

Perfil público mínimo: nombre público y empresa declarada si está cargada. No muestra correo, nombre privado, rangos internos, palets, registros ni datos de acceso. No se indexa y responde no-store/no-referrer. Token inválido/inexistente da 404 sin atribución. Ver un perfil no habilita consultar el API privado de perfiles.

Para visitante sin sesión, captura la invitación con los helpers existentes, límite por sesión y vencimiento de siete días. Persiste al navegar antes del registro. El registro crea referido pendiente; confirmación de correo lo acredita según reglas existentes (sin duplicados/autorreferidos/cuentas preexistentes). Una cuenta ya autenticada no recibe nueva atribución. Si referidos está deshabilitado, el perfil sigue disponible sin captura. No se cambian códigos, reglas de ranking ni invitaciones previas.

Pruebas locales public-profile-referral-ui: QR anterior a perfil sin login, nombre público sin correo/nombre privado, registro referido pendiente, no modificación de cuenta existente, 404 no atribuye, POST de código falso ignorado. user-qr-ui incluye ruta nueva y anterior y selección sin envío; perfilesAPI/referralsPHP/SW/sintaxis aprobados; captura móvil revisada. Datos SQLite temporales, sin correos reales ni operaciones en bases remotas. Cámara física pendiente.

Revertir archivos de esta entrega al padre y publicar versión superior; las atribuciones ya registradas no se borran al revertir código. Main intacta.

En v1.48 el QR nuevo usa /u/CODIGO (12 caracteres), reutilizando el código aleatorio permanente de invitación, sin nueva tabla/migración. RewriteApache sirve perfil y baseHTML fija asegura recursos/enlaces relativos correctos. El API autenticado y el lector de moderación aceptan código corto y token anterior manteniendo permisos. Pruebas perfilreferidoUI con ruta corta, selecciónQRcorto y perfilesAPI aprobadas. Revertir archivos de esta entrega y publicar versiónsuperior; no borrar códigos/referrals existentes.
