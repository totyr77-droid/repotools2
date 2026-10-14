# Entrega y auditoría de desarrollo — 13/10/2026

## Publicación

Todo en desarrollo. Main no fue modificado y BETA sigue exclusivo de desarrollo. Push confirmado por Git; no equivale a confirmar el deploy Live de Render.

| Punto | Commit | Resultado |
|---|---|---|
|4 Sincronización visible|383453d|Mismas colas, estado real y retry|
|5 CSV|fa06e57|Stock/Palets/Vencimientos privados|
|6 Resumen Stock|ec57fb9|4 métricas + últimos5 plegados|
|Auditoría: loader|98a335f / cdf70e6|Primer ajuste incompleto; cdf70e6 cierra el finally exterior. Prueba exige cierre <2s|
|Auditoría: migraciones|5ba72e2|27 consultas de versiones reducidas a1, con medición anterior/posterior|

Revertir cada commit para retirar esa entrega, sin borrar datos ni IndexedDB. Los puntos4–6 no agregan migraciones. Conservar los commits/fragmentos BETA fuera de main al publicar mejoras.

## Regresión

49 suites JavaScript pasan tras actualizar siete fixtures antiguos: demos públicas en Stock/Biblioteca, nombre y apellido, public_name en TOPs,401 anónimo, nueva presentación de Ajustes y contratos actuales de Reportes. No se relajaron permisos para conseguir pruebas verdes. Las pruebas de reportes conservan CSRF, falsas fotos, bloqueos, approve/reject, idempotencia, avisos privados y límites de moderador/admin/manager. Subidas reales a almacenamiento externo no se declararon probadas sin configuración.

20 suites PHP pasan: preferencias, stock, exportación, overview, notificaciones, sucursales, TOPs, reportes, perfiles, sesiones, dispositivos, reset, nombres públicos, compartir bibliotecas, catálogo, configuración DB, metadata, fotos de referencia, dirección y locales. Aviso esperado de correo no configurado en el entorno aislado de reset; no es una entrega real de Resend.

PostgreSQL: esquema temporal en Neon desarrollo, instalación/repetición, períodos, timestamps, actividad, FK SET NULL, rollback, resumen y CSV. No se copiaron cuentas ni datos de producción.

Comando repetible JavaScript: `node tests/run-regression.cjs` (registra resultados en tmp, sin versionarlos).

Después del último arreglo se repitieron exportaciones/UI, CSV vacío/número negativo, loader temporizado, petición única del resumen, Stock offline e historial. Mobile360/390/430 táctil zurdo (Exportar accesible a la izquierda) y PC1366 sin desbordamiento; SVG/FA local y mano móvil se conservan. Escáner/cámara/audio/vibración de tests son simulados.

Navegación: Inicio, Stock, Biblioteca, TOPs, Cuenta, Moderación; back/forward y pageshow persisted simulado, timeout/fallback, loader RT, iconos, red lenta y movimiento reducido. Esto no prueba BFCache de Safari físico.

## Seguridad

Sin problemas críticos nuevos encontrados en el alcance probado. Autenticación/roles y permisos se validan en servidor; no depende de botones ocultos. IDOR de reportes/fotos, bibliotecas, actividad, palets, sucursales, usuarios y exportaciones se prueba variando IDs/cuentas; colaboración pending/revocada no concede acceso. CSRF en modificaciones y parámetros preparados. Las descargas GET no modifican Stock.

CSV limita al stock propio y colaboración aceptada, sin ampliar automáticamente por rango administrativo. Nombres públicos, sin tokens/emails/IDs internos. Fórmulas en texto neutralizadas; días negativos numéricos conservados.

Sesiones: identificadores almacenados como digest, cookies HttpOnly/SameSite=Lax, Secure en Render/HTTPS, revocación por versión y cupos de dispositivos. Fixtures verifican cierre, recuperación, concurrencia y sesiones obsoletas. .dockerignore excluye credenciales, herramientas, tmp, tests y SQLite; data/.htaccess bloquea lectura directa.

