# Corrección medida de consultas de migraciones

En cada conexión `initialize_database` realizaba27 SELECT consecutivos, uno por versión. En un esquema temporal del Neon de desarrollo, desde PHP local se midieron6064/6021/6044ms para comprobaciones sin migraciones pendientes.

Ahora lee todas las versiones en una consulta dentro de la misma transacción y bloqueo advisory existentes. Cada migración sigue independiente, en orden y con su registro de versión. No se añaden índices ni tablas, no se presume la última versión por MAX y no se guarda una caché que pueda quedar obsoleta entre despliegues.

Después:959/966/961ms en el mismo entorno remoto (aprox84% menos). Conexión antes1847ms/después1195ms y resumen~580–780ms; estas diferencias de conexión/red no se atribuyen a la corrección. Son mediciones desde Windows a Neon, no desde Render. La latencia de producción debe medirse tras desplegar la beta.

Validación: migraciones nuevas/repetidas SQLite y PostgreSQL, defaults, transacciones/rollback, activity/FK, ranking, resumen y CSV. tests/tops-activity-postgres.php usa exclusivamente un esquema temporal de desarrollo y lo elimina al terminar; nunca datos reales. tests/performance-database.php mantiene baseline local.

Archivos: includes/database.php y tests/tops-activity-postgres.php. Revertir este commit restaura las27 comprobaciones; no se revierten migraciones ni se borran datos.
