# Base de datos

Base `tres_bigotes`, charset `utf8mb4`, motor InnoDB. El dibujo de tablas está en el skill `tres-bigotes-mysql`, archivo `modelo.md`.

Migraciones aplicadas: `database/migrations/001_inicial.sql`, `002_precio_compra.sql`, `003_medios_caja.sql`, `004_servicio_insumo.sql`, `005_categoria_servicio.sql`, `006_equipo_resena.sql`, `007_roles_permisos.sql`, `008_nombre_3_bigotes.sql`, `009_descripcion_servicio.sql`, `010_medios_y_ajustes_caja.sql`, `011_resena_cliente.sql`, `012_horarios_y_bloqueos.sql`, `013_alcance_usuario.sql`, `014_orden_servicio.sql`, `015_medios_efectivo_digital.sql` y `016_indice_aviso_envio.sql`. Semilla de desarrollo, una sola vez sobre una base vacía: `database/seeds/desarrollo.sql`.

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
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\008_nombre_3_bigotes.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\009_descripcion_servicio.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\010_medios_y_ajustes_caja.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\011_resena_cliente.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\012_horarios_y_bloqueos.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\013_alcance_usuario.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\014_orden_servicio.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\015_medios_efectivo_digital.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\016_indice_aviso_envio.sql
```

## Estado — 5 de noviembre de 2026 (índice avisos)

Hecho: `016_indice_aviso_envio.sql` agrega `idx_aviso_envio (estado, programado_para)` para el cron de recordatorios. Aplicada en `3bigotesbarberie.com`. Pendiente aplicar en local si MySQL no estaba arriba.

## Estado — 4 de noviembre de 2026 (medios efectivo / digital)

Hecho: `015_medios_efectivo_digital.sql` deja `pago.medio`, `arqueo_linea.medio` y `movimiento_caja.medio` en `efectivo` o `digital`. Consolida filas históricas de Nequi, QR, Daviplata y otros medios digitales. Aplicada en local y en `3bigotesbarberie.com`.

## Estado — 30 de octubre de 2026 (orden de servicios)

Hecho: `014_orden_servicio.sql` agrega `servicio.orden` y deja el catálogo en: corte → ritual barba → cejas → spa facial → barba express → combo. Listados ordenan por `orden`. Aplicada en `3bigotesbarberie.com`.

## Estado — 23 de octubre de 2026 (alcance usuario / sede)

Hecho: `013_alcance_usuario.sql` deja `usuario.establecimiento_id` nulo para el super, crea el rol `superadministrador` con todos los permisos, quita `sedes` al `administrador`, añade `sesion.establecimiento_activo_id` y siembra `super@tresbigotes.local`. Aplicada en local y en `3bigotesbarberie.com`.

## Estado — 23 de octubre de 2026 (horarios)

Hecho: `012_horarios_y_bloqueos.sql` crea `horario_trabajo` y `bloqueo_agenda`, permiso `horarios` (admin y recepción) y siembra lunes–sábado 08:00–20:00 por sede. Aplicada en local y en `3bigotesbarberie.com`.

Una sede no puede tener dos turnos de caja abiertos: `turno_caja.abierto_clave` es única y solo se llena cuando el estado es `abierto`. `fecha_ingreso` del colaborador es un día (`DATE`). El resto de fechas de negocio son `DATETIME`.

## Usuarios de desarrollo

Clave: `tresbigotes`. No son personas reales.

| Correo | Rol | Sede |
|---|---|---|
| super@tresbigotes.local | superadministrador | ninguna (sistema) |
| admin@tresbigotes.local | administrador | sede 1 |
| recepcion@tresbigotes.local | recepcion | sede 1 |
| barbero@tresbigotes.local | colaborador | sede 1 |

El barbero tiene fila en `colaborador` ligada a su usuario. El super nace con la migración `013` (no con la semilla antigua).

## Estado — 21 de octubre de 2026 (reseña cliente)

Hecho: `011_resena_cliente.sql` agrega a `resena` las columnas `correo`, `origen` (`admin`/`cliente`) y `publicada`. Aplicada en local y en `3bigotesbarberie.com`.

## Estado — 21 de octubre de 2026 (caja)

Hecho: `010_medios_y_ajustes_caja.sql` agrega `efectivo` a `pago.medio` y `arqueo_linea.medio` (sigue admitiendo `daviplata` por historia) y crea `movimiento_caja` (`ingreso`/`egreso`, medio `nequi`/`qr`/`efectivo`, motivo obligatorio). Aplicada en local y en `3bigotesbarberie.com`.

## Estado — 21 de octubre de 2026

Hecho: `servicio.descripcion` guarda hasta 500 caracteres y puede quedar vacía. Los servicios que ya existían siguen sin texto. Aplicada en local y en la base de `3bigotesbarberie.com`.

## Estado — 8 de octubre de 2026

Hecho: `rol` gana `nombre` y `sistema`. `permiso` es el catálogo y `rol_permiso` une cada rol con lo que puede hacer. Los tres roles de sistema quedan con los permisos que ya tenían. `008_nombre_3_bigotes.sql` deja el nombre de las sedes escrito con el número 3.

## Estado — 6 de octubre de 2026

Hecho: `servicio_insumo` relaciona un servicio con un insumo y la cantidad que consume cada vez que ese servicio se completa. Un servicio no repite el mismo producto. `servicio.categoria` agrupa el catálogo de la carta pública. `colaborador.foto` guarda la imagen del equipo y `resena` guarda las opiniones de la carta.

## Estado — 5 de octubre de 2026

Hecho: las 22 tablas del modelo, claves foráneas, índices de agenda y caja, la semilla de sede, roles y usuarios, `producto.precio_compra`, y los medios de pago `nequi`, `daviplata` y `qr`.

La semilla no carga servicios, productos ni citas. Esos datos los crean los módulos. El detalle del día está en `docs/avance-2026-10-05.md`.
