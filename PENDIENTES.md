# Continuidad de RepoTools

Actualizado: 24/10/2026. Entrega de código actual: **v2.77 / interna182**. Ver últimas entregas al final y estado vigente en ROADMAP.md; las notas históricas conservan sus comprobaciones de cada momento.

## Trabajo actual

Pruebas indicadas de Excel/API, escáner e impresión confirmadas por el usuario; offline marcado listo a su pedido. Push automático ya confirmado en su cuenta/dispositivo. No reabrir estos pendientes por notas antiguas.

## Estado y prioridades

- Pendientes reales y opcionales: [ROADMAP.md](ROADMAP.md).
- Arquitectura: [ARCHITECTURE.md](ARCHITECTURE.md).
- Próximo trabajo interno: revisar fixture antiguo de notificaciones de vencimiento y mantener documentación; medir rendimiento antes de optimizar.
- Push: espera entre revisiones reducida de60 a15 segundos en v2.72. Resumen diario de vencimientos conservado; medir entrega y carga en producción.
- Empresas, comparaciones/exportación XLSX, selección por integrante/sucursal, cajas internas, etiquetas y moderación están implementadas. Ver entregas recientes para validación y reversión.

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

## Entrega v2.55 — Selección cómoda, cierre de descarga y detalle compacto

Comparación/exportación ya no marca por defecto todo el equipo y las sucursales. Respeta filtros específicos previamente elegidos; si eran «all», pide selección explícita, con resúmenes «Elegí integrantes/sucursales» y botones de consulta/descarga deshabilitados hasta completar alcance. Seleccionar todo queda como acción voluntaria. Listas de más de6 opciones tienen búsqueda local. Columnas iniciales: solo Unidades en comparación, EAN/Producto/Unidades en descarga independiente. Conserva elección ante error.

Descarga muestra Preparando Excel, evita doble envío, cierra el modal solo tras recibir el blob y activar descarga y muestra RepoToast existente «Descarga iniciada». Es confirmación de inicio, no prueba de escritura en disco. Cancelación/navegación o cambio de configuración durante la petición invalida respuesta, y errores/archivo vacío conservan modal y selección sin éxito falso.

Exportación: UXB solo muestra valores conocidos:14 en el ejemplo224 unidades con UXB14 más1 unidad sin UXB; blanco si no hay ninguno; varios tamaños se listan como12 /18 sin cantidades ni leyendas. Vencimientos: una única fecha del producto se muestra sola; varias fechas mantienen cantidades por fecha. Sin vencimiento se explicita; no altera detalle por palet, stock ni permisos.

Validación: UI Chromium y WebKit320/390/1366 ambas manos, sin selección masiva inicial, columnas básicas, buscador con8 integrantes y selección voluntaria, descarga correcta/cierre/único aviso, error503 sin cierre ni pérdida y reintento. Pruebas supervision/export/import/layout para UXB conocido+desconocido, varios tamaños y fecha única/múltiple; sintaxis/diff aprobados. Sin cambios de DB. Teléfono físico pendiente. Reversión: revertir entrega y publicar caché superior; los Excel previos no se modifican.

## Entrega v2.56 — Cajas junto a Unidades y anchos por contenido

Cajas usa resumen compacto:22 +1 unidad, sin repetir tamaño de caja ni «sin equivalencia». Cajas completas sin sobrantes siguen numéricas; si solo hay unidades muestra su cantidad con singular/plural correcto. Suma cajas físicas de las presentaciones conocidas y unidades restantes, conservando total numérico independiente en Unidades. No inventa equivalencia para unidades desconocidas.

Cajas queda inmediatamente después de Unidades en información a descargar y en orden canónico del XLSX. Anchos de las columnas exportadas calculados según encabezado y línea de contenido más larga, con mínimos/máximos por tipo y ajuste multilínea para detalles largos; altura existente y columnas originales se conservan. Funciona tanto en comparación como exportación independiente, incluso solo Unidades.

