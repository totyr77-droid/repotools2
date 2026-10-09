# Render Free + Neon Free

La web permanece en Render. Cuentas, estadísticas y colecciones se guardan en PostgreSQL de Neon, fuera del contenedor. El proyecto crea sus tablas automáticamente al acceder a cuentas o TOPs.

1. Entra a https://console.neon.tech y crea una cuenta.
2. Crea un proyecto llamado `repotools`. Selecciona el plan **Free** y una región cercana a tu servicio Render. Usa la base predeterminada `neondb` y la rama principal.
3. En el proyecto, abre **Connect**, selecciona esa rama, base y usuario, y activa **Connection pooling** si está disponible.
4. Copia la dirección de conexión: debe comenzar con `postgresql://` o `postgres://`. Copia solo la URL, sin `psql`, comillas ni otro comando.
5. En Render, abre el Web Service de Repotools → **Environment** → **Add Environment Variable**.
6. Crea `DATABASE_URL` y pega la URL de Neon como valor. No la publiques en GitHub, en capturas ni en el chat.
7. Guarda con **Save, rebuild, and deploy**. Si Render solo ofrece guardar, luego usa **Manual Deploy → Deploy latest commit**.
8. Espera a que figure **Live**. La imagen instala PDO PostgreSQL y las tablas se crean automáticamente.
9. Crea una cuenta de prueba, realiza un escaneo y crea una colección. Cierra sesión y vuelve a entrar.
10. Haz un nuevo despliegue manual y vuelve a ingresar con esa cuenta. Confirma que la cuenta, TOPs y la colección siguen presentes. Render puede cerrar tu sesión al reiniciar; basta con entrar nuevamente.

No necesitas crear una base Render Postgres, agregar un disco ni pagar un servicio. Mantén el plan Free de ambos proveedores y revisa sus límites de uso en sus paneles. Consulta los planes vigentes de Neon en https://neon.com/pricing.

## Datos que ya existían

Conectar Neon no copia la base SQLite anterior. Si esos datos ya desaparecieron y no tienes una copia, deberás crear nuevamente la cuenta. Si aún hay datos que quieras conservar, expórtalos antes de desplegar o cambiar el almacenamiento: este cambio no incluye una importación automática.

Los escaneos locales y los palets siguen almacenados en el navegador; la gestión de palets todavía no se sincroniza con PostgreSQL.

## Si no conecta

- Verifica que la variable se llame exactamente `DATABASE_URL`.
- Confirma que copiaste solo la URL y que pertenece a la misma rama/base después de cada despliegue.
- Si cambiaste la contraseña en Neon, actualiza el valor en Render y vuelve a desplegar.
- Confirma que Render está usando el commit que instala `pdo_pgsql`.
- Si falta la URL o Neon no responde, la aplicación muestra un error y no cambia silenciosamente a una base temporal.

No compartas la URL completa para pedir ayuda; contiene la contraseña.
