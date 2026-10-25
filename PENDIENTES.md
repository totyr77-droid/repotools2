# Continuidad de RepoTools

Actualizado: 24/10/2026. Entrega de código actual: **v2.98 / interna203**. Ver últimas entregas al final y estado vigente en ROADMAP.md; las notas históricas conservan sus comprobaciones de cada momento.

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


## Entrega v2.78 — Descarga compacta junto a empresa

Mueve Descargar stock al lado del nombre de empresa, con icono SVG existente, control44px y nombre accesible/título. Conserva ID y flujo del modal de descarga. Barra de inventario deja Filtros, buscador flexible y limpiar; corrige orientación de label que apilaba lupa e input y evita que descarga quite espacio. Adaptación de mano en encabezado y barra, nombre largo con elipsis. Sin cambios en consultas ni permisos.

Validación: sintaxis PHP y diff aprobados; geometría Chromium con markup real y CSS de supervisión320/390/1366 ambas manos, limpiar visible, input>=90px, filtro alineado y descarga junto al nombre, sin desborde. Cambio visual: no se repitió suite completa de exportación ya aprobada, conserva eventos/ID. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.79 — Estadísticas compactas en Moderación General

Panel General conserva acceso exclusivo de manager validado en servidor. Agrega Hoy/7 días/30 días/Personalizado (hasta un año, fechas reales, sin futuro), fechas interpretadas en Argentina y consultas UTC con final exclusivo. Cuatro cifras principales: usuarios nuevos, cuentas con actividad registrada, movimientos de stock y pendientes actuales. Bloques secundarios simples para actividad por sección, resoluciones y tiempo medio, stock y equipos actuales; iconos SVG ya existentes. Totales actuales explícitamente separados de métricas del período. Sin resultados de resolución muestra Sin datos. Pendientes enlaza a su lista y Ver registros mantiene fechas, sección y resultado confirmado.

Actividad usa identidades de transacción existentes de audit_operation_sql y misma clasificación de registros, agrupando por actor/operación/sección; excluye resultados no confirmados y no deduce operaciones por hora. Usuarios activos se definen por auditoría confirmada, no sesiones concurrentes ni visitas sin evento. Nuevos usuarios según creación; resoluciones según fecha de revisión, promedio entre creación y resolución, sin inventar duración para ausencia de datos. Vínculos de integrantes cuenta relaciones aceptadas en empresas activas, puede repetir personas con varios supervisores. No migración, tablas nuevas, librerías ni modificaciones de datos de producción.

Validación: moderation-stats PHP con límites UTC/Argentina,7 días inclusivos, fechas inválidas, futuro/invertidas, transacción con dos eventos contada una vez, exclusión de denegados y eventos fuera de período, usuarios únicos, resolución2h y vacío. moderation-stats-ui Chromium/WebKit con API real/SQLite temporal: panel, períodos, fechas, navegación a registros, anchos320/390/1366 ambas manos y denegación403 a administrador/moderador/usuario. Sintaxis PHP/JS y diff aprobados; revisión visual del panel móvil. PostgreSQL mantiene SQL por dialecto existente, no ejecutado contra producción. Reversión: revertir entrega y publicar caché superior; sin datos que deshacer.


## Entrega v2.80 — Encabezado del equipo con nombre de empresa

Supervisor muestra Mi equipo - nombre de empresa en título principal, sin repetir nombre en otro encabezado. Debajo botón Descargar stock con icono y texto pequeño del tamaño de Supervisor · Consulta, conservando área táctil44px, ID y modal existentes. Acciones adaptadas a mano elegida. Cuentas sin empresa mantienen Supervisores. Nombre escapado en PHP y actualizado con textContent. Sin cambios de datos/permisos.

Validación: sintaxis PHP/JS y diff; geometría Chromium320/390/1366 ambas manos con botón debajo del título, área44px y sin desborde. No se repite flujo de exportación porque conserva ID/eventos. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.81 — Armonía del encabezado de equipo

Título y actualizar comparten primera línea; debajo rol discreto y descarga pequeña con texto/icono, sin fondo de botón que compita con pestañas. Espaciado/alineación uniformes;44px táctiles preservados, ambas manos. Elimina estilos anteriores de acciones y conserva ID/eventos/modal. Actualizar oculto sin empresa. Sin datos/permisos nuevos.

Validación: PHP/JS/diff y geometría Chromium320/390/1366 ambas manos, título sobre acciones y objetivos44px sin desborde; captura local revisada. No se repite suite funcional de exportación. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.82 — Espacio interno en invitaciones de Stock

Invitaciones incorporan12px de espacio interno horizontal/vertical y separación12px entre información y acciones. Corrige texto pegado a la franja izquierda de la invitación resaltada; aplica también a invitaciones normales. Conserva acciones, foco, permisos y área táctil. Sin datos ni JS modificados.

Validación: geometría Chromium320/390/1366 ambas manos, texto separado12px, botones>=44px y sin desborde; diff aprobado. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.83 — Espaciado y acciones de listas

Separa Agregar lista14px de pestañas. Cada lista usa fila estable con nombre/filas, configuración, Comparar y papelera sin salto inferior; títulos largos con elipsis. Papelera alejada de mano hábil, acciones>=44px. Reutiliza eventos/confirmación/modal sin cambios de servidor/datos.

Validación: sintaxis JS/diff y geometría Chromium320/390/1366 ambas manos, nombre largo y tres acciones en una fila sin desborde, controles>=44px. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.84 — Invitación de integrante en modal

Integrantes conserva sección propia. Invitar integrante pasa de desplegable a botón principal con modal dedicado, mismo patrón de Agregar lista, iconos existentes y separación de pestañas. Formulario de correo con foco, Enviar invitación, progreso y errores dentro del modal, cierre/reset solo al enviar correctamente y confirmación en pantalla; datos se conservan ante error. Estado vacío abre el mismo modal. No altera consentimiento, permisos ni API.

