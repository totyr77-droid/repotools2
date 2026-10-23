# Continuidad de RepoTools

Actualizado: 23/10/2026. Entrega actual: **v2.42 / interna 147**, permisos del staff y registros. Última versión pública comprobada antes de esta entrega: v2.41 / 146. Comprobar147 después de publicar.

## Trabajo actual

1. Documentación obsoleta limpiada y archivada, contrastada con el código y las entregas actuales.
2. Notificaciones push de celular con la app cerrada, autorizadas por el usuario para **vencimientos, invitaciones y solicitudes**. Reutilizar service worker y notificaciones internas; recibir un aviso no marca leído. Activación voluntaria en Ajustes y soporte de instalación en iPhone. Implementadas y validadas automáticamente; publicar y probar recepción física. No afirmar recepción real hasta comprobarla.

## Estado y prioridades

- Pendientes reales y funciones opcionales: [ROADMAP.md](ROADMAP.md).
- Arquitectura vigente: [ARCHITECTURE.md](ARCHITECTURE.md).
- Última entrega: Registros separados en Usuarios/Mercadería/Moderación/Gestión, operaciones agrupadas antes de paginar, detalle siempre en modal, sin desplegar página. Deltas/antes-después/vencimientos/transferencias, filtros y sesiones nuevas; permisos existentes conservados. [Detalle y reversión](docs/REGISTROS-MODERACION.md).
- Supervisión/Empresas, consentimiento de integrantes, listas XLSX, filtros/columnas de exportación y transferencias **ya están implementados en main**. No volver a tratarlos como funciones pendientes.
- Cajas con paquetes internos, referencias online/offline y etiquetas personales para Usuario también están implementadas. Falta la validación física descrita en ROADMAP.

## Entorno y reglas de continuidad

El checkout activo es tmp/upstream dentro del workspace C:/xampp/htdocs/repotolls2; aquí rama main con publicación autorizada por los pedidos actuales. La beta es independiente y debe conservar su distintivo BETA exclusivamente allí. No cambiar el checkout beta ni copiar configuraciones privadas entre entornos.

Conservar cambios locales del usuario, preferencias de mano, componentes/SVG y diseño minimalista. Permisos en servidor, CSRF, revisiones, transacciones/idempotencia y colas offline vigentes. No borrar IndexedDB ni inventario al revertir. No guardar credenciales, datos privados ni archivos de usuarios en Git.

Publicar solo archivos propios de la entrega, documentar validación/reversión, incrementar release/cache con scripts/bump-release.cjs y comprobar versión pública antes de afirmar despliegue. Pruebas automatizadas de Chromium/WebKit no sustituyen iPhone/Android físicos ni justifican una puntuación objetiva.

## Validación de v2.37

PHP de auditoría/agrupación/permisos/Stock/transferencias/dispositivos; API Stock y salida; UI Chromium/WebKit con API real 320–1366, ambas manos/modos; perfiles y presentación unificada aprobados. Consulta PostgreSQL real con CTEs sintéticas, solo SELECT en rama de prueba. Sin migración ni modificación de datos reales. Teléfono físico pendiente.

Reversión: revertir cdfbd3d y publicar versión/cache superior. Conservar audit_events, metadatos de instantáneas, ranking e inventario; código anterior ignora transaction_id/transfer.

## Historial

Las notas anteriores se archivaron sin borrar en [HISTORIAL-CONTINUIDAD-20261022.md](docs/HISTORIAL-CONTINUIDAD-20261022.md). Contienen decisiones de beta y pendientes ya superados: no prevalecen sobre este documento ni sobre el código actual. Entregas versionadas recientes: release.json; historial adicional: CHANGELOG.md y docs/HISTORIAL-PROYECTO.md.


## Entrega v2.38: validación y reversión

Push voluntario en Avisos y respuesta, vencimientos agrupados diarios e invitaciones/solicitudes; campana sin lectura automática. Suscripciones ligadas al dispositivo/sesión, bajas y endpoints vencidos, validación al recibir y navegación segura. Proceso CLI de envío incluido en Docker cada60s; VAPID persistente en DB, nunca en Git/API. Migración aditiva44 y Composer bloqueado/auditado; sin servicio adicional.

