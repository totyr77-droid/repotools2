# Importación de listas CSV y XLS

El supervisor puede cargar XLSX, XLS binario o CSV de hasta3 MB. Se conserva el flujo de vista previa, hoja, títulos, EAN, correcciones, exclusión explícita y aportes voluntarios al catálogo. Solo guarda después de confirmar. Descargas de comparación siempre XLSX.

- XLSX conserva sus bytes originales.
- CSV detecta coma, punto y coma, tabulación o barra; se puede elegir separador explícito. Reconoce BOM UTF-8/UTF-16 y Windows-1252. Respeta comillas, saltos en celdas, cabecera sep= y ceros iniciales. Celdas se escriben como texto: no ejecuta fórmulas ni convierte fechas. Un EAN numérico/científico sigue el normalizador existente, sin inventar dígitos ausentes.
- XLS usa PhpSpreadsheet5.10.0 bloqueado en Composer. Admite archivos binarios OLE reconocidos, sin contraseña; se convierte a XLSX antes del parser existente. Conserva valores, formatos, fórmulas y hojas compatibles. No calcula fórmulas en servidor, no conserva macros, dibujos ni objetos incrustados. Vista previa informa conversión. HTML renombrado a XLS no se acepta: guardar como CSV/XLSX.

Límites adicionales de conversión:12 hojas XLS,20.000 filas físicas,256 columnas,300.000 celdas potenciales y32.767 caracteres por celda; límite existente de10.000 EAN válidos y de ZIP expandido se mantiene. XLSX convertido no puede superar3 MB. Temporales privados con limpieza en finally. No se modifica catálogo ni stock durante conversión; aportes siguen revisión y permisos del servidor.

Docker incorpora libzip-dev/ext-zip para requisitos del lector. Composer conserva plugins/scripts deshabilitados. No se carga biblioteca de planillas en el navegador ni se consulta servicio externo para convertir datos.

Validación: tests/supervision-import-formats.php y tests/supervision-ui.cjs, además de importador/exportador/aportes existentes. Composer audit sin avisos conocidos durante la entrega. Instalación local y API de prueba aprobadas; Docker no estaba disponible para construir imagen local.

Documentación del lector: https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/ .

Reversión: revertir entrega y publicar caché superior. Listas ya convertidas quedan almacenadas como XLSX y siguen siendo compatibles; no borrar listas ni propuestas. Conservar historial y dependencias según el commit que se restaure.