No se introduce CSP estricta ni se cambian reglas de permisos por conjetura. Esta revisión es limitada a rutas/pruebas descritas; no constituye garantía absoluta ni pentest externo.

## Cabeceras y PWA

Producción pública confirmó nosniff, strict-origin-when-cross-origin y SAMEORIGIN. Apache aislado verifica también no-referrer en recuperación y bloqueo de archivos sensibles. No hizo falta agregar headers.

Manifest: nombre/short_name/id/start_url/scope/display standalone/colores, iconos192/512 normales y maskable; Apple Touch Icon existente. Un único SW, shell-v10, offline fallback y FA local precache. Pruebas pwa-presentation/offline-start/local-icons pasan. Sin reimplementación de PWA.

## Mediciones reales

13/10/2026, curl desde este equipo, HTTPS producción pública sin autenticación. Tiempos en ms; tres muestras, no percentiles representativos. Primera observada no significa cold start demostrado.

|Ruta|HTTP|Primer TTFB|Siguientes TTFB|Tiempo total de las3 muestras|
|---|---|---:|---|---|
|Inicio|200|808|380 /333|861 /381 /337|
|Stock demo|200|351|361 /481|352 /363 /481|
|TOPs público|200|394|334 /368|394 /334 /368|
|Cuenta ingreso|200|340|360 /413|340 /360 /413|
|Reportes sin sesión|302|335|339 /378|335 /339 /378|

Reportes mide redirección al ingreso, no el contenido autenticado. Producción sigue main: estas muestras no miden aún las funciones nuevas de desarrollo.

Neon producción read-only, sin initialize_database ni lectura de cuentas/productos: conexión1343ms; SELECT1 ~199–200ms; lectura de versiones ~199–201ms. Son cliente Windows→Neon, no Render→Neon.

Neon desarrollo temporal antes/después de optimización: comprobación de esquema6064/6021/6044ms →959/966/961ms (~84% menor); resumen~575–776ms. Conservados transacción/advisory lock/versiones individuales. No nuevos índices. No se extrapola este porcentaje a toda la página.

Baseline local autenticado PHP/SQLite (10 muestras): medianas Inicio52ms, Stock53ms, TOPs50ms, Cuenta53ms, Reportes sucursales44ms. No compararlas como equivalente a producción: entorno/datos/red difieren.

Falta medir requests autenticados dentro de Render, separar conexión/consultas/total y cold start controlado. No se dispone de sesión del servicio ni URL beta confirmada. Preparado diagnóstico CLI read-only; nunca exponer credenciales ni un profiler público. No se infieren timings DB a partir del TTFB.

## Base de datos y errores

Hallazgo medido:27 viajes de comprobación de migraciones; corregido sin cambiar esquema. Dashboard tiene una agregación y una lista reciente, no request/consulta por card. Actores por join, colaboradores por lote. No hay evidencia suficiente para agregar otros índices o reescribir consultas existentes.

Suites principales sin errores JS no controlados; iconos y assets locales verificados. Lint de72 archivos PHP pasa. Sin warnings PHP de flujos normales en pruebas. Servicios externos reales (mapa/API/fotos/correo) necesitan validación en beta configurada; algunos tests interceptan proveedores.

## Checklist real pendiente

- [ ] iPhone Safari cámara física/EAN diagonal/QR, Normal/Rápido y repetición.
- [ ] Pitido ON/OFF, volumen, silencio y música; vibración solo si navegador soporta API.
- [ ] Instalación iPhone/Android, standalone, sesión persistente y Apple icon.
- [ ] Preparar offline en el mismo dominio, modo avión, recarga/cerrar/reabrir, cargas y reconexión sin duplicados.
- [ ] Descargar3 CSV en Safari/Android y abrir en Excel con acentos/códigos como texto.
- [ ] Compartir enlaces/etiquetas y fotos reales celular-PC.
- [ ] Loader RT y back/forward/BFCache en Safari real.
- [ ] Confirmar URL/deploy beta Live y medir contenido autenticado Render→Neon.

No se declara puntaje objetivo de la app ni estas pruebas físicas como realizadas.
