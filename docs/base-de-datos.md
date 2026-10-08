# Base de datos

Base `tres_bigotes`, charset `utf8mb4`, motor InnoDB. El dibujo de tablas está en el skill `tres-bigotes-mysql`, archivo `modelo.md`.

Migraciones aplicadas: `database/migrations/001_inicial.sql`, `002_precio_compra.sql`, `003_medios_caja.sql`, `004_servicio_insumo.sql`, `005_categoria_servicio.sql`, `006_equipo_resena.sql` y `007_roles_permisos.sql`. Semilla de desarrollo, una sola vez sobre una base vacía: `database/seeds/desarrollo.sql`.

En XAMPP:

```text
C:\xampp\mysql\bin\mysql.exe -u root < database\migrations\001_inicial.sql
C:\xampp\mysql\bin\mysql.exe -u root < database\seeds\desarrollo.sql
C:\xampp\mysql\bin\mysql.exe -u root tres_bigotes < database\migrations\002_precio_compra.sql
C:\xampp\mysql\bin\mysql.exe -u root tres_bigotes < database\migrations\003_medios_caja.sql
C:\xampp\mysql\bin\mysql.exe -u root tres_bigotes < database\migrations\004_servicio_insumo.sql
C:\xampp\mysql\bin\mysql.exe -u root tres_bigotes < database\migrations\005_categoria_servicio.sql
C:\xampp\mysql\bin\mysql.exe -u root tres_bigotes < database\migrations\006_equipo_resena.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\007_roles_permisos.sql
```

Una sede no puede tener dos turnos de caja abiertos: `turno_caja.abierto_clave` es única y solo se llena cuando el estado es `abierto`. `fecha_ingreso` del colaborador es un día (`DATE`). El resto de fechas de negocio son `DATETIME`.

## Usuarios de desarrollo

Clave de los tres: `tresbigotes`. No son personas reales.

| Correo | Rol |
|---|---|
| admin@tresbigotes.local | administrador |
| recepcion@tresbigotes.local | recepcion |
| barbero@tresbigotes.local | colaborador |

El barbero tiene fila en `colaborador` ligada a su usuario.

## Estado — 8 de octubre de 2026

Hecho: `rol` gana `nombre` y `sistema`. `permiso` es el catálogo y `rol_permiso` une cada rol con lo que puede hacer. Los tres roles de sistema quedan con los permisos que ya tenían.

## Estado — 6 de octubre de 2026

Hecho: `servicio_insumo` relaciona un servicio con un insumo y la cantidad que consume cada vez que ese servicio se completa. Un servicio no repite el mismo producto. `servicio.categoria` agrupa el catálogo de la carta pública. `colaborador.foto` guarda la imagen del equipo y `resena` guarda las opiniones de la carta.

## Estado — 5 de octubre de 2026

Hecho: las 22 tablas del modelo, claves foráneas, índices de agenda y caja, la semilla de sede, roles y usuarios, `producto.precio_compra`, y los medios de pago `nequi`, `daviplata` y `qr`.

La semilla no carga servicios, productos ni citas. Esos datos los crean los módulos. El detalle del día está en `docs/avance-2026-10-05.md`.
