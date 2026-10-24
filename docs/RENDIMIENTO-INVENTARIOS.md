# Rendimiento con inventarios grandes — v2.99

Medición local del 24/10/2026. No equivale al rendimiento del servidor de producción ni de un celular físico.

## Escenario y reproducción

Desde la raíz del repositorio: `node tests/performance-large.cjs`.

Usa PHP/SQLite y Chromium local, con 21 cuentas ficticias, 2.000 palets, 20.000 lotes, 5.000 EAN y 50.000 eventos. Stock consulta 1.000 palets propios; Mi equipo consulta los otros 1.000, repartidos entre 20 integrantes con invitación aceptada. No usa la base configurada de producción; el generador rechaza DATABASE_URL y exige una ruta temporal rt-large-. No modifica datos reales, esquema ni índices.

La prueba crea una base temporal propia y la elimina al finalizar; RT_KEEP_BENCHMARK=1 permite conservarla para diagnóstico. Salida: tmp/performance-large.json y log local en tmp/performance-server.log (ignorados por Git). Puertos de pruebas 18964/18966, no ejecutar dos instancias juntas. Usa el PHP, Chrome y Playwright locales, igual que los otros recorridos del repositorio.

Un proxy serial local evita bloqueos del servidor PHP de Windows por sockets especulativos; drena respuestas que el navegador cancela. No forma parte de la aplicación. Solicitudes HTTP: una primera consulta y cinco adicionales, mediana y máximo de las cinco. Carga visual: una muestra por pantalla y red, desde navegación hasta contenido real y desaparición del loader. Caché del navegador deshabilitada; fuentes/CDN externos bloqueados; service worker y enlace entre dispositivos excluidos. No mide instalación/PWA, CPU móvil ni carga concurrente de producción. No se presenta un p95 con tan pocas muestras.

## Hallazgos y correcciones

### Avisos de vencimiento

stock_notify preparaba una sentencia y confirmaba una escritura por cada aviso. Incluso avisos idénticos volvían a escribirse. Ahora reutiliza la sentencia, agrupa la actualización en una transacción y omite UPDATE cuando título y cuerpo no cambian. Conserva lectura; un cambio de título vuelve a avisar, uno de cuerpo conserva lectura. Respeta una transacción externa y revierte toda la actualización propia ante fallo.

Comparación aislada del código anterior (9a0fb2a) y actual en la misma fixture, 2.520 avisos, una muestra por estado:

| Estado | Antes | Después |
|---|---:|---:|
| Generación inicial | 21.810 ms | 80 ms |
| Sin cambios | 20.517 ms | 72 ms |

Son tiempos de esta fixture SQLite; no son una promesa de entrega push ni cifras extrapolables a PostgreSQL.

### Registros agrupados

La consulta correlacionada buscaba coincidencias de operación repetidamente. Se reemplaza por JOIN a identidades DISTINCT; sin filtros adicionales usa directamente el alcance autorizado. Conserva operaciones completas aunque el filtro coincida con un solo evento, separación por actor/resultado/sección, conteos, orden y paginación.

Con 50.000 eventos: consulta anterior interrumpida a los 15.000 ms en prueba PHP aislada; nueva consulta HTTP sin filtro adicional: mediana 1.997 ms. Con filtro de acción: 2.931 ms. Se verificaron además transacciones con varios eventos coincidentes para evitar multiplicarlos al hacer JOIN.

SQL compatible en estructura con ambos controladores; pruebas funcionales ejecutadas en SQLite y generación de consulta PostgreSQL revisada. No se ejecutó la carga ni esta consulta contra PostgreSQL de producción.

## Referencia de la corrida completa

| API | Mediana HTTP local |
|---|---:|
| Stock | 91 ms |
| Mi equipo, palets | 144 ms |
| Mi equipo, mercadería | 146 ms |
| Registros agrupados | 1.997 ms |
| Avisos | 114 ms |
| Moderación General | 185 ms |

| Pantalla | Caché vacía, red local | Caché vacía, 150 ms / 1,6 Mbps |
|---|---:|---:|
| Stock | 3,64 s | 15,00 s |
| Mi equipo | 1,12 s | 9,30 s |
| Registros | 3,36 s | 11,03 s |

Ninguna de las tres pantallas desbordó horizontalmente a 390 px; sin errores JavaScript. Los tiempos de pantalla incluyen descarga de recursos, scripts y contenido; no deben confundirse con tiempo SQL o de API. Variaciones entre corridas son esperables.

## Próxima oportunidad, sin cambio aplicado

La primera carga de Stock sigue creando aproximadamente 9.180 nodos con 1.000 palets y descarga muchos recursos compartidos en caché vacía. Estudiar renderizado progresivo y recursos por pantalla preservando búsqueda, filtros, copia offline, enlaces a palets y desplazamiento. Mi equipo y registros ya paginan 30/50 elementos. No introducir paginación de Stock ni cambiar el contrato offline sin cubrir esos recorridos.

## Limpieza de estilos y validación

css/supervision.css consolida reglas de buscador/toolbar, textarea de configuración y modal de comparación; combina controles de exportación/sucursales que tenían declaraciones idénticas. No fusiona Listas e Integrantes ni cambia la distribución visual.

Comparación local de estilos calculados (geometría, tipografía, colores, tamaños, espacios y distribución): 30 estados por navegador en Chromium y WebKit, 320/390/1366 px, ambas manos, Mi equipo con modal de descarga/invitación y pantallas de empresas/sucursal. Valores iguales antes/después. No se afirma limpieza completa de todo el CSS de la aplicación.

Pruebas: stock-notifications (incluye cero UPDATE repetidos, lectura, transacción externa/rollback, fallo parcial, escalamiento, permisos y eliminación); stock; audit-records (agrupación/filtros/paginación/permisos); audit-records-ui (modal, destinos y cantidades); moderation-stats; expiry-alerts-ui. Esta última esperaba estados today/warning en elementos que actualmente son urgent por diseño naranja <=7 días: se actualizó solo la expectativa; los datos de fecha ya estaban presentes.

## Reversión

Revertir los archivos de esta entrega y publicar una versión de caché superior. No requiere revertir datos ni migraciones. Los archivos de benchmark son independientes de la aplicación; pueden conservarse aunque se revierta una optimización.
