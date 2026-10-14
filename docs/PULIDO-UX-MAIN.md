# Pulido UX publicado selectivamente en Main

Autorización: el mensaje humano de2026-10-14 pidió publicar directamente en Main, prevaleciendo sobre la restricción beta del adjunto. No se fusionó desarrollo completo. Main recibe solo este pulido y sus dependencias visuales de navegación/una mano. Referidos, nuevas migraciones, mejoras offline de beta y demás lógica de negocio no se trasladan. No cambios de planes, credenciales, configuración del hosting ni datos reales.

## Interfaz
Stock/Biblioteca: títulos pequeños con descripción breve en la misma fila donde cabe; márgenes compactos y objetivos44px. Generar palet/Crear colección con más+texto, color principal. Stock utiliza el título de la pestaña real; quita «Etiquetas listas para usar» redundante, conserva reservas y etiquetas de estado. Últimos movimientos con icono y Ver mis compartidos juntos debajo del resumen; historial solo cuando la respuesta existente contiene actividad, compartidos conserva su condición real. Sin reservar fila si ninguno existe.

Biblioteca: + reutiliza Escanear producto, con carga manual desde el control ya integrado en cámara; teclado independiente oculto, handler conservado. Tarjeta/descripcion abre el visor existente mediante su botón original oculto, sin nueva generaciónEAN. Enter/Espacio accesibles en descripción; acciones secundarias no abren ficha. Engranaje y funciones intactos, incluidos estados disabled cuando controles se mueven fuera del contenedor original.

Moderación: encabezado grande redundante oculto también para moderadores sin vista administrativa; Mis reportes y mensajes de permiso siguen visibles. No nuevas capacidades de roles.

Una mano: preferencia localporcuenta existente de desarrollo trasladada como dependencia visual (apagada por defecto; no nueva columna/endpoint). Nav/subnav existentes de beta reutilizados, no sistema paralelo. Inicio coloca lista arriba y escanear abajo junto al pulgar; mueve los botones reales. Stock FABs suben con subnav y bajan al cerrarlo; color coherente. Biblioteca FABs: agregar escaneando, compartir, configuración (es la acción real, no existe «Condición» separada), separación y nueva colección. Fuente compartida/permisos conservada y sin teclado duplicado. Productos dejan espacio para las acciones.

Moderación: filtros reales se mueven a sheet inferior; + únicamente reutiliza Agregar sucursal en su vista autorizada, sin inventar creación en otras secciones. Filtros no aplican cambios hasta Aceptar; X conserva borrador para reabrir y restaura los valores aplicados; Limpiar restablece defaults antes de Aceptar. Enter del formulario también confirma sin aplicar parcialmente; email inválido impide aceptar. Orden/altura consistente.

Puntos del subnav replican fuentes reales: propiedad/compartidos con vencimientos y Reportes/Pendientes con tickets; no indicadores nuevos. Menú Reportes48% del viewport, anclado arriba, dentro del ancho, opciones existentes. Ambas manos sin reflejar textos.

## Archivos y alcance
Nuevos: js/ux-polish.js, css/ux-polish.css, tests/ux-polish.cjs. Dependencias necesarias en Main: js/one-hand-mode.js, css/one-hand-mode.css, js/mobile-navigation.js, css/mobile-navigation.css. Ediciones pequeñas en cuenta.php (solo switch), vistas/header.vista.php y footer.vista.php (assets/bootstrap), js/biblioteca.js (alcance de disabled), js/stock-overview.js (visibilidad basada en activity existente), sw.js (cache de recursos visuales, shellv34), PENDIENTES.md y este documento.

No edición de APIs/SQL, autenticación, usuario/rangos, reportes de productos, operaciones de palets, escáner, sonido/vibración, ni colas/confirmaciones offline. CambioSW solo añade assets, sin borrar IndexedDB ni operaciones. Main conserva su rama de sincronización existente; no se arrastró el contrato nuevo de beta.

## Pruebas
Checkout exacto de Main y SQLite temporal: ux-polish, responsive-workspaces (7pantallas×10tamaños, ambos lados, orientación), settings-ui, stock-api, navigation y pallet-deduct-ui aprobadas. Prueba específica: visor por tarjeta/Enter, papelera sin abrirEAN, keyboard oculto, historial/título, FAB siguiendo subnav, punto correcto, filtros sin aplicación parcial/borrador/aceptar/limpiar, prioridad Inicio, ambos modos/manos y escritorio. PHP/JS sintaxis y gitdiffcheck aprobados. En desarrollo, one-hand-workspaces y responsive-workspaces también aprobadas. No pruebas con datos reales.

Pendientes físicas: Safari/iPhone y PWA instalada, teclado virtual/áreas seguras, cámara real desde +, dropdown al cambiar orientación/zoom del navegador. No afirmar que se probó hardware.

## Hosting y reversión
La publicación solicitada apunta a Main/servicio de producción, no depende de esperar despliegue del servicio betaFree. No se implementó un supuesto arreglo de infraestructura ni se cambió facturación: restricciones o incidencias de Render se gestionan en el proveedor. No se atribuye el fallo previo a una causa técnica sin logs.

Revertir el commit de este pulido en Main, sin borrar datos ni almacenamiento local. Desactivar el switch restaura modo tradicional. Verificar despliegue por hashes y plantilla sin BETA; push no demuestra que Render publicó.
