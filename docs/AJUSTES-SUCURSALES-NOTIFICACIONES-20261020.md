# Ajustes de sucursales, notificaciones y colecciones — v2.08

## Comportamiento

- La sucursal de pruebas ID 12 está reservada a `users.role=manager`. Lista explícita en `includes/branch-visibility.php`; el nombre puede cambiar sin perder la restricción. Sin migración ni modificación del registro real. No afecta las otras sucursales.
- Servidor filtra directorio, búsqueda y cercanía antes de devolver resultados; rechaza selección/edición/favorito por ID para otras cuentas. Rango de encargado o administrador de empresa no sustituye al manager de plataforma. Invitaciones/tokens, selección de equipo y solicitudes respetan el mismo límite. Enlaces públicos y detalle de palet no exponen esa ubicación; exportaciones conservan mercadería autorizada sin mostrar la sucursal privada; ranking no incorpora la sucursal oculta.
- Avisos de solicitudes e invitaciones llevan etiqueta de sucursal con el color y SVG habituales; contexto resuelto por registros, sin analizar texto ni insertar HTML del usuario. Dos consultas por lote como máximo para esos contextos. Fecha/hora discreta junto al título y punto de no leída. Anunciar logros sigue sin marcarlos como leídos.
- Vencimientos conservan la lógica existente: un aviso por producto/fecha dentro de cada palet. No se implementó agrupamiento por fecha en esta entrega.
- Enlace de colección sin sesión lleva a ingreso, conserva destino al alternar registro/login, fallar contraseña o reemplazar sesión del dispositivo y vuelve con el token a la colección exacta. Recargar conserva vista previa; cerrar no importa nada. Biblioteca normal sin enlace conserva su demo.
- Escanear un palet propio ya usado y sin sucursal puede sugerir `¿Estás en …?` a 100 m con precisión suficiente. Confirmación inline, acción principal según mano, Ahora no no modifica ni repite durante la página. Consultar desde lista no pregunta. Servidor vuelve a validar dueño, revisión, sucursal visible y ausencia de ubicación; no publica el palet en enlaces anteriores. No es una validación de seguridad de equipo.

## Validación

Pruebas aisladas SQLite: branch-visibility (roles, directorio, ID directo, invitación/token sin consumir, pertenencia, contexto/conteo de avisos, enlaces y metadatos públicos), branch-team, branch-manager, manager-team-access, team-pallet-requests, stock, stock-export, tops, library-sharing. Stock API completa incluye notificaciones, permisos, CSRF e idempotencia. UI de colecciones compartidas y nuevo recorrido sin sesión en Chromium y WebKit; login erróneo/registro/cambio de dispositivo/enlace corto y largo/recarga/cancelación sin copia. UI de notificaciones con etiqueta y fecha junto a título a 320/390/1366; UI de cercanía con confirmación/cancelación y ambas manos, conserva 600 unidades y fecha. Service worker y sintaxis/diff verificados. GPS simulado; falta validación en teléfono físico. Sin correos enviados ni inventario real alterado durante pruebas.

## Reversión

Restaurar solo los cambios necesarios de esta entrega y publicar una versión superior. Mantener `branch-visibility.php` y sus controles si TEST debe seguir privada: quitar esos controles vuelve a exponerla. Para devolverla al directorio público de forma deliberada, retirar su ID de la lista y publicar; no borrar la sucursal ni inventario. La sugerencia de cercanía puede retirarse sin deshacer vínculos ya confirmados. No borrar colecciones/importaciones/notificaciones. El arreglo de retorno de Biblioteca no requiere modificar enlaces ya emitidos.