Validación: sintaxis PHP/JS y diff; supervision-ui Chromium con nuevo botón/modal y flujo real local de invitación/aceptación, listas, consulta y permisos. Reversión: revertir entrega y publicar caché superior.


## Entrega v2.85 — Alertas de Supervisores y encabezado compacto

Confirmaciones y errores de Supervisores/gestión de empresas usan RepoToast explícito, sin depender de detectar frases ni dejar mensajes globales persistentes. Progreso de invitación/configuración/locales en el botón, con restauración y bloqueo de doble envío. Errores no se duplican en modal y párrafo; se conservan valores para reintentar. Filas de Excel por revisar, datos de comparación, vacíos y explicaciones necesarios permanecen inline. Descargas conservan su alerta existente. Revisión adicional encontró errores de nombre público inline: pasan a alerta, con fallback accesible si RepoToast falta. Otros textos revisados eran estados de copia offline/resultados o confirmaciones ya cubiertas por alertas; se conservan.

AGENTS.md registra criterio para futuros desarrollos: avisos rutinarios por alertas existentes, texto inline solo cuando es necesario para entender/corregir contenido, progreso local y modales para decisiones/tareas. Reduce separación del encabezado a pestañas y elimina márgenes de estado global vacío, sin reducir controles44px.

Validación: supervision-ui Chromium real local con confirmaciones y error de descarga en RepoToast, importación, invitaciones y permisos; sintaxis JS/diff. Conserva contexto de revisión/importación. Sin DB. Reversión: revertir código de entrega y publicar caché superior; criterio de UX puede conservarse. Respaldo offline pospuesto por usuario, siguiente función CSV/XLS.


Continuidad: CSV/XLS solicitado como próximo desarrollo. Investigación inicial documentada en ROADMAP.md; sin cambios de importador/dependencias. Pausa preventiva por límite de uso incluido, no consumo adicional autorizado. Alertas y márgenes ya publicados en v2.85. Respaldo offline pospuesto.


## Entrega v2.86 — Orden de vencimientos compatible con PostgreSQL

Orden de inventario supervisor usaba expires agregado como alias dentro de CASE en ORDER BY, válido en SQLite pero no en PostgreSQL. Agrupación pasa a tabla derivada inventory_groups y orden se aplica afuera, donde expires y units son columnas disponibles. Conserva permisos, filtros, fechas ausentes al final, desempates y paginación.

Validación: supervision PHP con ambas vistas/órdenes aprobado, sintaxis/diff. No se ejecutó PostgreSQL de producción; corrección de estructura SQL por restricción de alias identificada. Reversión: revertir entrega y publicar caché superior. Cámara faltante en buscador de Supervisores confirmada en markup: queda solicitada, antes de CSV/XLS. Se difiere incorporación por apenas3% de uso incluido y prohibición de créditos adicionales.


## Entrega v2.87 — Cámara del supervisor e importación CSV/XLS

Buscador de Supervisores agrega cámara con lector existente y selección Producto/Palet. EAN validado o QR propio completa búsqueda respetando sección/filtros/permisos, cierra y detiene cámara; entrada manual devuelve foco, cierre/navegación detienen lector. Reutiliza modal y estilos de búsqueda de Mi equipo, iconos existentes,44px y mano elegida. No activa Inicio ni modifica inventario.

CSV y XLS pasan a estar disponibles junto con XLSX en Agregar lista. Normalización de servidor preserva XLSX sin cambios, CSV literal con separador/codificación y XLS mediante lector mantenido; vista previa informa conversión, CSV permite corregir separador. Comparación/exportación/aportes usan flujo existente. Dependencia PhpSpreadsheet5.10.0 y zip en Docker; detalles/límites/reversión en docs/IMPORTACION-CSV-XLS.md. Sin migraciones ni consultas externas para datos del usuario.

Validación: supervision-ui Chromium/WebKit con cámara simulada EAN/QR/manual/cierre y comparación descargada de CSV/XLS con EAN normalizado y150 unidades de fixture; importación/catalog/export PHP, formatos con comillas/BOM/Windows1252/ceros/texto tipo fórmula/límites/archivos inválidos, XLS hojas/estilos/fórmulas y XLSX intacto aprobados. Composer audit sin vulnerabilidades conocidas, requisitos locales con gd/zip aprobados, sintaxis/diff. Cámara física y Docker local no disponibles; no afirmar prueba de hardware ni construcción local. No datos reales cambiados. Reversión: revertir entrega y publicar caché superior; listas convertidas quedan XLSX compatibles.


## Entrega v2.88 — Unidades por bulto junto a contenido neto

Reporte/alta de producto, edición del catálogo y revisión de moderación muestran Contenido neto y Unidades por bulto juntos, sin ocultar UXB en Más datos ni duplicar controles. Campo opcional entero1–1.000.000, precarga UXB existente y envía su valor por el flujo de permisos/revisión del servidor. Explicación automática de reportes reconoce cambios solo de UXB para no exigir nota escrita. Conserva UXC histórico y subcajas/paquetes internos sin conversiones ni migraciones.

Validación: reports/catalog PHP aprobados, regresión de propuesta solo UXB14 sin explicación; editor-photo y report-success UI con precarga12/cambio14 y envío conservando foto. product-pack-fields-ui Chromium/WebKit verifica3 formularios, campos únicos alineados, entero/opcional y320/390/1366 ambas manos sin desborde. Sintaxis PHP/JS/diff aprobados. Reversión: revertir entrega y publicar caché superior; UXB ya guardada permanece compatible con catálogo/revisión existentes.


## Entrega v2.89 — Menú de Moderación en una fila

Menú principal de Moderación cambia grilla de dos filas por fila horizontal única. En móvil se desplaza dentro de la barra para mantener nombres legibles y botones>=44px; en escritorio distribuye espacio disponible. Reserva espacio para insignia de pendientes, mantiene navegación/eventos y preferencia de mano, sin desborde horizontal de página. Respeta movimiento reducido.

