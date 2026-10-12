# Próximos pasos de Repotools

Alineación en modo zurdo: títulos y descripciones junto a controles desplazados, nombres de productos y sus EAN se alinean a la derecha, sin invertir cifras ni iconos. Formularios y textos largos conservan su alineación. Verificado con las pruebas visuales de Biblioteca y Stock en móvil/PC. Reversión: commit «Align accompanying text for left-handed layouts».

Biblioteca y prioridad de alcance: en modo zurdo, el encabezado de colección muestra escanear, manual, compartir, ruedita y nombre, en ese orden visual. Inicio mueve el grupo de acciones de la lista a la izquierda; Crear colección y Generar etiquetas también se ubican a ese lado. Se conserva el orden interno de los botones, la navegación anterior/siguiente y la disposición de cámara. El diseño diestro se conserva. Comprobado con nombre largo, menús dentro del viewport y acciones en 320, 390 y 1366 px mediante `tmp/library-hand-priority.cjs`; se repitió `tmp/hand-placement-ui.cjs`. Reversión: commit «Prioritize scan controls in left-handed collection headers».

Preferencia zurda ajustada: los grupos de acciones se ubican a la izquierda manteniendo el orden de sus botones. Escanear/configuración no se invierten; zoom, flash y teclado de la cámara conservan su disposición habitual. Se mantiene cerrar a la izquierda. Comprobado en 320, 390 y 1366 px con `tmp/hand-placement-ui.cjs`. Este criterio reemplaza el reflejo de controles de cámara de la primera entrega de preferencias.

Moderación: menú principal en una fila a ancho completo, estilo de palets; Pendientes/Resueltos debajo con dos columnas iguales. Validado a 320 y 1366 px, con contadores y cambio de sección. Archivos: `css/reports.css`, versión de estilos en `vistas/header.vista.php`. Revertir mediante el commit «Align moderation tabs with pallet navigation».

Registro del 11 de octubre de 2026. Estas notas definen trabajo pendiente; no implican que las funciones estén implementadas.

## Uso sin conexión

Alcance inicial acordado: guardar nuevas cargas temporalmente en el dispositivo para evitar perder el trabajo cuando falte señal, cobertura o Wi-Fi.

- Escanear EAN y guardar producto, cantidad y vencimiento como cargas pendientes.
- Permitir un nombre de referencia si el producto no está disponible localmente; consultar la base al recuperar conexión.
- Consultar productos y palets abiertos anteriormente, indicando la fecha de la última actualización.
- Mostrar un aviso con icono y contador: «Sin conexión · 3 cambios por enviar».
- Sincronizar al volver la conexión con identificadores de operación para evitar duplicados, revalidando sesión y permisos.
- Si hubo modificaciones simultáneas, mostrar el conflicto antes de aplicar un cambio.
- Reclamar o crear palets, gestionar permisos y moderar reportes requerirá conexión en la primera versión.

Primera entrega implementada en Stock: cargas nuevas en palets existentes y previamente abiertos, con cantidad y vencimiento. IndexedDB conserva la cola por cuenta (hasta 200 cargas); las consultas recientes de hasta 50 palets por cuenta se conservan durante 7 días, sin los correos de los invitados. La cola no caduca automáticamente. Se distingue «guardado en este dispositivo» de «enviado» y se permite descartar con confirmación.

Al recuperar conexión, o entrar nuevamente a Stock, se envían las cargas con su identificador original, renovando CSRF y comprobando la cuenta. La API vuelve a validar permisos y versión del palet dentro de la transacción: cambios externos se detienen para revisión explícita. Los reintentos tras perder la confirmación no suman unidades nuevamente. Las pestañas con Web Locks coordinan envío, revisión y descarte. Si el navegador no ofrece almacenamiento, se mantiene el formulario y no se declara éxito.

Alcance pendiente: abrir la web desde cero o recargarla sin conexión (no hay un service worker nuevo); se debe abrir Stock con conexión antes de trabajar sin señal. No hay edición offline, reclamación/creación de palets offline, ni cola de Biblioteca o fotos. Quedan pendientes el catálogo local de productos y el registro diferido de escaneos para TOPs. El nombre de referencia cargado se conserva; no se reemplaza automáticamente con la API al sincronizar. Las cargas locales dependen de conservar los datos del navegador en ese dispositivo.

Pruebas: `node tests/stock-api.cjs` (cuenta original, revisión, idempotencia y permisos); `node tests/stock-offline-ui.cjs` (requiere Playwright disponible en `tmp/ui-check/node_modules`, Chrome local; incluye dos cargas sin conexión, recarga posterior, separación por cuenta, bloqueo y resolución explícita de conflictos, y pérdida de confirmación después de guardar). También se verificaron búsqueda administrativa en móvil/PC, navegación y limpieza de recursos. Reversión: revertir el commit «Queue Stock product loads locally and synchronize safely»; revisar/exportar cargas locales antes, ya que el código anterior no muestra la cola nueva.

## Supervisores

Dirección acordada en conversaciones anteriores, pendiente de implementar:

- Supervisión como permiso adicional, independiente del rango principal.
- Desde Mi cuenta, un usuario podrá compartir un enlace para vincular a su superior registrado; al aceptarlo se habilitará la supervisión automáticamente.
- Un usuario podrá vincular varios supervisores y desvincularlos.
- Los supervisores podrán consultar los palets de sus usuarios vinculados.
- Para editar, deberán solicitar acceso; solo el propietario del palet podrá aprobarlo.
- El rango Manager no debe convertirse automáticamente en supervisor.
- La propuesta inicial de tickets para obtener supervisión fue reemplazada por la vinculación mediante enlace.
- Antes de implementar, concretar aceptación del enlace, revocación y efectos sobre los accesos ya concedidos.

## Empresas

Idea aplazada por el usuario para pensarla mejor. No crear todavía un rango o permiso EMPRESA.

Propuesta por definir: una empresa podría consultar los palets de los usuarios que incorpore a su lista sin solicitar acceso a cada palet. No se acordaron aún las reglas de vinculación, autorización, alcance de lectura ni edición.

El campo opcional «Empresa o agencia» del perfil es información descriptiva; no concede permisos de empresa.

## Stock para administradores y managers

Aplicado el 11 de octubre: mantener acceso global, pero mostrar solo sus palets y los compartidos al entrar. La pestaña «Buscar palet» permite abrir otros por código o QR. La API ya no descarga el listado global en cada carga de Stock. Pruebas: `node tests/stock-api.cjs`.

## Acceso desde dispositivos

El usuario consultó la posibilidad de limitar una cuenta a un dispositivo. No está aprobado ni implementado.

Recomendación pendiente de decisión: permitir varios dispositivos personales y ofrecer gestión de sesiones y cierre remoto. Si se decide restringir, preferir una sola sesión activa con aviso al iniciar en otro dispositivo; no identificar dispositivos mediante IP, ya que cambia y puede compartirse.

## Otras ideas discutidas

- Registrar cambios de cantidades con responsable y fecha, sin exigir motivo.
- Transferencia de palets: propuesta de requerir aceptación del nuevo propietario; aún no confirmada.
- Búsqueda de productos o EAN dentro de Stock, vista reunida de vencimientos y recuperación de contraseña.

## Última entrega y continuidad para mañana

El usuario pidió mejorar estabilidad y UX con trabajo acotado por su cuota restante. No se completaron todas las mejoras ni se atribuye un 9/10 a la app.

Problema corregido: el resumen de Stock descargaba todos los palets para administradores y managers, incluso al consultar preferencias. Solución: retirar esa consulta global del resumen y reemplazar «Todos los palets» por búsqueda mediante los lectores y formularios existentes. Se conservan permisos de administración y gestión del palet abierto.

Validación: sintaxis PHP/JS y suite de API de Stock, incluyendo permisos, CSRF, reintentos idempotentes, conflictos de reclamación, invitaciones y acceso global por código. El resumen administrativo se verifica sin palets ajenos.

Reversión: esta entrega se publica en un commit separado titulado «Keep administrative Stock focused on own pallets and lookup». Usar `git revert <hash de ese commit>` en un checkout limpio; no resetear ni borrar las mejoras anteriores. Punto anterior: commit `b499245`, que contiene el registro de pendientes.

Prioridades al retomar:

1. Completado al retomar: búsqueda administrativa y edición autorizada de un palet ajeno comprobadas en Chrome a 320, 390 y 1366 px, sin desbordamiento horizontal ni errores JavaScript. Prueba local: `tmp/stock-lookup-ui.cjs`. Commit publicado: `13615e7`; la publicación no confirma por sí sola que Render terminó el despliegue.
2. Implementado: sesiones persistentes en la base (migración 13), duración fija de 30 días, cookie HttpOnly/SameSite Lax/Secure en Render, identificador renovado al ingresar, cierre y caducidad verificados. El primer despliegue invalida las antiguas sesiones de archivos: iniciar sesión nuevamente una vez. Confirmar el despliegue de Render y hacer seguimiento en uso real.
3. Implementar recuperación de contraseña con tokens de un solo uso, caducidad, límites y proveedor de correo definido. Falta elegir/configurar el servicio de correo; no inventar entregas.
4. Continuar el uso sin conexión descrito arriba: la cola inicial de Stock está implementada y probada. Falta abrir la web desde cero sin conexión y extender el almacenamiento a productos/Biblioteca. No simular éxito cuando algo no se sincronizó.
5. Agregar búsqueda de productos/EAN en Stock y vista reunida de vencimientos.
6. Retomar supervisores; empresas sigue pendiente de definición. La restricción de dispositivos para suscripciones está discutida, no aprobada ni implementada.

Pruebas ya disponibles: `tests/stock-api.cjs`, `tests/reports-api.cjs`, `tests/user-profiles-api.cjs`, `tests/navigation.cjs`, `tests/navigation-timeouts.cjs` y `tests/date-time.cjs`. Los archivos `tmp/*` son apoyos locales de pruebas y no deben publicarse.

