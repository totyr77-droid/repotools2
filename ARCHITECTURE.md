# Arquitectura actual de Repotools

## Aplicación y despliegue

Aplicación PHP con HTML, CSS y JavaScript sin framework frontend. `index.php` es el inicio real; `index.html` es una prueba estática y no ejecuta cuentas ni APIs PHP. Vistas comunes en `vistas/header.vista.php` y `vistas/footer.vista.php`; estilos y componentes en `css/` y `js/`. `js/navigation.js` realiza navegación AJAX y limpieza de cámaras/temporizadores, con navegación normal como respaldo.

Docker usa PHP 8.3 y Apache, PDO PostgreSQL/SQLite, mbstring, curl y GD. `.htaccess` conserva entrada PHP, protección de archivos sensibles, redirección del dominio Render y política de actualización del service worker. Docker habilita rewrite y headers. No se añadió CSP ni HSTS. Recuperación conserva `Referrer-Policy: no-referrer`.

## Datos, cuentas y permisos

`includes/database.php` crea/aplica el esquema existente. Producción requiere `DATABASE_URL` PostgreSQL externo; SQLite se usa para desarrollo/pruebas. No crear migraciones para cambios visuales. Preservar PDO, transacciones, revisiones y claves idempotentes. No versionar bases, variables privadas, fotos ni credenciales.

`includes/auth.php` y `includes/sessions.php` mantienen sesiones persistentes en DB, duración30días, renovación del identificador al ingresar y cookies HttpOnly/SameSite. `includes/account-devices.php` valida una sesión desktop y una mobile por cuenta. Cambios de contraseña invalidan las sesiones correspondientes mediante versión de autenticación. Recuperación está implementada con Resend, tokens de un uso y vencimiento; configuración en [docs/RECUPERACION.md](docs/RECUPERACION.md).

Rangos actuales: usuario, moderador, supervisor, administrador y manager. APIs validan permisos en el servidor; ocultar botones no concede ni revoca acceso. Manager tiene capacidades superiores; supervisión por vínculos y Empresa como permiso siguen pendientes. El usuario invitado puede escanear y consultar contenido público, pero no administrar colecciones/palets privados.

## Escaneo y celular-PC

`js/ean-scanner.js` es el lector compartido EAN/QR. Mantiene validación/confirmación de lecturas, localización y rotaciones del canvas, Quagga, zoom y flash según dispositivo. Html5Qrcode2.3.8 y Quagga1.12.1 son archivos locales en `js/vendor/`; no modificar algoritmos al cambiar presentación. Fuentes, iconos y mapas todavía utilizan otros proveedores externos.

`js/device-connection.js` y `api/scan-relay.php` conectan el celular y PC de la misma cuenta con consentimiento. En PC se usa teclado/lector o celular; el contexto determina EAN/QR y destino. Internet es necesario en ambos equipos. Véase [docs/ESCANEO-PC-CELULAR.md](docs/ESCANEO-PC-CELULAR.md).

## Stock, Biblioteca y Moderación

Stock administra palets, etiquetas QR/A4, cantidades por unidad/caja, vencimientos, propietarios/invitados y sucursales comunes. Una referencia personal no sobrescribe el catálogo. Palets públicos y enlaces seleccionados/por sucursal muestran contenido autorizado y resúmenes por EAN; selección fija y sucursal dinámica son conceptos distintos.

Biblioteca guarda colecciones privadas, EAN únicos por colección y referencias personales; permite compartir una copia desde un producto y confirmar importación. El catálogo actualizado aparece separado de la referencia. Productos se consultan primero en DB y luego en API cuando corresponde; las propuestas aceptadas actualizan el catálogo.

Moderación incluye reportes pendientes/resueltos; rangos autorizados administran usuarios, registros y sucursales. General es exclusivo manager. Reportes incluyen evidencias privadas, comparativa y bloqueo para enviar reportes según rango. TOPs es público; solo cuentas registradas puntúan y repetir un EAN no aumenta el TOP de códigos únicos.

Sucursales: `js/branch-maps.js` comparte Leaflet/MapLibre; mapa vectorial con respaldo raster. Al marcar un punto, `api/direccion-sucursal.php` busca primero DB dentro200m; Es otro local consulta Geoapify si está configurado. Se confirma antes de guardar en directorio común. La clave permanece en servidor; caché temporal no reemplaza la DB. La cuota local por instancia no garantiza la cuota global del proveedor.

El selector abre primero el mapa. Alta/edición muestra el mapa arriba y tres sugerencias por vez, conservando las demás en memoria. Controles de centrar/quitar se integran en el mapa; confirmación queda fuera del área desplazable y conserva la asociación nativa con cada formulario. Limpiar el punto no elimina una sucursal: requiere guardar el formulario para persistir ese cambio.

## Offline y PWA

Un único service worker: `sw.js`, registrado por `js/offline-cache.js`. Shell actualv9 con `offline.html`; no se cachean APIs privadas. Navegaciones cubiertas muestran shell offline al fallar la red. Requiere preparar recursos con conexión en el mismo origen antes de usarlos offline.

`js/stock-offline.js` utiliza IndexedDBv2 para cargas Stock/Biblioteca, catálogo y envíos recientes, separados por cuenta. `js/library-offline.js` y Stock verifican conectividad autenticada, identidad e idempotencia antes de sincronizar. Inicio offline conserva lista local, sin enviar lecturas a TOPs. No borrar IndexedDB al revertir una entrega.

`manifest.webmanifest` define la misma aplicación PHP con rutas relativas a su directorio, modo standalone y colores existentes. Iconos reales del pack en `img/Repotools_Logo_Pack_Blanco/`: normales/maskable192/512, Apple180 y logo horizontal blanco. El encabezado general enlaza manifest una vez. No existe un segundo service worker ni se cambió la estrategia de caché para instalar la PWA.

## Trabajo y pruebas

Leer `PENDIENTES.md` y ROADMAP antes de continuar. Historial de decisiones y entregas en CHANGELOG; las notas antiguas no prevalecen sobre código actual. Mantener diseño minimalista, SVG y preferencia de mano móvil; escritorio conserva disposición diestra. Pruebas en `tests/` usan mayormente DB temporal/Chrome simulado, no equivalen a pruebas de campo.

La raíz de trabajo no contiene Git; publicación mediante checkout `tmp/upstream` en main y despliegue automático Render. Versionar solo archivos de la entrega, conservar cambios del usuario y documentar validación/reversión. Publicar un commit no demuestra que Render haya terminado.