Validación: geometría Chromium con seis pestañas/insignia320/390/1366 ambas manos, misma fila, objetivos44px y scroll local sin desborde; diff aprobado. Sin lógica ni permisos modificados. Reversión: revertir CSS y publicar caché superior.


## Entrega v2.90 — Limpieza técnica tras revisión general

Retira js/hand-preferences.js sin referencias (la preferencia activa sigue en stock-preferences.js), inclusión duplicada de stock-branches.css en Supervisores y css/development.css de Main. Conserva el ajuste funcional del logo en ux-polish.css, sin distintivo de desarrollo ni modificación de beta. Elimina reglas de columnas de grilla ya reemplazadas por el menú horizontal y su tamaño de letra antiguo; conserva separación vigente. Recorte de fotos carga únicamente en Inicio/Biblioteca, donde se incluyen sus editores; permanece disponible en caché offline. Sin cambios en permisos, datos, equivalencias ni funciones de negocio. No se hace una reescritura global de CSS.

Validación: settings-ui Chromium aprobado (incluye mano hábil y avisos), moderation-stats-ui Chromium aprobado (320/390/1366 ambas manos y permisos), product-editor-photo aprobado (recorte/guardado/reintento). Revisión local de pantallas principales y demo sin desbordes; Supervisores con una sola hoja de sucursales. Navegación Stock→Biblioteca comprobada: recorte ausente en Stock y disponible al entrar al editor; encabezado sin desborde320/390/1366 ambas manos. Sintaxis PHP y diff aprobados. Propuesta visual de encabezado de sucursal/Ajustes/Moderación preparada fuera del repositorio; no aplicada. Cámara del supervisor sin hoja específica y zoom bloqueado siguen identificados para corrección posterior, no quedan solucionados por esta limpieza.

Reversión: restaurar archivos de esta entrega desde su revisión anterior y publicar con contador de caché superior. No hay datos que revertir. No eliminar index.html (demo estática documentada), preferencias activas, subcajas ni funciones por supuesto desuso.


## Entrega v2.91 — Encabezado de sucursal sin recorte

Mi equipo de sucursal usa título/rol en primera fila y sucursal/configuración/salida en segunda en móvil; en PC reparte ambas zonas según ancho. Nombre largo puede ocupar varias líneas y selector de varias sucursales se adapta al espacio disponible. Se eliminan reglas previas que comprimían el título y reducían el rol a .65rem; se conserva tamaño .72rem, controles44px, SVG, preferencia de mano e IDs/eventos. Ajustes/Moderación de la propuesta no se modifican. Sin permisos ni datos nuevos.

Validación local Chromium/WebKit320/390/1366, ambas manos, modo una mano0/1, sucursal única/selector múltiple y nombres largos: sin recorte de título, solapamientos ni desborde, controles44px con tolerancia de redondeo subpíxel y configuración abre. Captura final revisada; diff aprobado. No prueba de dispositivo físico. Reversión: restaurar css/branch-team.css anterior y publicar caché superior. Conserva limpieza local de v2.90, sin desplegar desde este trabajo.


## Entrega v2.92 — Accesos de Inicio unificados

TOPs, Supervisores/Mi empresa y Asignar empresa comparten una fila de anchos iguales, icono SVG arriba y texto centrado; se adapta a uno/dos/tres accesos sin dejar posiciones vacías. Grupo semántico de accesos, separación6px, mínimo70px de alto y orden reflejado según mano. Mantiene destinos y condiciones PHP de visibilidad, selector de sucursal y acción principal de escanear. Elimina regla anterior que permitía repartir esos accesos entre filas desparejas.

Validación: supervision-home-ui Chromium aprobado (empresa/sucursal/manager, ambos modos y manos320–1366 sin scroll de página); geometría con página real y fixture del caso de captura en Chromium/WebKit,1/2/3 enlaces,320/390/1366, ambas manos y modo una mano0/1: misma fila/ancho/alto, sin desborde ni recorte, área táctil>=44px. Captura local revisada. Sintaxis index.php y diff aprobados. Sin datos reales, permisos nuevos ni publicación. Reversión: restaurar markup de accesos en index.php y reglas correspondientes de home.css/branch-team.css; publicar contador de caché superior. Preservar limpieza y encabezado de entregas locales anteriores.

Publicación autorizada por el usuario: v2.90–v2.92 se agrupan para publicar limpieza técnica, encabezado de sucursal y accesos unificados de Inicio. Validaciones documentadas en cada entrega. El resto del rediseño propuesto sigue pendiente de decisión.


## Entrega v2.93 — Inicio con equipo de sucursal y supervisor

Acceso Mi equipo sale del bloque de selector y comparte fila con TOPs y Mi empresa/Supervisores; Asignar empresa se agrega para manager con el mismo patrón. Mi equipo usa SVG de sucursal y destino explícito sucursales.php?ambito=sucursales; Mi empresa/Supervisores conserva SVG de personas y supervision.php. Se elimina la condición que ocultaba empresa cuando ya existía equipo: son accesos distintos sin duplicar destino. Selector de sucursal conserva IDs y eventos, ocupa su fila solo con locales; invitaciones pendientes mantienen el acceso al equipo sin dejar bloque vacío. JS actualiza visibilidad del nuevo enlace fuera del selector. Limpia estilos del enlace anterior.

Validación: supervision-home-ui Chromium aprobado, empresa/sucursal/manager sin doble acceso de empresa ni scroll de página en320/390/1366 ambas manos/modos. Prueba local Chromium/WebKit con equipo y supervisor simultáneos, cuatro accesos de manager iguales y cuenta normal con tres: sin desbordes, fila/ancho/alto iguales y controles>=44px. Navegación comprobada a sucursales explícitas y supervision.php; captura WebKit revisada. Sintaxis PHP/JS y diff aprobados. Solo fixture temporal, sin datos reales ni cambios de permisos. Publicación solicitada en continuidad de v2.92. Reversión: revertir archivos de esta entrega y publicar caché superior; sin datos que revertir.


