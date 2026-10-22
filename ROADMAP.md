# Pendientes vigentes de RepoTools

Revisado contra la entrega v2.38 el 22/10/2026. Lo implementado se documenta en ARCHITECTURE.md; no figura aquí como desarrollo pendiente.

## Próxima validación: notificaciones del celular

- Notificaciones push en celular con la app cerrada: vencimientos, invitaciones y solicitudes; permiso voluntario, bajas/privacidad por dispositivo, envío desde servidor y recordatorios agrupados. La campana no se marca leída al recibir el aviso. Implementadas en v2.38; falta confirmar recepción real tras despliegue en iPhone/Android instalados, con app cerrada y pantalla bloqueada. Ver docs/NOTIFICACIONES-PUSH.md.

## Validación de campo prioritaria

- iPhone/Android: instalación PWA, apertura desde icono, sesión, actualización sin reiniciar y navegación/cámara.
- Escáner: etiquetas juntas/elección correcta, altura, zoom progresivo, temblor, luz/reflejos; pistola USB/Bluetooth y conexión celular-PC con cámaras reales.
- Offline: preparación con conexión, carga de cajas/paquetes internos, cierre y reapertura en modo avión, reconexión intermitente y ausencia de duplicados; retención real de IndexedDB en Safari.
- Mi equipo/Empresas: recorrido con cuentas de Usuario, operador, encargado, administrador, manager y supervisor; solicitudes aceptadas/rechazadas/revocadas, etiquetas y ambas manos/modos en teléfono.
- Excel: abrir descargas en Excel real y probar más plantillas de proveedores; comprobar cantidades por presentación, fecha, palet y persona con filtros. XLSX ya implementado y validado automáticamente.
- Impresión/AirPrint: A4, varias etiquetas y lectura de impresiones reales a distancia.

## Operación y medición

- Correo: recepción real Gmail/Outlook/Apple Mail, botón/enlace/logo y bandeja de entrada. Revisar DNS/DMARC y alineación SPF/DKIM con Resend; no atribuir spam a una sola causa ni afirmar configuración actual sin consultarla. Ver docs/CORREO-VERIFICACION-20261020.md.
- Ubicaciones: verificar configuración del proveedor en Render y coincidencia/sugerencias con locales reales; no copiar claves privadas.
- Rendimiento: medir endpoints autenticados en Render/Neon con inventario/historial grandes y red móvil lenta, antes de optimizar. Mantener pruebas aisladas y sin datos personales en telemetría.

## Opcionales, todavía no comprometidos

- Exportar/resguardar cargas locales pendientes antes de borrar el navegador o cambiar de equipo.
- Ampliar offline a ediciones, fotos o activación/generación de palets: hoy no se promete esa capacidad.
- Definir si lecturas de Inicio offline deben sumar al TOP al reconectar; no cambiar rankings implícitamente.
- Importación de listas de supervisión desde CSV/.xls además de .xlsx, si se solicita.
- Planes comerciales/suscripciones y tendencias por período en General, solo si se decide desarrollarlos.

## Quitado de la lista antigua porque ya existe

Empresas/supervisores con consentimiento y desvinculación, equipos de sucursal en main, transferencias de stock/propiedad, comparación y exportación XLSX configurable, cajas con paquetes internos, etiquetas personales para Usuario y nuevos registros de moderación. Sus pruebas físicas pendientes están arriba; no son funciones por implementar de nuevo.
