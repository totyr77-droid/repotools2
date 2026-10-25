# Pendientes vigentes de RepoTools

Actualizado el 24/10/2026 contra v3.04. Lo implementado se documenta en ARCHITECTURE.md y PENDIENTES.md.

## Confirmado por el usuario

- Excel de escritorio: descarga y comparación, columnas, productos fuera de lista y datos de descripción/UXB.
- Columnas ambiguas: comportamiento revisado y aceptado.
- API con productos faltantes: recorrido de prueba indicado, dado por listo por el usuario.
- Escáner e impresión: pruebas indicadas de etiquetas juntas, selección y lectura de impresiones dadas por listas.
- Offline de Stock: marcado listo a pedido del usuario; la revisión adicional que mencionó no se mantiene como bloqueo.
- Push automático: recepción confirmada en su cuenta/dispositivo tras v2.51. El fallo de arranque/conexión está cerrado.

Estas confirmaciones cubren lo probado por el usuario; no acreditan todos los modelos de teléfono, impresoras o proveedores.

## Próximo mantenimiento interno

1. Prueba expiry-alerts-ui.cjs corregida y aprobada en v2.99: las fechas estaban en la fixture; la expectativa visual no contemplaba urgent (hoy/<=7 días). No era un fallo del aviso.
2. Mantener documentación de continuidad y prioridades al día, sin reabrir funciones implementadas.
3. Medición grande completada en v2.99, correcciones de avisos/registros aplicadas. Ver docs/RENDIMIENTO-INVENTARIOS.md. Implementado en v3.00: renderizado por tramos sobre fuente completa y motores de cámara bajo demanda; offline/filtros/enlaces verificados. Las cifras son locales y no incluyen la instalación inicial del SW.

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

Encabezado de sucursal corregido en v2.91. Ajustes simplificado en v2.96. Nombres unificados en v2.97. Estadísticas compactadas en v2.98 con desgloses en modal. No se aplicó rediseño ni se retiraron funciones de negocio. Revisión de cámara cerrada en la conversación; no reabrir como desarrollo pendiente sin una incidencia nueva. Acceso flotante aceptado por el usuario: su posición se puede mover. No inferir desuso de funciones sin datos reales.

Accesos de Inicio unificados en v2.92: misma fila, dimensiones e iconografía, respetando mano y permisos existentes.

Inicio con equipo de sucursal y supervisor corregido en v2.93: accesos juntos y destinos explícitos, selector independiente.

Gestión de empresas v2.94: asignación en modal, sin actualización manual redundante ni mensajes globales persistentes; AJAX al guardar/volver/abrir pestaña.

Inicio v2.95: resuelto salto de Palet debajo de Escanear producto; alturas móviles iguales incluso con PWA visible. Empresas usa Asignar supervisor en botón/modal.

Ajustes v2.96: retirados cuatro atajos repetidos; Preferencias primero, actividad/insignias plegables y accesos propios conservados. Perfil público sin cambios.

Zoom (punto6) descartado explícitamente por el usuario: conservar comportamiento actual de aplicación y no habilitar ampliación de página.

Nombres v2.97: Mi equipo = empresa supervisada; Mi sucursal = equipo de sucursal; Mis supervisores = vínculos recibidos. Sucursales visibles como nombre único del alcance. Punto6 (zoom) descartado por el usuario.

Moderación General v2.98: cuatro cifras y tres desgloses en modal; todas las métricas y accesos a registros conservados, sin repetir movimientos de stock.

Estilos v2.99: consolidados buscador/toolbar, comparación, textarea y controles repetidos de Mi equipo, sin cambio visual comprobado en Chromium/WebKit. Limpieza restante gradual por componente, no tarea para reescribir todo el CSS.

Carga inicial v3.00: Stock por tramos, filtros/orden sobre inventario completo y motores de cámara al usarlos; no queda como desarrollo pendiente. Mejora local comprobada con inventario grande/red lenta. No declarar instantaneidad ni rendimiento garantizado en dispositivos/producción no medidos.

Preparación offline con prioridad de uso aplicada en v3.01: pausa entre lotes durante modales/sincronización/interacción/pestaña oculta y agrupa escrituras de progreso. Mantiene validaciones y cargas pendientes; no implementar respaldo pospuesto por esta mejora.

Limpieza gradual v3.02: retiradas declaraciones anuladas de Ajustes, notificaciones y resumen de Stock, con comparación de estilos/medidas Chromium/WebKit. El resto se revisa por componente; no declarar limpieza global terminada.

Limpieza v3.03: iconos de Biblioteca y reglas antiguas del recorte de fotos revisados y depurados. Formularios y UXB conservados; validación funcional y estilos equivalentes. No quedan cambios comprometidos de esta tanda.

Tanda de limpieza cerrada en v3.04 con Stock/mercadería y comparación de Moderación. Verificación funcional y de equivalencia CSS aprobada; no quedan desarrollos comprometidos de esta revisión. Continuar mantenimiento solo ante evidencia o nuevas prioridades, sin prometer app libre de errores.
