# QR de usuario — beta

El encabezado autenticado incorpora una flecha discreta con el QR personal, generado al abrirlo mediante el token opaco de perfil existente. No contiene una contraseña ni una sesión. Se informa que quien lo escanee podrá completar el correo de la cuenta.

Cámaras en correo de accesos al palet, transferencia y responsable de empresa. Reutilizan RepoEANScanner en modo perfil, sin zoom ni guías de palet/OCR. El lector de perfiles existente también desactiva zoom/guías. No se añaden cámaras a inicio de sesión ni edición del correo propio.

Solo admite URL del perfil de esta instalación y token de 64 caracteres hexadecimales. El servidor exige autenticación y CSRF para resolver el QR y devuelve únicamente nombre público/correo. Mantiene los permisos previos de consulta de perfiles y de cada operación. Escanear rellena el campo, sin enviar formularios ni otorgar accesos. Cancelar/cambiar página detiene cámara y descarta consultas tardías. Resolver requiere conexión.

Validación local con SQLite y cuentas temporales: user-qr-ui (QR renderizado, 320/390/1366 ambas manos, destinatario completado sin envío, rechazo de URL externa/palet, errores de token/CSRF/perfil), stock-transfer-ui, user-profiles-api, scanner-zoom (incluye perfil con capacidad de zoom desactivada), service-worker-offline, sintaxis PHP/JS y captura móvil revisada. Sin escrituras de datos reales. Cámara y Safari físicos pendientes.

Reversión: restaurar los archivos de esta entrega al padre y publicar una versión superior del caché; no requiere migración ni elimina tokens existentes. Main permanece intacta.
