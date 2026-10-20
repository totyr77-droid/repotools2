# Perfil e insignias — beta v1.64

## Alcance
Perfil propio integrado en Ajustes y perfil público con la misma identidad compacta, iniciales, antigüedad, palets creados, días activos e insignias. El público muestra rango y sello de correo verificado, nunca dirección de correo, equipos ni movimientos. El propio conserva el rango del saludo. El sello informa Correo verificado al tocarlo o pasar el cursor; no acredita identidad.

Ajustes conserva acciones y preferencias. La elección de mano queda en un selector corto de dos opciones con áreas táctiles de 44px. SVG originales suministrados por el usuario, sin rediseñar los 72 recursos. Insignias pequeñas, brillo de una pasada solo para nuevas; movimiento reducido lo desactiva. Colección desplegable de 12 categorías, seis niveles, bloqueadas atenuadas con candado y metas. Progreso exacto privado; fechas de obtención públicas. Hasta tres destacadas guardadas en servidor. Si todavía no se eligieron, se muestran hasta tres logros obtenidos como vista inicial.

## Inicio desde cero y reglas
Migración aditiva 40 agrega achievement_events (claves únicas por usuario/categoría), achievement_progress, achievement_days, achievement_receipts (reintentos) y user_achievements (nivel, fecha, destacada, visto). No rellena actividad histórica ni reinicia datos operativos existentes. Los contadores públicos de este perfil corresponden al nuevo sistema, independientes de TOPs históricos.

- Ojo de águila: EAN únicos de cámara, checksum validado. Repeticiones no incrementan; captura manual y OCR de palets no suman EAN.
- Arquitecto de palets: primera carga que activa un palet propio. Recibir propiedad o trasladar a otro no cuenta como crear.
- Repositor incansable: operación de carga nueva confirmada, cualquiera sea la cantidad; editar una fila existente no suma.
- Maestro del despacho: retiro explícito confirmado, excluye correcciones, borrar filas y traslados.
- Maestro del traslado: una transferencia completada entre palets o de propiedad, solo actor de origen.
- Embajador RepoTools: referido validado por el flujo existente, único por invitado; sin autoatribución.
- Usuario fiel: días distintos de acciones válidas; abrir la app no suma. Zona Argentina. Operaciones offline cuentan al confirmarse en servidor; días atribuidos a la confirmación, no al reloj del dispositivo.
- Imparable: mejor racha de semanas lunes-domingo con al menos dos días activos; domingo no requerido y sí válido. Metas 2/4/8/12/26/52. Insignias permanentes.
- Organizador profesional: EAN únicos guardados en Tu lista o Biblioteca; borrar/recrear/importar la misma referencia no duplica.
- Maestro del QR: palets activos y otros usuarios únicos, por identidad persistente; excluye perfil propio, invitaciones, inválidos y números OCR. El lector de equipo registra el QR después de superar validación de sucursal.
- Detective de productos: búsqueda explícita al confirmar o terminar un campo de búsqueda (Enter/change), no cada tecla ni consultas automáticas. Normalizada, una misma consulta por día no duplica, límite 100 distintas por día. Las búsquedas sin resultados siguen siendo búsquedas.
- Perfeccionista: corrección real de campos de producto aprobada por otra persona, una vez por reporte. Pendientes, rechazados, sin cambios y autoaprobaciones excluidos. Metas 5/15/40/100/250/500.

Las demás metas son las del manifest del ZIP. Los hooks de inventario/referidos/reportes comparten transacción e idempotencia de negocio. La cámara envía códigos, nunca contadores; servidor valida formato, checksum o existencia/identidad. Este transporte no constituye una certificación física de cámara: un cliente modificado puede enviar códigos válidos, como en el registro previo de escaneos.

## Avisos y rendimiento
Notificaciones existentes con SVG y acceso a Mis insignias. Se agrupan, esperan al cierre de cámara y se reclaman atómicamente en servidor para evitar repetición entre pestañas. Si se interrumpe el cliente luego de reclamar, queda la insignia en el perfil. La cola local de lecturas/lista se separa por usuario y operación; reintentos no duplican. No se bloquea el lector esperando logros. Preferencias y selección destacada requieren conexión. SVG se cargan diferidos y cachean al usarse, sin descargar 72 imágenes al instalar PWA.

## Validación y límites
SQLite aislada: achievements.php, achievements-ui.cjs, settings-ui.cjs, public-profile-referral-ui.cjs, reports.php/API, referrals.php, stock.php/API, schema-fastpath, EAN, main-rapid-scanner y service-worker-offline aprobados. Casos: cero, niveles simultáneos, rollback, claves únicas, confirmación offline/reintento, límites/CSRF/propiedad, domingos/rachas, privacidad, 72 recursos, persistencia destacadas, 320/390/1366 ambas manos. Prueba rápida actualizada para leer RepoEANList actual en lugar de la antigua clave lista. Capturas propias/públicas y Ajustes revisadas. PostgreSQL de integración, Safari/cámara/GPS físicos y prueba prolongada de carga pendientes; no se accedió a inventario real.

## Reversión
Volver código a v1.63 y publicar versión superior. Conservar tablas nuevas y registros; no borrar inventario, contadores operativos ni datos de usuarios. Main no se modifica. Quitar las nuevas referencias de recursos si se retira la función, sin borrar archivos usados por clientes anteriores.
