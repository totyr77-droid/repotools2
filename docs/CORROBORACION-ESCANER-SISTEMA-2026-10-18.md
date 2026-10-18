# Corroboración y mejoras de escáner, Sistema y móvil

Entrega: 1.59, 18/10/2026. Referencia de código: 0c4cf2f (1.58). Se corroboró mediante el release.json público que la 1.58 estaba desplegada al iniciar esta revisión.

## QR con perspectiva

Se reprodujo el caso severo del banco de video sintético: tres fallos de tres intentos, tanto con el enlace largo de etiqueta como con el QR nuevo de ruta corta. Se agregó una pasada de rescate, posterior a la lectura normal:

- Busca en una imagen pequeña una etiqueta clara distinguible del fondo, con límites de tamaño, proporción y superficie. Estima sus cuatro esquinas y rectifica una copia mediante una homografía.
- El brillo de referencia se adapta a la iluminación; no se exige blanco puro. Si no hay un candidato adecuado, continúa el lector habitual.
- El rescate comienza después de 900 ms y se limita a una pasada cada 1100 ms. Usa buffers reutilizados y detalle acotado a 960 px; conserva el presupuesto de la lectura rápida. No gira la imagen que ve el usuario ni cambia zoom o lente.
- Se entrega la copia al mismo decodificador QR. Continúan la validación de contenido/origen, la exclusión de decodificaciones simultáneas, el cierre de cámara y los permisos normales del servidor.

Comparación consecutiva, tres repeticiones, mismo banco centrado 960×1280 a 15 fps:

| Escena | 1.58 | 1.59 |
| --- | --- | --- |
| Perspectiva QR severa, enlace largo | 0/3 dentro de 4 s | 3/3; mediana 0,95 s |
| Perspectiva QR severa, etiqueta nueva | 0/3 dentro de 4 s | 3/3; mediana 0,95 s |
| QR frontal | 3/3; mediana 0,089 s | 3/3; mediana 0,082 s |
| QR a 45° | 3/3; mediana 0,634 s | 3/3; mediana 0,641 s |
| EAN-13 en movimiento | 3/3; mediana 0,313 s | 3/3; mediana 0,306 s |

No se atribuyen las diferencias pequeñas de esos casos normales a una aceleración del motor. El resultado relevante es la recuperación del caso severo sin perder los casos normales del banco. CPU reducida (Chrome a 4× y dos núcleos declarados): ambos QR severos también pasaron 3/3, medianas aproximadas 1,35 y 1,31 s. Es emulación, no un teléfono físico.

## OCR

Se conservaron resolución completa, imagen visible completa, confianza mínima 80 y tres fotogramas distintos. Se descartaron experimentos con recortes o menor resolución que no ofrecieron una ganancia consistente al mantener la verificación completa.

La entrega final reduce la espera entre candidatos de 180 a 80 ms. El OCR comienza después de 1000 ms en equipos fuera del presupuesto reducido; en equipos de hasta cuatro núcleos declarados conserva 1500 ms. No vuelve a contar el mismo fotograma. El rescate QR no hace trabajo adicional de rectificación mientras el OCR tiene un candidato.

La estimación de inclinación ajusta el umbral de tinta al brillo. Para fotogramas oscuros con información útil se aplica una ganancia de brillo limitada a 2,8× sobre la copia de análisis. No se amplifica una imagen prácticamente negra ni se recortan otros números visibles.

Comparación consecutiva final, tres repeticiones por caso:

| Número de palet | Mediana 1.58 | Mediana 1.59 | Resultado |
| --- | ---: | ---: | --- |
| Frontal | 3,86 s | 3,00 s | 3/3 en ambas |
| A 25° | 4,29 s | 3,55 s | 3/3 en ambas |
| Vertical | 4,49 s | 3,93 s | 3/3 en ambas |
| Invertido | 4,57 s | 3,69 s | 3/3 en ambas |
| Con perspectiva moderada | 6,66 s | 5,27 s | 3/3 en ambas |
| Ceros iniciales a −25° | 4,43 s | 3,82 s | 3/3 en ambas |

El número inclinado con iluminación simulada al 42 % pasó 3/3, mediana 3,55 s. También pasaron el QR severo con esa iluminación y el QR con movimiento/desenfoque simulado. Estos casos no reproducen ruido del sensor, autoexposición ni reflejos de una cámara real.

## Sistema

- Agrega mediana y P90 de lecturas confirmadas por lector y versión, conservando el promedio y los cierres sin lectura/errores. Se calculan sobre todos los registros filtrados, no sobre la página visible.
- Permite filtrar por dispositivo y modo normal/rápido, dentro de Más filtros.
- Distingue apertura, lectura y confirmación. Agrega la última duración desde confirmar hasta completar la acción en este dispositivo: incluye el callback y su trabajo de consulta/carga, no garantiza el instante exacto del primer pintado.
- Este último dato es local a la cuenta/dispositivo, sin códigos ni imágenes, con vencimiento visible de siete días; no se presenta como agregado histórico del servidor. La telemetría enviada y su esquema permanecen intactos.
- Clasifica como Stock los lectores de los buscadores de informes. Solo managers pueden acceder a la sección y al endpoint de consulta, con el rol actual validado en servidor.

Las fórmulas de mediana y P90 se probaron en SQLite y mediante un SELECT de valores sintéticos en PostgreSQL, sin escrituras ni migraciones de producción. Los datos de uso no permiten una conclusión causal entre versiones sin condiciones comparables.

## Móvil, seguridad y validación

Se verificaron buscador/filtro en 320, 390 y 1366 px, ambas manos y una altura reducida de 420 px con el campo enfocado. Los controles permanecen contenidos y el popover entra en el viewport. Esto simula menos espacio; no certifica el teclado real de Safari.

Pruebas: ean-scanner, scanner-scheduling, scanner-identification con WASM local, ean-scan-photo, scanner-performance-ui, scanner-performance-offline, public-stock-summary y service-worker-offline. El corpus negativo exige análisis efectivos del motor y rechaza QR de otro origen (también oscuro), dos números distintos y un EAN-13 que no es número de palet. No se acepta como prueba de rechazo una cámara sin actividad.

Pendiente de campo: iPhone/Android reales, reflejos fuertes, etiquetas dañadas, oscuridad extrema, varios QR válidos en la misma vista y condiciones de altura/distancia. No se afirma una tasa general de precisión ni que todos los ángulos estén resueltos.

## Reproducción y reversión

Usar tests/scanner-motion-benchmark.cjs con la referencia 1.58 y el lector actual. RT_SCANNER_SCENES selecciona casos, RT_SCANNER_EXPECT_ALL=1 exige los resultados esperados y RT_SCANNER_CPU=4 habilita emulación reducida. JSON de evidencia local: tmp/scanner-159-qr-paired-before.json, qr-paired-after.json, ocr-full-before.json, ocr-full-after.json, dim-ocr-final.json, low-cpu-final.json y negative-final.json (todos con prefijo scanner-159-).

Para revertir, restaurar selectivamente js/ean-scanner.js, métricas API/cliente, vista/CSS/JS de Sistema y publicar una versión superior. No hay cambios de esquema, inventario, enlaces ni colas de movimientos. El dato local de última acción puede retirarse sin afectar registros del servidor.