Publicación utilizada: checkout Git en `tmp/upstream`, repositorio `totyr77-droid/repotools2`, rama `main`, despliegue automático en Render. Copiar y versionar solo los archivos de la entrega. La raíz del workspace no es un repositorio Git.

### Auditoría de sesiones al retomar

Confirmado en `includes/auth.php` y `Dockerfile`: se usa el almacenamiento de sesiones predeterminado de PHP, sin un manejador persistente ni configuración de duración propia. Los archivos del contenedor pueden desaparecer al desplegar o reiniciar; las cuentas en PostgreSQL no se eliminan por ello. La cookie dura la sesión del navegador y la limpieza de PHP puede caducar los archivos independientemente de esa cookie. La duración efectiva en Render requiere comprobar su configuración en ejecución.

Auditoría histórica anterior al cambio. La solución ya se implementó en `includes/sessions.php` y `includes/auth.php`: credenciales opacas con digest en la base, caducidad fija de 30 días y bloqueo temporal por sesión, sin mantener abierta una transacción durante consultas de la aplicación. Una petición atrasada no puede sobrescribir otra ni restaurar una sesión cerrada. Si falla el inicio del almacenamiento, se devuelve 503; no se recurre a archivos temporales ni se interpreta como cuenta inexistente. La IP no identifica dispositivos.

Validación de sesiones: `tests/sessions.php` en SQLite y en la rama aislada de Neon `br-empty-dawn-b5xa8l24` con conexión directa; `tests/sessions-api.cjs` comprueba reinicio real del servidor conservando la base, rotación al ingresar, rechazo del identificador anterior, cierre y caducidad; `tests/sessions-concurrency.cjs` comprueba tres procesos simultáneos sin pérdida de cambios. Pasaron además autenticación, cierre, Stock, reportes y perfiles. No cambia los permisos ni limita la cantidad de dispositivos. No incluye recuperación de contraseña ni gestión visual de sesiones.

Reversión: revertir el commit «Persist PHP sessions in the application database». La tabla nueva puede quedarse sin uso; no borrar usuarios ni deshacer migraciones anteriores. Volver a archivos implica otro inicio de sesión y recupera el problema de reinicios de Render.

Al retomar se repitieron satisfactoriamente `tests/stock-api.cjs`, `tests/navigation-timeouts.cjs` y `tests/date-time.cjs`, además de la prueba visual mencionada. Para revertir Stock sin perder otros cambios: `git revert 13615e7`.

Actualización de accesibilidad: la disposición zurda se aplica solamente hasta 768 px con puntero táctil. En PC se conserva siempre la disposición diestra sin cambiar la preferencia guardada. El resultado del escaneo principal alinea nombre, estado y EAN a la derecha en móvil zurdo; tamaño y EAN conservan la misma línea. Cámara y orden de botones se mantienen. Esta regla reemplaza la aplicación anterior en PC. Validado en Chrome táctil a 320/390 px y escritorio a 1366 px y ventana estrecha con ratón; sin desbordamiento. Prueba local: tmp/hand-mobile-check.cjs. Reversión: revertir el commit Limit handed layouts to mobile and align scanned products.

Diseño de accesos y cuenta: Crear colección y generar etiquetas usan azul suave para distinguirse del escaneo. Stock sin palets ni etiquetas muestra un aviso verde. Configuración conserva el ancho de trabajo general, sin tarjeta exterior; accesos, vencimientos y QR incluyen SVG. Navegación: Ingresar sin sesión, Ajustes con sesión. QR sin texto explicativo de rangos; los permisos del servidor permanecen. Validación PHP/JS, navegación y Chrome a 320/390/1366 px con cuenta local vacía, Biblioteca, Stock y QR abierto; sin desbordamiento. Prueba local tmp/account-design-check.cjs. Revertir el commit Refresh account layout and creation affordances.

Ajustes: título y saludo en la misma fila; correo y botón de edición a la izquierda, empresa una sola vez a la derecha. Editor de nombre y empresa con guardado por AJAX, validación de sesión/CSRF y actualización de sesión y saludo. No cambia correo ni contraseña. Validación local del editor a 320/390/1366 px: guardado persistente tras recarga y rechazo de CSRF inválido, PHP/JS sin errores. Prueba tmp/account-edit-check.cjs. Revertir el commit Arrange account contact details and add profile editor.

Header adaptado a móvil zurdo: campana y Salir a la izquierda, logo y saludo a la derecha; panel de notificaciones anclado a la izquierda. Navegación conserva el orden y PC conserva disposición diestra. Chrome táctil: ambos modos a 320/390 px, panel dentro del viewport y sin desbordamiento; 1366 px mantiene disposición de PC. Prueba tmp/header-hand-check.cjs. Revertir el commit Adapt mobile header controls to handed preference.

Prioridad táctil corregida para diestros: escanear queda primero desde el borde derecho y manual a continuación en Biblioteca y lectura de QR de Stock. Escanear producto del palet también pasa al lado derecho antes de la ruedita. Para zurdos se conserva escanear primero desde la izquierda. PC y controles dentro de cámara conservan su disposición. Inicio tiene botón principal a ancho completo. Validado en Chrome táctil a 320/390 y PC a 1366 px con ambos modos, sin desbordamiento. Prueba tmp/scan-priority-check.cjs. Revertir el commit Prioritize scan controls from the selected mobile hand.

Pulido de Ajustes: editor centrado con posición fija, inset y margen automático; conserva animación y límite de alto. Eliminado saludo repetido de la sección, queda el del header y se actualiza al guardar. Accesos y ruedita con bordes, fondos y estados visibles de interacción. Probado editor centrado a 320/390/1366 px tras animación, guardado persistente y CSRF inválido. Prueba tmp/account-polish-check.cjs. Revertir el commit Center account editor and clarify settings buttons.

Excepción de mano solicitada: la barra de usuario y Salir conserva siempre la disposición diestra (nombre a izquierda, Salir a derecha), tanto en móvil como PC. Se mantienen los demás ajustes de mano, incluida la campana. Validado ambos modos a 320/390/1366 px con tmp/account-bar-check.cjs. Revertir el commit Keep account bar in default right-handed layout.

Iconografía de títulos: SVG como máscara en títulos de secciones, diálogos y desplegables de texto, respetando iconos existentes y evitando nombres de productos. Aviso de perfil incompleto movido al contenido de Inicio exclusivamente; no queda detrás de barra del usuario. Punto amarillo en Ajustes (nav/título) y Comodidad de uso; bloque pendiente resaltado y explicación del guardado automático. Se ocultan indicadores al guardar mano y permanecen ocultos al recargar. Validado 320/390/1366 px con sesión local, Inicio/Biblioteca/Stock/Ajustes, geometría del aviso, iconos, persistencia y navegación; sin desbordamiento. Prueba tmp/title-alert-check.cjs. Revertir el commit Add consistent title icons and guide incomplete profile setup.

Separación de Ajustes corregida: su margen superior se había anulado al ampliar el perfil, causando superposición con la barra de usuario desplazada. Restaurado espacio del header (85 px PC, 64 px más área segura móvil), manteniendo 24/28 px entre barra y título. Verificados título y ruedita sin solapamiento a 320/390/1366 px con tmp/account-gap-check.cjs. Revertir el commit Restore account spacing below the user bar.

Foto temporal del escaneo: retirado texto de IP del formulario, manteniendo controles existentes del servidor. Lector EAN compartido captura el encuadre visible solo al confirmar (no QR), JPEG hasta 1280 px y 1 MB. Una sola foto en memoria, asociada al EAN; caduca a 5 minutos y se descarta al comenzar otro escaneo, cambiar de sección o dejar página. Reporte consume la foto del mismo EAN si almacenamiento de fotos está habilitado; muestra vista previa, permite quitar o volver a tomar y sube únicamente al enviar. Cierre/envío limpian blobs y URLs. Carga manual conserva tomar foto; no persistencia local de estas imágenes. Pruebas tests/ean-scan-photo.cjs, tests/ean-scanner.cjs, tests/navigation.cjs y tmp/report-scan-photo-check.cjs: encuadre, confirmación, identidad EAN, captura tardía, caducidad, vista previa, envío y quitar imagen. Revertir el commit Reuse temporary confirmed scan photos in product reports.

Biblioteca: Compartir habilitado desde 1 producto, oculto en colección vacía, validación de mínimo actualizada en servidor. Cámaras y demás ventanas: bloqueo temporal del fondo con cuerpo fijo y posición conservada, sin impedir scroll interno del diálogo; diálogos superpuestos conservan bloqueo hasta cerrar el último. Restauración instantánea del scroll original y limpieza al navegar. Zoom evita gesto de desplazamiento también en su recuadro. Comprobado enlace real con 1 producto y botón oculto vacío, rueda de scroll con cámara abierta, ventanas anidadas y restauración exacta: tmp/share-scroll-check.cjs. Pasaron tests/library-sharing.php (incluye 1 producto), tests/navigation.cjs y tests/ean-scanner.cjs. Verificar gestos de zoom en un iPhone físico. Revertir el commit Share single-item collections and lock dialog background scroll.

Tickets: Ficha actual y Propuesta en dos columnas también en móvil, columnas de ancho limitado y campos alineados por filas mediante subgrid. Resaltado de datos modificados conservado. Prueba con ticket real tmp/report-columns-check.cjs a 320/390/1366 px sin desbordamiento. Revertir el commit Compare report details in aligned side-by-side columns.

