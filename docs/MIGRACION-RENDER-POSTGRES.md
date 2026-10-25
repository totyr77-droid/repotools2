# Migración de Neon a Render Postgres

## Estado al 25/10/2026

Migración solicitada y autorizada por el usuario. Acceso Render confirmado mediante CLI oficial y autorización del usuario desde el celular. Destino comprobado: PostgreSQL18.6, Ohio, plan256MB/USD6 y1GB de almacenamiento/USD0,30, sin ampliación automática ni HA. Estaba vacío antes de restaurar. Producción y desarrollo comparten origen; ambos se pausaron reversiblemente para el corte. Configuraciones originales y credenciales conservadas exclusivamente en carpeta privada fuera de Git/web.

Una consulta de diagnóstico `SELECT 1` en la rama production fue rechazada por Neon con HTTP402 y cuota excedida. La lista de snapshots manuales del proyecto está vacía. No se encontró dump en las rutas del repositorio revisadas; esto no demuestra ausencia de otros respaldos externos. Las ramas antiguas de prueba no sustituyen un respaldo actual.

Acceso de lectura restablecido y confirmado con consulta mínima tras la acción del usuario. Exportación completa inicial realizada con pg_dump18.6: archivo custom23,7MB con índice legible y SHA256, guardado en carpeta privada fuera de la web y Git. Restauración local aislada completada y servidor temporal detenido:79 tablas y297.978 filas. Copia posterior con servicios pausados restaurada en Render, en una sola transacción y sin errores:79 tablas y297.981 filas. Columnas, índices, restricciones y secuencias coinciden. Comparación de contenido usa zona UTC y orden determinista; se reconciliaron únicamente checked_at de dos suscripciones push con valores actuales del origen y precondición del valor anterior. SQL de ajuste y datos de comparación quedan privados. Exportación completa final actualizada y restauración local de ese archivo también aprobadas; servidor temporal detenido. Los hashes de contenido de todas las tablas coinciden entre proveedores y el origen permaneció estable durante el cotejo. No se cambió el plan desde herramientas ni se consultaron datos de tarjeta.

Corte completado: DATABASE_URL de producción y desarrollo apunta a Render por red interna con TLS requerido. Las demás variables se verificaron intactas. Producción y desarrollo figuran live; desarrollo necesitó redeploy del mismo build porque reanudar inicialmente conservó el entorno anterior. Sesiones anónimas nuevas de ambos sitios comprobadas directamente en Render y ausentes en Neon. Inicio y conectividad responden200; Stock, Biblioteca, Supervisión y Reportes rechazan solicitudes sin autenticar con401. Versión212/v3.07 conservada, sin modificación de código de aplicación. Tras la reapertura de desarrollo solo cambió app_sessions en Neon; no aparecieron operaciones de negocio posteriores al corte. No se usaron credenciales de usuarios ni se probaron operaciones autenticadas o recepción push en un teléfono. Confirmar esos recorridos con el usuario, sin afirmar auditoría integral.

Neon y respaldos se conservan; no se canceló ni modificó su facturación. La app ya acepta nuevas escrituras en Render: revertir a Neon requiere conciliar primero cambios posteriores, no basta con recuperar DATABASE_URL anterior.

## Antes de copiar

1. Confirmar acceso al origen con una consulta mínima, sin inicializar esquemas ni ejecutar el bootstrap de la aplicación.
2. Contar con clientes pg_dump/pg_restore de PostgreSQL18: la base origen utiliza18. Clientes oficiales18.6 descargados en tmp ignorado; no se instaló un servicio del sistema.
3. Confirmar región del Web Service de producción. Crear destino PostgreSQL18 en la misma región, con el plan económico acordado de256MB de RAM y1GB de almacenamiento; revisar rendimiento antes del corte.
4. Mantener DATABASE_URL de la app apuntando al origen. La aplicación no debe inicializar una base destino vacía antes de restaurar.
5. Guardar el dump fuera de Git y de la carpeta pública del servidor, con acceso restringido. Contiene datos privados y hashes de contraseña. No imprimir credenciales, sesiones, filas ni URLs privadas en documentación/logs.

## Copia

- Usar conexión directa al origen para pg_dump, TLS y credenciales proporcionadas por variables de entorno o archivo de contraseña restringido; nunca argumentos con contraseñas visibles.
- Inspeccionar los esquemas/extensiones de aplicación antes de elegir el alcance. Exportar estructura, datos, secuencias y objetos grandes correspondientes; no excluir fotos u otras tablas por su tamaño.
- Formato custom, sin propietarios ni privilegios del proveedor. pg_dump obtiene una instantánea consistente. Requerir salida0 y archivo no vacío; generar hash SHA256 y revisar el índice con pg_restore --list.
- Si el origen vuelve a recibir escrituras, acordar una ventana de mantenimiento y hacer la copia final antes del corte. Un respaldo tomado mientras hay cambios posteriores no garantiza ausencia de pérdida en el corte.
- Restaurar en destino vacío con pg_restore --no-owner --no-privileges --exit-on-error --single-transaction. No usar --clean contra una base con datos. Resolver extensiones/roles específicos del proveedor en una copia de prueba; no ignorar errores.

## Validación antes del corte

- Comparar tablas, columnas, constraints, índices, secuencias y conteos exactos por tabla entre origen y destino en la ventana sin escrituras. No publicar valores personales.
- Verificar catálogo/fotos, usuarios y hashes, palets/productos/vencimientos/cantidades, equipos/permisos, listas, auditoría, configuración, notificaciones y claves push.
- Guardar configuración y claves existentes de la app: el dump no reemplaza las variables de entorno, archivos externos ni datos offline pendientes en los dispositivos.
- Probar con sesión/control de acceso en la copia. Conservar el bloqueo de datos de otros usuarios y validación de permisos del servidor.

## Corte y recuperación

1. Con respaldo validado y consentimiento para la ventana de mantenimiento, cambiar DATABASE_URL del Web Service a la conexión interna del destino si está en la misma región. Conservar el valor anterior en el gestor de secretos.
2. Desplegar y comprobar Inicio/API, login, Stock, Biblioteca, Mi equipo, Moderación y cola de notificaciones. La respuesta de app-version.php solo verifica el servidor, no la base ni las sesiones.
3. Habilitar escrituras después de la verificación. Conservar Neon y el dump hasta confirmar funcionamiento.
4. Si hay fallo antes de aceptar escrituras nuevas, restaurar la conexión anterior solo si Neon está disponible. Si el destino ya recibió cambios, primero conciliarlos: volver al origen sin ellos perdería operaciones.

No declarar migración completada ni datos recuperados hasta tener dump válido, restauración y verificación. No borrar Neon como parte del corte.
