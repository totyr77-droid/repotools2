# Acceso Pro en beta

Solo desarrollo,25/10/2026. Usuario pide que una cuenta sin Pro no pueda continuar con funciones Pro y vea comparación/beneficios para unirse. Pagos pospuestos. Producción no recibe este cambio.

## Reglas

Acceso activo si paid_until es posterior a la hora del servidor; sin bypass por rango, atributos HTML, datos del navegador o flags enviados en formularios. Cancelar renovación conserva tiempo ya pagado. No se habilita cobro, se concede plan a cuentas reales ni se fijan precios/cupos. Pro no reemplaza propiedad, permisos, CSRF ni revisión/idempotencia.

Se exige Pro para cambiar propietario del palet (vista previa y confirmación), invitar a otra cuenta, crear enlaces de stock y generar el enlace para compartir un palet. Protección en servicios de dominio/API. Ampliar alcance de enlaces por exclusiones o nueva asignación de palet a sucursal compartida también exige Pro. El destinatario Free puede aceptar y trabajar con permisos existentes.

Free conserva stock propio, movimiento de mercadería entre palets con permisos, revisión/revocación de accesos, baja de enlaces y reducción de alcance compartido. Enlaces/derechos ya otorgados no se destruyen al vencer Pro. Consulta pública por QR y reimpresión conservan su comportamiento existente: controlar la función de compartir no convierte un QR público en recurso privado ni impide que alguien copie manualmente su URL.

El distintivo de transferencia se mueve al destino A un usuario: el otro destino mueve mercadería y sigue Free. Compartir/gestionar accesos conservan distintivo. La revisión de accesos actuales es accesible desde el aviso para no impedir revocar permisos.

## Comparación

Modal reutiliza componentes/SVG, muestra stock propio incluido en ambos planes y compartir/transferir propiedad/invitar como beneficios Pro. Cada intento consulta al servidor sin caché de derecho. Errores de API devuelven pro_required y feature para mostrar el mismo aviso. El cliente no usa un enlace alternativo cuando se deniega compartir un palet ni reutiliza alias cacheado para esa operación.

Unirme a Pro aparece deshabilitado con explicación mientras contratación no esté configurada. Cuando la prueba privada esté lista, únicamente managers podrán abrir el flujo preparado; no anunciar checkout público disponible. No inventar precio ni activar billing al mostrar el aviso.

## Validación y reversión

pro-access-ui comprueba APIs directas Free/Pro/vencido/cancelado con tiempo pagado, mutaciones ausentes ante denegación, propiedad adicional, aceptación Free, revocación/reducción Free, transferencia interna Free y ampliación indirecta bloqueada. UI320/390/1366 y ambas manos, comparación y revisión de accesos, sin errores JS. Fixtures locales, sin cuentas/cobros del proveedor. Stock API conserva pruebas de permisos/idempotencia con suscripciones ficticias explícitas. Ver resultados de motores en PENDIENTES.md.

Sin migración. Revertir cambios de esta entrega únicamente en desarrollo y publicar versión/cache superior. Mantener tablas47, suscripciones, pagos, stock, accesos y secretos existentes. No conceder Pro ni borrar datos para revertir.
