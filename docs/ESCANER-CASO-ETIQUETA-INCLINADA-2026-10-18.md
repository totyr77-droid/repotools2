# Caso pendiente: etiqueta inclinada (2026-10-18)

El usuario reporta que una etiqueta inclinada no se reconoce ni automáticamente ni con Analizar detalle en iPhone. La captura muestra zoom 2,2×, perspectiva, pliegues y guías de interfaz superpuestas. No permite aislar el fotograma original de cámara ni afirmar cuál es la causa en el dispositivo.

Validación local: reproducción de la región de cámara de la captura como video, con el motor actual y análisis manual; tres intentos sin lectura confirmada. Comparación local con decodificador QR alternativo ya disponible y rectificación manual de perspectiva tampoco recuperó lectura. La captura contiene superposiciones que no existen en los píxeles originales de cámara, por lo que estos resultados no sustituyen una prueba de sensor.

Se probaron estimación del renglón de ocho dígitos y umbrales alternativos para papel con sombra. No mostraron recuperación en esta captura. Ambos experimentos se descartaron y el lector quedó restaurado exactamente al estado anterior. No se publicó versión ni se relajaron validación, confianza o confirmaciones de OCR.

Siguiente paso: obtener foto directa de la etiqueta (sin interfaz) para comprobar integridad y geometría del QR, contrastarla con el SVG original cuando esté disponible y después volver a probar en iPhone con el mismo encuadre y zoom. Para operar mientras tanto, conservar entrada manual del número y probar etiqueta plana/de frente, con menor zoom y acercamiento físico cuando permita enfocar.

No incluir imágenes ni identificadores reales de palets en Git. Los insumos y resultados diagnósticos privados permanecen solo en tmp, fuera del conjunto a publicar.
