---
name: tres-bigotes-mysql
description: >-
  Diseña y migra la base MySQL relacional de 3 Bigotes, con InnoDB, claves
  foráneas y aislamiento por establecimiento. Úsala al crear tablas, relaciones,
  índices, migraciones o seeds. No escribe React, PHP de aplicación ni reglas
  calculadas en la interfaz.
---

# MySQL 3 Bigotes

Solo `database/`. El modelo relacional está en [modelo.md](modelo.md).

## Motor y estilo

- MySQL, motor InnoDB, charset `utf8mb4`
- Clave primaria `id` entera autonumérica
- Toda tabla operativa lleva `establecimiento_id` o cuelga de un padre que ya lo tiene, con `ON DELETE RESTRICT`
- Dinero en `DECIMAL(12,2)`. Porcentajes en `DECIMAL(5,2)`. Fechas de negocio en `DATETIME`
- Nombres de tablas y columnas en español, en singular: `cita`, `pago`, `producto`
- Estados como `ENUM` cerrados, los mismos textos del modelo

## Migraciones

```
database/
  migrations/001_inicial.sql
  seeds/desarrollo.sql
```

- Un cambio de esquema es un archivo nuevo, numerado. No se reescribe una migración ya aplicada
- El archivo crea tablas, claves e índices. No contiene PHP ni JavaScript
- Los seeds de desarrollo no son datos reales de clientes
- Índices para los filtros de siempre: establecimiento + fecha en `cita` y en `turno_caja`; establecimiento + estado en `cita`; `producto_id` en `movimiento_inventario`

## Integridad

- Una cita, su cliente, su colaborador y sus servicios comparten establecimiento. Eso se valida en la API y se refuerza con claves foráneas hacia filas de esa sede
- Precios cobrados se copian a la línea (`precio_aplicado`, `precio_unitario`). Cambiar el catálogo no reescribe historia
- No uses JSON para guardar líneas de venta, pagos ni comisiones. Cada línea es una fila

## Documentación

Al empezar, lee [../../../docs/contexto.md](../../../docs/contexto.md) y [../../../docs/base-de-datos.md](../../../docs/base-de-datos.md).

Al terminar, actualiza `docs/base-de-datos.md` y, si el esquema cambió, [modelo.md](modelo.md). Una migración ya aplicada no se reescribe: el cambio nuevo va en el archivo numerado siguiente.
