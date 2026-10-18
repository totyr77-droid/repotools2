# Caso pendiente: etiqueta inclinada (2026-10-18)

El usuario reporta que una etiqueta inclinada no se reconoce ni automáticamente ni con Analizar detalle en iPhone. La captura muestra zoom 2,2×, perspectiva, pliegues y guías de interfaz superpuestas. No permite aislar el fotograma original de cámara ni afirmar cuál es la causa en el dispositivo.

Validación local: reproducción de la región de cámara de la captura como video, con el motor actual y análisis manual; tres intentos sin lectura confirmada. Comparación local con decodificador QR alternativo ya disponible y rectificación manual de perspectiva tampoco recuperó lectura. La captura contiene superposiciones que no existen en los píxeles originales de cámara, por lo que estos resultados no sustituyen una prueba de sensor.

Se probaron estimación del renglón de ocho dígitos y umbrales alternativos para papel con sombra. No mostraron recuperación en esta captura. Ambos experimentos se descartaron y el lector quedó restaurado exactamente al estado anterior. No se publicó versión ni se relajaron validación, confianza o confirmaciones de OCR.

Siguiente paso: obtener foto directa de la etiqueta (sin interfaz) para comprobar integridad y geometría del QR, contrastarla con el SVG original cuando esté disponible y después volver a probar en iPhone con el mismo encuadre y zoom. Para operar mientras tanto, conservar entrada manual del número y probar etiqueta plana/de frente, con menor zoom y acercamiento físico cuando permita enfocar.

No incluir imágenes ni identificadores reales de palets en Git. Los insumos y resultados diagnósticos privados permanecen solo en tmp, fuera del conjunto a publicar.

## Mejora implementada: rescate QR local (v1.70)

Se probó ZXing-C++ mediante el paquete reader-only zxing-wasm 3.1.5. La integración final usa un Web Worker local, conserva el lector rápido actual y activa el rescate ante una lectura no resuelta. Compara binarización local/global y, si falla, amplía el muestreo 1,5× sin suavizado, que fue el ajuste que permitió recuperar una captura real. Solo acepta resultados con integridad válida y vuelve a validar dominio/ruta en el hilo principal. Múltiples candidatos válidos se rechazan.

Resultados medidos localmente, tres repeticiones por caso:

| Caso | Antes | Con rescate final |
|---|---|---|
| Primera captura del usuario | 0/3 | 3/3, mediana 0,84 s |
| Segunda captura del usuario | 0/3 | 0/3; pendiente |
| QR sintético con perspectiva 0,75 | 0/3 dentro de 4 s | 3/3, mediana 1,39 s |
| Mismo QR con CPU 4× más lenta y 2 núcleos simulados | — | 3/3, mediana 1,71 s |

Las capturas contienen superposiciones de interfaz y no son fotogramas originales del sensor. Leer una captura en Chrome de escritorio no prueba el mismo tiempo ni precisión en Safari/iPhone. El caso de la segunda captura sigue sin resolverse y no se afirma equivalencia con Cámara de iOS ni precisión general.

Regresiones: 27 lecturas sintéticas positivas de QR/EAN/giros/poca luz aprobadas; 12 rechazos de QR ajenos y números OCR ambiguos/no válidos aprobados. Worker real verifica múltiples candidatos, límites de fotograma y ausencia de red externa. Se mantuvieron consenso/confianza de OCR, encuadre visible, cámara única, serialización, pausa y limpieza. Verificación de fallback forzado y pruebas de identificación/programación descritas en PENDIENTES.md.

No se incorporan capturas, códigos reales ni sus resultados privados en Git. Evidencias privadas se conservan solo en tmp.

Reversión: retirar helper y llamadas de rescate de js/ean-scanner.js, worker y directorio vendor versionado; retirar sus tres entradas de ASSETS en sw.js y publicar una versión superior. Conservar OCR, inventario, colas y enlaces. La nueva descarga WASM es de aproximadamente 967 kB sin comprimir; permanece cacheada localmente y se prepara también para uso offline.

## Lectura del número inferior (v1.72)