## Entrega v2.94 — Asignar empresa en modal y alertas de gestión

Empresas cambia desplegable de Asignar empresa por botón principal con SVG y modal dedicado, foco en nombre, correo/QR existente y Habilitar supervisor. Envío bloquea doble acción, muestra Habilitando, conserva datos/modal ante error y cierra/reset al éxito. Sin cambios de API ni permisos.

Sucursales retira botón Actualizar; conserva AJAX al guardar y al volver a ventana/visibilidad, y actualiza al entrar a su pestaña. Evita solicitudes superpuestas y refresco durante borradores/edición; no agrega polling ni promete actualización instantánea entre dispositivos. Actualización automática no genera Información actualizada. Confirmaciones/errores/avisos usan RepoToast explícito y limpian los antiguos párrafos duplicados; progreso de guardado en loader local de botón. Búsqueda, vacíos y estados necesarios de lista conservan texto inline.

Validación: branch-manager-ui Chromium aprobado (asignar/añadir, renombrar, error conserva borrador, sucursales, permisos401/403, CSRF, actualización por focus sin botón). supervision-ui Chromium aprobado con nuevo modal y resto del flujo de equipo/listas. Prueba de modal real Chromium/WebKit320/390/1366 ambas manos: encaje, error conserva valores, reintento habilita, cierre/reset al éxito, alerta flotante, actualización silenciosa. Captura final opaca y centrada revisada, sintaxis PHP/JS y diff aprobados. Solo cuentas/base temporales, no datos reales. Tests existentes ajustados a alertas y focus, sin conservar dependencia de controles retirados.

Reversión: revertir archivos de esta entrega y publicar contador de caché superior; sin datos que revertir.


## Entrega v2.95 — Escáneres alineados y Asignar supervisor

Corrige regresión del ajuste v2.93: selector de alineación .home-scan-actions quedó precedido accidentalmente por .home-shortcuts al retirar estilos del enlace anterior. Restablece el componente independiente de escaneo con display:flex. En móvil iguala Producto/Palet a64px y fija ancho64px de Palet tanto en modo normal como una mano, conservando orden según mano, colores y eventos. No cambia lectura de cámara ni ubicación.

En Empresas, botón de apertura, título del modal y submit pasan a Asignar supervisor; progreso Asignando. Asignar empresa de Sucursales conserva su función/nombre distintos. API enable/permisos/confirmación del servidor sin cambios.

Validación: home-scan-pair-ui aprobado, ahora incluye hoja home.css y estructura real del modo de una mano, sin simular dicho DOM en PC donde no aplica. Página PHP real Chromium/WebKit320/390/1366, ambas manos y preferencias persistidas0/1, aviso PWA visible/oculto: Producto/Palet con misma fila/altura, sin desborde horizontal; rótulos del modal comprobados. Captura final revisada, sintaxis PHP/JS y diff aprobados. No datos reales modificados ni nueva dependencia. Reversión: revertir esta entrega y publicar contador de caché superior; conservar corrección de flex si se revierte solo terminología.


## Entrega v2.96 — Ajustes con menos accesos y prioridad a preferencias

Retira de Accesos los atajos duplicados Biblioteca/Palets/Escáner (navegación principal) y Estadísticas y TOPs (Inicio). Conserva Mi empresa/Supervisores, Mi equipo, gestión de empresas según condiciones previas, Mis reportes, Compartir app y Ver introducción. No elimina rutas ni funciones. Preferencias pasa al primer bloque del formulario en orden de DOM/teclado/móvil y primera columna en PC. Avisos y respuesta permanece debajo de Nombre público, respetando la ubicación solicitada.

Perfil propio conserva identidad, editar y compartir arriba. Actividad/contadores/insignias quedan en details opcional Mi actividad e insignias, cerrado inicialmente, reutilizando el perfil existente con parámetro compacto optativo. Perfil público conserva presentación anterior. Modales de insignias permanecen fuera del bloque cerrado para mantener cuenta.php#insignias y notificaciones de logros. Sin cambios de permisos, datos, cálculos ni preferencias guardadas.

Validación: settings-ui Chromium/WebKit aprobado (nuevo orden, ausencia de duplicados, avisos, guardado y reintentos, mano, defaults, CSRF/identidad y sesión). achievements-ui aprobado (destacar, límites, privacidad pública, sincronización y modalidades). Página real Chromium/WebKit320/390/1366 ambas manos con actividad abierta/cerrada, enlaces de equipos, cuenta.php#insignias y edición en modal, sin desborde. Captura final revisada; PHP/diff aprobados. Pruebas con base temporal, sin datos reales. Acceso flotante superpuesto detectado en auditoría sigue como mejora2, no se modifica en este alcance3.

Reversión: revertir cuenta.php, includes/profile-card.php y estilos de esta entrega y publicar caché superior; no datos que revertir. Tests actualizados al bloque plegable y a la nueva jerarquía, no a nuevos permisos.


## Entrega v2.97 — Nombres de equipos consistentes; zoom descartado

Mi equipo identifica exclusivamente el panel de empresa del supervisor y conserva título Mi equipo - nombre de empresa. Mi sucursal identifica el equipo de sucursal. Mis supervisores identifica invitaciones y vínculos recibidos por el integrante. Inicio/Ajustes, navegación de ámbito, encabezados, título de navegador y accesibilidad usan esta distinción. El acceso flotante de sucursal pasa a icono de sucursal, etiqueta Mi sucursal y destino explícito sucursales.php?ambito=sucursales para evitar redirección al panel de empresa; posición/arrastre siguen igual (mejora2 pendiente).

Locales visibles/seleccionados pasa a Sucursales visibles/seleccionadas en integrantes y modal. Detalles de movimientos y contexto de cámara distinguen Mi sucursal; nuevas invitaciones indican Mi sucursal o Mis supervisores según origen. No se reescriben avisos históricos, nombres elegidos por usuarios, enums, IDs, consultas, permisos ni relaciones. Título dinámico usa document nativo durante carga, no el proxy de navegación.

