# Entorno de prueba

Código: rama Git `desarrollo`, desde3014899. Servicio propuesto: `repotools-desarrollo`, Docker, Free, Ohio. Configuración `render-desarrollo.yaml` incluye únicamente este servicio nuevo; no gestiona producción ni grupos de variables compartidos. Revisar el resumen de Render antes de crear: debe aparecer solo el nuevo servicio Free, nunca repotools2.

Base Neon independiente creada13/10/2026: proyecto `repotools-desarrollo`, ID `square-bonus-56273687`, región aws-us-east-2, PostgreSQL17, cómputo0,25CU. Sin cuentas, datos ni credenciales copiados de producción. El proyecto original alcanzó el límite de ramas y rechazó la creación schema-only; no se eliminó ninguna rama para hacer espacio. La aplicación crea sus tablas al primer acceso. Revisar cuotas del plan Neon en su consola; no se cambió el plan de la cuenta.

## Activación

1. En Neon, abrir proyecto repotools-desarrollo → Connect. Seleccionar su rama predeterminada/neondb, activar pooling y copiar solo la URL PostgreSQL.
2. Render → New → Blueprint → repositorio totyr77-droid/repotools2. Elegir rama desarrollo y ruta del archivo render-desarrollo.yaml.
3. Completar DATABASE_URL con la conexión del nuevo proyecto Neon. No vincular el grupo de producción ni copiar su DATABASE_URL. Revisar que solo se crea repotools-desarrollo en Free y confirmar.
4. Esperar Live y abrir la URL HTTPS asignada por Render. En Environment agregar APP_URL con esa URL exacta, sin barra final ni rutas, y guardar/redeploy.
5. Crear una cuenta de prueba: las cuentas reales no existen aquí. Crear una biblioteca/palet, recargar y volver a ingresar tras otro despliegue para comprobar persistencia.
6. Usar esta dirección en el celular; los commits/push de desarrollo activan despliegues del servicio de prueba. Solo se publicará main al autorizar la fusión.

Correos y fotos externas no están habilitados por esta configuración inicial. Configurar claves/remitentes y almacenamiento de pruebas cuando se necesiten, sin reutilizar archivos privados de producción. Geoapify también requiere configurar su variable si se prueba el mapa. El servicio Free puede dormir y comparte cuotas de uso de la cuenta Render; no es un servicio sin límites.

Validación previa: Dockerfile existente instala PDO PostgreSQL, no requiere comandos build/start adicionales. El nombre de host de prueba no coincide con la redirección fija repotools2.onrender.com de .htaccess. Configuración revisada contra la especificación oficial https://render.com/docs/blueprint-spec. Creación, conexión PostgreSQL, Live, permisos y cámara del servicio nuevo deben verificarse tras completar Render; todavía no se creó el servicio desde este entorno.

Reversión: retirar render-desarrollo.yaml y esta nota en desarrollo; producción no cambia. No borrar el proyecto/base de pruebas sin exportar datos que se quieran conservar y autorización específica.
