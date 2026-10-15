# Auditoría y optimización de escáneres — RepoTools 1.12

## Alcance y conclusión comprobada

Se optimizó el componente global existente, sin reemplazar bibliotecas de reconocimiento en producción. La comparación reproducible usa el motor real sobre video sintético de fotogramas distintos, no solamente fotografías. Mejoró principalmente la latencia QR anterior al tono. Se conservaron checksum, tres fotogramas EAN y tres lecturas OCR, permisos y operaciones del servidor.

**No está comprobada la lectura a seis metros ni la estabilidad de un iPhone/Android físico. La perspectiva sintética más severa sigue sin reconocerse: 0/3 antes y después; también 0/3 con la etiqueta nueva. No se da ese problema por resuelto.**

## A. Diagnóstico, causas y evidencia

- Motor común: Html5Qrcode 2.3.8, BarcodeDetector cuando el navegador lo admite y ZXing incluido como alternativa. EAN conserva Quagga 1.12.1 para orientación difícil. OCR numérico Tesseract/WASM local, en worker. No se incorporó una dependencia nueva al funcionamiento de la app.
- Antes: lector principal a 15 análisis/s sobre un recuadro dimensionado por el visor; en un visor de 360 px el QR principal podía quedar reducido aproximadamente a 306 px aunque el video tuviera más detalle. Además funcionaba un analizador auxiliar hasta 1920 px aproximadamente cada 100 ms, sobre el mismo decodificador. No compartían exclusión de procesamiento. Fuente: versión 1.11, funciones background y start de js/ean-scanner.js y foreverScan del paquete incluido.
- El QR validado esperaba tres coincidencias, con separaciones mínimas de 60 ms. Esa espera era innecesaria para un QR ya decodificado y validado por contexto. El OCR sí necesita su consenso propio y se mantuvo.
- OCR empezaba a intentar su carga desde los primeros 550 ms, incluso para QR fáciles. No tenía un control explícito de visibilidad de la página en su propia lectura.
- EAN tomaba su evidencia temporal antes de pedir el sonido. Esa preparación puede trabajar sobre canvas y normalizar la imagen. Ahora se pide el tono antes de iniciar la evidencia opcional.
- El control de zoom imponía un mínimo de 1x e incluso podía subir automáticamente desde un 0.5x real. Ahora representa el mínimo real verificable y no cambia el zoom al abrir.
- Un error del callback en modo continuo podía dejar la cámara abierta sin volver a aceptar lecturas. Ahora se rearma conservando la protección del código ya entregado: **no se vuelve a enviar automáticamente una operación ambigua**.
- Las consultas del palet, producto y envío PC–celular ocurren en el callback posterior a la confirmación/tono. No se atribuyó su tiempo de red a la decodificación. La carga inicial de recursos y el arranque de OCR sí pueden depender de caché/red; no se confundieron con una consulta de inventario.

Instancias auditadas que heredan la mejora: Inicio Normal/Rápido (js/script.js), Stock/agregar/restar/buscar palets y EAN (js/stock.js), Biblioteca (js/biblioteca.js), shell offline (js/offline-page.js), teléfono conectado a PC (js/device-connection.js), y perfiles QR (js/user-profiles.js). Se conservan sus callbacks, formatos esperados y autorizaciones. Las cámaras para fotos de reportes no son motores de reconocimiento y mantienen su flujo.

## B. Correcciones y arquitectura