Punto6 descartado explícitamente por el usuario: no habilitar zoom de página; viewport y comportamiento de app se conservan. No reabrirlo como mejora pendiente.

Validación: supervision-ui y supervision-home-ui Chromium aprobados, consentimiento/consulta/listas/descarga, permisos, ambos modos/manos y navegación. supervision-unlisted-ui Chromium aprobado con nombres/modal/alertas actuales y puerto propio para evitar interferencia de fixtures; comparación real con producto fuera de lista y alcance conservados. stock-partial-box-ui aprobado, detalle Mi sucursal y cantidades exactas conservadas. Página real Chromium/WebKit320/390/1366 ambas manos: accesos de empresa/sucursal, integrante con Mis supervisores, atajo flotante a sucursal, panel cargado, títulos y viewport anterior. Sintaxis PHP/JS y diff aprobados; solo base temporal, sin datos reales.

Reversión: revertir textos/rutas de interfaz de esta entrega y publicar caché superior; sin datos que revertir. Mantener decisión del usuario de descartar zoom.


## Entrega v2.98 — Moderación General compacta

General conserva cuatro indicadores principales: usuarios nuevos, usuarios con actividad registrada, movimientos de stock y pendientes actuales. Movimientos de stock muestra su cifra una sola vez y permite abrir registros de mercadería del período; pendientes conserva acceso a su lista. Fecha/rango en una línea breve. Tres filas discretas abren desgloses en un modal reutilizado: Actividad y reportes (usuarios/moderación/gestión, resoluciones y tiempo medio), Stock actual (palets/productos/unidades/vencimientos) y Cuentas y equipos (catálogo, usuarios/vínculos y rangos). No expande la página ni elimina métricas.

Modal nativo con cierre, Escape y devolución de foco al origen explícita para WebKit. Navegar a registros cierra modal y conserva categoría, fechas y resultado confirmado. Desgloses reutilizan respuesta ya obtenida, sin nuevas consultas, cálculos ni permisos. Diferencia período elegido de totales actuales; actividad modal muestra fechas y definición de actividad/agrupación por transacción. Sin librerías ni datos nuevos.

Validación: moderation-stats PHP aprobado (límites Argentina, períodos, transacciones agrupadas, actividad confirmada, resolución y vacíos). moderation-stats-ui Chromium/WebKit aprobado: cuatro cifras/tres accesos, sin desgloses inline, modales320/390/1366 ambas manos, stock y rangos conservados, Escape/foco, períodos personalizados, navegación a registros de mercadería/usuarios y403 a administrador/moderador/usuario. Sintaxis PHP/JS/diff y captura móvil aprobados. Solo fixture local; no medición de rendimiento ni cambio de datos reales.

Acceso flotante aceptado por el usuario por su posición movible: no retirarlo ni rediseñarlo como pendiente. Zoom sigue descartado. Reversión: revertir reportes.php/js/reports-page.js y estilos de esta entrega, publicar contador de caché superior; no hay datos que revertir.

## Entrega v2.99 — Rendimiento de inventarios grandes y estilos consolidados

Benchmark reproducible con base ficticia aislada: 21 cuentas, 2.000 palets, 20.000 lotes, 5.000 EAN y 50.000 eventos. Mide APIs y carga móvil local/conexión lenta con caché vacía. Documentación y límites en docs/RENDIMIENTO-INVENTARIOS.md; no mide PostgreSQL real ni CPU móvil.

Detecta escrituras por aviso de vencimiento: stock_notify reutiliza sentencia, agrupa en transacción y omite avisos sin cambios. Mantiene lectura, escalamiento, permisos, eliminación y transacciones del llamador; rollback propio ante fallo. Comparación aislada de 2.520 avisos: inicial21.810→80ms, repetición20.517→72ms, solo SQLite local.

Registros agrupados sustituyen búsqueda correlacionada por JOIN a identidades DISTINCT; cuando filtro y alcance coinciden omiten cruce redundante. Conserva operaciones completas, jerarquía, actor/resultado/sección, orden y paginación. Código anterior supera15s en fixture50k; versión nueva mediana HTTP2,00s sin filtro extra y2,93s con acción. No se ejecutó en PostgreSQL de producción ni se modificaron índices/esquema.

Consolida reglas de búsqueda/toolbar de supervisores, comparación, textarea y controles idénticos de exportación/sucursales en css/supervision.css. Sin rediseño ni unificación de Listas/Integrantes. Comparación visual calculada en30 estados por navegador Chromium/WebKit (320/390/1366, ambas manos, modales de descarga/invitación, empresas y sucursal) conserva geometría y estilos.

Validación: stock-notifications (sin escrituras repetidas, lectura, fallo/rollback y transacción externa), stock, audit-records (incluye múltiples coincidencias sin multiplicar eventos), audit-records-ui y moderation-stats aprobados. expiry-alerts-ui corregida: esperaba today/warning pero el diseño actual usa urgent para hoy/<=7d; fixture ya contenía esas fechas. Recorrido completo aprobado. Benchmark verifica contenido/paginación, errores JS y desborde. Sintaxis y diff revisados. Solo datos ficticios.

La carga inicial lenta/caché vacía sigue siendo oportunidad: Stock15s, Mi equipo9,3s, registros11s en una muestra de150ms/1,6Mbps. No presentarla como instantánea ni puntuación objetiva. Futuro: revisar recursos por pantalla y renderizado progresivo de Stock sin romper offline/filtros/enlaces. Acceso flotante movible aceptado y zoom descartado se conservan.

Reversión: revertir archivos de aplicación de la entrega y publicar contador de caché superior; no datos ni migraciones que revertir. Los benchmarks pueden conservarse.

## Entrega v3.00 — Stock progresivo y cámara bajo demanda

