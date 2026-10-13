# Punto6 — Escaneo rápido de Inicio

La entrega anterior se publicó antes de iniciar este punto:680fcde (registro y principios UX). El volumen/vibración solicitado aparte también se publicó:915b2f0. No se desarrolla Punto7.

## Instancia y alcance

El principal real es RepoEANScanner.create('contenedor') en js/script.js; su diálogo está en vistas/escaner.vista.php, incluido por index.php. Solo esa instancia activa keepOpen mediante rapidActive. El estado comienza false y al finalizar vuelve a Normal. No se añaden controles a Stock/Biblioteca/palets/QR/relay/vencimientos ni al shell offline. index.html es prueba estática, no la entrada PHP; conserva funcionamiento normal.

El componente compartido ya tenía keepOpen=false por defecto. Se reutiliza su confirmación, encuadre, validación y análisis auxiliar: no se cambia el algoritmo ni se reinicia el stream después de cada EAN. El ciclo vuelve a aceptar después de350ms. Las búsquedas de producto se ejecutan sin retener ese ciclo y sus respuestas tardías se descartan mediante la revisión existente.

El filtro anterior de lecturas repetidas actualizaba lastDeliveredSeen en cada callback. Tras un intervalo largo podía admitir el primer frame del mismo EAN, pero volver a bloquear los siguientes y nunca completar la confirmación. Se añade configuración opcional repeatCooldown=900 y rearmRepeated=false: otros lectores conservan exactamente esos valores y comportamiento. Solo Inicio usa1500ms/true, para rearmar la confirmación tras1,5s sin detectar ese EAN. Mantener el mismo código frente a cámara no suma accidentalmente. Retirarlo y presentarlo otra vez permite una lectura intencional. Las lecturas siguen necesitando la confirmación de varios frames existente.

## UX y datos

Normal/Rápido en un control de dos botones con aria-pressed. Rápido mantiene cámara, confirmación compacta verde/amarilla, último nombre/tamaño y EAN con SVG, contador de sesión y Finalizar como acción principal. Reportar/editar permanecen como acciones secundarias con permisos existentes. Mientras un reporte u otro modal requiere foco no se aceptan lecturas detrás; al cerrarlo se continúa.

Se reutiliza mostrarResultadoEscaneo y lista local bajo la clave lista. No hay otra lista ni DB; el EAN desconocido también queda guardado y muestra aviso, con opción de reportar. La lista conserva su unicidad; una repetición intencional suma al contador de sesión, sin duplicar el EAN en la lista. registrarEscaneoTop conserva sus condiciones/idempotencia: no cambia estadísticas globales, permisos ni APIs.

Cambiar a Normal detiene la captura continua y reinicia el flujo normal existente. Finalizar, X y Escape detienen cámara, invalidan respuestas pendientes, restablecen Normal y muestran resumen mediante toast. Navegar también libera la instancia mediante onLeave. La entrada manual conserva su formulario existente; en Rápido vuelve a abrir captura después de agregar el código.

Actualización posterior: al usar Inicio en PC, el contexto de la solicitud transmite Normal/Rápido al celular. Su interruptor continuo se oculta para ese contexto, evitando dos decisiones contradictorias; Rápido deja abierta la cámara y Normal la cierra al enviar. Otros destinos conservan su control propio. Cada apertura del principal restablece Normal. La altura del feedback está reservada a dos líneas (nombre completo en title), meta y acciones tienen filas estables. Normal queda a la derecha para diestros y a la izquierda para zurdos en móvil; PC conserva disposición estándar, sin invertir iconos de cámara.

Validación de esta corrección: main-rapid-scanner incluye posición de Normal para ambas manos y nombre largo sin aumento de altura; device-connection-ui prueba dos lecturas remotas rápidas, cámara única, ausencia del segundo control y cambio a Normal con cierre. Ambas pasan, junto con scan-result, ean-scanner y lint PHP/JS. Captura de320px zurdo revisada. Revertir esta corrección restaurando sus archivos a ed78b6b; sin migraciones ni cambios de datos. Render y dispositivos físicos pendientes.

Se reutiliza el pitido actual aumentado y vibración compatible de915b2f0; no se agrega otro sonido, preferencia o librería. Safari/iPhone no ofrece navigator.vibrate: ver docs/PITIDO-IPHONE.md. Tono físico y efecto sobre música aún requieren prueba real.

## Archivos y validación

Modificados: js/script.js, js/ean-scanner.js (solo opciones optativas del filtro), vistas/escaner.vista.php, css/scan-result.css, versiones en header/footer e index.html. tests/scan-result.cjs actualiza su fixture a los atributos/parentElement/renderSummary que la UI real ya utiliza.

tests/main-rapid-scanner.cjs pasa:20 EAN válidos consecutivos, cámara iniciada una sola vez y no detenida entre ellos, API lenta y respuesta tardía, duplicado sostenido, relectura intencional, producto desconocido, pausa con modal, lista original única, Finalizar/reset, regreso a Normal con cierre tras una lectura,320px zurdo/390/1366 y ausencia de controles en otras secciones. Chrome con cámara simulada y SQLite temporal; no prueba cámara física ni tiempos ópticos.

También pasan ean-scanner (Normal/EAN8/13/QR/ciclo/audio), ean-scan-photo (evidencia), scan-result (resultados/duplicados/copiar) y device-connection-ui (PC/celular/Stock/Biblioteca/QR/aislamiento). Lint PHP y sintaxis JS pasan. Capturas móvil/PC revisadas para minimalismo, controles táctiles y jerarquía. No se declara toda la suite del repositorio aprobada. Render/Safari/cámara física pendientes.

## Reversión

Revertir únicamente el commit de Punto6 o restaurar sus archivos de implementación y prueba scan-result a915b2f0, retirar prueba main-rapid-scanner y este documento. Mantener el registro Nombre y apellido, reglas UX y aumento de sonido/vibración previamente publicados. No hay migraciones ni datos creados en producción que eliminar.