La nueva captura reportada con v1.71 tampoco entregaba resultado en tres repeticiones locales de 12 segundos. Se añadió localización de ocho componentes alineados con un campo de tinta similar al QR encima, recorte del renglón y corrección de su inclinación. El OCR usa modo de una línea solo cuando localiza este diseño; conserva análisis completo y barrido de giros si no lo localiza. Empieza a intentar antes, sin esperar el descifrado del QR. No se infiere el número por el diseño: siempre se reconoce el texto y se exige confianza mínima 80 y el mismo resultado en tres fotogramas distintos.

Reproducción final de la nueva captura: 3/3 lecturas correctas por OCR, 2,10 / 2,62 / 2,53 s; mediana 2,53 s. Antes: 0/3. Son tiempos de reproducción local en Chrome, no mediciones de cámara física ni garantía de reconocimiento instantáneo. La captura privada y los identificadores permanecen únicamente en tmp. No se ha vuelto a probar la segunda captura anterior y sigue pendiente de validación.

Se impide recortar un prefijo de ocho cifras dentro de una fila más larga; varias filas candidatas de tamaño comparable devuelven al análisis completo. Las regiones con excesivos componentes conservan el fallback. Se conserva transformación de coordenadas para el seguimiento visual. Pruebas: pallet-number-region (diseño real de QR, EAN largo, dos filas y número sin QR), scanner-identification, scanner-scheduling, ean-scanner y service-worker-offline aprobadas. Benchmark seleccionado: 21/21 lecturas de OCR/giros/ceros iniciales, QR y EAN; 6/6 rechazos de números ambiguos o longitud inválida. Sin cambios en inventario, permisos, API ni red externa.

Reversión: restaurar js/ean-scanner.js desde v1.71, retirando localizador, recorte, modo OCR de una línea y adelanto de inicio; publicar una versión superior. Conservar worker QR/WASM de v1.70 y datos del usuario. Pendiente validar sensor y latencia en iPhone bajo las condiciones reales de trabajo.

## Giro con QR parcialmente tapado (v1.75)

La captura nueva con v1.74 mostraba el QR tapado y un renglón numérico girado unos20°. Antes de la corrección:0/3 en reproducción local,12s por intento. Se localizaron dos problemas: agrupación casi horizontal de componentes y rechazo de cajas que se superponen en X al inclinarse, aunque los trazos no estén unidos. La localización ahora prueba varias pendientes con agrupaciones deduplicadas y tolerancia de superposición acotada al giro. Después del deskew recorta otra vez la banda del número, para excluir tinta del QR/pie y no pedir al OCR de una línea que interprete todo el rectángulo diagonal.

El agrupamiento por sí solo obtenía el número correcto pero confianza77, inferior al mínimo80: no se redujo el umbral para aceptarlo. El recorte fino y la optimización final permitieron3/3 confirmaciones por OCR en1,88/2,35/2,38s (mediana2,35s). Tres lecturas del mismo número en fotogramas distintos, mínimos de confianza y tiempos de consenso de v1.74 intactos. Captura anterior con número inferior:3/3 después del cambio (mediana2,50s). Se valida seguimiento con offset de la banda y liberación de su canvas al cerrar.

Pruebas geométricas incluyen giros de20° hacia ambos lados, EAN largo que no debe recortarse, dos filas ambiguas y fallback de número sin QR. Validación en Chrome con capturas que incluyen interfaz; no garantiza todos los ángulos ni sustituye Safari físico. Imágenes/códigos/resultados privados quedan solo en tmp. Reversión: restaurar localizador y recorte de palletOCR desdev1.74, conservando consenso, workerQR y datos, y publicar versión superior.

Optimización para CPU lenta: la primera prueba4× no completaba consenso. Se redujo el recorte objetivo a600px de ancho y se reutiliza solo su geometría por menos de650ms tras una lectura válida, conservando dimensiones del encuadre. Cada intento captura y reconoce un fotograma nuevo; el texto anterior no se reutiliza. Una lectura fallida/reset descarta esa geometría. Con versión final CPU4×/2núcleos:3/3,2,90/3,25/4,78s; es simulación, no latencia de iPhone. Suites finales ocr-consensus/pallet-number-region/ean-scanner/scanner-identification/scanner-scheduling/service-worker-offline aprobadas. Benchmark del cambio de localizador:15lecturas correctas en giros25°/vertical/inversión/QR/EAN y6rechazos de dos números/EANlargo.
