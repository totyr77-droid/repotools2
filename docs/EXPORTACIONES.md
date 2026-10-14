# CSV de Stock (desarrollo)

Un menú secundario Exportar ofrece Stock, Palets y Vencimientos. Descarga con nombre fechado en horario argentino, UTF-8 con BOM y separador punto y coma. No requiere otra pantalla. Preparación local y toast de resultado; descarga estándar sin exigir Web Share.

La API GET privada `api/stock-export.php` valida sesión, tipo y palet opcional. Alcance: palets propios o colaboración aceptada, no liberados ni preparación vencida. Un administrador no recibe automáticamente todo el stock ajeno. No se aceptan filtros de sucursal para ampliar permisos. Sin emails, tokens, IDs internos o nombres privados en CSV. Formulas iniciadas por =, +, -, @ (incluso con espacios) se neutralizan. EAN se exporta como dígitos; algunos lectores de planillas pueden convertir códigos con ceros iniciales y deben importar esa columna como texto.

Stock: EAN, referencia personal o catálogo, unidades, código de palet, sucursal, vencimiento. Palets: código, sucursal, estado, cantidad de lotes/productos, creación argentina y nombre público actual. Vencimientos: producto, EAN, palet, sucursal, fecha, días y estado. Cada lote EAN+vencimiento es una fila; no se agrupan fechas diferentes. El helper `stock_expiry_state` se comparte con las notificaciones y usa los mismos límites y preferencia actual (máximo30 días existente).

Generación incremental en php://temp, con derrame a disco después de2MB. Se completa antes de enviar headers para no anunciar éxito con un archivo incompleto. No hay consultas por fila.

Archivos: includes/stock-export.php, includes/stock.php, api/stock-export.php, js/stock-export.js, css/stock-export.css, stock.php, vistas/footer.vista.php, tests/stock-export.php, tests/stock-export-ui.cjs.

Pruebas: formatos3, BOM, acentos, campos escapados, límite de fecha, colaboración/pending/IDOR403, inyección de fórmulas y3001 filas; descargas360/390/430/1366, errores401/403/405/422/503, toast y consola. stock-notifications mantiene comportamiento. Compatibilidad estructural para Excel comprobada; apertura en Microsoft Excel y Safari iPhone físicos pendiente.

Revertir este commit; no hay migraciones ni datos que borrar.
