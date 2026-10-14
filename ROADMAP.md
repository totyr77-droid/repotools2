# Pendientes de Repotools

Consultar [ARCHITECTURE.md](ARCHITECTURE.md) para el estado actual y [CHANGELOG.md](CHANGELOG.md) para entregas realizadas. Esta lista no representa funciones ya terminadas.

## Verificación en dispositivos y despliegue

- Probar instalación PWA en iPhone y Android reales, apertura desde su icono, sesión y cámara. La configuración existe; falta validar la experiencia física.
- Confirmar en Safari iPhone el aviso de preparación offline y la recarga en modo avión desde el mismo dominio. Chrome simulado no prueba Safari.
- Probar cámara real, QR lejanos, pistola USB/Bluetooth y conexión celular-PC en ambos sistemas. Las pruebas automatizadas utilizan lectores simulados.
- Verificar configuración de `GEOAPIFY_API_KEY` en Render y cobertura de locales reales en Stock/Moderación; probar selector de tres sugerencias, confirmación visible y dos locales cercanos distintos.
- Verificar las nuevas cabeceras HTTP tras el despliegue Apache/Render, incluido `no-referrer` en recuperación.
- Ejecutar las pruebas pendientes de conexión celular-PC y cambios recientes en una base PostgreSQL aislada; las últimas integraciones usaron SQLite temporal.

## Límites offline actuales

- Considerar edición de productos, alta/reclamación de palets y fotos offline. Hoy se agregan productos a palets/colecciones previamente consultados; no se realizan estas otras operaciones offline.
- Definir si los escaneos de Inicio sin conexión deben sumar al TOP al reconectar. Hoy permanecen en la lista local y no suman.
- Evaluar exportación/respaldo de cargas locales antes de borrar datos del navegador. El almacenamiento local no constituye un respaldo remoto.

## Funciones por definir

- Supervisión como permiso adicional: vínculo por enlace, varios supervisores, desvinculación y lectura de palets vinculados. La edición debe aprobarla exclusivamente el propietario. El rango actual no implementa todavía esta vinculación.
- Empresa como permiso adicional: propuesta aplazada; definir consentimiento y alcance antes de programar. El campo Empresa del perfil es descriptivo.
- Transferencia de palets: definir aceptación del nuevo propietario antes de implementarla.
- Suscripciones y límites comerciales: por definir. El cupo técnico actual de una sesión PC y una móvil ya existe; no hay planes de pago implementados.
- Tendencias por período en General de Moderación, únicamente si se decide ampliar los conteos actuales.

## Offline inteligente — validación pendiente en campo
Implementación progresiva en desarrollo: shell, copias autorizadas, colas compartidas, reintentos y conflictos. Probar Safari físico, red móvil lenta/intermitente y retención de IndexedDB. La lista EAN offline aún es separada de Inicio online; evaluar unificación por identidad sin perder listas existentes. Ver docs/OFFLINE-INTELIGENTE.md.