Stock conserva la respuesta completa autorizada y la copia offline, pero monta tarjetas de palets propios/compartidos por tramos de32. Agrega automáticamente el siguiente tramo al acercarse al final, con botón Ver más palets como alternativa táctil/teclado y para navegadores sin IntersectionObserver. Filtros de sucursal/etiqueta y orden actúan sobre toda la fuente, antes del recorte visual; no sobre las tarjetas montadas. Metadatos de sucursales y etiquetas se indexan por código, evitando búsquedas repetidas. No repinta tarjetas al escribir si esos filtros/datos no cambiaron. Etiquetas pendientes de impresión conservan su selección existente.

Conserva cantidad mostrada al recargar con el mismo alcance, enlaces directos a palet/lote fuera del primer tramo y navegación interna; al salir desconecta observadores y descarta fuente/opciones. Ignora actualizaciones tardías. No cambia API, permisos de servidor, exportación, stock guardado ni colas de operaciones. Helpers nuevos precacheados por SW.

Inicio, Stock, Biblioteca, Mi equipo, sucursal y Moderación dejan de incluir eager los motores html5-qrcode/Quagga. El adaptador y validaciones de EAN siguen disponibles; descarga/ejecuta el motor primario al abrir un lector, y el auxiliar solo para EAN, con promesa compartida y reintento si falla. Detener/cerrar no espera la descarga ni activa cámara tardíamente. Mantiene audio preparado en el toque, controles/encuadre y fallback manual. SW sigue preparando ambos motores para offline; la instalación inicial en segundo plano no está incluida en el benchmark.

Medición local con la misma fixture2k palets/20k lotes/50k eventos,390px y caché vacía: Stock propio1k pasa de9180 a1866 nodos (1876 en una muestra lenta por estado de fondo); carga local3,64→1,43s y red150ms/1,6Mbps15,00→9,71s. Mi equipo9,30→6,63s y registros11,03→8,24s en red lenta. Muestras orientativas, sin extrapolar a Render, CPU móvil, primera instalación de SW ni cámara física. docs/RENDIMIENTO-INVENTARIOS.md conserva metodología y referencias.

Validación: stock-progressive-ui Chromium/WebKit (32/64, filtro de último palet no montado, sucursal/etiqueta/orden completo, compartidos, recarga, navegación AJAX, enlace a lote y manifiesto offline completo,320/390/1366 ambas manos); scanner-lazy-ui Chromium/WebKit (sin motores previos al uso, cancelación inmediata, sin arranque tardío, fallo/reintento, QR sin auxiliar y EAN una sola descarga); stock-tags, stock-branches, stock-export, stock-offline, offline-start, supervision, main-rapid-scanner, ean-scanner, scanner-scheduling, scanner-frame, service-worker-offline y navigation/navigation-scroll aprobados. Captura local revisada, sintaxis y diff sin errores. Cámara simulada, no prueba de hardware.

Ajustes de fixtures: formatos de lector explícitos en mock de cámara; elección sin sucursal cercana para probar asignación manual; introducción marcada vista/loader finalizado y espaciador sin contracción para medir scroll; importScripts simulado y validado en prueba SW centrada en offline. No cambian funciones de negocio.

Reversión: revertir JS/listado/lectores y plantillas de esta entrega y publicar caché superior. Sin migraciones ni datos que revertir; para restaurar arranque eager reponer ambos script tags. No borrar copia offline ni operaciones pendientes. Acceso flotante aceptado, mano del usuario, zoom descartado y listas/integrantes separados se conservan.


## Entrega v3.01 — Preparación offline con prioridad de uso

La copia de lectura de Stock cede entre lotes mientras hay modal abierto (detalle/formulario/cámara), sincronización Stock/Biblioteca, pestaña oculta o interacción reciente. Verifica cada200ms, con400ms de margen tras interacción; no interrumpe el lote de lectura ya iniciado. Revisa caché por tramos32 para ceder el hilo y conserva lotes de red5. Cancelar/navegar aborta también las esperas, sin peticiones tardías. El trabajo retoma al quedar libre la pantalla. Sin nuevos controles, mensajes ni cambios visuales.

Reduce guardados del manifiesto de progreso a un máximo aproximado cada2s, forzando inicio, final y error. Los detalles de cada palet siguen guardándose al recibirlos; solo se agrupa la escritura del contador. Conserva fuente completa, comprobaciones de identidad/revisión/permisos y reconciliación de lecturas revocadas; nunca borra cargas pendientes. No es el respaldo/exportación pospuesto.

Validación: stock-preparation Chromium/WebKit con prioridad de modal/sincronización/interacción, reanudación, lotes/revisiones, revocación sin perder cola; Chromium incluye cancelación por salida. offline-start aprobado (entrada offline desde cero, búsqueda/vencimientos/Biblioteca, sincronización8x5 y privacidad). service-worker-offline y sintaxis/diff aprobados. Datos ficticios; sin medición nueva de velocidad ni prueba física.

Reversión: revertir js/stock-preparation.js y publicar contador de caché superior; tests/documentación pueden conservarse. Sin migraciones ni datos que revertir.


## Entrega v3.02 — Limpieza gradual de estilos

Retira declaraciones anuladas de tres hojas: Ajustes (encabezados repetidos y antigua grilla de dos columnas reemplazada por tres), notificaciones (display anterior y reglas repetidas del encabezado) y resumen de Stock (tamaños, espaciados y grilla móvil antiguos anulados por la fila compacta actual). Conserva reglas finales, especificidad necesaria, controles, colores, tipografía, mano elegida y estructura. No retira funciones ni hace una reescritura global del CSS.

Validación: comparación local de geometría y propiedades calculadas antes/después en fixture de los tres componentes, Chromium/WebKit,12 combinaciones por motor (320/390/1366, ambas manos, puntero táctil/no táctil), sin diferencias. Animaciones desactivadas para comparar estados estables. settings-ui Chromium/WebKit, notification-presentation-ui y stock-overview PHP aprobados; captura de Ajustes revisada y diff limpio. Sin datos reales ni cambios de lógica/permisos. No representa auditoría completa de todas las pantallas ni medición de velocidad.