Foto automática EAN recortada como la manual: recuadro horizontal centrado, 88% del ancho visible y proporción 2.4, conservando barras y números si se encuentran dentro del marco. No captura todo el visor. Verificados recorte centrado, proporción, limpieza temporal, lector y adjunto de reportes con tests/ean-scan-photo.cjs, tests/ean-scanner.cjs y tmp/report-scan-photo-check.cjs. Revertir el commit Crop temporary scan photos to the EAN frame.

Ticket: Datos queda en fila propia debajo del nombre de quien reporta; Bloquear separado a la derecha. Desplegable compacto con borde suave y flecha que gira. Probado ticket de cuenta registrado/moderador a 320/390/1366 px, apertura, ubicación y sin desbordamiento: tmp/report-sender-final-check.cjs. Revertir el commit Place reporter details below the sender name.

Ticket integrado: Datos dentro del bloque del remitente, despliega una tarjeta superpuesta sin modificar posición de la comparación; mantiene privacidad de campos según respuesta del servidor. Ficha y propuesta comparten una única tarjeta con separador central, colores de cambios conservados, etiquetas abreviadas y más ancho del diálogo (hasta 960 px, margen móvil 12 px). Validado en 320/390/1366 px: control dentro del remitente, panel dentro de pantalla, posición estable al abrir y comparación con borde único. Pruebas tmp/report-unified-check.cjs y tmp/report-columns-check.cjs. Revertir el commit Unify ticket comparison and overlay reporter details.

Permisos de reportes: moderador puede bloquear usuario pero no desbloquear; administrador y manager desbloquean solo rangos inferiores, sin acciones sobre sí mismos. Validado en servidor y estado can_manage, UI oculta Habilitar al moderador tras bloquear. Remitente agrupado con nombre y Datos compacto a izquierda, Bloquear a derecha; overlay sin movimiento. Comparativa con espacios y etiquetas compactados, filas alineadas y colores conservados. API tests/reports-api.cjs verifica 403 de desbloqueo moderador, bloqueo persistente y desbloqueo por administrador; UI tmp/report-permission-ui-check.cjs y columnas tmp/report-columns-check.cjs a 320/390/1366 px. Revertir el commit Restrict report unblocking and compact reviewer controls.

Biblioteca existente: al consultar listas se cruza EAN con catálogo aprobado y, solo si referencia está vacía, se muestra marca/producto/contenido sin sobrescribir datos guardados. Nombres personalizados se conservan. Información de Biblioteca solicita ficha fresca para evitar caché anterior sin datos. No necesita eliminar/reagregar producto; volver a abrir o recargar Biblioteca actualiza lista. Prueba tmp/library-catalog-check.cjs: guardado sin ficha, inserción posterior en catálogo, nombre actualizado, referencia propia intacta y valor original vacío conservado. Revertir el commit Resolve unnamed library items from the approved catalog.

Foto EAN orientada: se conserva el encuadre completo temporal antes del recorte para no cortar códigos verticales. Localización de la misma identidad EAN mediante Quagga en captura fija, dirección de lectura y refinamiento de orientación por gradientes de barras; se gira y recorta con margen para números debajo. Procesamiento no bloquea la aceptación del escaneo; formulario espera la foto pendiente. Si no localiza con certeza el mismo EAN en hasta 4 segundos conserva captura completa, no inventa recorte. Mantiene caducidad/limpieza/upload solo en reporte. Pruebas tests/scan-photo-orientation.cjs con imágenes generadas a 0,90,-90,35,180 grados, vuelve a decodificar resultado horizontal en sentido normal; inspección visual de vertical con números debajo. Pasaron tests/ean-scan-photo.cjs, tests/ean-scanner.cjs y tmp/report-scan-photo-check.cjs. Pendiente prueba con envases reales y condiciones de luz del teléfono. Revertir el commit Orient temporary EAN evidence using detected barcode geometry.

Cinco mejoras (2026-10-11): comprobado lector/foto a 0, 90, -90, 35 y 180 grados, envío de foto solo en reporte, aprobación/permisos y actualización de Biblioteca. Comparación de tickets muestra cambios primero y datos iguales plegados, conserva columnas y colores. Stock incorpora búsqueda nombre/EAN y filtro conjunto de vencimientos según preferencia, solo palets propios/compartidos; máximo 200 resultados con aviso. Recuperación preparada con Resend: migración 14 (tokens digest y auth_version), enlace único 30 minutos, CSRF, límite cinco solicitudes/15 min por IP, sesiones anteriores revocadas al cambiar contraseña. Envío NO habilitado: usuario todavía no tiene dominio/remitente; docs/RECUPERACION.md explica variables. No se envió correo real.
Offline: service worker guarda exclusivamente pantalla/recursos públicos, nunca HTML privado ni APIs. Consultas Biblioteca/Stock separadas por cuenta en el dispositivo, detalle de palet en IndexedDB; consulta hasta siete días. Apertura desde cero sin conexión muestra pantalla específica, Biblioteca solo consulta; Stock permite cargar unidades manualmente con calculadora/vencimiento a palets previamente abiertos y editables. Cola existente valida sesión/permisos/revisión al reconectar y evita duplicados. No incluye fotos, moderación, etiquetas, edición offline ni búsqueda global offline. Registro de detalle del palet se completa antes de abrir diálogo, evita pérdida al navegar rápido. Salir borra consultas de pantalla; cargas pendientes permanecen separadas por cuenta para no perderlas.
Validación: tests/password-reset.php, tests/password-reset-api.cjs, tests/offline-start.cjs, tests/stock-offline-ui.cjs, tests/reports-api.cjs, tests/stock-api.cjs, tests/sessions-api.cjs, tests/navigation.cjs, tests/ean-scanner.cjs, tests/ean-scan-photo.cjs, tests/scan-photo-orientation.cjs; UI tmp/report-changes-check.cjs, tmp/library-catalog-check.cjs y tmp/report-scan-photo-check.cjs. Migración 14 aplicada y comprobada en rama aislada de PostgreSQL, no fixtures en producción. Pendiente cámara real iPhone/Android y entrega de correo luego de configurar dominio. Revertir el commit Enhance moderation, recovery, stock search and offline access; puede conservarse migración 14 sin borrar cuentas.

Stock: cruz SVG dentro del buscador, visible solo con texto. Borra consulta, cancela demora/descarta resultados antiguos, mantiene filtro de vencimientos y devuelve foco al campo. Oculto control nativo duplicado. Validado tmp/stock-clear-check.cjs: aparición, borrado, foco, resultados y anchos 320/390/1366. Revertir el commit Add an inline clear button to Stock search.

Indicador Stock del menú: reemplazado punto rojo por insignia numérica con estilo de Moderación, cuenta filas de productos por vencer/vencidos (EAN+vencimiento por palet), no unidades ni notificaciones sin leer. Mantiene límite de aviso y permisos propios/compartidos; cero oculto, más de 99 muestra 99+. Actualización al cargar Stock y consultar avisos. Validado tmp/stock-badge-check.cjs: cuenta 1 visible, API coincidente, cuenta ajena 0 y anchos móvil/PC. Revertir el commit Show Stock expiry counts like moderation badges.

Carga offline de Stock mejorada: formulario inline reemplazado por escaneo EAN o teclado → ficha de producto con EAN fijo, referencia, unidades/cajas/bultos, contenido por caja, calculadora y fecha DD/MM/AA con máscara. Nombres/referencias se recuperan de Biblioteca, palets consultados y cargas pendientes, exclusivamente de esta cuenta/dispositivo; sin consultas remotas de producto. Cámara utiliza RepoEANScanner habitual (confirmación, análisis Quagga, zoom/flash según dispositivo, teclado); Html5Qrcode 2.3.8 incluido localmente con licencia. SW shell v2 conserva lector y recursos locales; sin caché de APIs/sesión privada; actualización se activa al completar recursos. Recursos de lector usan red primero cuando hay conexión para no tapar versiones nuevas. Cierre y pagehide paran cámara y descartan foto temporal; bloqueo del fondo mientras diálogo abierto. Preferencia de mano conservada para cierres/accesos móviles, cámara sin invertir. Botón manual disponible incluso si falla permiso de cámara. Aviso verde tras guardar, pendientes visibles por palet y datos para sincronización/idempotencia originales conservados. Biblioteca offline sigue solo consulta; no nuevos palets, permisos, fotos de reportes ni cambios de productos existentes sin conexión.
Validación tests/offline-start.cjs: apertura desde cero sin red, scripts locales del lector/Quagga disponibles, cámara simulada de Chrome abre, teclado dentro del visor pasa a carga, EAN fijo, nombre local, 5 cajas × 8 unidades, máscara de fecha, producto desconocido sin buscar API, sincronización real de 40 unidades y privacidad. Diálogos 320/390/1366 sin desbordamiento; inspección visual tmp/offline-product-390.png. tests/ean-scanner.cjs y tests/stock-offline-ui.cjs. Cámara física iPhone/Android y zoom/flash reales pendientes de comprobar. Revertir el commit Align offline Stock entry with the shared scanner and product form; conservar cargas pendientes en IndexedDB.

Palet disponible: aviso «Palet disponible. Puedes utilizarlo.» y plazo para primer producto usan notificación verde común con icono, mediante action-notices. Manager: excepción explícita de control absoluto; ve pendientes propios en bandeja/contador/header y puede aprobar/rechazar sus tickets, bloquear o habilitar reportes incluso de su cuenta o igual rango. Rango consultado en DB, no sesión obsoleta. Administrador/moderador siguen sin pendientes propios en moderación/contador y no pueden revisarlos ni gestionar su propio permiso; listado de cuentas bloqueadas excluye propia cuenta salvo manager. Historial de auditoría conserva información. Reemplaza restricción anterior de manager sobre sí mismo. Validado tests/reports-api.cjs (admin self 403/no visible → promoción manager visible/aprobación y bloqueo/desbloqueo propio, contador disminuye), tests/reports.php (corregida expectativa vieja de desbloqueo por moderador), y tmp/pallet-green-check.cjs (palet disponible real, aviso/color verde). Revertir el commit Style available pallets as notices and allow manager self-review.