1. Se pausa **el análisis** interno del lector, sin pausar su video, y se reutiliza su mismo decodificador con un planificador común. Su interfaz de cámara, permisos, linterna y lease global continúan siendo los existentes. Un guard compartido impide decodificaciones simultáneas y descarta solicitudes concurrentes; no forma una cola de imágenes antiguas.
2. El adaptador está acotado al paquete incluido 2.3.8. Si no está disponible o no se puede adaptar, se conserva el lector principal. Hay prueba de fallback con un decodificador no modificable. No se editó el vendor.
3. Se captura el fotograma actual del área realmente visible, incluyendo posiciones fuera de la guía central. El número de fotograma se verifica antes de analizarlo otra vez. No se considera nueva evidencia EAN una segunda lectura del mismo fotograma.
4. QR: pasada rápida hasta 960 px; 640 px como presupuesto inicial en equipos que informan hasta cuatro hilos. Si persiste la dificultad, se intercalan pasadas de detalle hasta 1920 px, siempre limitadas por los píxeles reales del video. El detalle se adelanta en el presupuesto reducido. Nunca se amplía artificialmente el canvas para inventar detalle.
5. EAN: resolución rápida hasta 1280 px, o 960 px en presupuesto reducido; pasadas de detalle hasta 1920. Se reutiliza la orientación útil. El intervalo se acorta mientras se reúnen las tres confirmaciones válidas y se alarga cuando el análisis es costoso. Quagga/JPEG queda limitado a una consulta cada 1200 ms, en vez de repetirlo continuamente. QR no ejecuta Quagga ni EAN ejecuta OCR de palets.
6. El tiempo de la última tarea regula el descanso siguiente (con límites); no se lanza una nueva mientras la anterior sigue pendiente. Página oculta o PC sin petición: se suspende el análisis pesado. OCR espera 1500 ms **desde que se abrió la cámara**, no desde el clic/espera de permiso, y conserva tres fotogramas diferentes/confianza/ambigüedad.
7. QR válido: confirmación en la primera decodificación válida. EAN: checksum y tres coincidencias de fotogramas distintos dentro de las ventanas anteriores, sin relajarlas. OCR: tres lecturas, confianza mínima y rechazo de ambigüedad intactos.
8. Sonido, vibración, preferencias, protección contra duplicados, flujo rápido y limpieza de cámara se conservaron. No se agregaron escrituras de inventario ni reintentos de negocio al escáner.

## C. Pulso, movimiento y zoom

No se estabiliza artificialmente la vista ni se suaviza el video con un buffer. El usuario sigue viendo el movimiento real. Se aprovecha el primer fotograma QR que el motor puede decodificar correctamente; ya no se pierde la lectura por exigir otras dos coincidencias mientras el brazo se mueve. EAN mantiene su confirmación conservadora, admitiendo su ventana temporal existente.

El slider, botones, pinch y mano hábil siguen compartidos. No hay reinicios al moverlos ni zoom automático después de detectar. Se conserva la cola que agrupa cambios de zoom y comprueba el nivel que devuelve la cámara. Se verificaron rangos 0.5–8 y paso grueso 0.5; abrir en 0.5 no manda una orden de subir a 1. Sin capacidades o nivel actual verificables no se muestra un zoom ficticio. El navegador decide si el zoom corresponde a recorte digital, óptica o cámara virtual: la app no lo presenta como zoom óptico ni fuerza cambios de lente.

No se solicitó una propiedad de estabilización inventada ni exposición manual. Se mantiene cámara trasera estándar y enfoque continuo solo si se declara disponible. Se pide frameRate ideal 24 y resolución ideal 1920x1080, sin requisitos exactos que excluyan cámaras básicas. No se enumeran/reabren lentes buscando una etiqueta heurística.

La exclusión y el muestreo reciente evitan backlog de tareas del lector. No pueden eliminar desenfoque de movimiento, latencia del sensor o exposición deficiente. Si hay varios QR válidos simultáneos, el adaptador actual no expone una lista de candidatos para elegir intención; sigue siendo necesario apuntar a uno. Falta comprobar ese corpus en depósito.

## D. Altura, perspectiva y etiquetas

El motor existente sigue realizando su localización y corrección de perspectiva. No se promete un nuevo algoritmo geométrico: se le entregan más píxeles útiles cuando hacen falta, la zona visible completa y menos trabajo redundante. No se simula seguimiento QR cuando el adaptador no entrega coordenadas. Se preservan las cajas reales proporcionadas por OCR/Quagga.

QR nuevos: ruta operativa /q/12345678, sin alias ni tabla de compartidos. Los enlaces /p/, /s/, /b/ siguen siendo otra función. La etiqueta mantiene el número original, A4 vertical, contraste negro/blanco, corrección M y cuatro módulos blancos. En la comparación de impresión pasó de **33 a 29 módulos por lado**; se verificó lectura de PNG/PDF y páginas 1/2/6. Esto no demuestra por sí solo lectura a seis metros.

Los QR impresos con palet.php?codigo= siguen aceptándose. /q/ resuelve al mismo controlador y reglas; sus recursos y fallback offline usan la base correcta. No exige reimprimir etiquetas antiguas. Al revertir, hay que conservar esa compatibilidad para etiquetas nuevas ya entregadas.