Pruebas PHPpush/stock-notifications/unified-core/audit-records y APIStock/logout aprobadas; UIpush Chromium/WebKit con APIreal/dispositivo simulado,320–1366/ambasmanos/modos y destino vencimientos; serviceworker verificación/nolectura/privacidad, regresión Ajustes y sintaxisPHPJS/shell/diff. PostgreSQL44 y dedupe en tablas temporales de rama prueba, sin tocar datos reales. Cifrado/firma reales con HTTPsimulado, no envíos reales ni construcción Docker local. Recepción física/Render pendiente. Fixture Ajustes bloquea SW solo en pruebas para que POST503 simulado sea interceptado.

Reversión: docs/NOTIFICACIONES-PUSH.md. Mantener tablas44/claves VAPID/colas/inventario y publicar versión/cache superior; detener worker por variable si hace falta. Historial previo preservado sin credenciales.

## Entrega v2.39

Aviso voluntario de activación desde la app para cuentas sin push activo, espera ante intro/otros avisos/cámara/escritura; posposición 30 días. Mantiene activación manual y campana sin lectura. Validación y reversión en docs/NOTIFICACIONES-PUSH.md. Sin cambios de DB.

## Entrega v2.40

Urgencia naranja hasta7 días en etiquetas de fechas/cantidades/días y +N por fechas distintas, ejemplos en configuración y lectura del plazo0–365 corregida. Sin migración. Detalle, validación y reversión: docs/VENCIMIENTOS-URGENTES.md. Comprobar145 después de publicar; no confundir pruebas de navegador con recepción/pruebas físicas.

## Entrega v2.41

Guías de cámara corregidas: número separado del marco QR, adaptación por altura y recorte QR/OCR consistente. Detalle/validación/reversión: docs/GUIAS-CAMARA-20261022.md. Sin DB ni cambios de permisos/inventario.

Prioridad pendiente detectada en prueba física push: usuario confirmó recepción de pruebas enviadas manualmente (proveedor201); el worker automático no toma la cola (checked_at0/attempts0). Consultas de preparación verificadas en PostgreSQL sin cambios. Revisar arranque/logs/override del contenedor Render. No afirmar envío automático operativo hasta corregirlo y verificarlo.

## Entrega v2.42

Staff: moderador solo sus actuaciones; administrador sus actuaciones y las de moderadores; manager global. Usuarios/sesiones y asignación de rangos exclusivos de manager; perfiles superiores y vías alternativas de historial protegidas. Registros con iconos/tonos por acción, Antes/Después y enlaces a palet/perfil con permisos. Filtros adaptados para no taparse con menú móvil. Sin migración ni cambios de roles reales. Detalle, validación y reversión: docs/REGISTROS-MODERACION.md. Se mantiene pendiente la reparación del worker push de Render.

## Entrega v2.43

Biblioteca: papelera en el visor EAN, igual a la de cada producto, a la izquierda para mano derecha y a la derecha para mano izquierda. Confirmación compartida con los boxes; elimina solo de la colección, conserva el siguiente producto (o el anterior al borrar el último), actualiza contador y cierra el visor si queda vacía. Evita doble envío y conserva el producto ante error/sin conexión. Se mantiene la validación de propietario y CSRF del servidor existente, sin cambios de DB.

Validación: PHP/JS y diff; tests/library-viewer-remove-ui.cjs con API local y SQLite temporal en Chromium/WebKit,320/390/1366, ambas manos, nombres largos, cancelar/error/reintento/último producto. Service worker bloqueado solo en el fixture para simular503. Revisión visual local; teléfono físico pendiente. Reversión: revertir esta entrega y publicar versión/cache superior; productos ya quitados no se recuperan con el revert. Sigue pendiente worker push de Render.

## Entrega v2.44

Stock recuerda por cuenta/dispositivo la última presentación elegida explícitamente para cargas nuevas (cajas, bultos o unidades). Comparte preferencia con la página offline, sin trasladar unidades por caja entre productos. Las ediciones conservan su presentación, cajas parciales y borradores mantienen su comportamiento; retiros no cambian la preferencia. Sin nuevo control ni migración; permisos y cálculos servidor conservados.