Biblioteca: referencia guardada permanece como título y, si existe y es diferente, nombre actual del catálogo (marca/producto/contenido) se muestra debajo en lista y visor EAN. API distingue reference_name y catalog_name sin sobrescribir library_products.name; sin referencia usa catálogo como título, sin catálogo omite segunda línea. Consulta offline conserva ambos nombres desde última actualización, SW shell v3. No se puede inferir retrospectivamente si un nombre guardado antes fue escrito o autocompletado: se conserva cualquier referencia no vacía. Validado tmp/library-test-simple.cjs: ficha incorporada después de guardar, título propio intacto, segunda línea/lista/visor y almacenamiento intacto.
Stock: cámara dentro del buscador comparte lector habitual; EAN confirmado completa campo y ejecuta búsqueda respetando filtro de vencimientos, sin abrir carga ni modificar palet. Teclado del visor vuelve al campo; borrar consulta sigue disponible. Validado tmp/search-camera-check.cjs (confirmación simulada dispara búsqueda, sin diálogo producto) y tests/offline-start.cjs (cámara offline real simulada, layouts y sincronización). Revertir el commit Preserve library references above catalog names and scan Stock searches. Cámara física pendiente de prueba del usuario.

Stock: conserva name como referencia guardada, cruza catálogo por EAN y agrega display_name/reference_name/catalog_name sin sobrescribir referencias. Lista de palet, enlace público y buscador muestran catálogo debajo solo si distinto. Búsqueda incluye marca/nombre de catálogo. Vencimientos separados en Vencidos/Vencen hoy/Próximos; resultado abre palet y resalta lote por id. DB primero, API fallback guarda ficha confirmado en lookup_product; no cambio de flujo. Validado tests/stock.php (referencia intacta y actualización catálogo pública), tests/catalog.php, tests/stock-api.cjs; cámara física pendiente. Revertir commit Preserve pallet references and group expiry search results.

Alertas de vencimiento: rojo vencido, naranja vence hoy, amarillo próximo; etiqueta textual e icono. Notificaciones incluyen destino palet/lote validado por pertenencia/acceso; tocar navega por AJAX a Stock y resalta lote. stock_notify actualiza texto sin duplicar ni reabrir avisos leídos diariamente; cambio de estado a hoy/vencido vuelve a avisar una vez. Elimina avisos obsoletos al retirar lote, cambiar fecha fuera del plazo o perder acceso. Fechas de avisos Argentina 24 h. SW shell v4 para estilos/utilidades offline. Sin migración de esquema. Muestras creadas por pedido explícito del usuario en cuenta manager: palet 56849591, descripción PRUEBA, cinco lotes EAN 96385074 (vencido 08/10/26, hoy 11/10/26, próximo 12/10/26, futuro 10/12/26 y sin fecha), no modifica catálogo/referencias globales ni palets anteriores. Eliminar ese palet desde Stock al terminar; fechas evolucionan naturalmente. Validado tests/stock-notifications.php, tests/expiry-alerts-ui.cjs (320/390/1366, tres estados, destino por AJAX, resalte/grupos), tests/stock-api.cjs, y upsert/lectura en PostgreSQL rama aislada con rollback. Revertir commit Link expiry notifications to pallet lots with distinct status labels; el palet de muestra es independiente del código.

Moderación: quitado fondo blanco, borde, redondeado y padding del contenedor exterior reports-panel, incluido override PC/mobile; conserva márgenes de sección como Stock/Biblioteca. Tabs/tickets/dialogos conservados. Verificado estilos calculados transparentes/borde0/padding0 en 320/390/1366 y comparación de ticket sin desbordamiento (tmp/moderation-flat-check.cjs). Revertir commit Remove the outer moderation card to match section layouts.

Dominio: .htaccess redirige exclusivamente repotools2.onrender.com (HTTP_HOST exacto, case-insensitive/puerto opcional) a https://repotools.online con 308 y misma ruta/query. Docker habilita mod_rewrite. Localhost/dominio propio/otros hosts conservados; FilesMatch de secretos intacto. Verificado Apache real aislado con tests/domain-redirect.cjs: raíz, Biblioteca, palet/código, token y parámetros codificados, ruta con espacio, POST, ausencia de bucles y .env 403. Usuarios con sesión solo en Render pueden tener que ingresar nuevamente en dominio propio, cookies separadas por host; no se transfiere sesión por URL. Revertir commit Redirect Render URLs to the Repotools custom domain. Activación requiere próximo despliegue Docker.

Palets: SVG de Registros reutilizado en stock_icon(pallet), encabezado Palets, tarjetas propias/compartidas/pendientes y título del diálogo. Registros: aprobaciones nuevas vinculan product_edits.after_data.reviewed_report_id al ticket; auditoría completa conservada pero entradas automáticas excluidas de Registros y perfil, mostrando solo revisión Aprobado. Ediciones directas siguen Editado. Sin migración ni modificación de registros anteriores sin origen explícito (no inferir retrospectivamente). Condición JSON por driver SQLite/PostgreSQL. Validado tests/reports.php, tests/catalog.php, tests/reports-api.cjs (aprobación sin product_edit y edición directa visible), tests/user-profiles-api.cjs, tests/expiry-alerts-ui.cjs; expresión de filtro PostgreSQL verificada en rama aislada. Corregida expectativa vieja del test de perfil: indicador permanece en DOM oculto tras completar mano. Revertir commit Reuse pallet icons and distinguish direct edits from report approvals.

