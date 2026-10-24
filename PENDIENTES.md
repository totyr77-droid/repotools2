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

## Diagnóstico automático v2.49

El estado de error reportado confirma que el proceso arranca; todavía no identifica la causa. El worker registra ahora etapa (dependencia/conexión/preparación/entrega), categoría y SQLSTATE validado. Sistema muestra etapa y código para manager, sin mensajes de excepción, contenido de avisos ni credenciales. Prueba de salud con suscripción ficticia activa, notificación no enviable y SQLite temporal: checked_at avanza, permisos 401/403/200 y campos privados excluidos. Sintaxis PHP/JS y diff aprobados. No se enviaron pruebas ni se alteraron datos de cuentas. Falta conocer el error de Render y corregir su causa; esta entrega es diagnóstico, no reparación confirmada. Reversión: revertir entrega y publicar caché superior; no requiere migración.

## Entrega v2.50

Push: reemplazado su por setpriv (util-linux explícito en Docker) para bajar privilegios a www-data sin sesión PAM y conservar directamente las variables heredadas. Motivación: fallo de Render en fase database, sin SQLSTATE informado; pérdida de entorno es hipótesis, no causa demostrada. Diagnóstico seguro diferencia DATABASE_URL ausente/inválida y controlador PostgreSQL ausente, sin exponer valores. No cambia credenciales, datos, esquema ni permisos de usuarios.

Validación: startup con stubs y URL ficticia comprueba herencia tanto root/no root y CMD reemplazado; CLI/HTTP con SQLite temporal y suscripción ficticia comprueba avance, fallos de configuración ausente/inválida, permisos y exclusión de secretos. Sintaxis JS/PHP y diff aprobados. Sin Docker local: comportamiento real en Render y entrega automática pendientes de confirmar. Reversión: revertir entrega y publicar caché superior; sin migración.

## Entrega v2.51

Worker root→www-data: HOME=/var/www, USER y LOGNAME de www-data solo para el hijo, manteniendo variables de conexión. Evita heredar /root al consultar archivos SSL de libpq. Es un defecto real del entorno anterior, pero falta confirmar que explique el error PDO sin código reportado en Render. SQLSTATE ahora usa errorInfo como prioridad; clasificación fija de errores de permisos, autenticación, DNS, timeout, rechazo y TLS sin persistir mensajes ni valores privados. Sistema y logs muestran categoría segura.

Validación: tests/push-connection-reason.php, startup/health temporales, sintaxis y diff aprobados. Git Bash reinicia HOME en shells stub: la asignación de identidad se verifica estáticamente; no afirmar prueba Linux/Docker real. Sin Docker instalado, validación Render y recepción física pendientes. Reversión: revertir entrega y publicar caché superior; no altera DB ni credenciales.

## Confirmación de campo — 23/10/2026
El usuario confirma tras v2.51 que Sistema muestra «Envío automático activo» y que recibió los avisos pendientes en el teléfono. Se cierra el pendiente de arranque/conexión del worker y recepción automática comprobada en esta cuenta/dispositivo. No implica validación de todos los dispositivos o tipos de evento. Sin cambios adicionales de código.

## Entrega v2.52 — Empresas y catálogo desde Excel

Integrantes: «Locales visibles» por persona aceptada, todos (incluye locales nuevos) o selección explícita, incluido sin sucursal. Vacío oculta su stock. Configuración por vínculo supervisor/integrante, independiente de otros supervisores y sin modificar palets ni acceso de edición. Servidor aplica el alcance persistente a resumen, palets, mercadería, lotes, comparaciones y exportaciones; revisiones separadas para no interferir con desvinculación. Permisos/CSRF/estado aceptado y validación de locales vigentes. Modal compacto existente, controles en fila y preferencia de mano.

Listas: opción progresiva «Aportar al catálogo general». Detecta y permite mapear descripción, UXB (unidades por bulto), UXC (unidades por caja), con vista previa explícita. No deduce paquetes internos ni convierte UXB↔UXC. Guarda propuestas en la cola de Reportes existente; EAN válido obligatorio, fórmulas/datos inválidos/duplicados contradictorios excluidos del aporte, lista de comparación conservada. Campos no mapeados conservados; fichas actuales no cambian hasta aprobar. Máximo 200 candidatos por carga/día; respeta bloqueo de reportes y evita otro pendiente del mismo autor/EAN. Moderación muestra y permite revisar UXB/UXC; editores anteriores conservan esos valores. Los tamaños aprobados sirven como referencia al cargar Stock cuando no existe referencia de carga previa, sin alterar lotes actuales.

Migración45 aditiva: products.uxb/uxc, selección por integrante y revisión independiente. Probada SQLite idempotente y PostgreSQL en rama aislada test-supervision-catalog-20261023, expiración automática 24/10/2026 23:49 UTC; alcance por defecto conserva42/42 palets de la copia. Sin escritura manual en producción. Pruebas supervision/supervision-import/supervision-export/supervision-catalog, reports/catalog/stock, APIs reports/catalog y UI supervision Chromium/WebKit320/390/1366 ambas manos; guardado vacío/local, permanencia, límites de alcance, importación con mapa y propuesta sin publicación. Se corrigió expectativa vieja de tests/reports-api: moderador no puede bloquear remitentes (política servidor ya existente). Capturas locales revisadas y ajuste de checkbox en fila. Teléfono físico de esta función pendiente.

