# Pendientes vigentes de RepoTools

Actualizado el 24/10/2026 contra v2.96. Lo implementado se documenta en ARCHITECTURE.md y PENDIENTES.md.

## Confirmado por el usuario

- Excel de escritorio: descarga y comparación, columnas, productos fuera de lista y datos de descripción/UXB.
- Columnas ambiguas: comportamiento revisado y aceptado.
- API con productos faltantes: recorrido de prueba indicado, dado por listo por el usuario.
- Escáner e impresión: pruebas indicadas de etiquetas juntas, selección y lectura de impresiones dadas por listas.
- Offline de Stock: marcado listo a pedido del usuario; la revisión adicional que mencionó no se mantiene como bloqueo.
- Push automático: recepción confirmada en su cuenta/dispositivo tras v2.51. El fallo de arranque/conexión está cerrado.

Estas confirmaciones cubren lo probado por el usuario; no acreditan todos los modelos de teléfono, impresoras o proveedores.

## Próximo mantenimiento interno

1. Revisar la prueba expiry-alerts-ui.cjs: espera una notificación con estado today que no estaba presente en su fixture durante la última ejecución documentada. No atribuirlo a un defecto del producto sin reproducirlo.
2. Mantener documentación de continuidad y prioridades al día, sin reabrir funciones implementadas.
3. Medir rendimiento con inventarios/historiales grandes y red móvil lenta antes de optimizar.

## Mejora de rapidez push consultada

- Aplicado en v2.72: scripts/push-loop.sh espera15 segundos entre ejecuciones; cada ejecución prepara y despacha pendientes. Vigilar carga de la base con la mayor frecuencia; no prometer entrega instantánea del proveedor/teléfono.
- El resumen de vencimientos se prepara una vez al día desde las09:00 de Argentina; es independiente de la espera entre revisiones.

## Verificaciones operativas sin incidencia confirmada

- Correo: recepción en proveedores y alineación DNS/SPF/DKIM/DMARC con Resend, si se requiere auditoría. No afirmar configuración actual sin consultarla.
- Ubicaciones: proveedor en Render y coincidencia con locales reales, si se reporta un problema o se amplía cobertura.
- Ampliar pruebas a dispositivos/impresoras no probados cuando se incorporen; no bloquea los recorridos ya confirmados.

## Opcionales no comprometidos

- Resguardar/exportar cargas locales pendientes: pospuesto por el usuario; prioriza sincronización habitual con internet.
- Ampliar offline a ediciones, fotos o activación/generación de palets.
- Definir si lecturas de Inicio offline suman al TOP al reconectar.
- CSV/XLS implementados en v2.87; no queda como desarrollo pendiente.
- Planes comerciales/suscripciones y tendencias por período, si se decide desarrollarlos.

Empresas/supervisores, consentimiento, equipos de sucursal, transferencias, XLSX configurable, cajas con paquetes internos, etiquetas personales y registros de moderación ya están implementados; no son desarrollo pendiente.

Moderación General: estadísticas por período, actividad agrupada, resoluciones y totales actuales implementados en v2.79; permisos globales exclusivos de manager conservados.

## Últimas funciones solicitadas

Cámara del buscador supervisor y listas CSV/XLS implementadas en v2.87. Detalles y límites en docs/IMPORTACION-CSV-XLS.md. Respaldo offline y unificación de listas/integrantes permanecen pospuestos por el usuario.

UXB visible junto a contenido neto en formulario de producto, edición y revisión: implementado en v2.88.


## Revisión general del 24/10 — próxima decisión de interfaz

Limpieza técnica aplicada en v2.90: script antiguo sin uso, estilos de desarrollo, inclusión duplicada, reglas de grilla reemplazadas y carga de recorte solo en sus editores. Consolidación general de CSS queda gradual, por componente; no retirar reglas activas en bloque.

Encabezado de sucursal corregido en v2.91. Ajustes simplificado en v2.96. Quedan por decidir nombres consistentes de equipo de empresa/sucursal y estadísticas con menos repetición. No se aplicó rediseño ni se retiraron funciones de negocio. Correcciones detectadas aún por hacer: incluir hoja específica del escáner de Supervisores y permitir zoom. Revisar acceso flotante para evitar superponer contenido. No inferir desuso de funciones sin datos reales.

Accesos de Inicio unificados en v2.92: misma fila, dimensiones e iconografía, respetando mano y permisos existentes.

Inicio con equipo de sucursal y supervisor corregido en v2.93: accesos juntos y destinos explícitos, selector independiente.

Gestión de empresas v2.94: asignación en modal, sin actualización manual redundante ni mensajes globales persistentes; AJAX al guardar/volver/abrir pestaña.

Inicio v2.95: resuelto salto de Palet debajo de Escanear producto; alturas móviles iguales incluso con PWA visible. Empresas usa Asignar supervisor en botón/modal.

Ajustes v2.96: retirados cuatro atajos repetidos; Preferencias primero, actividad/insignias plegables y accesos propios conservados. Perfil público sin cambios.
