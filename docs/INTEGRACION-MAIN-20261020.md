# Integración de beta en main — 2026-10-20

Producción 1.86 / contador interno91 integra desarrollo hasta28c9e0e, conservando los siete commits exclusivos de main mediante merge. Incluye empresas/sucursales/equipos, invitaciones y acceso temporal, controles por rango/ubicación, inventario y movimientos, etiquetas de propietario autorizadas, detalle de palet local, perfiles/QR/referidos, insignias y mejoras de cámara/cargas/caché ya validadas en beta.

Resoluciones de producción: favicon RT oficial (sin B ni botónBETA); excepción de varias sesiones móviles de managers desactivada; transferencia de palet/mercadería conserva exclusividad del propietario de origen tanto en servidor como en UI (política main1.85). Se adaptaron pruebas de equipo a esa política sin relajar permisos. Conservados RT/barras de carga/logos/filtros publicados en main. Release parte de main1.85/internal90 y sube a1.86/internal91, sin retroceder al contador de beta.

Base existente verificada por lectura: esquema40,40migraciones aplicadas; equipos e insignias disponibles. No se ejecutaron migraciones, se copiaron cuentas ni se modificaron inventario, actividad o logros reales. Dos ejemplos de notificaciones se generaron como capturas de los componentes/SVG reales, fuera de Git; no son premios ni avisos insertados en cuentas.

Validación: PHP de transferencias/seguridad/equipos/administración/logros/etiquetas/watch/sucursales/notificaciones/referencias/perfiles/referidos/core y límites de dispositivos; fastpath de esquema; EAN y service-worker. UI transferencias, Mi equipo/inventario, perfiles/logros, avisos, caché manager y escaneo rápido. PHP lint de51archivos modificados. Se corrigió prueba antigua de bienvenida que contaba el logo anterior como ilustración email; mantiene cuatro ilustraciones y logo oficial. PostgreSQL producción: solo lectura de esquema. Cámara/GPS/Safari físico siguen requiriendo campo; no se atribuye una puntuación objetiva.

Reversión: revertir el merge de esta entrega con main como primer padre (base05dfebb), mantener contadorrelease superior a91 y publicar caché nueva. No eliminar tablas ni datos de equipos/logros. Preferir hotfix si hay operaciones confirmadas nuevas; una reversión de código no revierte movimientos de mercadería ni autorizaciones ya registradas.

## Main 1.87 — orden habitual de Stock

El listado de la cuenta se ordena por última modificación descendente, con id descendente para empates. Se elimina la prioridad automática del vencimiento: un palet recién creado/cargado o con retiro reciente aparece primero incluso si otro vence antes. Consultar no escribe actividad ni cambia la posición. La búsqueda y vista filtrada de vencimientos conservan su orden específico; sucursal/etiqueta filtran sin alterar el orden restante. No se modificó Mi equipo.

Validación: tests/stock-recent-order.php, tests/stock.php, tests/stock-api.cjs, tests/stock-tags-ui.cjs y SW. Fixtures aisladas, sin datos reales ni migración. Reversión: restaurar solo ORDER BY de stock_list anterior a esta entrega y publicar versión superior para caché.
