# Fotos desde edición — 1.46

Editar producto permite agregar o reemplazar una foto (JPG, PNG o WebP, hasta 6 MB), revisar la vista previa, ajustar el encuadre y deshacer el cambio antes de guardar. Reutiliza el recortador de reportes: retira márgenes solo si detecta un fondo uniforme; ante fondos ambiguos conserva la imagen completa. No identifica objetos mediante IA. Foto completa restaura el original de esta sesión.

La foto existente también se puede encuadrar. Abrir y cancelar ese ajuste no cambia ni vuelve a subir la foto. Al cargar otra imagen se conserva su original durante la edición para corregir recortes. El archivo publicado es JPEG hasta 1280 px; el servidor vuelve a validar, redibujar y retirar metadatos. No se almacena el original de la sesión después de guardar.

Los permisos vigentes de moderador, administrador o manager se comprueban en el servidor. Se conserva CSRF y control de revisión: un conflicto no reemplaza la foto. La subida usa el almacenamiento privado existente y claves nuevas; si falla el guardado se intenta limpiar únicamente el archivo nuevo, conservando las fotos anteriores.

Migración aditiva 34: `catalog_photos(ean, object_key)`. Unifica la foto vigente de edición y de reportes aprobados. Los reportes pendientes o rechazados no publican fotos. Aprobar un reporte con foto la convierte en la vigente; una aprobación sin foto o una edición de texto conserva la actual. Las fotos aprobadas antiguas siguen disponibles mediante la consulta anterior cuando no hay registro nuevo. El historial señala `photo_changed` sin incluir bytes de imagen. No modifica usuarios, palets ni colas de inventario.

Validación local: catálogo y reportes PHP (incluidos reemplazo, conservación y conflicto), API autenticada con CSRF/rangos, recortador y editor en Chrome móvil, cancelación de encuadre existente, JPEG, imagen inválida, protección de formularios y reporte exitoso. La prueba de PostgreSQL en rama temporal fue rechazada por revisión automática por requerir autorización explícita para crear la tabla; no se ejecutó ese DDL remoto. Captura física iPhone/Android y almacenamiento real pendientes.

Reversión: retirar controles de foto del editor, parámetro de subida y actualización de foto vigente; restaurar consulta anterior de fotos aprobadas. Conservar tabla, archivos y migración 34 para no perder las fotos publicadas. Publicar una versión superior para actualizar caché. No borrar objetos antiguos compartidos con reportes.
