# Empresas y supervisores

El permiso de supervisor de empresa es independiente del rango general de la cuenta y de los roles de una sucursal.

## Uso

1. Un manager entra en **Empresas y sucursales → Empresas → Asignar empresa**, indica el nombre y el correo del supervisor. Puede escanear el QR de la persona con el control existente.
2. La persona habilitada encuentra **Mi empresa / Mi equipo** y puede invitar integrantes por correo o QR de perfil.
3. La persona invitada encuentra **Supervisores**, revisa quién solicita consultar su stock y acepta o rechaza. Puede tener varios supervisores y desvincularse de cada uno por separado.
4. El supervisor consulta Palets o Mercadería y filtra por integrante, sucursal y vencimiento. Los lotes se despliegan con propietario, sucursal, composición y fecha. **Actualizar equipo** vuelve a consultar los datos actuales.
5. En Listas sube un `.xlsx`, elige la columna de EAN y guarda un nombre. Antes de comparar puede elegir los mismos filtros de stock. **Comparar** muestra cantidades por fila; **Descargar comparación** conserva el libro y agrega `Stock (unidades)` a la hoja elegida. Sin lista, **Descargar stock** genera otro Excel agrupado por EAN, con las columnas elegidas y el desglose de cantidades por fecha y palet.

Las sucursales anteriores siguen en su propio ámbito. Si una persona tiene empresa y sucursal, Mi equipo ofrece ambas entradas; Inicio reutiliza el acceso sin duplicarlo.

## Datos y consentimiento

- Solo un manager de Repotools habilita o deshabilita supervisores. Habilitar no concede permisos generales de administrador ni de edición de palets.
- Solo el dueño de un equipo activo puede invitar integrantes, guardar listas y consultar/exportar su equipo.
- Se cuentan los palets activos propios de los integrantes que aceptaron, una sola vez por propietario. No se amplía el acceso por colaboraciones de terceros ni por compartir una sucursal. Las propuestas pendientes de una sucursal no sustituyen el stock confirmado del propietario.
- Cada integrante acepta o rechaza con revisión del vínculo; un vínculo revocado no puede aceptarse con un formulario antiguo. Desvincular o deshabilitar revoca las consultas y descargas siguientes en el servidor. Habilitar de nuevo no restaura consentimientos revocados.
- Un supervisor no puede editar stock por este permiso. Las cuentas que ya tienen otro permiso independiente conservan su alcance anterior.
- La sucursal reservada para pruebas de managers mantiene su restricción de visibilidad. Los nombres de personas se presentan con el nombre público, sin enviar correos o nombres privados en los listados.
- Las notificaciones de habilitación, invitación y aceptación se guardan en la app y abren Supervisores/Mi empresa.
- Estas consultas y la carga de listas requieren conexión. No se agregan copias de stock de otras personas al almacenamiento offline.

## Excel

Detección de hoja con productos y títulos en las primeras 100 filas, con selección manual de hoja, fila de títulos y columna de EAN (0 para hojas sin títulos); hasta 10.000 filas de productos, 200 columnas y archivo de 3 MB. Se admiten 20 listas por equipo y 200 integrantes activos o pendientes. Los EAN admitidos son los mismos de la app (8 o 13 dígitos con verificador válido). La vista previa muestra cinco ejemplos y el total válido, más las primeras 100 filas inválidas con motivo y corrección manual. Se puede excluir expresamente todo lo que siga pendiente; sin esa elección el servidor rechaza guardar errores. Las fórmulas no se interpretan como EAN. Los títulos EAN repetidos entre categorías se omiten. Se leen códigos numéricos completos aunque Excel los muestre abreviados; textos científicos incompletos no se expanden para inventar dígitos. Solo .xlsx, hasta 30 hojas. No admite .xls, CSV, fotos ni PDF.

Se conservan orden, repeticiones, columnas originales, fórmulas de otras columnas, formatos y otras hojas. Cada EAN repetido recibe el mismo stock; no duplica el inventario. Las filas sin EAN permanecen sin cantidad y los EAN sin stock se completan con 0. Las correcciones manuales se guardan en el mapeo de comparación; el archivo original permanece intacto. Las filas excluidas no reciben stock (no se confunden con cero). En hojas sin títulos se agregan cantidades desde la primera fila sin insertar títulos ni desplazar datos. Los códigos del export completo se guardan como texto para conservar ceros iniciales. La cantidad comparativa se expresa siempre en **unidades**, para no mezclar cajas o bultos de distinta composición.

Se usan PharData y DOM de PHP, sin bibliotecas nuevas ni servicios externos. El ZIP se inspecciona con límites de entradas/tamaño expandido y se lee sin extraer rutas del archivo. El XML no admite DTD/entidades ni acceso de red. El archivo temporal se elimina al finalizar. Se guarda el original en la base de datos de la empresa; las consultas de listas excluyen el archivo salvo al descargar esa lista. La exportación completa permite hasta 20.000 lotes; para más datos se solicita filtrar, sin truncar silenciosamente.

## Validación y reversión

