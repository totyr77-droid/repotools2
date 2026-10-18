# Lectura de palets con giros y entrega más rápida

Entrega: 1.58. Se conserva Html5Qrcode/ZXing, Quagga y Tesseract locales, sin nuevas dependencias ni cambios de permisos, etiquetas o inventario.

## Cambios

- Se elimina la espera artificial de 180 ms posterior a confirmar. El sonido sigue solicitándose inmediatamente; se libera la cámara antes de entregar el código y se conserva la cola única de cámara. No se reducen las confirmaciones EAN.
- El OCR estima la inclinación de la línea de números a partir de una copia pequeña del fotograma. Endereza una copia con fondo blanco y conserva el área visible completa, sin recortar los extremos del número ni girar el video del usuario.
- Si esa estimación no basta, recorre orientaciones alternativas en intentos separados. Cuando obtiene un candidato, mantiene la orientación para reunir tres lecturas de fotogramas diferentes. El intervalo entre confirmaciones OCR baja de 550 a 180 ms; los intentos sin candidato usan 350 ms, y la pausa de página oculta sigue a 550 ms.
- Se mantienen confianza mínima 80, ocho dígitos exactos, ceros iniciales, rechazo de URLs ajenas y protección contra duplicados. Se rechazan dos números distintos de ocho dígitos incluso si uno tiene confianza inferior: no se elige automáticamente entre ellos.
- El marcador de OCR se transforma de vuelta a las coordenadas del video. Los canvas temporales se liberan al cerrar. El diagnóstico de análisis solo informa ángulo, duración y presencia de candidato, sin código ni imagen.
- Los QR siguen aprovechando la detección de orientación/perspectiva del motor existente. Se descartó un barrido adicional de giros QR porque no resolvió el caso severo y no justificó más procesamiento.

## Comparación OCR reproducible

Chrome de escritorio sin interfaz, video sintético 960×1280 a 15 fps y OCR WASM real. Tres repeticiones por escena. Tiempo desde imagen visible hasta entrega del código; incluye arranque del OCR y sus confirmaciones. Límite de 12 segundos por intento.

| Escena | Referencia 1.57 | Propuesta 1.58 | Mediana propuesta |
| --- | --- | --- | --- |
| Número de frente | 3/3, mediana 4,60 s | 3/3 | 3,66 s |
| Número a 25° | 0/3 dentro del límite | 3/3 | 4,25 s |
| Número vertical, 90° | 0/3 dentro del límite | 3/3 | 4,69 s |
| Número invertido, 180° | 0/3 dentro del límite | 3/3 | 4,41 s |
| Perspectiva moderada y giro 20° | Sin referencia emparejada | 3/3 | 6,79 s |
| Ceros iniciales a −25° | Sin referencia emparejada | 3/3 | 4,65 s |

No hubo códigos incorrectos entregados en estas escenas. Esto no demuestra una tasa general de precisión ni rendimiento físico: son pocos ensayos sintéticos, no un depósito, iPhone o Android real.

## QR y EAN: entrega del resultado

Banco centrado, tres repeticiones y límite de cuatro segundos para QR/EAN. Medianas desde imagen visible hasta callback de resultado:

| Escena | 1.57 | 1.58 | Lecturas correctas antes/después |
| --- | ---: | ---: | --- |
| QR frontal | 303 ms | 87 ms | 3/3 y 3/3 |
| QR a 45° | 936 ms | 697 ms | 3/3 y 3/3 |
| QR a 90° | 412 ms | 114 ms | 3/3 y 3/3 |
| QR a 180° | 277 ms | 95 ms | 3/3 y 3/3 |
| QR con inclinación moderada | 298 ms | 103 ms | 3/3 y 3/3 |
| EAN-8 en movimiento | 506 ms | 317 ms | 3/3 y 3/3 |
| EAN-13 en movimiento | 497 ms | 322 ms | 3/3 y 3/3 |

La perspectiva QR más severa sigue sin reconocerse: 0/3 en ambas versiones. No se presenta como resuelta. El ahorro garantizado por código es retirar una espera fija de 180 ms; las demás diferencias entre ejecuciones incluyen variación del banco. El modo continuo ya entregaba sin esa espera y conserva su comportamiento.

Las primeras pruebas de giros grandes tenían un error: rotaban alrededor de una esquina y sacaban el número del video. Se descartaron esos resultados. El banco corregido gira alrededor del centro; las filas anteriores usan exclusivamente esa referencia corregida.

## Alcance de las métricas

Sistema mide hasta confirmar y no incluye la espera posterior ni mostrar el resultado completo. La eliminación de 180 ms mejora la entrega del código, sin atribuir ese ahorro al decodificador. Para comparar uso real deben mantenerse separados QR, EAN y OCR, con dispositivo y condiciones comparables.

## Reproducción y reversión

El banco tests/scanner-motion-benchmark.cjs recibe ruta del lector y JSON de salida. RT_SCANNER_SCENES selecciona casos; RT_SCANNER_EXPECT_ALL=1 exige aciertos en todas las repeticiones seleccionadas. Para extraer la referencia sin alterar finales de línea, usar execFileSync de Node con git show 982d033:js/ean-scanner.js y guardar su Buffer en tmp/scanner-before-1.58.js. Servirla bajo la ruta js/ean-scanner.js del banco conserva la ubicación del OCR local.

La reversión consiste en restaurar js/ean-scanner.js de la entrega anterior y publicar una versión superior. Se conservan enlaces QR existentes, inventario, colas, bibliotecas y esquema. La cámara física, iluminación, desenfoque, varios códigos y ángulos severos requieren pruebas de campo.

Validaciones funcionales aprobadas: ean-scanner.cjs, scanner-scheduling.cjs, scanner-identification.cjs (WASM local y rechazo de ambigüedad), ean-scan-photo.cjs y scanner-performance-ui.cjs. La prueba de métricas ahora consulta la versión publicada en runtime, en lugar del filtro fijo 1.45 obsoleto.
