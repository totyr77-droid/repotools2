# Introducción opcional — 13/10/2026

## Archivos creados

- `vistas/onboarding.vista.php`: un único dialog accesible con logo, iconos SVG, progreso y controles. Se incluye en Inicio y Configuración autenticada.
- `js/onboarding.js`: cuatro pasos, apertura segura, cierre/persistencia, foco y navegación de teclado. Sin solicitudes de red.
- `css/onboarding.css`: tarjeta oscura de 480 px, móvil con 16 px laterales, adaptación a pantallas bajas, cruz/acción principal según mano en móvil y reduced motion.
- `tests/onboarding.cjs`: prueba Chrome con servidor PHP y SQLite temporal; no modifica producción.

Modificados: encabezado/pie para cargar el componente y recursos; `cuenta.php` añade «Ver introducción»; prueba PWA prepara un visitante que ya omitió la introducción para seguir validando la interfaz principal.

## Comportamiento y pasos

Automático únicamente en Inicio, 400 ms después de terminar la carga de página. No aparece si el usuario ya interactuó, navega, tiene otra ventana abierta, un bloque ocupado, fullscreen loader o cámara activa. En esos casos se omite durante esa visita, sin abrirse inesperadamente al acabar la tarea. No obliga a escanear, crear ni compartir.

1. Escaneá productos: cámara y códigos EAN.
2. Organizá tu stock: cantidades/productos con cuenta.
3. Trabajá con palets: productos separados por tarea o sucursal.
4. Compartí cuando lo necesites: stock y palets.

Siguiente/Atrás recorren los pasos; Omitir, X, Escape o Empezar guardan visto y cierran. «Ver introducción» en Configuración vuelve al paso uno sin borrar lo guardado. Foco inicial en Siguiente, Tab/Shift+Tab permanecen dentro y al cerrar se devuelve al control anterior o al título. Se usa dialog nativo, aria-modal, título/descripción asociados y progreso live polite. Animación existente sutil y sin movimiento cuando se solicita reduced motion.

## Estado y reset

No hay preferencia genérica de cuenta adecuada: se usa localStorage sin migración ni cambios de APIs. Claves por identidad (ID de cuenta o `guest`): `repotools_onboarding_seen_<identidad>` = `true` y `repotools_onboarding_version_<identidad>` = `1`. No se guardan correos ni nombres. Si almacenamiento falla, memoria de esa pestaña evita insistir durante la sesión.

La primera implementación puede aparecer una vez a cuentas existentes: no se añadieron consultas ni heurísticas para distinguir antigüedad. El estado es por navegador/origen, no sincronizado entre dispositivos; limpiar datos del navegador puede volver a mostrarlo. Invitado y cuenta tienen estados separados.

Para pruebas, reemplazar `<identidad>` por el ID de prueba o `guest`, ejecutar en consola y recargar Inicio:

```js
localStorage.removeItem('repotools_onboarding_seen_<identidad>');
localStorage.removeItem('repotools_onboarding_version_<identidad>');
location.href = 'index.php';
```

No existe botón de debug. Para verlo sin reset usar Configuración → Ver introducción.

## Validación y reversión

onboarding.cjs pasa: primera visita, pasos adelante/atrás, omitir/completar/X/Esc, persistencia/recarga, separación invitado-cuenta, replay, foco/trap, interacción y ventana crítica, tamaños 320/390/1366, ambas manos y reduced motion. Capturas móvil/PC revisadas. Pasan device-connection-ui y pwa-presentation (esta última usa estado visto). Sintaxis JS y PHP lint correctos. Safari y teléfono físico pendientes; no se afirma validación física.

Restaurar encabezado/pie, cuenta.php y prueba PWA desde `1056a58`, retirar los archivos creados. No hay datos de servidor, permisos, sesión, cámaras, Stock, APIs ni service worker modificados. Los restos de localStorage son inocuos.
