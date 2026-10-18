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
