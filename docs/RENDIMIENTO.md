# Perfil inicial de rendimiento ? 13/10/2026

No se modific? la l?gica de datos para optimizarla. Medici?n aislada: PHP local, SQLite temporal, dos usuarios y dos palets. Diez muestras por ruta tras una primera petici?n. Incluye respuesta HTTP completa, sin renderizado, c?mara ni APIs externas. No representa Render/Neon ni una carga de usuarios real.

```json
{
  "environment": "Local PHP/SQLite, 2 users, 2 pallets, authenticated manager; HTTP response only, not rendering or external APIs",
  "samples": 10,
  "results": [
    {
      "path": "/index.php",
      "first_ms": 47.93,
      "median_ms": 18.98,
      "p95_ms": 22.62
    },
    {
      "path": "/stock.php",
      "first_ms": 21.98,
      "median_ms": 18.15,
      "p95_ms": 38.78
    },
    {
      "path": "/api/stock.php",
      "first_ms": 16.92,
      "median_ms": 21.48,
      "p95_ms": 24.96
    },
    {
      "path": "/api/stock.php?code=12345678",
      "first_ms": 20.3,
      "median_ms": 21.86,
      "p95_ms": 29.54
    },
    {
      "path": "/biblioteca.php",
      "first_ms": 19.01,
      "median_ms": 16.78,
      "p95_ms": 21.41
    },
    {
      "path": "/tops.php",
      "first_ms": 16.14,
      "median_ms": 17.27,
      "p95_ms": 18.71
    },
    {
      "path": "/cuenta.php",
      "first_ms": 16.83,
      "median_ms": 16.89,
      "p95_ms": 18.63
    },
    {
      "path": "/reportes.php?view=branches",
      "first_ms": 19.98,
      "median_ms": 18.42,
      "p95_ms": 22.21
    }
  ]
}
```

Prueba interna (30 muestras): conexi?n SQLite 0,251 ms; esquema nuevo 14,610 ms; comprobaci?n de migraciones actualizadas mediana 0,088 ms y p95 0,141 ms; consulta COUNT(users) mediana 0,033 ms. Son ejecuciones distintas de la prueba HTTP; no deben restarse para calcular otros tiempos.

Inspecci?n: database() reutiliza PDO dentro de cada request. initialize_database() inicia transacci?n, crea tabla IF NOT EXISTS, toma advisory lock en PostgreSQL y hace 22 consultas de versi?n. Es candidato a medir en PostgreSQL; no se optimiz? por su peque?o coste observado en SQLite. OFF tiene timeout 8 s; Geoapify 8 s y Photon 7 s. Estos son l?mites, no latencias medidas.

Siguiente etapa: medir conexi?n/migraciones/consultas/procesamiento/total en Render con telemetr?a interna opcional sin SQL, par?metros ni datos personales. Comparar primera carga y peticiones calientes, cat?logo DB/API y peticiones duplicadas. Usar PostgreSQL aislado para probar cualquier cambio de migraciones. A?n faltan desglose interno por endpoint, conteo total de consultas y tiempos de renderizado. No hay ?ndices ni migraciones nuevas ni porcentajes de mejora.

Reproducir: php tests/performance-database.php; node tests/performance-pages.cjs (Chrome/Playwright y PHP locales, puerto 18851). Ambas usan fixtures temporales, no DATABASE_URL de producci?n.