Recuperación: usuario confirmó entrega real y cambio de contraseña con Resend configurado en Render, remitente acceso@repotools.online. Correo ahora incluye HTML con identidad Repotools (fondo #f5f6fb, tarjeta, botón #27187e), plazo30min/unuso, aviso de no solicitado y cierre de sesiones, enlace alternativo y versión texto. Sin recursos/imágenes remotos; tablas/estilos inline. Preview tmp/correo-recuperacion-preview.html/png usa enlace de ejemplo sin token real. Verificado render navegador320/390/800 sin overflow y tests/password-reset.php/password-reset-api.cjs. No se envió correo real nuevo; diferencias de clientes de correo pendientes de prueba tras desplegar. Revertir commit Style password recovery emails to match Repotools.

Registros: unificación retrospectiva en consulta, sin borrar auditoría ni escribir en producción. Actualizaciones sin reviewed_report_id se vinculan por EAN, actor y timestamp exactos, con ficha anterior idéntica y candidata única, o cantidad de ediciones igual a aprobaciones en el mismo instante (cada aprobación crea exactamente una edición); si ambiguo conserva entrada. Misma condición en perfil. Confirmados15 matches anteriores exactos en producción mediante SELECT solamente. Filtro Acción solo en Registros, controla event_type/action en servidor antes de paginar; categorías centralizadas includes/report-log-filters.php ampliables, whitelist y parámetros preparados. Cambiar filtro reinicia página y no recarga documento. Incluye aprobar/rechazar/editar/bloquear/habilitar/palets y acciones específicas/cambio rango; no inventa registros de acciones todavía no auditadas. Tests/reports-api.cjs simula auditoría antigua quitando marca, comprueba unificación, edición directa visible, filtros/422/permisos. tests/user-profiles-api.cjs y tmp/log-filters-ui.cjs 320/390/1366 passed. Revertir commit Unify legacy approval logs and add extensible action filters.

Próxima etapa acordada de seguridad: cambiar contraseña desde Ajustes; cerrar todas las demás sesiones mediante botón con confirmación; correo de aviso tras cambiar contraseña. Usuario pidió dejarlo en memoria y priorizó fotos de reportes; todavía no implementado.

Fotos de reportes: formulario consulta ficha fresca con image=true; foto actual visible y botón Cambiar foto, sin añadirla a capturedPhotos ni subir/copiar evidencia existente. Foto nueva sustituye preview; Quitar foto nueva vuelve a referencia existente. Moderación recibe reference_image/source desde catálogo (prioridad foto aprobada propia, luego imagen API guardada), sin consulta externa durante detalle; cuando no hay adjunto product muestra imagen de referencia y aviso explícito, si no existe también informa. Adjuntos nuevos titulados Foto enviada por el usuario y no mezclados con referencia. Mantiene permisos/evidencia EAN. Validado tests/reports-api.cjs, tmp/report-existing-photo-run.cjs (foto guardada en formulario, Cambiar foto, Quitar oculto, referencia moderación y comparación320/390/1366), tmp/report-scan-photo-check.cjs (captura/upload solo al enviar, limpieza). Revertir commit Show existing product photos as references in report forms and moderation.

Mis palets: tarjetas compactadas con padding12/gap10/altura mínima64, código1rem, descripción justo debajo y metadatos/cantidad+aviso en fila flexible. Clase específica stock-pallet-card evita alterar resultados de buscador. Comparte diseño con palets compartidos/pendientes. Validado tmp/compact-pallet-ui.cjs (descripción larga bajo código y320/390/1366 sin overflow) y tests/expiry-alerts-ui.cjs. Revertir commit Compact pallet cards and place descriptions beneath codes.

Referencia de foto al abrir ticket (incluidos anteriores): API GET detalle, después de validar acceso y fuera de transacciones de revisión, resuelve imagen solo si no hay adjunto product ni referencia ya disponible. Prioridad foto aprobada propia/DB, fallback API para producto o foto; persiste URL externa en products.image, no copia binario ni cuenta como adjunto del usuario. Conserva ficha propia. Ausencia/error/límite externo nunca impide revisar; UI informa ausencia de imagen disponible y de adjunto. Evita llamadas externas durante aprobación o servir foto privada. Validado tests/report-photo-reference.php (DB/API/persistencia/cero consulta al repetir/adjunto prioritario/sin foto ni producto), tests/reports-api.cjs. Revertir commit Resolve missing report reference photos through the cached product catalog.

Usuarios en Moderaci?n: listado autom?tico solo admin/manager, 20 cuentas por p?gina ordenadas por nombre/id, carga bajo demanda al abrir pesta?a. Filas compactas nombre/correo/empresa/rango, sin IP ni datos privados adicionales; apertura de ficha por id validada en servidor. Volver conserva p?gina, cambio de rango refresca lista. Correo/QR y jerarqu?a conservados. Validado tests/user-profiles-api.cjs (27 cuentas, p?ginas20/7, permisos/campos/id inv?lido) y tmp/user-directory-ui.cjs (20/6, ficha/regreso,320/390/1366 sin overflow). Revertir commit Add a compact paginated user directory to moderation.

Inicio: resultado de escaneo ordenado con nombre arriba, contenido/EAN en fila flexible y acciones debajo; espacios y botones compactados sin alterar c?mara/lector. Aviso con SVG y fondo verde al agregar, amarillo al duplicar/quitar, texto expl?cito. Validado tmp/main-result-ui.cjs con lectura simulada y API simulada: agregado/duplicado,320/390/1366 sin overflow; no prueba c?mara f?sica. Revertir commit Simplify scanned product results and status notices.

Usuarios: filtro por rango Todos/Usuario/Moderador/Administrador/Manager aplicado en SQL preparado antes de paginar20; whitelist rechaza valores desconocidos/arrays. Cambio reinicia primera pagina y conserva correo/QR. Validado tests/user-profiles-api.cjs y tmp/user-role-filter-ui.cjs (manager, vacio moderador, todos, reinicio y320/390/1366). Revertir commit Filter the moderation user directory by role.

Ficha de usuario: estado de permiso reportes y boton rojo Bloquear reportes con escudo; Habilitar verde si bloqueado. Confirmacion, CSRF, politica existente de jerarquia validada dentro de transaccion/lock, admin solo rangos inferiores y manager todos. Permite gestionar incluso sin ticket previo; evento auditado e idempotente. Validado tests/user-profiles-api.cjs (bloqueo/desbloqueo/CSRF/rango superior/moderador/evento), tmp/user-profile-block-ui.cjs (acciones de ficha y retorno/listado/filtro/layout). Revertir commit Manage report blocking from user profiles.

Seguridad acordada completada: Ajustes tiene Cambiar contrase?a y Cerrar otras sesiones, ambos requieren clave actual. Confirmaci?n adicional al cerrar dispositivos. auth_version cambia at?micamente con condici?n de versi?n; dispositivo actual recibe versi?n nueva y cookie regenerada, otros pierden autenticaci?n al siguiente pedido. Invalida tokens de recuperaci?n existentes. L?mite10 intentos/15min por cuenta, CSRF,10-72bytes/nueva distinta/confirmaci?n. Correo de aviso HTML Repotools con Resend tras cambio desde Ajustes y recuperaci?n; fallo de correo no revierte contrase?a y Ajustes lo informa. Sin migraci?n ni cambios de cuentas reales. Validado tests/account-security-api.cjs (dos sesiones, CSRF/clave/confirmaci?n/l?mite/tokens), tests/password-reset-api.cjs, tests/password-reset.php, tmp/account-security-ui.cjs320/390/1366/ojos independientes y PostgreSQL con tablas temporales en rama aislada. Entrega real del nuevo correo pendiente de prueba tras Render. Revertir commit Add account password changes and revoke other sessions. Pr?ximo paso: usuario prueba desde dos dispositivos y verifica aviso de correo.

Estetica perfiles: etiquetas de rango en listado y ficha reutilizan account-role/color del nav y nombre completo con SVG corona/usuario ajustes/escudo/usuario. Permiso reportes en fila compacta con estado iconografico y boton rojo blanco redondeado, habilitar verde. Sin cambios de permisos. Validado tmp/user-profile-style-ui.cjs: icono ficha, bloqueo/habilitacion, filtros y listado/ficha320/390/1366 sin overflow. Revertir commit Polish profile report controls and full role badges.

Usuarios reorganizados: toolbar buscador/QR/filtro alineada PC y apilada movil; filtro oculto al mostrar ficha. Filas normalizan margenes/botones compartidos. Ficha orden datos, estadisticas, rango, permiso reportes; actividad segunda columna PC/seccion inferior movil con separador. Anchos minimos, labels/datos y controles ajustados, sin contenedor blanco exterior. Corregidos signos/acentos de confirmacion de bloqueo. Verificado tmp/user-layout-ui.cjs/listado/ficha/bloqueo/filtros320/390/1366, capturas inspeccionadas movil390/PC1366. Revertir commit Organize moderation user controls and profile layouts.

Ventana seguridad: ojos dentro de cada input con padding reservado, independientes y accesibles; titulo/labels/guardar con SVG, formulario compacto ancho440/alto46, foco violeta suave. Preferencia zurdo solo movil/coarse mueve ojo al lado accesible. Iconos conservados al alternar cambio/cierre. Validado tmp/password-dialog-style-ui.cjs: ojos geometricamente dentro de inputs, funcionalidades,320/390/1366 sin overflow y captura movil inspeccionada tras animacion. Revertir commit Refine password dialogs with embedded visibility controls.

Seguridad Ajustes: dos acciones en fila con columnas iguales incluso320px; cerrar otras sesiones rojo suave diferencial, textos permiten dos lineas dentro de boton sin desbordar. Validado tmp/security-actions-row-ui.cjs alineacion320/390/1366 y funciones intactas. Revertir commit Align account security actions and distinguish session closure.

Moderacion conserva pesta?a por query view al recargar; script de Usuarios detecta pesta?a ya seleccionada aunque click/restore ocurra antes de enganchar listener. Estado URL incluye ficha user_id, rango/list_page y activity/activity_page, vuelta conserva listado/pagina. Respuesta tardia de ficha no fuerza URL Usuarios si persona paso a Reportes; listas existentes ya descartan respuestas viejas por version, probado con retraso. Volver con flecha, badge Usuario sin icono, rango al lado del nombre en movil/PC. Actividad filtros solo kinds presentes en toda auditoria del perfil; SQL whitelist/preparado antes de LIMIT30, tipos disponibles consultados fuera de pagina. Validado tests/user-profiles-api.cjs (tipos presentes/ausentes,35escaneos paginados,422), tests/reports-api.cjs y tests/user-moderation-state.cjs (reload usuarios/ficha/filtro/logs, ida-vuelta rapida con delay, returnpagina, bloqueos,320/390/1366). Revertir commit Preserve moderation tabs and filter user profile activity.

Edicion staff de cuentas segun ultima aclaracion de jerarquia: admin nombre/empresa de usuario, moderador o propia cuenta; manager nombre/empresa/correo de cualquiera. Cambio de rango conserva reglas existentes. Editor modal minimalista con lapiz/labels SVG, validacion, CSRF, snapshot original y bloqueo de fila PostgreSQL/advisory para no sobrescribir cambios concurrentes. Correo solo manager, normalizado/validado/unico; al cambiar auth_version++ y elimina recoverytokens, cuenta vuelve a ingresar, si es manager propio navega al login. Nombre/empresa actualizado se refleja en sesiones vigentes en siguiente peticion; propio nombre actualiza saludo inmediato. Migracion15 crea user_profile_events e indice, sin cambiar datos existentes. Guarda actor/antes/despues/campos cambiados, evita eventos si sin cambios. Nueva actividad profile y filtro global profiles en Registros. Actividad de fichas redisenada con SVG por tipo, color, fechas alineadas, cambios antes->despues y etiquetas traducidas. Aviso datos actualizados usa notificacion verde existente. Validado tests/user-profiles-api.cjs (admin inferior/no superior/correo403, managercorreo, invalidacion sesion/token, conflicto409, validacion y auditoria), tests/user-profile-edit-ui.cjs, tests/user-moderation-state.cjs, tests/reports-api.cjs, tests/account-security-api.cjs. Migracion/edicion/filtro/cambio correo probados en rama Neon aislada con cuentas temporales tmp/profile-edit-postgres.php; ninguna cuenta real modificada. Capturas ficha mobile/PC inspeccionadas. Revertir commit Edit account information by rank and style profile activity; tabla15 puede permanecer sin uso al revertir codigo.


# Sucursales y stock compartido — 11/10/2026

## Decisiones acordadas

- Sucursal opcional, privada de cada cuenta. No es un directorio global.
- Se carga por lista: nombre, calle/altura, localidad, provincia y descripción opcional.
- Búsqueda local por nombre o dirección y selección desde los palets. El mapa se deja para el final; no se contrató proveedor ni se añadieron claves.
- Solo el propietario asigna sus palets a sus sucursales. Los permisos anteriores para editar productos y consultar palets siguen vigentes.
- Los palets deben estar activos (primer producto cargado) antes de asignarlos o compartirlos en una selección. Las etiquetas pendientes no se comparten.
- Compartir una sucursal incluye sus palets actuales y los que se asignen después. Compartir una selección conserva los IDs de los palets elegidos, con contenido actualizado.
- Los enlaces son públicos, de solo lectura y no requieren cuenta. Quien tenga el enlace puede ver nombres, empresa, descripciones, productos, cantidades, fechas y último movimiento; no obtiene correos, IP, IDs de cuentas ni permisos de edición.
- Se pueden desactivar los enlaces. Eliminar una sucursal desactiva sus enlaces y deja sus palets sin sucursal, conservando los productos.
- Un código de palet reutilizado no incorpora el nuevo palet a una selección anterior.

## Implementación

Migración 16 en `includes/database.php`: `stock_branches`, columna nullable `pallets.branch_id`, `stock_shares` y `stock_share_pallets`, con índices. Es aditiva; los palets anteriores quedan sin sucursal.

`includes/stock-branches.php` valida propiedad, campos, revisión de formularios, duplicados exactos por nombre/dirección/localidad/provincia y permisos. `api/sucursales.php` usa sesión, CSRF, operaciones idempotentes separadas de Stock y el mismo lock transaccional de palets. Todos los cambios se confirman juntos o se revierten. No utiliza APIs externas.

Stock incluye sección plegable Sucursales, búsqueda, agregar/editar/eliminar, filtro de mis palets por sucursal o sin sucursal, selección para compartir y lista de enlaces activos. La tuerca de cada palet propio activo ofrece Sucursal del palet y permite crear una desde allí. El palet muestra ubicación y descripción de sucursal; su descripción propia sigue separada.

`stock-compartido.php` y GET `api/sucursales.php?token=…` muestran el stock actualizado, con 20 palets por carga y botón Ver más. El token se genera con 32 bytes aleatorios. No hay caché offline nueva de sucursales, tokens ni vistas públicas; administrar sucursales requiere conexión. La navegación AJAX se integra sin listeners de páginas anteriores. CSS e iconos siguen el diseño existente y la preferencia de mano solo en móvil con entrada táctil.

## Validación y cómo probar

- `php tests/stock-branches.php`: migración idempotente, privacidad entre cuentas, duplicados, propietario, revisión obsoleta, sucursal dinámica, selección fija, descripciones, eliminación, paginación y reutilización del QR. Disponible para SQLite en memoria o PostgreSQL con `REPOTOOLS_TEST_DATABASE_URL`; datos de prueba dentro de una transacción con rollback.
- `node tests/stock-branches-ui.cjs`: Chrome real, crear/asignar/filtrar/compartir, público sin cuenta, CSRF, reintentos idempotentes, desactivar enlace, navegación AJAX, móvil zurdo y PC, geometría sin desbordamiento a 320/390/1366.
- Regresión `tests/stock.php`, `tests/stock-api.cjs` y `tests/stock-notifications.php`.
- PostgreSQL se valida en la rama de pruebas existente, nunca con cuentas de producción. Solo la migración aditiva permanece en esa rama; los datos de prueba se revierten.

Prueba manual: Stock → Sucursales → +; abrir un palet con productos → tuerca → Sucursal del palet. Volver a Mis palets y filtrar por sucursal. Compartir sucursal con el icono de compartir o Compartir selección para elegir palets; abrir el enlace en incógnito. Agregar otro palet a esa sucursal y actualizar el enlace para comprobar que aparece. Desactivar el enlace desde Enlaces compartidos.

## Continuidad y reversión

Revertir el commit `Add private stock branches and revocable public sharing` revierte interfaz/API y consultas; mantener las tablas y la columna de migración 16 para conservar datos y facilitar retomar. No borrar ni revertir filas de producción. Se puede restaurar el código sin quitar el esquema aditivo.

Pendiente: prueba del usuario en iPhone/Android y confirmar despliegue de Render. La selección mediante mapa es una etapa posterior; primero decidir proveedor y condiciones de costos/almacenamiento. No añadir Google Places ni facturación sin ese acuerdo. No hay gestión de sucursales ajenas, integración de supervisores, empresas anexas ni nueva suscripción en esta entrega. Esos proyectos siguen pendientes.

Al incorporar nuevas acciones al registro unificado, considerar auditoría específica de sucursales y enlaces: el estado ya se guarda, pero esta entrega no crea una nueva categoría de eventos para sus cambios. Mantener la restricción anterior de notas y permisos de moderación.


## Ajuste de sucursales comunes y preparación de palets — 11/10/2026

Esta aclaración reemplaza la decisión anterior de sucursales privadas: usuario confirmó lista común para todos. Todas las cuentas registradas ven/crean/seleccionan las mismas sucursales; solo administrador y manager editan/eliminan, validado contra el rango fresco en DB. El nombre puede repetirse si la dirección, localidad o provincia son distintas. Al crear con la misma identidad normalizada (mayúsculas/acentos habituales/espacios), se selecciona la existente sin sobrescribirla. Se conservan posibles duplicados históricos; no se borraron datos de producción. Columna owner_id de sucursal queda como creador interno, no limita acceso; no nueva migración.

Los palets y enlaces siguen por propietario: compartir una sucursal común muestra solo stock del creador del enlace, incluyendo sus futuras asignaciones. Contador del directorio también es por cuenta. Admin/manager eliminar sucursal afecta asignaciones/enlaces de todas las cuentas, con confirmación explícita, y conserva palets/productos. Los datos de cuenta del creador no aparecen en la respuesta del directorio ni al guardar/reutilizar una sucursal.

Usar un QR disponible abre selección opcional de sucursal (Sin sucursal por defecto). Crear nueva se despliega en ese mismo diálogo, con flecha que gira, cierre habitual y formulario compacto; evita usar el palet mientras la nueva sucursal todavía no se guardó. API prepare valida sucursal y reclama/asigna QR en la misma transacción/idempotencia, con reubicación ante competencia. También se puede cambiar sucursal de palets propios sin productos; etiquetas sin reclamar no se comparten.

Icono duplicado: el título dinámico de Palet elimina la decoración automática al incorporar su SVG. CSS evita el pseudoicono en títulos con SVG directo. Editores de sucursal ahora usan el encabezado habitual con cruz a la derecha (izquierda para zurdos en móvil), fijo al desplazar el diálogo.

Validado tests/stock-branches.php en SQLite y rama aislada Neon PostgreSQL con rollback (directorio compartido, duplicados, permisos admin/manager, aislamiento de enlaces y reutilización/paginación); tests/stock-branches-ui.cjs prueba dos cuentas, rechazo de edición/borrado de usuario, alta inline de sucursal al reclamar QR todavía sin productos, ausencia de doble icono, CSRF/idempotencia/AJAX y geometría320/390/1366/táctil zurdo. Regresiones tests/stock.php y tests/stock-api.cjs pasan. Captura inline-branch-390.png revisada, en tmp ignorado. Falta prueba física del usuario y confirmar despliegue Render. Mapa sigue pendiente al final.

Revertir el commit Refine common branch permissions and pallet preparation conserva las tablas de migración16 y datos; no revertir datos ni quitar columnas. Detalles actualizados en docs/SUCURSALES.md.


## Stock: retirar bloques del inicio — 11/10/2026

El usuario confirmó quitar solo los bloques Sucursales y Enlaces compartidos del inicio de Stock. Permanecen elegir/crear sucursal dentro del palet, filtro y Compartir selección. No se eliminan datos ni se revocan enlaces existentes. Los avisos de sucursales quedan fuera del panel oculto para seguir visibles. Se actualiza la prueba UI para comprobar que ambos paneles permanecen ocultos después de compartir y navegar por AJAX; mantiene alta inline y asignación desde el palet. Para revertir, restaurar interfaz/JS y versión del recurso del commit de este ajuste, sin cambios de esquema.


Stock: también se retira del inicio el selector de sucursales y Compartir selección para dejar el buscador de productos seguido de Palets, según aclaración del usuario. Se oculta el contenedor completo sin ocupar espacio; elegir/crear sucursal dentro del palet y los datos/enlaces existentes permanecen. Prueba UI adaptada al inicio simplificado; compartir se verifica mediante API. Revertir este ajuste de vista para recuperar los controles.


Palets: bloque principal sin tarjeta blanca, borde ni padding exterior. Sucursal pasa de la tuerca a botón directo a la derecha de la barra del palet; conserva permisos de propietario y el diálogo existente. Validación UI de asignación sin abrir configuración y tamaños móviles/PC. Revertir commit de diseño para volver a la disposición anterior, sin cambios de datos.


Sucursal del palet: búsqueda instantánea sin distinguir acentos/mayúsculas por nombre, dirección, localidad, provincia y descripción. Resultados con dirección y selección visible; Sin sucursal sigue opcional. Sin coincidencias ofrece Nueva sucursal en el mismo diálogo y precarga el nombre escrito. Prueba UI verifica búsqueda por dirección, selección y alta tras búsqueda sin resultados; mantener permisos de servidor. Sin migraciones.


12/10: al abrir Sucursal de un palet ya asignado, muestra primero nombre del supermercado, dirección completa y descripción si tiene, junto a botón de lápiz para cambiar la asignación. Sin sucursal abre búsqueda directamente. Conserva permisos del propietario; cambiar sucursal no edita el directorio común. UI prueba vista previa y paso a edición; reversión solo de vista/JS/CSS sin datos.


Selector de sucursal al usar/cambiar palet: dos pestañas iguales Buscar y Por ubicación, con SVG, roles accesibles y flechas de teclado. Buscar mantiene selección/alta inline; ubicación muestra aviso de mapa pendiente, sin geolocalización ni proveedor nuevo. Al abrir vuelve a Buscar; conserva borrador al alternar. Validar UI alternar y continuar alta/asignación; revertir vista/JS/CSS para selector anterior.


Aclaración: botón Sucursal de barra del palet ahora muestra el nombre del local asignado, con lápiz separado al lado para cambiarlo directamente. Sin asignación mantiene Sucursal. Nombre largo se trunca, controles siguen juntos y permisos del propietario se conservan. Revertir ajuste de vista/JS/CSS sin cambios de DB.


Se oculta información duplicada de sucursal sobre la barra del palet; nombre queda en botón y dirección/descripción al abrirlo. Descripción propia del palet permanece. UI verifica ausencia del bloque duplicado y nombre actualizado tras asignar. Revertir JS y versión del recurso para restaurar bloque, sin cambiar datos.


Sin coincidencias de sucursal: mensaje centrado, botón Agregar sucursal centrado de ancho ajustado al texto y altura mínima40px, conserva SVG y flecha de despliegue. Ajuste visual sin cambiar búsqueda/alta/permisos; revertir CSS y texto del botón para restaurar tamaño anterior.


Agregar sucursal siempre visible en pestaña Buscar, haya coincidencias o no: locales con mismo nombre y diferente dirección son válidos. Estilo compacto discreto con borde fino sin relleno, alineado al extremo derecho. Mapa pendiente: almacenar latitud/longitud por sucursal, permitir fijar pin al crear/corregir, solicitar geolocalización solo al abrir pestaña ubicación y ofrecer alternativa si se rechaza. Elegir proveedor de mapas y condiciones antes de integrar; sin servicio nuevo en esta entrega.

Mapa implementado 12/10: Leaflet local 1.9.4 + OSM est�ndar sin clave, carga al abrir Por ubicaci�n. Migraci�n17 agrega coordenadas opcionales de sucursal; selecci�n de marcadores y alta con pin, edici�n de datos desde vista previa solo admin/manager. Posici�n del usuario temporal, permiso al abrir y alternativa sin GPS. Bot�n Agregar sucursal sin +. Pruebas SQLite/Chrome y regresiones Stock pasan; teselas simuladas para evitar tr�fico automatizado a OSM. PostgreSQL aislado pendiente: credenciales previas rechazadas. Revisar docs/MAPA-SUCURSALES.md para uso, condiciones, pruebas y reversi�n; conservar columnas y datos al revertir c�digo. Pendiente prueba f�sica y confirmar Render.


Mapa: tocar un punto muestra sucursales de nuestra DB dentro de100m (máximo5) y Agregar sucursal aquí. Alta precarga coordenadas; consulta Photon reverse al confirmar agregar, nunca al escribir/panear ni con GPS automático. Dirección sugerida editable sin sobrescribir campos que cambió usuario; solo propone nombre si es shop, descarta resultados lejanos. Servicio gratuito de demostración sin SLA: caché24h máximo1000 puntos y bloqueo compartido por instancia, máximo1consulta/2s. Cambiable mediante PHOTON_REVERSE_URL HTTPS, sin clave. Endpoint POST autenticado/CSRF, coordenadas validadas, respuesta/tiempo limitados y alternativa manual. No migración nueva ni cuentas producción alteradas. Mapa mayor y detectRetina para pantallas densas, mantiene atribución y no estira teselas al pasar zoom máximo. Pruebas PHP de normalización/caché/límite y Chrome de alta desde mapa/prefill/auth/CSRF pasan; una consulta real en punto público devolvió localidad/provincia, calle/altura no siempre disponibles. Ver docs/MAPA-SUCURSALES.md. Revertir commit de flujo de mapa conservando coordenadas existentes; revalidar uso proveedor si crece tráfico o varias instancias.


Carga de dirección: aviso arriba del nombre con SVG animado (respeta movimiento reducido), campos y guardar deshabilitados/inertes durante consulta; se liberan al terminar, fallar o cerrar. Revisión se muestra como notificación verde, error como aviso ámbar. Cercanía pasa a200m: al tocar cerca se abre automáticamente ficha del local más próximo en el mapa y permanece Agregar sucursal aquí para otro comercio. Prueba Chrome con respuesta retenida verifica bloqueo, orden del aviso, desbloqueo y ficha automática manteniendo alta. Revertir commit de UX para disposición previa, sin cambios de datos.


Mapa UX: diálogo más ancho solo en Por ubicación y mapa más alto; ubicación como botón de icono flotante, zoom con superficie translúcida y objetivos táctiles44px. Marcadores con icono de local y ficha compacta; cercanos usan nombre/dirección en dos líneas, sin botones pesados. Conserva OSM/retina y atribución: este ajuste mejora presentación, no sustituye el proveedor raster ni promete nitidez vectorial. Pruebas de mapa y formulario siguen; revertir commit de estilo sin tocar datos.


Mapa vectorial � 12/10/2026: OpenFreeMap Liberty con MapLibre GL JS5.6.2 y adaptador Leaflet0.1.3 locales, carga diferida. Mantiene marcadores, selecci�n, cercan�a200m, formulario y preferencia de mano. Sin cuenta/clave/facturaci�n. Atribuci�n del proveedor conservada por adaptador. Sin WebGL, error inicial o espera12s: mapa OSM anterior; b�squeda escrita disponible. Limpieza de mapas/temporizadores al cerrar o navegar AJAX. No cambia DB ni permisos. Pruebas Chrome con estilo vectorial vac�o simulado (sin tr�fico automatizado al proveedor); nitidez cartogr�fica real y rendimiento en iPhone/Android requieren prueba f�sica. Revertir archivos del commit de esta entrega, conservando coordenadas y datos.


## Favoritos y sucursales recientes — 12/10/2026

Selector con Todas/Favoritos/Recientes y estrella independiente de la selección. Sin sucursal usa ubicación tachada. Favoritos por cuenta, guardados en DB, disponibles entre dispositivos. Recientes muestra hasta8 sucursales ordenadas por último uso, con frecuencia como desempate; se registra solo al guardar asignación o preparar palet con sucursal, no al tocar resultados. Todas prioriza favoritos y último uso. Quitar favorito conserva recientes. No otorga permisos ni cambia directorio común. Migración18 aditiva user_branch_preferences con clave user_id/branch_id y borrado en cascada; endpoint autenticado/CSRF/idempotencia, usuario tomado de sesión. Sin cambios manuales a producción.

Validado PHP sintaxis y tests/stock-branches.php en SQLite (migración repetible, aislamiento, favorito explícito repetido, validación, reciente al asignar y conservación al quitar estrella); Chrome tests/stock-branches-ui.cjs (estrella persistente al reabrir, filtros, vacío, asignación, AJAX y tamaños320/390/1366). PostgreSQL aislado pendiente por credenciales de prueba previas rechazadas; no afirmar validación Neon. Probar físicamente en móvil, confirmar Render y favoritos en otro dispositivo. Revertir el commit de favoritos conservando tabla18 y datos.


## Moderación: Sucursales — 12/10/2026

Nueva pestaña solo admin/manager junto a Reportes/Registros/Usuarios, filas con separadores, acciones SVG44px y búsqueda por nombre/dirección/localidad/provincia.20 resultados por página, navegación anterior/siguiente; conserva view=branches al recargar y cancela respuestas al cambiar sección. Formulario modal de alta/edición con descripción y mapa vectorial para corregir coordenadas o quitarlas. Coincidencias por nombre al escribir muestran dirección y botón Revisar con confirmación antes de descartar formulario; exactos reutilizan registro mediante reglas existentes. Mismo nombre con otra dirección permitido. Eliminar usa RepoConfirm, conserva palets sin sucursal y desactiva enlaces comunes; permisos de servidor frescos.

Migración19 aditiva branch_events, actor/acción/nombre y valores antes/después, guarda alta/edición/borrado junto con operación; no crea evento al reutilizar duplicado. Registros unificados incluyen categoría Gestión de sucursales, iconos y etiquetas de acción. No publica datos del creador en directorio; gestión GET autenticada restringida a admin/manager. Favoritos/recientes y permisos de palets conservados.

Validación: tests/stock-branches.php SQLite (migración repetible, permisos, filtro, paginación20, auditoría), tests/reports.php y sintaxis PHP/JS. tests/stock-branches-ui.cjs Chrome: pestaña conservada tras recarga, editar desde moderación, punto en mapa, sugerencias similares, registro filtrado, rechazo usuario normal, regresiones de Stock y geometría320/390/1366; proveedor simulado sin tráfico externo. Capturas móvil revisadas; ajuste extra a pestañas320 para evitar texto pegado. PostgreSQL aislado sigue pendiente por credenciales previas rechazadas; no validado ni alterado manualmente. Falta prueba física y confirmar Render. Revertir commit de panel, conservando tablas18/19 y datos.


## Compartir Stock desde barra de escaneo — 12/10/2026

Barra Escanear palet / teclado / compartir / ojo enlaces compartidos. Compartir visible con al menos un palet propio activado; pendientes e invitados no se seleccionan. Ojo visible también si quedan enlaces activos aunque no haya palets, para poder desactivarlos. No restaura bloques ocultos del inicio. Modal Palets/Sucursal, búsqueda código/descripción/sucursal, selección persistente al filtrar, contador, Seleccionar visibles y Limpiar; inicialmente sin selección, crear deshabilitado hasta elegir, máximo200 por selección. Sucursal ofrece solo locales con palets propios activados, explica actuales y futuros; servidor rechaza compartir sucursal sin stock propio. Preferencia de mano móvil conserva escanear primero, sin invertir SVG.

Modal Enlaces compartidos: nombre/tipo/palets disponibles actuales, abrir en nueva pestaña, compartir y desactivar con RepoConfirm. Copiar muestra aviso dentro del modal; errores también. GET incluye contador de palets por enlace sin datos privados. No migración nueva, enlaces anteriores siguen funcionando. No publica QR pendientes ni palets ajenos. Vista pública y permiso de solo lectura existentes se conservan.

Validación: sintaxis PHP/JS, tests/stock-branches.php SQLite (reglas previas, sucursal sin stock rechazada), tests/stock-branches-ui.cjs Chrome (selección sin perder al filtrar, seleccionar visibles/limpiar, compartir1, vista pública sin cuenta, sucursal, listar enlaces, desactivar mediante modal devuelve404, regresiones AJAX/permisos/mapa y tamaños320/390/1366). Captura móvil del selector revisada; estilos afinados para pestaña activa y barra compacta. Falta prueba física y confirmar Render. Revertir commit de compartir conserva DB/enlaces y devuelve controles anteriores.


Aclaración enlaces compartidos12/10: botón del ojo visible únicamente con al menos1 enlace activo propio. No basta tener palets cargados. Al crear el primero aparece y al desactivar el último se oculta; el modal abierto conserva aviso de éxito. Compartir mantiene condición de palet activado. Prueba Chrome comprueba estados antes/después de crear/desactivar. Revertir este ajuste JS para visibilidad anterior; sin DB nueva ni cambios a enlaces.


## Sucursales compartidas con exclusiones — 12/10/2026

En compartir por sucursal, despliegue Ver palets incluidos con contador, chevron giratorio y casillas inicialmente marcadas. Desmarcar excluye del enlace sin convertirlo en lista fija: futuros palets propios activados de esa sucursal se incorporan automáticamente. Puede excluir todos los actuales; próximos siguen incluidos. En Enlaces compartidos, lápiz Elegir palets incluidos permite cambiar exclusiones manteniendo token/URL; sucursal y título bloqueados al editar. Excluidos guardados por ID interno del palet, no código reutilizable.

Migración20 aditiva stock_share_exclusions, claves share/pallet y cascada. API autenticada/CSRF/idempotencia valida propiedad del enlace activo y de cada ID, límite200 exclusiones; el público y contador aplican NOT EXISTS antes de paginar. Cuentas ajenas no pueden editar ni excluir palets ajenos. Enlaces anteriores sin exclusiones mantienen comportamiento. Valores excluidos se conservan por enlace y no modifican palets ni permisos.

Validado sintaxisPHP/JS, tests/stock-branches.php SQLite: ocultar excluido, incorporar nuevo, restaurar en mismaURL y rechazo de cuenta/ID ajenos; tests/stock-branches-ui.cjs Chrome: despliegue/desmarcado, público vacío, lápiz/estado persistido y restauración sin cambiarURL, regresiones previas. PostgreSQL aislado pendiente por credenciales previas; no cambio manual producción. Falta prueba física y despliegue Render. Reversión segura: restaurar solo UI manteniendo filtro de exclusiones en público/API. Revertir todo el backend haría reaparecer excluidos: antes habría que desactivar enlaces de sucursal con exclusiones. Conservar tabla20 y datos.


## Calculadora opcional al cargar palets — 12/10/2026

Formulario producto: calculadora en despliegue nativo con SVG y flecha giratoria, cerrada por defecto. Recuerda apertura/cierre con localStorage por user_id en este navegador; no sincroniza dispositivos ni agrega telemetría, y tolera almacenamiento bloqueado. Se aplica al agregar/editar y al volver a abrir/navegar. Mantiene cálculo escrito, teclado de calculadora y Enter; no invierte iconos ni teclado. Placeholder simple8 cerrado, 8×5 abierto. Cantidad de unidades/cajas/bultos según presentación, total legible y validación de tamaño por caja antes de mostrar números incorrectos; vacío sin error prematuro. Botón igual destacado, C discreto rojo y separación compacta.

Validado sintaxisPHP/JS, tests/stock.php y tests/stock-branches-ui.cjs Chrome: cerrado inicial, 8×5=40, cajas40×5=200, preferencia abierta al reabrir y cerrada tras recarga, regresiones de Stock y resto de flujos. Sin migraciones/servidor nuevo, permisos y datos conservados. Falta prueba física en móvil. Revertir archivos del commit para calculadora siempre visible; la clave local puede conservarse sin efecto.


## Diagnóstico de desactivación de enlaces — 12/10/2026

Usuario informa link aún visible tras desactivar y recargar. Consulta HTTP de solo lectura al enlace indicado: API200 con2 palets, Cache-Control:no-store en dominio raíz/www; código de desactivación nuevo desplegado. Esto demuestra enlace activo en servidor, no caché, pero no se identificó causa exacta de su intento anterior. No se alteraron enlaces/datos reales ni se guardó token en documentación.

Corregido fallo adicional de vista pública: error de revalidación dejaba palets anteriores visibles. Ahora se limpia contenido/datos y oculta Ver más; al volver a pestaña/foco/pageshow se revalida y respuestas obsoletas no repintan. Desactivación desde lista comprueba404 público antes de confirmar éxito. Cada enlace muestra referencia de6 caracteres y fecha; confirmación aclara que otros enlaces del mismo stock siguen activos. Sin cambio DB.

Validado node sintaxis y tests/stock-branches-ui.cjs Chrome: revocar enlace ya abierto, foco limpia palets, recarga sigue sin contenido; revocación desde modal con comprobación404 y flujos anteriores pasan. Pendiente: usuario reintentar enlace concreto tras despliegue y comprobarlo; no afirmar que el enlace real ya se desactivó. Revertir commit de JS/vista conserva estados de links enDB.


## PC y celular como lector — 12/10/2026

Escaneo de escritorio con input/Enter para teclado o pistola, cámara opcional y Usar celular sin QR. Preferencia local por cuenta, consentimiento explícito en celular de la misma cuenta, aviso verde y desconexión desde ambos. Inicio recibe la lista, Biblioteca y Stock abren sus formularios; pausa durante cargas individuales. Cámara compartida y entrada manual del celular. Lecturas aisladas por apertura para evitar interferencias entre pestañas, acuse y una lectura pendiente. Permisos/CSRF/códigos validados en servidor, caducidad30 minutos sin actividad. Migraciones21/22 aditivas.

Una sesión de navegador PC + una celular: ingreso nuevo del mismo tipo pide Cancelar/Entrar aquí; reemplaza ese cupo, conserva el otro y las cargas locales. Dispositivos clasificados por User-Agent: no identificación física ni anticopia. Sesiones antiguas adoptan cupo al usarse. Token en sesión, digest en DB. Cierre/cambio de contraseña conserva cupo actual y revoca otros; logout libera solo token coincidente. Corregido formulario que deshabilitaba el botón antes de enviar su decisión y carga duplicada del módulo de conexión.

Diseño PC ampliado: Stock/palets, Biblioteca, Moderación, Ajustes e Inicio con distribución de escritorio; móvil conserva mano e iconos, herramientas no se invierten. Ver docs/ESCANEO-PC-CELULAR.md para uso, pruebas, límites y reversión. Validación SQLite/Chrome simulada; falta teléfono real, lector físico, PostgreSQL aislado y verificar despliegue Render. No transferimos fotos ni ejecutamos relay sin Internet. Revertir commit completo preservando tablas21/22; mantener actualización de cupo en Seguridad si se conserva el control de sesiones.


## Aviso de contraseña actualizada — 12/10/2026

Contraseña actualizada y cierre de otras sesiones ahora usan la notificación compartida verde, borde suave, esquinas redondeadas e icono SVG de confirmación. No cambia seguridad ni mensajes del servidor. Clasificación JS verificada para éxito, error y vacío; sintaxis correcta. Revertir este commit restaura presentación anterior sin cambios de datos.


## Paleta de correos — 12/10/2026

Correos de recuperación y contraseña actualizada con los mismos colores de la web: violeta #27187e, fondo #f4f7fb, etiqueta lavanda #e9e6f5 y confirmación verde #eaf7ef/#17633d. Marca en cabecera violeta y texto blanco; botones conservan violeta. HTML de confirmación extraído a función para previsualizar sin enviar mensajes. Validación PHP y render HTML local; no se enviaron correos. Los clientes de correo pueden adaptar colores en modo oscuro. Revertir este commit restaura plantillas anteriores sin cambiar envío, tokens ni sesiones.


## PC sin cámara y espera de celular — 12/10/2026

Retirada cámara de escritorio, incluidas preferencias antiguas guardadas: teclado/lector o celular vinculado. Perfil por QR en PC acepta enlace/lector; móvil mantiene cámara. Offline en PC abre entrada manual, sin cámara. Espera del celular usa aviso lavanda con icono y explicación breve, cambia a verde al conectar. Se elimina fondo negro y altura reservada de cámara en Inicio/Biblioteca/Stock; botón para volver a teclado/lector. Caché offline v5 para actualizar lector. Validación del relay completo con móvil simulado, preferencia antigua y fondo blanco; pruebas de lector y navegación. Revertir este commit conserva DB/sesiones/conexiones y devuelve cámara opcional en PC.

Verificación adicional: apertura offline y carga pendiente/sincronización pasan tanto con User-Agent móvil como de escritorio; PC no crea video, móvil sí conserva cámara. Vista de espera revisada mediante captura Chrome.


## Correos minimalistas — 12/10/2026

Cabecera con marca sobre fondo claro, sin bloque violeta sólido; se mantienen botones #27187e, tarjetas blancas, fondo #f4f7fb y confirmación verde. Padding más compacto y metadatos de esquema claro (el cliente puede ignorarlos). Tablas de ambos modelos sin espaciado implícito. Validado PHP, tests/password-reset.php y render Chrome móvil sin desborde. Sin cambios de contraseña, tokens ni sesiones reales. Envío desde Render pendiente: no hay credencial Resend ni sesión de consola disponible localmente; no afirmar envío realizado. Revertir commit de plantilla restaura diseño anterior sin alterar datos.


## Solicitud de lector móvil y acceso flotante — 12/10/2026

Solicitud PC en celular presentada en diálogo modal con fondo oscurecido, cierre por cruz y bloqueo de desplazamiento de fondo. Aceptación explícita conserva validación existente de misma cuenta/CSRF; aceptar cierra aviso. Botón circular flotante inferior, verde al conectar y amarillo mientras espera, abre controles de escaneo/desconexión; izquierda para zurdos en móvil y derecha por defecto. Cerrar solicitud permite volver desde botón sin repetir modal en cada consulta. Limpieza al navegar para evitar controles duplicados. PC conserva su aviso existente. Recursos versionados.

Validado node --check y tests/device-connection-ui.cjs: modal automático, cierre tras aceptar, posición por mano y relay Inicio/Stock/Biblioteca, aislamiento/permisos y desconexión. Prueba física móvil pendiente. Sin migraciones ni cambios de seguridad. Revertir commit de JS/CSS/vistas/tests restaura aviso en bloque sin alterar conexiones ni datos.