Validación: supervision/export/layout, lectura cruzada XLSX comprimido y preservación de fuente con supervision-xlsx, UI completa Chromium con selección conjunta/orden real de celdas y descarga, singular/sobrantes/desconocidos, ancho real10 para Unidades y14 para cajas del fixture. Sintaxis/diff aprobados. Sin DB/migración. Reversión: revertir entrega y publicar caché superior; archivos ya descargados requieren exportar de nuevo, no se modifica el original.


## Entrega v2.57 — Ajuste de mercadería en escritorio

Corregida regla de escritorio que forzaba la fila Presentación a ocupar ambas columnas y superponía Cantidad. La grilla existente vuelve a distribuir cada campo en su área; conserva campos internos y diseño móvil, sin controles nuevos ni cambios de datos.

Validación: prueba de geometría Chromium/WebKit en 320/390/1366, ambas manos, unidades/cajas/bultos; stock-inner-packs-ui Chromium aprobado (guardar, editar y retirar paquete interno). Captura de escritorio revisada y diff sin errores. Teléfono físico pendiente. Reversión: revertir esta entrega y publicar con caché superior; no requiere migración.


## Entrega v2.58 — Comparación con selectores separados y Excel legible

Integrantes, Sucursales e Información a descargar se eligen en modales independientes, con iconos existentes, resumen compacto y botón Listo. Conserva búsqueda, selección voluntaria, filtros, vista previa y descarga. Al cambiar integrantes consulta sus locales con stock y selecciona la unión de sucursales permitidas por su alcance; incluye Sin sucursal si corresponde. Se pueden ajustar después. No marca todas las sucursales indiscriminadamente. Descarga y selector de sucursales esperan la consulta; respuestas obsoletas se ignoran y ante error se exige volver a elegir. Permisos siguen validados por servidor.

Encabezados nuevos de comparación y exportación independiente usan fondo violeta RepoTools y texto blanco en negrita. Respeta columnas originales, fórmulas y estilos previos; agrega estilos sin reemplazarlos. Conserva columnas numéricas y anchos por contenido.

Validación: UI completa Chromium/WebKit, modales 320/390/1366 ambas manos, búsqueda con8 integrantes, sucursal automática, ajuste manual, exportación/cierre/error/reintento; supervision, supervision-export, supervision-export-layout y lectura cruzada openpyxl aprobadas. Verificado color/negrita de encabezados y preservación del resto del libro. Capturas móvil revisadas, sintaxis/diff aprobados. Teléfono físico pendiente. Sin migración. Reversión: revertir entrega y publicar caché superior; descargar de nuevo para regenerar Excel.


## Entrega v2.59 — Columnas de identificación simples

Excel de comparación y descarga: Palets muestra solo códigos de8 dígitos como texto, conservando ceros iniciales; Sucursales e Integrantes muestran solo nombres públicos. Valores únicos ordenados, uno por línea, sin unidades, fechas ni prefijo Palet. Encabezados y selección usan Palets/Sucursales/Integrantes. Vencimientos conserva su detalle de cantidades cuando hay varias fechas y Unidades/Cajas mantienen sus totales.

Validación: supervision-export con varios lotes/fechas del mismo palet, varios locales e integrantes, código05121614 y texto real del XLSX; supervision-export-layout y lectura cruzada openpyxl aprobadas. UI completa Chromium con descarga comparativa y exportación independiente; sintaxis/diff aprobados. Sin cambios de permisos ni DB. Reversión: revertir entrega y publicar caché superior; regenerar descargas para aplicar formato nuevo.


## Entrega v2.60 — Una descripción por producto exportado

Comparación y exportación independiente muestran una sola descripción por EAN aunque existan varios lotes o fechas con nombres de referencia diferentes. Se conserva la primera descripción disponible no vacía; totales y detalle de vencimientos mantienen todos los lotes. No modifica las descripciones originales del Excel fuente.

Validación: supervision-export con referencias distintas, varias fechas, primera descripción vacía y contenido real del XLSX; supervision y supervision-export-layout aprobados, sintaxis/diff sin errores. Sin DB. Reversión: revertir entrega y publicar caché superior; regenerar exportaciones.


