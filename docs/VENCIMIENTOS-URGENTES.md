# Vencimientos urgentes — v2.40

Naranja entre hoy y siete días inclusive; amarillo desde ocho días hasta los días de anticipación configurados; verde para el resto de las fechas futuras y rojo para vencidas. Sin fecha conserva su estado neutro. La urgencia visual es fija en siete días, independiente del plazo elegido para los avisos; no altera el momento de las notificaciones ni amplía los filtros.

El helper compartido aplica en palets, resultados, mercadería personal/de sucursal, lotes, supervisión y vistas compartidas. Cantidades de un lote/fecha, fecha y días restantes usan el mismo tono; los totales que mezclan fechas no adquieren el color de un solo lote. Campana reconoce urgencia sin cambiar identidad/lectura de las notificaciones. Los +N cuentan fechas distintas: urgentes, próximas, vigentes, sin fecha y vencidas al final; se descuenta siempre la fecha principal.

Configuración muestra una leyenda compacta de ejemplo, cuyo rango amarillo cambia con la preferencia y se oculta si el plazo es menor a ocho días. Se corrigió el límite de lectura de la preferencia que antes recortaba a treinta aunque el formulario admitía365. Se respeta0–365. Sin cambios de esquema ni nuevas dependencias; permisos y cantidades conservados.

Validación: pruebas PHP de mercadería/alcance/permisos/agrupación y notificaciones; fechas -1/0/1/7/8/30/31, deduplicación entre palets y plazos0/365. UI de mercadería en Chromium/WebKit320–1366 y ambas manos, colores fecha/días, etiquetas y navegación; configuración Chromium/WebKit con guardado y ejemplos, errores/reintento y preferencias. Capturas móviles revisadas. El fixture de error del interruptor activa click DOM: check() de Playwright compite con la reversión inmediata tras la respuesta503 simulada. Lint PHP/JS y diff sin errores. No se modificaron datos productivos ni se hicieron pruebas físicas.

Revertir los archivos de esta entrega y publicar versión/cache superior; no borrar preferencias, inventario, suscripciones ni colas. Los campos nuevos de agregados son aditivos y el servidor viejo sigue ignorándolos.