Reversión: restaurar css/settings.css, css/notifications.css y css/stock-overview.css de la revisión anterior y publicar contador de caché superior. Sin datos que revertir. Próxima limpieza, si se continúa, siempre por componente con evidencia; no borrar reglas solo por parecer repetidas.


## Entrega v3.03 — Biblioteca y recorte sin reglas anuladas

Biblioteca define el tamaño de los SVG de acciones una sola vez, conservando18px y flex-shrink0; el código de producto conserva20px y16px en móvil estrecho. Recorte de fotos retira las antiguas esquinas desplazadas -22px, anuladas por la posición0 vigente. Revisados product-info.css y campos de formularios: sin repetición equivalente que justifique cambios; se conservan. Sin rediseño ni cambios de datos, permisos o flujo.

Validación: library-dialog-polish-ui aprobado (código único, copia, acciones320–1366, edición y duplicados); product-editor-photo aprobado (carga, recorte, JPEG, reintento/reset); product-pack-fields-ui Chromium/WebKit aprobado en tres formularios, ambas manos320/390/1366. Comparación de medidas y propiedades calculadas antes/después en fixture de Biblioteca/editor/recorte, Chromium/WebKit,12 combinaciones por motor (tres anchos, dos manos, táctil/no táctil), sin diferencias. Diff limpio. Solo fixtures locales; no prueba de cámara física ni auditoría global.

Reversión: restaurar css/library.css y css/product-crop.css de la revisión anterior y publicar caché superior; sin datos que revertir.


## Entrega v3.04 — Cierre de limpieza en Stock y Moderación

Retira declaraciones comprobablemente anuladas: posición relativa antigua de la barra del palet (sigue sticky), ancho anterior del total de mercadería y transición ya desactivada de su chevron; márgenes/tamaños anteriores de comparación, figuras y campos de reportes. Conserva flex de escritorio, grilla móvil, reglas finales, colores, tipografía, acciones y mano elegida. No altera filtros, cantidades, API, permisos ni datos. No se retiran reglas por simple coincidencia de selector ni funciones por supuesto desuso.

Validación: stock-product-layout-ui Chromium/WebKit aprobado (unidades/cajas/bultos, móvil/PC y ambas manos); audit-records-ui aprobado (secciones/transacciones, detalles solo modal, permisos, scroll y geometría); moderation-stats-ui aprobado (resumen compacto, períodos, navegación y rechazo por rango). Comparación antes/después de geometría y propiedades CSS calculadas en fixture de barra/mercadería/comparación de reportes: Chromium/WebKit,12 combinaciones por motor,320/390/1366, ambas manos y puntero táctil/no táctil, sin diferencias. Diff limpio. Datos ficticios; no auditoría exhaustiva ni garantía de ausencia de errores en toda la app.

Reversión: restaurar css/stock.css, css/inventory-goods.css y css/reports.css de la revisión anterior, publicar caché superior. Sin datos ni migraciones que revertir. Tanda de limpieza solicitada cerrada; no inventar nuevas etapas o fallos sin evidencia. Respaldo offline pospuesto, zoom descartado, acceso flotante aceptado y Listas/Integrantes separados se conservan.


## Entrega v3.05 — URL de Inicio y scroll de edición en modales

Inicio intentaba descomprimir cualquier query como lista compartida, incluyendo parámetros normales; ahora el lector legado exige payload Base64 completo con padding final válido. Parámetros con nombre (from/utm/view/check) se ignoran sin tocar la lista local. Conserva listas comprimidas anteriores, códigos entregados por servidor y error ante payload legado inválido. No cambia from del navegador/proveedor ni permisos de enlaces.

Ajuste de viewport del modal movía el scroll hacia el campo enfocado también al recibir scroll del viewport o mutaciones de contenido. Ahora actualiza posición/altura sin forzar scroll en esos eventos; revela el campo solo al enfocarlo o cambiar tamaño/teclado. Acumula la intención de revelar si coinciden eventos en un frame, conserva cierre/confirmación de borradores/bloqueo de fondo y limpieza de navegación.

Validación: shared-list-query comprueba parámetros normales, listas legadas/códigos de servidor y payload corrupto. dialog-edit-scroll-ui Chromium/WebKit simula viewport reducido por teclado: foco visible, scroll manual estable ante viewport.scroll y contenido asíncrono, resize revela campo y edición conserva valor. dialog-dirty-ui aprobado online/offline (X/fondo/Escape, mantener/salir, busy, restauración y valores). Sintaxis/diff aprobados. No se reprodujo teclado físico de iPhone; solicitar confirmación de ese dispositivo sin afirmar cobertura completa.

Reversión: restaurar js/script.js y js/dialog-scroll.js de revisión anterior y publicar caché superior. Sin datos, permisos ni migraciones que revertir.


## Entrega v3.06 — Preparación y recuperación del pitido QR/EAN

AudioContext del lector se reutiliza entre navegaciones internas, evitando acumular contextos. Al abrir con sonido activo recrea contexto cerrado y vuelve a preparar buffer silencioso si estaba suspendido/interrumpido, siempre desde el arranque existente en el gesto. Fallar al configurar audioSession ambient/playback ya no cancela la preparación o el pitido. Conserva política de playback solo durante tono confirmado y restauración, volumen/timbre y preferencias independientes.

En lectura que cierra cámara espera a que beep termine de programar, con reanudación limitada200ms, antes de liberar cámara/entregar código. Reanudación rechazada o colgada no bloquea lectura; timeout no reproduce tono tardío. Modo continuo conserva entrega sin espera. No cambia detección, QR/EAN, controles ni permisos. Referencia de interrupciones de Safari: https://bugs.webkit.org/show_bug.cgi?id=273511 ; no atribuir todos los fallos reales a esa incidencia histórica.