## Entrega v2.61 — Texto breve en Información a descargar

La opción de fechas se llama Vencimiento, sin la aclaración de cantidades. Cambio solo de etiqueta en el selector; conserva contenido y formato del Excel. Validación: sintaxis PHP y diff sin errores. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.62 — Resaltar lote al abrir desde Mercadería

Ver palet/Ver en Stock y el contenido del lote transmiten EAN y fecha al abrir el detalle. Stock y Mercadería de sucursal resaltan y centran únicamente el lote elegido con la animación existente, conservando sección y despliegues. Resaltado compartido conserva soporte del identificador numérico usado por enlaces previos y respeta movimiento reducido. Los resúmenes de sucursal, incluidos cambios pendientes, exponen EAN/fecha para el mismo comportamiento. Sin cambios de permisos ni consultas de datos.

Validación: stock-goods-ui Chromium/WebKit con mismo EAN y varias fechas, una sola fila resaltada para la fecha elegida; team-inventory-ui Chromium con resaltado, móvil/PC y ambas manos. Sintaxis JS y diff aprobados. Prueba adicional expiry-alerts-ui no alcanzó la apertura por enlace: espera una notificación data-state=today ausente en su fixture actual; no se modificó esa prueba ni notificaciones. Teléfono físico pendiente. Reversión: revertir entrega y publicar caché superior. Sin DB.


## Entrega v2.63 — Actualizar resultados filtrados al cambiar Stock

La recarga de Stock invalida búsquedas anteriores y vuelve a consultar la búsqueda/vencimientos activos tras actualizar los palets. Eliminar un producto deja de mostrar la tarjeta y los totales previos; conserva texto, filtros y sección. También aplica a las otras acciones que recargan Stock. Mercadería conserva su mecanismo de actualización existente para evitar consultas duplicadas. Respuestas de búsquedas previas se descartan con el contador existente.

Validación: stock-search-summary Chromium/WebKit con eliminación real y confirmación del único lote coincidente, resultado vacío, total actualizado, texto y sección conservados; búsquedas exactas, límites y permisos previos aprobados. stock-goods-ui Chromium para filtros, sección, sesión y ambos lados móvil/PC aprobado. Sintaxis JS/diff sin errores. Sin DB. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.64 — Quitar etiqueta sin insignia anterior

El detalle no muestra una segunda etiqueta del propietario cuando el palet pertenece a la cuenta actual: el botón personal ya representa esa etiqueta. Tras guardar/quitar se actualizan la etiqueta del palet activo y el estado compartido de etiquetas antes de renderizar; no reaparece el nombre anterior como insignia separada. Los palets ajenos conservan su etiqueta de propietario y los filtros se actualizan por el evento existente.

Validación: stock-tags-ui Chromium/WebKit con renombrado, borrado real, botón Etiqueta, ausencia de insignia anterior y etiqueta de tarjeta eliminada, luego restauración y flujo completo ambas manos/móvil/PC. stock-tags PHP aprobado para aislamiento personal, permisos, acceso revocado y privacidad. Sintaxis JS/diff sin errores. Sin DB. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.65 — Columnas del Excel sin ejemplos dentro de la lista

Los selectores de columna de EAN y aportes muestran solo letra y título, sin el sufijo Ej. con valores. La ayuda de ejemplos y códigos válidos debajo del selector EAN se conserva. Actualizada la expectativa existente de UI. Validación: sintaxis JS, supervision-import y diff aprobados; cambio solo de texto, sin modificar importación ni datos. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.66 — Compatibilidad Microsoft Excel, títulos y columnas vacías

Investigación con el XLSX aportado por el usuario, sin guardar su contenido en Git: el lector Microsoft OpenXML rechazaba el ZIP emitido por PharData (versión declarada0). Cambiar únicamente la versión de las cabeceras ZIP locales/centrales a2.0 permitió abrir el paquete, y el validador detectó el orden inválido de color dentro de la fuente del encabezado. Exportación ahora normaliza versiones ZIP preservando bytes de entradas, offsets y contenido, y genera fuentes b/sz/color/name en orden válido. Mantiene fondo violeta y texto blanco. Aplica a comparativa y descarga independiente. No modifica archivos ya descargados.