Para un QR difícil aparece Analizar detalle después de 1.8 s si hay más píxeles disponibles que en la pasada rápida. Es una solicitud opcional sobre el siguiente fotograma fresco del video, a su resolución útil real. No abre otra cámara, no sube/guarda una foto y funciona offline. No usa takePhoto para provocar cambios de lente o pausas de hardware. No promete una fotografía de sensor mayor que el video entregado.

## E. EAN

Se conservan EAN-8/EAN-13, rechazo de ceros, checksum, contexto de producto, tres fotogramas distintos, prevención de duplicados, contador y una cámara en Rápido. La evidencia del EAN para reportes sigue siendo temporal; no se eliminó su normalización ni el envío voluntario desde el teléfono. Las pruebas PC–móvil y fotos pasaron.

Durante la iteración, una configuración de EAN en alta resolución permanente empeoró la prueba de CPU limitada. Se corrigió con resolución rápida y descanso más corto durante consenso válido. Esos resultados intermedios no se presentan como la versión final ni se oculta el hallazgo.

## F. Mediciones antes/después

**Chrome 155.0.8059.41 en Windows, headless; video de canvas 960x1280 a 15 fps, con fotogramas móviles/proyectivos distintos. Sin cámara física, sin lente/zoom óptico, sin depósito.** Mismo QR operativo anterior en la comparación principal. Tres intentos por escenario, corte a 4 s. p95 empírico con n=3 coincide con el máximo y no es una estimación robusta poblacional. Se conservaron datos sin redondear en docs/measurements/scanner-*.json.

Tono = llamada real a start del oscilador WebAudio, **no el instante acústico en altavoz/Bluetooth**. Acción = entrada al callback, **no inventario confirmado ni pantalla del palet ya pintada**. El observer distingue ambas fases sin atribuir red al reconocimiento.

### Presupuesto normal

| Escenario | Tono antes / después (ms) | Acción antes / después (ms) | p95 acción antes / después (ms) | Correctas antes / después |
|---|---:|---:|---:|---:|
| QR frontal | 283 / 97 | 471 / 287 | 485 / 411 | 3/3 / 3/3 |
| QR trapezoidal moderado | 429 / 96 | 615 / 287 | 633 / 409 | 3/3 / 3/3 |
| QR trapezoidal severo + giro | Sin lectura / Sin lectura | Sin lectura / Sin lectura | Sin lectura / Sin lectura | 0/3 / 0/3 |
| QR pequeño (160 px) | 322 / 86 | 539 / 283 | 630 / 285 | 3/3 / 3/3 |
| QR con oscilación (16 px) | 342 / 82 | 542 / 279 | 626 / 311 | 3/3 / 3/3 |
| QR grande con movimiento (sin zoom real) | 336 / 85 | 549 / 285 | 573 / 304 | 3/3 / 3/3 |
| QR fuera del centro | 355 / 89 | 564 / 278 | 594 / 302 | 3/3 / 3/3 |
| EAN-8 con movimiento | 342 / 315 | 542 / 528 | 561 / 540 | 3/3 / 3/3 |
| EAN-13 con movimiento | 330 / 294 | 528 / 520 | 556 / 523 | 3/3 / 3/3 |

### CPU limitada 4x y dos hilos informados, sin zoom expuesto

Esto es una simulación de carga, no un teléfono económico real.

| Escenario | Tono antes / después (ms) | Acción antes / después (ms) | p95 acción antes / después (ms) | Correctas antes / después |
|---|---:|---:|---:|---:|
| QR frontal | 764 / 654 | 1004 / 856 | 1015 / 1008 | 3/3 / 3/3 |
| QR trapezoidal moderado | 1603 / 610 | 1822 / 811 | 1987 / 914 | 3/3 / 3/3 |
| QR trapezoidal severo + giro | Sin lectura / Sin lectura | Sin lectura / Sin lectura | Sin lectura / Sin lectura | 0/3 / 0/3 |
| QR pequeño (160 px) | 1060 / 695 | 1277 / 936 | 1336 / 956 | 3/3 / 3/3 |
| QR con oscilación (16 px) | 1069 / 608 | 1278 / 846 | 1289 / 864 | 3/3 / 3/3 |
| QR grande con movimiento (sin zoom real) | 1489 / 599 | 1734 / 855 | 2078 / 875 | 3/3 / 3/3 |
| QR fuera del centro | 1163 / 500 | 1354 / 717 | 1630 / 805 | 3/3 / 3/3 |
| EAN-8 con movimiento | 875 / 689 | 1097 / 970 | 1103 / 980 | 3/3 / 3/3 |
| EAN-13 con movimiento | 868 / 585 | 1134 / 824 | 1150 / 880 | 3/3 / 3/3 |