Validación: ean-scanner amplía regresiones para contexto cerrado/recreado, política de sesión que lanza error, reanudación demorada conservando cámara hasta tono, audio colgado200ms con código entregado y sin tono tardío. Conserva QR/EAN, normal/continuo y sonido/vibración activados/desactivados. scanner-lazy-ui Chromium/WebKit y scanner-frame aprobados; main-rapid-scanner aprobado. Audio simulado, no audición ni hardware iPhone; confirmar en dispositivo del usuario. Sintaxis/diff aprobados, sin datos reales.

Reversión: restaurar js/ean-scanner.js anterior y publicar caché superior; pruebas/documentación pueden conservarse. No datos ni migraciones que revertir.


## Entrega v3.07 — Pitido con ciclo de vida global

Extrae preparación y confirmación a un servicio singleton RepoScanFeedback de aplicación, definido fuera del módulo/página del lector y sin referencia a DOM, cámara o modal. Reejecutar el adaptador por navegación conserva servicio, contexto, fuentes activas y temporizadores; cerrar/detener cámara no los destruye. El lector invoca prepare/confirm; mantiene la espera limitada anterior para programar el tono, no su duración. No se agrega elemento de audio a ningún div ni dependencia.

Contabiliza tonos activos para que preparar otro lector no fuerce ambient mientras suena un pitido ni finalice playback al terminar el primero si queda otro. Restaura política anterior al terminar el último, con fallback existente500ms. Conserva tono, preferencias y recuperación de v3.06. El sistema operativo/volumen/interrupciones externas todavía pueden impedir audio: no prometer nunca se cancela ni audición garantizada.

Validación: scan-feedback comprueba servicio sin documento/cámara, tono activo conservado al reejecutar módulo, contexto único, reapertura que no cambia playback, tonos superpuestos y restauración final, preferencias. ean-scanner, main-rapid-scanner y scanner-lazy-ui Chromium/WebKit aprobados. Sintaxis/diff limpios. Audio simulado, sin prueba física iPhone.

Publicación: envío a main; el endpoint público continúa respondiendo502 desde la verificación de v3.06. No confirmar despliegue hasta obtener versión correspondiente; falta acceso a logs del servicio para diagnosticar el502. Sin evidencia de que el cambio JS cause fallo del servidor.

Reversión: restaurar js/ean-scanner.js anterior y publicar caché superior; no datos ni migraciones.


## Migración a Render — recuperación inicial25/10/2026

Usuario confirma acceso a Neon y base Render creada: PostgreSQL18, Ohio,256MB RAM y1GB de almacenamiento (USD6+0,30/mes según captura), sin ampliación automática. Consulta mínima del origen ya funciona. Exportación inicial completa en formato custom con cliente oficial PostgreSQL18.6, sin propietarios/ACL, solo lectura. Respaldo privado fuera de Git y raíz web, índice legible y hashSHA256. Restauración de prueba completa en PostgreSQL18 local aislado,79 tablas/297.978 filas, sin errores; servidor temporal detenido. No guardar filas ni credenciales en documentación.

Actualización: acceso Render confirmado por CLI oficial y autorización del usuario. Destino vacío18.6/Ohio restaurado en transacción; copia final79 tablas/297.981 filas, hashes de contenido, columnas, índices, restricciones y secuencias coincidentes. Respaldo final actualizado23,7MB también restaurado en PostgreSQL18 local aislado y detenido. Dos valores checked_at de suscripciones push reconciliados con origen antes de verificar; detalles y datos permanecen privados.

DATABASE_URL de producción y desarrollo cambiado a Render interno con TLS, conservando todas las demás variables. Ambos servicios live; desarrollo necesitó redeploy del mismo build para aplicar entorno nuevo. Confirmado mediante sesiones anónimas nuevas presentes en Render y ausentes en Neon. Inicio/conectividad200; Stock/Biblioteca/Supervisión/Reportes401 sin autenticar. Sin cambios de código ni versión:212/v3.07. Comprobación posterior del origen: únicamente app_sessions cambió durante reapertura de desarrollo, sin nuevas operaciones de negocio. Falta confirmación de recorridos autenticados en celular y recepción push física; no se tomaron sesiones de usuarios. Neon y respaldos conservados, facturación intacta. Si se revierte después de nuevas escrituras, conciliar primero datos de Render. Guía vigente docs/MIGRACION-RENDER-POSTGRES.md.

Usuario confirmó funcionamiento en celular después del corte y luego confirmó bajar Neon al plan gratuito desde su panel. No se modificó facturación mediante herramientas.

## Entrega v3.08 — Palet legible con teclado abierto

Con teclado abierto el modal desplaza todo su contenido, sin comprimir la lista de productos entre el buscador y las acciones. Buscador y productos conservan su altura; botones quedan después del producto y se alcanzan desplazando. Al cerrar teclado vuelve el desplazamiento habitual de productos. Foco del buscador se indica en el contenedor completo, evitando borde interno sobre el texto.

Detección de teclado conserva su estado al pasar del campo a un botón mientras el viewport sigue reducido; evita que Cancelar cambie la geometría entre pulsar y soltar en WebKit. Se libera al recuperar altura o cerrar modales. Conserva revelado de campos por foco/resize, scroll manual, controles, metadatos y preferencia de mano.

Validación: pallet-keyboard-layout-ui en Chromium y WebKit,320/390/1366 y ambas manos, viewport reducido con offset, producto sin recorte, acciones accesibles, búsqueda/cancelación, sin desborde horizontal y retorno al scroll normal. dialog-edit-scroll-ui y dialog-dirty-ui aprobados en ambos motores; último con servidor local y datos ficticios. Captura revisada. Teclado simulado, no prueba física de Safari/iPhone. Sin cambios de datos ni permisos. Reversión: restaurar css/ux-polish.css y js/dialog-scroll.js anteriores y publicar caché superior.
