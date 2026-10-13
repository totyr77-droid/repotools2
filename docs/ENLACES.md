# Vista previa al compartir — 13/10/2026

El encabezado PHP común utiliza el isotipo blanco sobre negro `img/Repotools_Logo_Pack_Blanco/04_pwa_fondo_negro/repotools-pwa-512x512.png`, sin modificar el archivo original. Open Graph declara PNG, 512×512 y texto alternativo; Twitter usa tarjeta cuadrada summary. Aplica a las páginas que incluyen header.vista.php, incluidos palets y stock compartido. No se publican datos privados del contenido en la tarjeta.

Validación: PHP lint del encabezado correcto; dimensiones PNG verificadas y recurso ya versionado. La visualización final depende de cada plataforma y de su caché; probar WhatsApp tras el despliegue. No se garantiza que un enlace antiguo actualice su tarjeta inmediatamente ni que las páginas privadas muestren su contenido sin acceso.

Reversión: restaurar vistas/header.vista.php desde 71fad03. No hay cambios de datos, permisos ni imagen original.