Validación: sintaxis JS/diff y stock-pack-references-offline-ui con API SQLite temporal, continuidad entre cargas, cajas/paquetes/bultos independientes, fallback offline, sincronización exacta e aislamiento por cuenta. Reversión: revertir entrega y publicar caché superior; la clave local de presentación puede quedar ignorada, no afecta stock. Teléfono físico pendiente. Worker push de Render continúa pendiente.

Prueba anterior aprobada en Chromium y WebKit, con vistas320/390/1366 y ambas manos. No requiere cambios visuales: reutiliza el selector de presentación existente.

## Entrega v2.45

Vencimientos: al apagar el acceso rápido restaura el orden previo en Mis palets y Mercadería, retirando el filtro automático «Vencimiento más cercano». Conserva un orden distinto elegido manualmente mientras estaba activo y guarda el orden anterior en la sesión para poder volver tras recargar. Sin cambios de DB/permisos ni diseño.

Validación: tests/stock-expiry-toggle-ui.cjs con API local/SQLite temporal en Chromium/WebKit: activar/desactivar en ambos paneles, restaurar orden habitual o descendente y ocultar etiqueta automática. Sintaxis JS y diff aprobados. Reversión: revertir entrega y publicar caché superior; el campo adicional de sesión queda ignorado por código anterior. Continúan pendientes teléfono físico y worker push de Render.


## Entrega v2.46

Moderación → Usuarios: Ingresar exclusivo de manager en cada fila y búsqueda parcial en vivo por nombre/correo/empresa combinada con rango, sin caracteres comodín involuntarios. Acceso con cambios reales, sesión/cookie/CSRF separados, permisos del usuario, aviso permanente y regreso a la cuenta principal, plazo30min y revocación por rango/dispositivo/seguridad. Dispositivos y sesión del usuario conservados. Auditoría identifica al manager real y la cuenta utilizada. Datos temporales separados; guardar requiere conexión, sin cola offline ni fallback del manager. Seguridad/rangos/push/dispositivos desde cuenta principal. Sin migración ni datos reales modificados en pruebas.

Detalle, validación y reversión: docs/ACCESO-COMO-USUARIO.md. Chromium/WebKit con API temporal, edición real de formulario, aislamiento, permisos, cortes/vencimiento y una mano; worker y regresiones de sesiones/dispositivos/perfiles/staff/registros aprobados. Teléfono/PWA física y reparación del worker push de Render siguen pendientes.

## Entrega v2.47

Comprobantes de sincronización: diseño aprobado aplicado al modal real de Stock/Biblioteca. Título corto por contexto, contador de movimientos, nombre de producto, palet/colección con SVG, carga positiva verde o retiro negativo rosado, EAN discreto y confirmación con fecha/hora compactas. Mantiene advertencia de comprobantes antiguos no verificables y movimientos posteriores; cierre de44px según mano hábil. Varios movimientos conservan lista desplazable dentro del modal, sin tarjetas extra. Solo presentación: no cambia cola, confirmaciones, vencimiento/lectura del aviso, stock ni permisos.

Validación: tests/sync-receipts-ui.cjs Chromium/WebKit,320/390/1366 ambas manos, retiro/carga singular, biblioteca, múltiples comprobantes, legado, movimientos posteriores y ciclo de lectura/expiración; sintaxis JS y diff. Captura local revisada. Teléfono físico pendiente. Reversión: revertir entrega y publicar caché superior; no borrar comprobantes ni cargas locales. Worker push de Render sigue pendiente.


## Entrega v2.48

Push automático: lectura de producción confirma suscripciones vigentes sin revisión y avisos nuevos pendientes, sin intentos de envío. Arranque movido de CMD a ENTRYPOINT para conservar loop ante comandos de inicio reemplazados en Render; PHP absoluto, errores de dependencia explícitos, estado local seguro y consulta manager en Moderación → Sistema. No cambia esquema, claves, suscripciones ni reglas de envío. Pruebas startup/health/PHPpush/SW y sintaxis; detalle y reversión docs/NOTIFICACIONES-PUSH.md. Falta comprobar en Render que avance checked_at y recepción automática física: la causa exacta del proceso detenido no se puede asegurar sin sus logs. No confundir esto con las entregas manuales ya confirmadas.