Títulos: si la lista quedó con header0 pero existe una cabecera EAN real, se usa esa fila para las columnas exportadas. Conserva el caso legítimo sin encabezados y los datos originales. Información a descargar inicia con0 columnas tanto en comparación como exportación independiente; exige elegir al menos una para descargar. Vista previa conserva su comportamiento.

Validación: reproducido rechazo del paquete exacto aportado y error de esquema de fuente tras aislar el ZIP; exportación nueva abre directamente con SDK Microsoft OpenXML2.20 y valida0 errores. supervision-export/layout/import y lectura cruzada openpyxl aprobadas, con fórmulas/otras hojas/estilos y ceros iniciales conservados. Regresiones de versión ZIP, orden de fuente y cabecera detectada con header0. UI completa Chromium móvil/PC ambas manos,0 columnas iniciales, elección y descargas correctas. Sintaxis/diff aprobados. No se afirma prueba interactiva en Excel de escritorio: pendiente confirmación del usuario con descarga nueva. Sin DB. Reversión: revertir entrega y publicar caché superior; volverían las incompatibilidades anteriores.


## Entrega v2.67 — Conservar sección del equipo supervisor al actualizar

Mi equipo de empresa registra sección activa en seccion y filtros en integrante/sucursal/vencimiento de la URL, sin datos de nombres ni almacenamiento entre cuentas. Al recargar restaura solo secciones válidas y opciones disponibles tras consultar permisos y equipo actuales. Actualizar equipo y recargas posteriores mantienen sección/filtros, refrescan sus datos y sincronizan panel visible, aria-selected, tabIndex y barra de filtros. No vuelve a Palets por reiniciar el módulo. Secciones sin inventario actualizan sus listas/integrantes por la consulta home existente. Sin cambios de permisos ni DB.

Validación: supervision-ui Chromium/WebKit con botón Actualizar equipo en Integrantes y Mercadería, recarga completa conservando esas secciones y filtro usable, y recarga en Listas (WebKit), además de flujo completo invitaciones/exportaciones/permisos. supervision PHP aprobado; sintaxis JS y diff sin errores. Reversión: revertir entrega y publicar caché superior; parámetros de vista anteriores se pueden ignorar sin migración.


## Entrega v2.68 — Alcance independiente al comparar listas

Comparar lista inicia su alcance propio sin heredar integrante, sucursal o vencimiento de la pantalla de consulta. El usuario elige integrantes/sucursales en el modal y la selección automática de locales se conserva. Descargar stock independiente conserva su comportamiento anterior. El servidor limita los parámetros de exportación a integrantes, sucursales y vencimiento; ignora búsqueda de producto y group_key de un lote para impedir que un detalle limite las columnas de stock. No altera permisos ni el alcance guardado por integrante.

Validación puntual por margen solicitado: supervision y supervision-export aprobados; regresión con dos EAN y group_key/búsqueda de uno, ambos mantienen sus cantidades y alcance permitido. Sintaxis JS y diff aprobados. No se reprodujo el recorrido exacto del usuario con su archivo actual; se corrigió la herencia de filtros y la posibilidad de restringir exportación por group_key comprobada en servidor. Sin DB. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.69 — Productos con stock fuera de la lista

Comparar lista incorpora opción Incluir productos fuera de la lista, desactivada al abrir cada comparación y oculta en exportación independiente. Al activarla, el Excel agrega un bloque al final de la hoja original con EAN del alcance elegido que no figuran en la lista y tienen stock positivo, una sola vez por producto. Usa datos ya autorizados y agrupados por servidor; mantiene integrantes, sucursales y vencimiento elegidos. Conserva todas las filas originales, duplicados, notas, fórmulas y columnas. Si no hay faltantes no agrega bloque vacío.

