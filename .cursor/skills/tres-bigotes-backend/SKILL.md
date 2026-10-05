---
name: tres-bigotes-backend
description: >-
  Implementa la API PHP de Tres Bigotes: autenticación, permisos, citas,
  ficha clínica, comisiones, inventario y caja. Úsala al crear endpoints,
  validar reglas o acceder a MySQL con PDO. No crea pantallas ni archivos SQL
  de esquema.
---

# Backend Tres Bigotes

Solo `api/`, `config/`, `controllers/`, `models/` y `public/`. Expone JSON. Un archivo PHP no contiene HTML ni fragmentos de página. No renderiza React ni Bootstrap.

El esquema se lee, no se inventa. Tablas y columnas salen de [../tres-bigotes-mysql/modelo.md](../tres-bigotes-mysql/modelo.md). Reglas de cálculo en [reglas.md](reglas.md).

## Forma

```
public/index.php     único punto de entrada HTTP
api/                 router, petición, respuesta y rutas
controllers/         uno por módulo
models/              consultas de cada tabla
config/              .env y conexión PDO, fuera de git el .env real
```

- PHP 8, PDO, consultas preparadas
- Cada respuesta es JSON con `Content-Type: application/json`
- Error de negocio: `422` y `{ "error": "..." }`. Sin permiso: `403`. Sin sesión: `401`
- Mutaciones de dinero o stock abren una transacción y confirman o revierten entera

## Sesión y sede

- Login devuelve un token opaco. En base se guarda solo su hash
- Toda ruta, salvo login, exige `Authorization: Bearer`
- El establecimiento sale del usuario autenticado. Nunca del cuerpo libre si contradice la sesión
- Un `id` de otra sede responde `404`

## Qué no hace esta capa

- No ejecuta `CREATE`, `ALTER` ni `DROP`
- No calcula una comisión distinta a la de [reglas.md](reglas.md)
- No confía en totales enviados por el navegador: precio, stock y comisión se leen de MySQL

## Documentación

Al empezar, lee [../../../docs/contexto.md](../../../docs/contexto.md) y [../../../docs/backend.md](../../../docs/backend.md).

Al terminar, actualiza `docs/backend.md`: endpoints tocados, qué quedó hecho y qué falta. No pegues PHP. Si cambia una regla de cálculo, actualiza [reglas.md](reglas.md).
