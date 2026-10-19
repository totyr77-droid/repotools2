# Equipos de sucursal — primera entrega beta

Solo rama desarrollo y servicio repotools-desarrollo.onrender.com. Main y su base no se modifican. La beta incorpora el código actual de main hasta v1.78 como base, conservando distintivo BETA y contador independiente.

## Uso

Con la cuenta habitual, Inicio → Mi equipo (también Ajustes → Accesos). Administrar una sucursal permite crear el primer equipo y su sucursal o seleccionar una entrada creada por esa cuenta. Desde una sucursal administrada se añade otra al mismo espacio. No requiere otro correo, cuenta o rango global.

Roles propios del vínculo, independientes de users.role:

- Administrador: propietario del espacio, administra todas sus sucursales, invita encargados/operadores, trabaja mercadería y consulta inventario y movimientos. Se mantiene un administrador por espacio en esta primera entrega.
- Encargado: gestiona las sucursales donde aceptó un vínculo; invita/revoca operadores, consulta inventario y movimientos, trabaja mercadería. No asciende personas a encargado ni modifica al administrador.
- Operador: trabaja la mercadería de palets en sus sucursales autorizadas. No recibe listado general, miembros ni registros del equipo. Propiedad y accesos personales existentes se conservan.

Invitación por enlace/QR, aceptación explícita con sesión existente o registro habitual y retorno a la invitación. Token aleatorio de un uso y tres días, solo hash en DB. Invitación de un encargado requiere que conserve su permiso al aceptarse. Revocar un vínculo elimina su sucursal activa e invalida las invitaciones pendientes de esa sucursal, para impedir reingreso mediante un enlace antiguo. Desvincularse conserva propiedad y movimientos. Se necesita una invitación nueva para volver. Un acceso personal independiente continúa siendo válido.

Inicio muestra selector de sucursal activa (incluye Uso personal) y Escanear palet al estar vinculado. La sucursal activa se comprueba en servidor junto con la sucursal esperada del lector, evitando asignar al contexto de otro dispositivo por una respuesta tardía. QR sin palet preparado no se reclama: el uso de etiquetas pendientes/offline quedó pospuesto por el usuario.

Al escanear desde Inicio un palet preparado sin sucursal, se asigna inmediatamente a la activa, mantiene dueño y registra asignación en historial del palet y del equipo. Repetir lectura no repite asignación/evento. No se publica por enlaces de sucursal previos del dueño: se excluye inicialmente de ellos. Si ya tiene sucursal, no se mueve; para modificarlo se exige vínculo con esa sucursal o acceso personal previo. El permiso se revalida en cada acción de Stock, incluida sincronización offline; un vínculo revocado no puede enviar cargas a espaldas del servidor.

Encargado/administrador ven palets paginados de treinta, unidades, propietario público, últimos veinticinco movimientos y acciones de acceso/asignación. Se abre el detalle de Stock existente. No se otorga eliminación de palet, gestión de colaboradores personales ni traslado por tener rango empresarial.

## Autorización del directorio

Agregar una sucursal al directorio no crea vínculo empresarial. Un espacio solo puede habilitar entradas creadas por su propia cuenta y sin otro equipo asignado. No existe toma de control de una sucursal ajena por coincidencia de nombre, ni por rango general de moderación. Para delegar otra sucursal ya gestionada se usan invitaciones. Verificar/transferir administración de entradas ajenas todavía no está implementado.

## Implementación y validación

Migración aditiva35: branch_workspaces, branch_workspace_locations, branch_team_members, branch_team_invites, branch_team_active y branch_team_events e índices. Transacciones y bloqueo de Stock existente para escrituras, CSRF, nombres públicos y respuestas no-store. La sucursal con equipo no puede borrarse desde el directorio general. No se copian cuentas ni inventario de producción.

Pruebas: branch-team.php (roles, directorio, invitación única/vencida, asignación idempotente, dueño intacto, cantidades, revocación y reautorización, varias sucursales, enlaces y sucursal ajena); branch-team-ui.cjs (crear con cuenta habitual, QR, login, aceptar, Inicio→escaneo manual→Stock, carga real, registros, CSRF, escalada y revocación; 320/390/1366 y ambas manos). Regresiones stock-api, stock-branches, catalog-reference, schema-fastpath, pallet-activity-grouping y service-worker-offline aprobadas. Sintaxis PHP/JS e imagen móvil revisadas. Bases locales de prueba SQLite, navegador Chrome; no equivalen a sensor físico ni validación PostgreSQL completa. Cámara física y experiencia de invitación en Safari siguen pendientes.

Marcas, listas Excel, transferencia/varios administradores, archivo de equipos, filtros/paginación histórica avanzada y generación/edición offline no se implementan en esta entrega.

## Reversión

Publicar un commit posterior en desarrollo que retire integración de equipo de Inicio/Ajustes, página/API/módulo y las ampliaciones team_role en stock_detail/stock_editable/pallet_activity_list. Conservar tablas35 y registros para no perder vínculos/historial; no borrar inventario, colas ni credenciales. Si se revierte el código, los permisos de equipo desaparecen y los personales siguen vigentes. No bajar el contador de versión de caché ni fusionar a main sin autorización. Antes de una futura fusión revisar que35 no colisione con otra migración de main y excluir siempre distintivo BETA.