Bloque con título y encabezados violetas, columnas elegidas en sus posiciones de exportación; si EAN no fue seleccionado, lo incluye en la columna EAN original para identificar cada producto. Mantiene ceros iniciales, composición y detalle de varias fechas. Anchos contemplan también faltantes y se actualiza dimensión final. Agrega después de filas y combinaciones existentes y rechaza exceder el límite de filas de Excel. Sin nuevas consultas de permisos ni migración.

Validación: supervision-unlisted PHP (opción apagada, EAN faltante único, cero excluido, datos originales y notas, columnas elegidas, solo Unidades y ceros iniciales), supervision/export/layout aprobados. UI puntual Chromium/WebKit con descarga real de EAN fuera de lista, cantidad25, cierre y reinicio desmarcado; geometría WebKit320/390/1366 ambas manos, captura revisada. SDK Microsoft OpenXML2.20 valida archivo nuevo con0 errores; sintaxis PHP/JS y diff aprobados. Excel de escritorio interactivo pendiente de prueba del usuario. Reversión: revertir entrega y publicar caché superior; regenerar descargas.


## Entrega v2.70 — Bloque fuera de lista sin cientos de filas vacías

Corregida ubicación del bloque adicional: se calcula el final según celdas con contenido o fórmulas, respetando combinaciones existentes, y se eliminan solo filas vacías finales con formato al agregar productos fuera de lista. No se agrega después del relleno invisible del Excel. Sin productos adicionales se conserva el archivo como antes. Mantiene notas, valores, fórmulas, stock y selección de alcance.

Diagnóstico del archivo aportado: el bloque sí estaba generado, pero después de cientos de filas vacías, lo que lo hacía difícil de encontrar. Se reprodujo con la planilla concreta de forma local; el mismo producto y cantidad quedaron junto al final real de la lista. No se guardaron archivos ni datos personales en Git, ni se modificó la base.

Validación: regresión con921 filas de formato vacío y nota final; bloque vuelve a fila6 del fixture y conserva su nota. supervision-unlisted y supervision-export-layout aprobados. Reproducción local con archivo aportado, cantidades conservadas y SDK Microsoft OpenXML2.20 con0 errores. Sintaxis PHP/diff aprobados. No se repitieron pruebas UI porque no cambian controles. Reversión: revertir entrega y publicar caché superior; descargar de nuevo para regenerar.


## Entrega v2.71 — Descripción y UXB para productos fuera de lista

El bloque adicional completa las columnas originales de descripción y UXB/BXU además de las columnas elegidas, sin sobrescribir filas originales. Detecta títulos de descripción y unidades por bulto; para títulos libres como marcas solo infiere descripción cuando existe una única columna de texto entre las filas de EAN. No adivina una columna ambigua. Mantiene una fila de separación y el bloque junto al último contenido real. EAN también queda en la columna original para identificar cada producto. Ajusta altura por el ancho original para que se lea información faltante.

Para productos agregados prioriza descripción y UXB aprobadas del catálogo, luego referencias/composición conocidas de Stock; si no hay datos escribe No tenemos información. No inventa UXB a partir de peso, packaging ni UXC. El servidor enumera EAN fuera de lista solo dentro del alcance autorizado; antes de exportar el cliente consulta fichas ausentes mediante api/productos.php existente, con progreso local y cancelación por cambio/cierre de modal. lookup_product ya persiste automáticamente fichas válidas de la API, respeta caché negativa y límites y no otorga puntos ni logros. Se vuelve a leer el catálogo al exportar. La API actual no proporciona UXB fiable: ese dato solo se completa si está disponible en catálogo o composición conocida. Fallos/límite externo permiten descargar con datos disponibles y aviso en el toast. No se modificaron reglas globales de lookup ni permisos. Sin migración.

Validación: supervision-unlisted/export/layout/supervision/catalog aprobados; catálogo canónico y UXB14, originales intactos, descripción con título libre y BXU, campos desconocidos explícitos. UI Chromium con consulta API simulada que persiste ficha y descarga que vuelve a leerla en columna original; opción, alcance, reinicio/cierre conservados. SDK Microsoft OpenXML2.20 valida fichero con0 errores; sintaxis PHP/JS y diff sin errores. Sin consultas externas reales durante pruebas. Reversión: revertir entrega y publicar caché superior; fichas API obtenidas por el circuito existente permanecen en catálogo.