Reversión: revertir entrega y publicar caché superior conservando columnas/tablas de migración45; nunca eliminar propuestas ni datos del catálogo automáticamente. El código anterior no respeta selección por integrante: antes de retroceder, deshabilitar esos equipos o preparar un parche que conserve la restricción. Aprobaciones ya realizadas son datos reales, requieren corrección auditada, no se deshacen con Git. Push automático confirmado por el usuario tras v2.51; pendiente cerrado en esa cuenta/dispositivo.

## Entrega v2.53 — Importación más clara y aportes sin puntos

Columnas del Excel muestran ejemplos reales y cantidad de códigos válidos. La fila de títulos se elige con opciones que incluyen el contenido de cada fila, dentro de «Ajustar hoja o títulos»; no exige adivinar un número. Aportes al catálogo pasan a modal separado, con controles asociados al formulario original, vista previa y feedback local. Respeta ambas manos y móvil/PC; conserva hoja, correcciones, exclusiones y guardado sin aportes.

Importaciones se marcan en servidor como supervision_excel. Al aprobar no se otorgan report_rewards, puntos de TOP ni eventos de logros/actividad al remitente; el camino manual sigue funcionando. Compatibilidad con aportes anteriores por descripción generada en servidor. Moderación detalla «Importación de Excel · No otorga puntos ni logros». No cambia esquema ni datos del catálogo existentes.

Limpieza de producción solicitada y confirmada explícitamente: borrados exactamente58 reportes de prueba pendientes de Excel, sin fotos, notificaciones asociadas ni recompensas, revalidando estado y dependencias al ejecutar. Confirmado0 pendientes restantes y0 recompensas Excel existentes. No se tocaron productos ni reportes aprobados/rechazados. No documentar datos personales ni IDs de reportes. Esta limpieza no se deshace con Git: recuperación solo desde respaldo/historial de Neon disponible, mediante restauración selectiva aislada.

Validación: supervision-import, supervision-catalog (marcador nuevo y legado, aprobación sin puntos/eventos), supervision, reports y achievements aprobados; UI completa Chromium/WebKit320/390/1366 ambas manos, modal separado, columnas con ejemplos y guardado con controles form externos. Sintaxis y diff, captura WebKit revisada. Reversión: revertir entrega y publicar caché superior; conservar exclusión de recompensas automáticas con un parche si se revierte la UI. No borrar registros para revertir código.

## Entrega v2.54 — Comparativa XLSX y Unidades/Cajas

Exportación comparativa: ancho real basado en celdas con contenido/fórmulas, sin contar celdas vacías con formato como columnas de datos. Conserva columnas originales con contenido, incluso notas a la derecha. Reemplaza referencias vacías al ocupar esas celdas, retira spans desactualizados y divide/ordena rangos de ancho para evitar celdas duplicadas o columnas superpuestas. Recupera encabezado EAN real si una lista antigua guardó otra fila y recupera productos iniciales excluidos por ese encabezado incorrecto; usa la columna EAN guardada. No inserta filas ni desplaza las cantidades respecto de los productos. Mantiene fechas, repeticiones, fórmulas, formatos y otras hojas.

«Stock (unidades)» pasa a «Unidades». Información a descargar permite Unidades y Cajas juntas, como columnas independientes. Unidades sigue siendo el total numérico. Cajas es entero numérico cuando toda la composición es conocida/compatible y exacta; sobrantes y composiciones distintas quedan explícitos, sin cajas fraccionarias. Sin equivalencia se informa la cantidad de unidades sin equivalencia; cero stock se exporta como0 numérico en ambas. Usa presentación boxes del lote o UXC aprobada del catálogo para unidades sueltas; no deduce UXC de UXB ni toca stock.

Validación: supervision/export/import/layout, lectura cruzada de XLSX comprimidos con openpyxl, preservación de fuente/estilos/fórmulas/hojas, primera fila y cero; navegador Chromium y selección conjunta WebKit. Fixture de celdas vacías con estilo/anchos superpuestos/encabezado equivocado y adjunto original previo validados con SDK Open XML oficial2.20:0 errores; ZIP CRC íntegro. No se reprodujo la alerta concreta de Excel con el adjunto original previo, y no se dispone del XLSX exportado de la captura ni Excel interactivo. La apertura física en Excel debe confirmarse con una descarga nueva; no afirmar reparación comprobada en el equipo del usuario. Herramienta SDK temporal ignorada en tmp, sin dependencia nueva de la app.

Reversión: revertir entrega y publicar caché superior; sin migración/edición de DB ni archivos de origen. Archivos ya descargados no cambian; hay que volver a exportar. No regenerar ni borrar listas del usuario para revertir.
