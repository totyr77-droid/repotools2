# Navegación móvil y subnav — beta

La navegación AJAX y los enlaces #nav-celular se conservan. mobile-navigation.js monta una capa fija: subnav fuera del flujo, barra de68px más safe area, conector medido con ResizeObserver y botón activo del mismo color. Ancho92%, unión sin separación, movimiento250/220ms y reduced-motion. CSS limitado a<=768px; escritorio conserva navegación.

Acciones enlazadas a controles reales: Stock Mis palets/Pendientes/Compartidos/Buscar; opciones ocultas no se muestran. Biblioteca Colecciones/Productos/Buscar en colección/+Crear (filtro local de artículos, no cambia catálogo ni datos). Moderación Reportes/Usuarios/Registros más General/Sucursales según controles autorizados existentes. Las barras Stock/Moderación duplicadas se ocultan solo visualmente en móvil; mismas funciones y validaciones servidor. Reportes Pendientes/Resueltos conserva su segunda barra.

Alternar sección activa, cierre fuera/Escape y4s inactivo; interacción sostenida y diálogos pausan cierre. Memoria de opción por sección durante sesión AJAX, timers/listeners/observers liberados al reemplazar página. Contextual+ lado de mano dominante, sin reflejar texto/iconos. Modal nativo y toast top-layer permanecen por encima. Recursos precache SWv27.

Validación: mobile-navigation.cjs (68px,92%,centrado/unión, sin salto, toggle/chevron,4s/reinicio/touch hold, cierre fuera, AJAX/limpieza, filtro biblioteca, ambas manos,320–768, desktop y reduced-motion); navigation.cjs/navigation-timeouts.cjs; responsive-workspaces.cjs (7 páginas,10 tamaños320–1920 incl.horizontal, ambas manos, sin overflow y espacio bajo nav). PHP lint header/footer y JS syntax. No se simula compatibilidad física Safari/PWA/teclado/VoiceOver: probar en iPhone y Android reales.

Reversión: revertir commit en desarrollo y sus referencias header/footer/SW, sin alterar IndexedDB ni datos. Main y producción intactos.