## Revisión de pendientes — 24/10/2026

Actualizados encabezado y ROADMAP con confirmaciones del usuario, distinguiendo pruebas realizadas, mantenimiento interno y mejoras opcionales. No se alteró código ni configuración. Inspección push: espera60 segundos entre pasadas y resumen diario desde09:00 Argentina; reducir espera a15 es propuesta, no cambio aplicado. Reversión: revertir esta actualización documental; no afecta datos ni versión de la app.


## Entrega v2.72 — Revisión de notificaciones cada15 segundos

Worker push reduce de60 a15 segundos la espera entre pasadas, conservando ejecución secuencial (no superpone procesos), conexión corta, identidad del proceso y configuración de desactivación. No altera preparación/cola, deduplicación, permisos, reintentos ni resumen diario de vencimientos desde09:00 Argentina. La espera del servidor se reduce; tiempo de procesamiento y entrega del proveedor/teléfono siguen siendo adicionales. Mantener observación de carga por mayor frecuencia de consultas.

Validación: bash -n y push-startup aprobados. push PHP aprobado para cola/dedupe, tipos, vencimientos agrupados, no lectura automática, bajas y cifrado con HTTP simulado; en Windows requiere OPENSSL_CONF de la instalación XAMPP para generar claves de prueba, sin cambio de configuración de la app. Diff sin errores. No se enviaron avisos reales ni se modificaron datos. Recepción física tras despliegue pendiente de medir. Reversión: restaurar sleep60 y publicar caché superior; no borrar claves, suscripciones ni cola.


## Entrega v2.73 — Unidades por bulto sin duplicar UXC

Stock online/offline y retiros muestran Unidades por bulto para el total exterior, tanto en cajas como bultos. Mantienen presentaciones internas boxes/packs, cantidades por presentación, pack_size e inner_pack_size y controles de paquetes por caja/unidades por paquete. No convierte ni modifica lotes, subcajas, borradores ni colas existentes.

Aportes Excel y revisión muestran una sola entrada visible UXB; UXC queda como compatibilidad interna. El mapeo UXC de aportes está deshabilitado y no se envía automáticamente. Archivo con solo UXC puede proponerse en UXB; si trae UXB y UXC exige elección explícita del total exterior, sin adivinar equivalencia de subcajas. Backend conserva clientes, propuestas e historial antiguos; revisión mantiene registro anterior y su dato sin duplicar el control visible. Descripciones del selector/exportación unificadas.

Nuevo helper de lectura de catálogo prioriza UXB y usa UXC antiguo solo si UXB no está informado. Referencias reales ya cargadas por presentación conservan prioridad; referencias de catálogo y cálculo de cajas sin composición propia usan ese total canónico. Exportación acepta cabeceras antiguas como alias sin reescribir el Excel original. Sin migración, borrado ni fusión masiva de datos.

Validación: stock-inner-packs-ui Chromium/WebKit360 unidades, edición y retiro de30; stock-pack-references-offline-ui Chromium con cajas/paquetes/bultos independientes, referencias y sincronización exacta. supervision-ui Chromium con UXC oculto, UXB ambiguo sin preselección, elección explícita y propuesta sin UXC nuevo. supervision-export/unlisted/catalog y catalog-reference/catalog/reports aprobados, incluida prioridad UXB y respaldo UXC. Sintaxis PHP/JS/diff sin errores. Reversión: revertir entrega y publicar caché superior; no borrar datos ni colas offline.


## Entrega v2.74 — Listas, búsqueda y orden del equipo

Agregar lista pasa a botón principal con formulario en modal. Cada lista incorpora configuración con icono SVG existente para editar nombre y descripción opcional. Descripción guardada en metadatos versionados existentes, sin migración; conserva archivo original, EAN, encabezado y permisos de empresa. Validación de servidor y CSRF para actualizar, sin puntos ni aportes automáticos al catálogo.