Migración aditiva 43: `supervisor_teams`, `supervisor_members` e índice de vínculos por usuario, `supervisor_lists` e índice por equipo. Se preservan PDO, sesiones/CSRF, mutex de cambios, revisiones e inventario existente. Inicialización repetida validada en SQLite; DDL e instrucciones de agrupación validadas en una rama aislada de PostgreSQL que ya tiene expiración automática. No se activaron supervisores reales ni se migró manualmente producción.

Pruebas: `tests/supervision.php`, `tests/supervision-ui.cjs` (Chromium/WebKit), `tests/supervision-home-ui.cjs` y `tests/supervision-xlsx.cjs`. Esta última crea y vuelve a leer un libro con openpyxl para comprobar interoperabilidad, estilos, fórmulas, ceros, filas finales y hojas adicionales. Regresiones de gestión de sucursales, propuestas de equipo y API de Stock comprobadas. El uso en teléfono físico queda pendiente de validación de campo.

Para revertir, revertir el código y publicar una versión/cache superior. Mantener las tablas 43 y las listas: no borrar archivos, vínculos ni inventario. El código anterior ignora las tablas nuevas. No se debe bajar el número del esquema ni eliminar datos para volver atrás.

La importación flexible guarda metadatos JSON versionados (fila de títulos y mapeo) en ean_rows existente, sin migración. Lectores actuales admiten listas anteriores. Para revertir la interfaz/importador conservando nuevas listas, mantener supervisor_list_rows/supervisor_list_header y su uso en comparación/exportación; un rollback completo a v2.30 no interpreta los metadatos nuevos. No borrar listas ni fuentes.

Comparar abre primero un modal de alcance: todo el equipo o varios integrantes aceptados, todas/varias sucursales o sin sucursal, y vencimiento. Ver comparación consulta bajo esa selección; Descargar Excel permite descargar directamente sin vista previa. Descargar stock usa el mismo modal sin lista. La selección inicial copia los filtros de inventario, pero luego es independiente: modificarla limpia el resultado y descarta respuestas antiguas. Paginación y descarga mantienen el alcance explícito; los filtros no son cambios de datos y cerrar no pide guardar. El servidor valida cada integrante de la selección múltiple contra consentimiento vigente; sucursales siempre intersectan ese mismo alcance. Hasta200 opciones seleccionadas, duplicados no duplican stock; no hay cambios en permisos ni esquema.

Acomodo v2.33: Palets/Mercadería comparten barra compacta Filtros(n)/Descargar stock; los filtros se despliegan en el lugar, se cierran al tocar fuera/cambiar pestaña/abrir exportación y conservan selección al alternar inventario. Integrantes/Listas no muestran filtros de inventario: cada comparación los elige en su modal. Etiqueta de sucursal con SVG/color existente en resumen de palet, singular/plural correcto, borrar lista como icono secundario con nombre accesible/confirmación, encabezado adaptable e invitación con campo y botón completos en móvil. No cambia el alcance de stock ni permisos.

Exportación v2.34: Información a descargar en el mismo modal, con EAN, nombre, UXB, stock total, vencimientos/cantidad por fecha, palets/cantidad por fecha, sucursales e integrantes/cantidades. Comparación conserva todas las columnas originales y agrega solo las seleccionadas (por defecto Stock); descarga sin lista genera una fila por EAN (por defecto todas). Repeticiones de EAN en lista conservan su posición y reciben el mismo detalle; no duplican inventario. Vista previa sigue mostrando stock total; elegir columnas de exportación no invalida esa consulta.
Stock y UXB únicos son números. UXB indica unidades de caja/bulto completo (6 paquetes ×30 =180); si difiere entre lotes muestra cada presentación con cantidad, y marca unidades sin UXB conocida cuando se mezclan. Sin dato conocido queda vacío, no se infiere desde la lista. Fechas se suman por fecha e incluyen Sin vencimiento; palets conservan cada par palet/fecha y unidades. Se incluyen todas las fechas dentro de los filtros elegidos, no solo la primera. Información de integrantes usa nombres públicos. Los EAN del nuevo archivo son texto para preservar ceros.
Detalle multilineal con ajuste de texto, columnas legibles y altura de filas ampliada cuando corresponde, hasta el máximo de Excel; datos originales, fórmulas, estilos de celdas originales y otras hojas se conservan. No se ejecutan fórmulas de nombres/textos exportados. Límites: 20.000 lotes para detalle, 20.000 productos para solo stock, 32.767 unidades UTF-16 por celda y 6MB de valores añadidos; si se exceden pide filtrar, sin truncar el contenido ni descargar parcialmente. Sin migración ni escrituras de stock. Revertir entrega/publicar versión superior conservando metadatos/listas/esquema43/inventario; restaurar API y helpers de exportación juntos.

Compatibilidad ZIP: para comparar se crea una copia temporal nueva a partir del contenido de cada entrada y se edita esa copia. Evita corrupción de partes no modificadas causada por padding de encabezados locales de ciertos Excel al reescribir directamente con PharData. El original almacenado no se modifica; no se extraen rutas al filesystem. Se mantienen límites y limpieza de temporales. Conservar esta corrección al revertir cambios de interfaz/exportación.
