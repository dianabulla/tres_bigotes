# Reglas de negocio

## Sedes y cuentas

- Crear una sede guarda el establecimiento y su primer administrador en la misma operación. Si el correo ya existe, no queda la sede.
- El catálogo de sedes lo ve cualquier administrador. La agenda, la caja y los clientes siguen filtrados por la sede de la sesión.
- No se da de baja la sede con la que se entró. Una sede inactiva no abre sesión.
- En Usuarios se asigna cualquier rol existente. El rol `colaborador` también puede nacer en Colaboradores.
- Un rol nuevo guarda nombre y permisos en la misma operación. Hace falta al menos un permiso. El administrador de sistema conserva sedes, usuarios y roles. Un rol de sistema no se borra. Un rol con usuarios asignados tampoco.
- Quien edita su propio rol no puede quitarse el permiso de administrar roles.
- Quien está dentro no cambia su propio rol ni se da de baja. La sede conserva al menos un administrador activo.
- La clave nueva, si se envía, tiene al menos 8 caracteres. Un correo repetido responde `422`.

## Citas

- Servicios y colaborador deben ser del mismo establecimiento y estar activos.
- `fin` es `inicio` más la suma de `duracion_minutos` de los servicios.
- Solape: el mismo colaborador no tiene dos citas activas (`pendiente` o `en_proceso`) que se crucen.
- Transiciones: `pendiente` → `en_proceso` | `cancelada`; `en_proceso` → `completada` | `cancelada`. `completada` y `cancelada` no vuelven atrás.
- Al pasar a `completada`, cada servicio descuenta sus insumos. Si dos servicios de la cita usan el mismo insumo, las cantidades se suman y queda una sola salida. Si el stock no alcanza, la cita no cambia de estado. El alta de la visita clínica la confirma el colaborador; no se inventan notas.
- La reserva pública no pide sesión. El orden es nombre, teléfono y correo, después el servicio, y solo entonces los profesionales con un hueco ese día. El teléfono es la clave de la ficha: si ya existe en la sede, se reutiliza y se actualizan nombre y correo. La categoría del servicio es obligatoria.
- Los horarios públicos van de 08:00 a 20:00, cada 30 minutos, y el servicio debe terminar antes del cierre. Solo se ofrecen desde ahora y hasta 60 días. El precio y el fin se calculan en el servidor.

## Ficha

- Cada cliente tiene una ficha: preferencias, cortes habituales y fórmulas. La guarda el administrador o el profesional que ya atendió a ese cliente. Recepción solo consulta.
- La nota de una visita la escribe el colaborador de esa cita. No se crea sola al completar ni al reservar.
- Una cita que no esté cancelada admite una sola nota.

## Recordatorios

Al reservar se guardan dos avisos, uno para el cliente y otro para el colaborador, programados una hora antes del inicio. Si falta menos de una hora, quedan para el momento de la reserva. No hay canal externo todavía: el aviso queda `pendiente` y la reserva no se bloquea.

## Comisiones

Base de una línea: `precio_aplicado` del servicio en la cita completada, dentro del rango pedido.

1. Regla de porcentaje del servicio (o la general de la sede si el servicio no tiene regla propia).
2. Si en el periodo `dia` o `semana` el colaborador supera `umbral_cantidad` de servicios completados, aplica el porcentaje escalonado de esa regla en lugar del base.
3. El monto de la línea es `base * porcentaje / 100`, redondeado a pesos COP.
4. La liquidación persiste cabecera y líneas. Reabrir el mismo rango devuelve el cálculo guardado; no lo reescribe salvo que el administrador anule esa liquidación.
5. El umbral cuenta las líneas de servicio de citas `completada` de ese colaborador en el día o en la semana (lunes a domingo). Superar es más que `umbral_cantidad`. Si se supera, esas líneas usan el porcentaje del tramo más alto alcanzado.
6. Si el servicio tiene porcentaje propio, no hereda el tramo general. Si no tiene regla, usa la base y el tramo general de la sede.
7. Una línea de servicio entra en una sola liquidación.

Los productos de venta no generan comisión.

## Inventario

- Todo producto, insumo o venta, tiene `precio_compra` de cero en adelante. `precio_venta` solo existe en `venta`.
- `stock` no baja de cero. Si la salida no alcanza, la operación completa se revierte.
- Venta de producto `venta`: movimiento `venta` ligado a la línea, y `stock` baja en la misma transacción.
- `insumo` no se cobra en caja. Sale por consumo o ajuste.
- Alerta cuando `stock <= stock_minimo` después del movimiento.

## Caja

- Una sede tiene como máximo un turno `abierto`.
- Abrir exige `saldo_inicial >= 0` y usuario `recepcion` o `administrador`.
- Los medios de una venta son `nequi`, `daviplata` y `qr`. No hay tarjeta, efectivo ni Bancolombia.
- Cada venta exige turno abierto y al menos un pago. La suma de pagos iguala el total de líneas. El total se calcula en el servidor con precios vigentes.
- Una cita `completada` se cobra una sola vez. Cada servicio usa `precio_aplicado`. Puede ir en la misma venta que productos. Una cita pendiente no se cobra.
- Cerrar guarda, por cada medio, `monto_sistema`, `monto_reportado` y `diferencia` (`reportado - sistema`). El turno pasa a `cerrado` y no acepta más ventas.
- Un cierre no se edita. Una corrección es un movimiento nuevo autorizado por `administrador`, con motivo.