### Apertura, hilo principal y memoria

| Medición del entorno sintético | Antes | Después |
|---|---:|---:|
| Apertura del stream virtual, mediana | 2.2 ms | 2.1 ms |
| Primer fotograma observado, mediana | 51.5 ms | 51.3 ms |
| Tiempo de tareas principales / tiempo de ensayo, mediana | 48.5% | 40.0% |
| Mismo indicador bajo CPU 4x | 94.3% | 80.5% |
| Heap JavaScript principal, mediana | 3.89 MiB | 4.02 MiB |

La apertura usa getUserMedia sustituido por captureStream; **no mide permiso, sensor ni apertura real de cámara**. El porcentaje de tareas incluye dibujo de la secuencia, canvas y decodificación; no es CPU total del dispositivo. El heap no incluye todos los buffers nativos, WASM ni workers. No se midieron batería, temperatura ni RAM total. La pequeña diferencia de heap no prueba una reducción de memoria; la liberación se verifica funcionalmente con cierre/reapertura y workers detenidos.

### Fotogramas legibles breves

Con una ventana legible de 120 ms cada segundo, antes hubo 0/3 confirmaciones a 4 s; después 3/3. Mediana hasta tono programado: 197 ms; hasta callback: 413 ms; máximo/p95 empírico: 1422 ms. Es un estímulo sintético de disponibilidad breve, no desenfoque óptico real. Se guardan scanner-brief-before/after. Prueba la utilidad de no exigir otras dos lecturas QR mientras desaparece la imagen legible.

### Ampliación digital suplementaria

Se compararon seis lecturas por versión: QR pequeño con oscilación y el mismo raster ampliado digitalmente 3x, amplificando también el desplazamiento. Se reutilizan los mismos píxeles, no se añade detalle óptico. Datos en scanner-digital-before/after. El QR agrandado de la tabla principal es un tamaño distinto del objetivo, no una prueba de zoom físico.

Motor alternativo: jsQR 1.4.0 se comparó solo en el banco de pruebas, sustituyendo el decodificador QR del adaptador. Tampoco resolvió el caso severo y no aportó una mejora consistente de latencia; no se incorporó como dependencia a la app. El modo candidate descarga una versión fija y comprueba SHA-256; no se ejecuta en producción.

En los archivos entregados hay 156 intentos entre comparación principal, CPU limitada, alternativa y suplementos: 135 reconocimientos correctos y 21 sin reconocimiento a 4 s, **ningún identificador incorrecto observado**. No es una estimación suficiente de falsos positivos en un depósito ni una garantía para otros códigos.

## G–H. Compatibilidad y pruebas

Aprobadas: ean-scanner; scanner-scheduling (una tarea, fotograma distinto, standby, oculto, detalle real, sin zoom ficticio, callback fallido sin reenvío automático); scanner-identification con OCR/WASM real local; scanner-zoom; shared-camera-ui; main-rapid-scanner con 20 EAN; scanner-frame; pallet-label-layout; pallet-label-pdf; stock-utils; offline-intelligent con ruta /q/ y colas preservadas; share-links-ui con rutas antiguas/compartidas/operativas; device-connection-ui; device-report-photos. Lint de los archivos cambiados y revisión de diff.

Fallos de lectura conocidos: trapecio severo + giro, 0/3 en cada versión/motor ensayado; 0/3 también con el QR menos denso. Las suites no presentan esos fallos como éxitos: se guardan como lecturas agotadas, no como identificadores inventados.

No ejecutadas físicamente: iPhone/Safari, PWA instalada, Chrome/Android real, cámara económica, alturas de depósito, distancia calibrada, iluminación/reflejos reales, zoom óptico/digital de hardware, Bluetooth/música y evaluación de calor/batería prolongada. Emulación táctil y CPU limitada no sustituyen esos casos. No se afirmaron pruebas WebKit.

