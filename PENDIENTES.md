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
