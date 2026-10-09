# Catálogo compartido de productos

Inicio y Biblioteca consultan `api/productos.php?ean=...`. El servidor valida el EAN y busca primero en `products`, dentro de la misma base de cuentas. Si falta, consulta Open Food Facts y guarda los campos utilizados por las fichas y la respuesta de esos campos en `external_payload`.

La migración 2 crea las tablas del catálogo al acceder a la aplicación. Conserva cuentas, escaneos y colecciones existentes. Render necesita la misma `DATABASE_URL`; el Dockerfile incorpora cURL para consultar la API por HTTPS.

Las consultas no encontradas se recuerdan durante 24 horas. Los errores y timeouts permiten reintentar. La consulta externa admite hasta 15 solicitudes nuevas en una ventana de 60 segundos y utiliza un User-Agent propio. Las fichas ya guardadas siguen disponibles al alcanzar ese límite. Referencia: [API de Open Food Facts](https://openfoodfacts.github.io/documentation/docs/Product-Opener/api/).

El catálogo importado conserva su procedencia Open Food Facts, el enlace al producto y la atribución ODbL en la ficha. Los datos derivados de Open Food Facts se reutilizan bajo [ODbL](https://opendatacommons.org/licenses/odbl/1-0/); las imágenes conservan su URL de origen y sus condiciones de reutilización. Las ediciones locales no se envían a Open Food Facts.

Solo las cuentas cuyo rol vigente en `users` sea `administrador` o `moderador` pueden editar. El registro no permite escoger ese permiso. No se asignan roles automáticamente; su gestión queda pendiente del CMS.

El lápiz permite corregir nombre, presentación, marca, categoría y envase, o completar un EAN ausente. El EAN no cambia. Cada modificación registra autor, ficha anterior y ficha nueva en `product_edits`. Una versión antigua recibe HTTP 409 y debe revisarse antes de guardar. Las importaciones nunca reemplazan una ficha existente.

Pruebas: `php tests/catalog.php`, `node tests/catalog-api.cjs`, `node tests/product-info.cjs`, `node tests/ean-scanner.cjs` y `node tests/scan-result.cjs`. El test PHP usa SQLite en memoria por defecto. Para PostgreSQL, `REPOTOOLS_TEST_DATABASE_URL` debe apuntar exclusivamente a una rama de prueba: el test crea datos de prueba, nunca debe ejecutarse sobre producción.
