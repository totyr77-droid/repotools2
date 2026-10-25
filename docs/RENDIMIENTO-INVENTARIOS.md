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

## Oportunidad detectada en v2.99 (implementada en v3.00 más abajo)

La primera carga de Stock sigue creando aproximadamente 9.180 nodos con 1.000 palets y descarga muchos recursos compartidos en caché vacía. Estudiar renderizado progresivo y recursos por pantalla preservando búsqueda, filtros, copia offline, enlaces a palets y desplazamiento. Mi equipo y registros ya paginan 30/50 elementos. En v3.00 se cubren esos recorridos y se recorta solo la representación, manteniendo el contrato completo de datos/offline.

## Limpieza de estilos y validación

css/supervision.css consolida reglas de buscador/toolbar, textarea de configuración y modal de comparación; combina controles de exportación/sucursales que tenían declaraciones idénticas. No fusiona Listas e Integrantes ni cambia la distribución visual.

Comparación local de estilos calculados (geometría, tipografía, colores, tamaños, espacios y distribución): 30 estados por navegador en Chromium y WebKit, 320/390/1366 px, ambas manos, Mi equipo con modal de descarga/invitación y pantallas de empresas/sucursal. Valores iguales antes/después. No se afirma limpieza completa de todo el CSS de la aplicación.

Pruebas: stock-notifications (incluye cero UPDATE repetidos, lectura, transacción externa/rollback, fallo parcial, escalamiento, permisos y eliminación); stock; audit-records (agrupación/filtros/paginación/permisos); audit-records-ui (modal, destinos y cantidades); moderation-stats; expiry-alerts-ui. Esta última esperaba estados today/warning en elementos que actualmente son urgent por diseño naranja <=7 días: se actualizó solo la expectativa; los datos de fecha ya estaban presentes.

## Reversión

Revertir los archivos de esta entrega y publicar una versión de caché superior. No requiere revertir datos ni migraciones. Los archivos de benchmark son independientes de la aplicación; pueden conservarse aunque se revierta una optimización.

## v3.00 — Carga inicial por tramos y motores de cámara al usarlos

Se aplica la oportunidad detectada en v2.99. No modifica consultas SQL ni contratos de autorización/datos. La respuesta de Stock y el manifiesto offline siguen completos; solo la representación visual es incremental.

- Palets propios/compartidos: primer tramo de32, siguientes por proximidad al final o Ver más palets. Sucursal, etiqueta y orden se resuelven contra la fuente completa. Conserva el tramo cargado al recargar con iguales filtros; los enlaces a productos abren su modal sin montar tarjetas intermedias. Los observadores/fuentes se liberan al navegar. Etiquetas para imprimir conservan el flujo anterior.
- Mapas por código/ID para sucursales y etiquetas; evita búsqueda lineal por cada tarjeta y no reconstruye la lista al tipear si su criterio/datos no cambiaron.
- Motores de cámara de375.364 y156.608 bytes dejan de ejecutarse/descargarse como script de cada pantalla antes de usar un lector. Primario bajo demanda, auxiliar solo EAN; fallo permite reintento y cerrar responde sin esperar al motor. No arranca una cámara después de cerrar. Validación de EAN y flujo manual siguen disponibles desde el inicio.
- Ambos motores y el helper nuevo permanecen preparados por el SW para offline. Su primera instalación puede descargar recursos en segundo plano y no está incluida en estas cifras. No se promete eliminar todo uso de red antes del primer escaneo.

Corrida de referencia final, misma metodología/fixture que v2.99, una carga por pantalla/red (no mediana de renderizado):

| Pantalla | v2.99 local | v3.00 local | v2.99 red lenta | v3.00 red lenta |
|---|---:|---:|---:|---:|
| Stock | 3,64 s | 1,43 s | 15,00 s | 9,71 s |
| Mi equipo | 1,12 s | 1,01 s | 9,30 s | 6,63 s |
| Registros | 3,36 s | 2,99 s | 11,03 s | 8,24 s |

Stock propio de1.000 palets: nodos DOM9.180→1.866 (1.876 en la muestra lenta por estado de fondo), aproximadamente80% menos nodos iniciales; no es una medición de memoria. La API de Stock continúa entregando255.325 bytes de esta fixture: la ganancia es de representación/recursos, no de quitar inventario. Red lenta:150ms RTT,200.000 bytes/s de descarga, caché vacía. Sin desborde ni errores JS en el benchmark. Fuentes externas bloqueadas; cámara física, CPU móvil, SW inicial y PostgreSQL real no medidos.

Nuevas pruebas reproducibles: `node tests/stock-progressive-ui.cjs` y `node tests/scanner-lazy-ui.cjs`; para WebKit usar RT_BROWSER=webkit y el directorio Playwright configurado. Comparten las restricciones de runtime local del benchmark. La primera comprueba manifiesto offline íntegro, palets fuera del primer tramo, orden/etiqueta/sucursal, compartidos, recarga, navegación y limpieza de actualizaciones tardías. La segunda usa cámara simulada y errores de descarga controlados; no acredita hardware.

También aprobados: stock-tags/branches/export/offline, offline-start (arranque offline real, búsqueda y carga sincronizada), supervision, main-rapid-scanner, pruebas de EAN/planificación/encuadre, SW y navegación/scroll. Fixtures actualizadas para no depender de motores cargados eager, no autoasignar sucursal cercana durante una prueba manual y no contraer el espaciador de scroll de Inicio.

Reversión: revertir los JS y las plantillas de la entrega, con caché superior. Mantener operaciones offline, datos y permisos; no hay migraciones. Estos resultados mejoran el escenario observado, no garantizan un tiempo fijo en producción ni una carga instantánea con conexión lenta.
