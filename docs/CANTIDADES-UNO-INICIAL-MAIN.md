# Cantidades: reemplazar el 1 inicial — main3.20

Al ingresar cantidades de bultos/cajas cerradas, los campos de unidades por bulto y paquetes internos conservaban el1 inicial al escribir, generando15 en lugar de5. El manejo compartido solo seleccionaba0; inputs number no admiten selección fiable en iOS.

Se arma reemplazo al enfocar un campo de cantidad con1. Primera inserción numérica cancelable reemplaza ese valor y emite input para mantener totales y referencias sincronizados. Los dígitos posteriores se agregan normalmente. No vacía el campo al enfocar, conserva1 si se sale sin escribir y no cambia cantidades distintas de1, identificadores/EAN ni campos de solo lectura. Incluye paquetes internos, sin modificar validación servidor ni stock almacenado por sí solo.

Validación: quantity-default-one-ui Chrome/WebKit con teclado sobre inputs number/text:1→5, segundo dígito52, foco/salida conserva1, cantidad12 editable y EAN con ceros intacto. JS sintaxis/diff. Main exclusivamente; beta sin cambios.

Reversión: revertir commit y publicar versión de caché superior. Sin migraciones ni datos que revertir. PENDIENTES.md/ROADMAP.md locales del usuario no se incluyen en este commit.
