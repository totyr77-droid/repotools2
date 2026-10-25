# Guías de cámara — v2.41

La franja de ocho dígitos estaba superpuesta a la parte inferior del marco QR: tapaba la esquina y los cuadrados de referencia. Se separan en dos filas, con ocho píxeles de separación y espacio reservado para el mensaje superior y los controles inferiores. El marco se conserva cuadrado y centrado horizontalmente. La franja numérica tiene altura y tipografía acotadas; en un viewport de cámara de menos de280px de alto (o menos de220px de ancho) se oculta solo esa referencia secundaria para priorizar el QR. La lectura OCR sigue disponible dentro del área señalada.

El recorte compartido QR/OCR incluye el marco más la franja visible; no se amplía a toda la cámara ni cambia las validaciones de códigos vecinos, conflictos, recenter y frames viejos. ResizeObserver vuelve a ubicar las guías al girar o redimensionar. Se conservan zoom, manos, cámara única, selector de formatos y liberación de recursos.

Validación: shared-camera-ui Chromium/WebKit,320/390/1366 y320x568/390x844/844x390/1366x768, marco cuadrado, SVG sin compresión, separación, márgenes y limpieza; prueba real local de worker multi-QR y OCR con etiquetas/números vecinos, rechazo fuera del marco y conflictos, recenter y pipeline html5-qrcode; scanner-frame normal/rápido y textos largos, scanner-hand-layout con44px y ambas manos. El fixture de mano ahora usa meta viewport y excluye botones ocultos por su contenedor (antes contaba atajos ya retirados de la UI). Captura móvil revisada. Sin prueba física del celular.

Reversión: revertir la entrega y publicar versión/cache superior. No toca DB, inventario, permisos, preferencias ni colas.