Supervisor: buscador por referencia/descripción de catálogo, marca, EAN y número de palet en Palets y Mercadería, con espera breve al escribir y coincidencias literales (no comodines). Respeta integrantes y locales autorizados, totales y paginación. Ordenar por agrega mayor/menor cantidad y vencimiento cercano/lejano, sin fechas al final y desempate estable. Conserva búsqueda/orden al actualizar o recargar. No hereda búsqueda al Excel: exportación mantiene su alcance independiente. Sin resultados ofrece Limpiar filtros.

Revisión de terminología: carga online/offline y retiro conservaban Cajas cerradas, Paquetes por caja y un título duplicado Unidades por bulto/bulto; corregidos a Bultos cerrados, Paquetes por bulto y Unidades por bulto. Se oculta la opción antigua packs sin eliminar su valor para editar lotes/borradores anteriores; datos, equivalencias por presentación, paquetes internos y colas se conservan. Mensajes de composición/cantidad y pendientes también corregidos. Alias de importación Unidades por caja se conserva para leer Excel antiguos.

Validación: supervision PHP con edición propia/denegación ajena, EAN intactos, búsqueda por nombre/EAN/palet y comodines literales, orden en ambas vistas con stock real de fixture; supervision-export aprobado. UI Chromium/WebKit: crear/configurar lista, descripción persistida, modales320/390/1366 ambas manos, comparación/descarga y permisos. Bultos internos Chromium/WebKit360 unidades, edición y retiro30; referencias online/offline Chromium independientes y sincronización exacta. Sintaxis y diff aprobados. Sin modificación de datos reales. Reversión: revertir entrega y publicar caché superior; metadatos de descripción pueden permanecer sin afectar clientes anteriores, no borrar lotes ni colas.


## Entrega v2.75 — Integrantes en una fila

Se reemplazan botones largos Locales visibles/Quitar por iconos SVG existentes con nombres accesibles y títulos. Fila de tres columnas: nombre/estado central sin saltos (elipsis para nombres largos), acciones táctiles44px a los lados. Quitar permanece alejado de la mano hábil; configuración de locales del lado próximo. Invitaciones pendientes conservan cancelar con confirmación. Sin cambios de servidor, permisos ni datos.

Validación: geometría Chromium320/390/1366 ambas manos con nombre largo, sin desborde y elementos en una fila; sintaxis JS/diff aprobados. Flujo supervision-ui Chromium mantiene configuración de locales, invitaciones y acciones existentes. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.76 — Limpiar filtros del supervisor

Barra de Palets/Mercadería incorpora botón compacto con SVG de cierre, título y nombre accesible Limpiar filtros, visible solo con búsqueda, integrante, sucursal, vencimiento u orden activo. Restablece todo en un toque, limpia parámetros de URL, cierra menú y actualiza inventario sin cambiar sección. Reutiliza la misma acción del estado vacío. Cancela búsqueda pendiente al usar el botón. Barra móvil distribuye búsqueda en primera fila y acciones en otra, evitando desborde; respeta mano elegida. Sin cambios de servidor ni datos.

Validación: sintaxis JS/PHP y diff aprobados; supervision-ui Chromium comprueba búsqueda sin resultados, botón limpiar, inventario recuperado, orden predeterminado, botón oculto y Mercadería activa, además del flujo existente. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.77 — Cancelar búsqueda después de escanear

Stock sincroniza el icono de búsqueda/filtros al comenzar cada búsqueda, también cuando el texto fue asignado por escáner o restaurado desde sesión, antes de consultar resultados. Se reutiliza RepoStockTags.apply existente: misma acción de borrar búsqueda/filtros, estado accesible e icono, en Palets y Mercadería. Sin cambios en API, cantidades, permisos ni datos.

Validación: stock-empty-search-ui Chromium con escáner simulado completa EAN ausente, verifica Borrar búsqueda y filtros, cancela y recupera lupa/campo vacío en ambas pestañas; conserva comprobaciones móvil/una mano. Sintaxis/diff aprobados. Reversión: revertir entrega y publicar caché superior.
