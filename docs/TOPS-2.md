# TOPs 2.0 — desarrollo

Categorías auditadas: Códigos cuenta EAN distintos en scan_events dentro del período; Escaneos cuenta todos los eventos, incluidos repetidos; Reportes cuenta report_rewards, fila única creada al aprobar una corrección. Fecha del último ranking es fecha de aprobación/recompensa, no envío. Aportes se pospone: hoy duplicaría report_rewards, sin otra métrica independiente verificable. No se importan datos ni resetean puntos. Semana por lunes00:00 y mes por primer día00:00 en America/Buenos_Aires, límites convertidos UTC para timestamps SQLite/PostgreSQL.

Helper includes/tops.php, SSR tops.php y fragmento privado por sesión/no-store; salida pública solo posición, public_name, valor e indicador Vos. Helper central de nombre público con fallback Usuario, sin nombre privado, correo ni identificador. Ranking RANK por puntaje y orden estable interno por ID,50 visibles; segunda consulta agregada como máximo para posición propia fuera de50. No N+1. Códigos significa distintos escaneados en ese período, no primer EAN de toda la vida; explicación visible.

Controles: selector Semana/Mes/Histórico44px y categoría select, resultados con loader local y fetch cancelable. SSR y submit noscript como respaldo. Medallas locales discretas en primeros puestos y lista compacta. BETA permanece exclusivo desarrollo.

Migración26 agrega scans_period_user_code(created_at,user_id,code) y rewards_period_user(created_at,user_id). Índices existentes scans_user_code(user_id,code) y report_rewards_user(user_id) no permiten búsqueda selectiva por fecha; EXPLAIN con historial mayor y ANALYZE confirma nuevo índice en filtro. Histórico puede usar índice existente según optimizador. Inicialización idempotente probada SQLite; confirmar PostgreSQL de beta al desplegar.

Pruebas tests/tops.php: límites lunes03UTC, mes/histórico, EAN repetido, aprobación frente a pendiente, fallback/privacidad, empates, más50 y posición propia, índice con2000 filas antiguas. tests/tops-ui.cjs: filtros sin recargar documento, vacío,360/390/430/1366 y errores JS. No acceso a datos reales ni conexión con producción. Safari y despliegue beta reales pendientes.

Archivos de aplicación: includes/tops.php, includes/database.php, tops.php, vistas/tops-ranking.vista.php, js/tops.js, css/tops.css y versiones/inclusión header/footer. Revertir commit de este punto para quitar UI/helper; índices26 son aditivos y pueden quedarse sin dañar datos. No bajar versiones de esquema ni borrar estadísticas. No llevar distintivo BETA a main.