## I. Archivos

- js/ean-scanner.js: planificador, exclusión, consenso, feedback, capacidades reales, captura asistida y observación opcional.
- css/ean-scanner.css: control asistido compacto y contraste de linterna/entrada manual sobre etiquetas blancas; sin cambiar distribución del visor/zoom.
- js/pallet-label.js: QR operativo más corto; composición y corrección conservadas.
- js/stock-utils.js: parser común y compatibilidad de rutas nuevas/antiguas.
- .htaccess, palet.php, includes/page-metadata.php: resolver operativo y base/canonical, sin permisos nuevos.
- sw.js: reconoce /q/ y entrega el shell offline con base correcta; no cambia colas ni APIs.
- tests/scanner-motion-benchmark.cjs y tests/scanner-scheduling.cjs: secuencias reproducibles y seguridad del planificador.
- tests/ean-scanner.cjs, scanner-identification.cjs, scanner-zoom.cjs, pallet-label-layout.cjs, pallet-label-pdf.cjs, offline-intelligent.cjs y share-links-ui.cjs: regresiones actualizadas.
- docs/measurements/scanner-*.json: resultados crudos; este informe, PENDIENTES.md y versionado de la publicación.

## J. Instrumentación, reproducción y riesgos

Para capturar etapas en una prueba de campo, antes de abrir la cámara: window.RepoEANScanner.observe(event => console.table(event)); devuelve una función para cancelar la observación. Alternativamente create/rawCreate admite onMetric. No registra códigos, fotos ni usuarios; no guarda ni envía datos. Etapas: camera-request, first-frame, camera-open, camera-capabilities, decoded, confirmed, feedback-request, tone-scheduled, action, action-complete, analysis y camera-control. La localización interna anterior a decodificar no está expuesta separadamente por el adaptador; no se inventó esa medición.

Reproducción: extraer js/ean-scanner.js del commit 769d25d a tmp/scanner-before-1.11.js, manteniendo finales de línea con un script Node; ejecutar tests/scanner-motion-benchmark.cjs con origen y archivo JSON de salida. RT_SCANNER_SCENES selecciona los nombres de la tabla; RT_SCANNER_CPU=4 aplica el perfil lento. candidate como cuarto argumento habilita exclusivamente el experimento jsQR. La comparación principal usa los nueve escenarios de la tabla; los suplementos digital y etiqueta nueva se ejecutan por separado. Se requiere el Playwright/Chrome del entorno de pruebas existente.

Riesgos: resultados sintéticos, muestra pequeña, caso severo sin resolver, presión de memoria/canvas y ZXing en el hilo principal cuando no hay detector nativo. El planificador reduce trabajo/colas, pero no convierte la decodificación ZXing en un worker. No se afirmó que todo corra fuera del hilo principal. La comparación jsQR no justificó añadir otro motor permanente.

Reversión: revertir selectivamente el planificador/presupuesto de escaneo y publicar un build superior, conservando soporte /q/ en .htaccess, parser, controlador y SW para etiquetas nuevas ya impresas. No eliminar datos, colas ni números. Una reversión total sin mantener esas rutas rompería compatibilidad con impresiones nuevas.

## K. Resumen para uso diario

El QR que el lector logra decodificar debería confirmar antes, sin pedir inmovilidad para otras dos lecturas. Zoom y cámara siguen abiertos mientras se ajustan; las lecturas EAN conservan precisión y modo rápido. Un QR difícil puede usar más detalle sin subir fotos. No hay promesa de leer cualquier QR elevado: con perspectiva extrema o pocos píxeles el motor puede seguir sin reconocerlo; se conserva OCR/entrada manual y falta prueba física de depósito.

Referencias técnicas consultadas: [API Html5Qrcode/pause](https://scanapp.org/html5-qrcode-docs/docs/apis/classes/Html5Qrcode), [capacidades reales de MediaStreamTrack](https://developer.mozilla.org/en-US/docs/Web/API/MediaStreamTrack/getCapabilities), [restricciones de captura](https://developer.mozilla.org/en-US/docs/Web/API/Media_Capture_and_Streams_API/Constraints), [jsQR y su API](https://github.com/cozmo/jsqr). Son soporte de API; no constituyen evidencia de rendimiento de RepoTools.
