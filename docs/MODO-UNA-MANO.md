# Modo de una mano — beta

Interruptor independiente en Ajustes, apagado por defecto. Reutiliza data-hand existente. Como beta y producción comparten base según confirmó el usuario, no hay SQL, migraciones ni escrituras de preferencias compartidas: repotools_one_hand_v1_user_ID en localStorage de este navegador/origen, separado por cuenta. No sincroniza entre dispositivos. Aviso explícito en Ajustes. Para persistencia servidor se requiere primero una base beta aislada.

El diseño tradicional y PC se restauran al apagar o salir del ancho móvil. La barra flotante previa aparece solo en modo activo: 100%, violeta suave, chevrón, unión dinámica y4s de inactividad, pausa durante interacción/diálogos/desplegable. Stock conserva Mis/Pendientes/Compartidos; cámara y buscar son botones independientes laterales. Buscar mueve el componente real a un panel inferior, conserva valores y vuelve al lugar original. Compartir/Ver compartidos junto a Últimos movimientos.

Biblioteca muestra las colecciones reales en el subnav desplazable; + mueve el botón real de creación, único flotante. Escanear/manual/compartir/configuración de la colección están abajo y se reservan espacios para + y nav. No se inventó un botón Información de colección: la interfaz tiene configuración y visualización por producto; se conservan sus funciones.

Moderación conserva todas las secciones autorizadas. Reportes abre solo las dos opciones reales cuando existen, en popover compacto sobre el botón; filtros/buscador existentes aparecen en panel inferior y no se clonan ni reinician. Palet: Cargar/Restar del lado hábil; Configuración/Sucursal/Historial desde el contrario, con grupos flex y posibilidad de segunda fila. Escáneres/offline/permisos/datos no se modifican.

one-hand-mode.js mueve nodos con marcadores y los restaura, sin duplicar callbacks ni formularios. Limpia listeners/paneles al navegar y al desactivar, incluso con búsqueda abierta. Los recursos se precachean SWv29. El modo offline dedicado conserva su interfaz actual.

Archivos: js/one-hand-mode.js, css/one-hand-mode.css, js/mobile-navigation.js, cuenta.php, vistas/header.vista.php, vistas/footer.vista.php, sw.js, tests/one-hand-mode.cjs, tests/one-hand-workspaces.cjs, tests/mobile-navigation.cjs, tests/settings-ui.cjs, tests/pallet-deduct-ui.cjs, PENDIENTES.md. Arreglo previo independiente: js/product-reports.js y tests/report-success-ui.cjs, commit d95bbb6.

Pruebas: reporte éxito/error; una mano default/restore/persistencia/browser user isolation, búsqueda retenida, callbacks reales, colecciones y filtros, menú reportes; nav4s/92% componente base y100% modo; ajustes/reload/cambio página/otra cuenta; acciones palet ambos lados360 sin solaparse; modo tradicional7páginas10 tamaños320–1920; modo activo5páginas320/390/1366 con colecciones reales en SQLite temporal; PHP/JS sintaxis. No se probó iPhone físico: Safari/PWA/teclado/VoiceOver/alcance pulgar pendientes.

Revertir commit del modo en desarrollo; no borrar IndexedDB ni datos. No hace falta revertir esquema porque no hay migraciones. Main/producción intactos.

Ajuste de integración: js/biblioteca.js conserva el bloqueo de +Crear durante operaciones aunque el botón esté en la capa flotante.
