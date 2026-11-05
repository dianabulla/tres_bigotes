# Reglas de negocio

## Sedes y cuentas

- Crear una sede guarda el establecimiento y su primer administrador en la misma operación. Si el correo ya existe, no queda la sede. Solo quien tiene permiso `sedes` (el `superadministrador`) lo hace.
- Alcance: `superadministrador` → `sistema` (`establecimiento_id` nulo); cualquier otro rol → `sede` (sede obligatoria). `Acceso` resuelve el alcance en cada petición.
- El catálogo de sedes lo ve el super. Agenda, caja y clientes filtran por la sede de la cuenta o, si es super, por `sesion.establecimiento_activo_id` (`PUT /api/sesion/sede`).
- No se da de baja la sede de trabajo actual. Una sede inactiva no abre sesión a sus usuarios de sede.
- En Usuarios el super envía `establecimiento_id` al crear o editar (salvo otro super). El administrador de sede ignora ese campo y usa siempre la suya. Solo el super puede asignar el rol `superadministrador`.
- Un rol nuevo guarda nombre y permisos en la misma operación. Hace falta al menos un permiso. El super conserva sedes, usuarios y roles; el administrador conserva usuarios y roles. Un rol de sistema no se borra. Un rol con usuarios asignados tampoco.
- Quien edita su propio rol no puede quitarse el permiso de administrar roles.
- Quien está dentro no cambia su propio rol ni se da de baja. La sede conserva al menos un usuario activo con permiso `usuarios`.
- La clave nueva, si se envía, tiene al menos 8 caracteres. Un correo repetido responde `422`.

## Citas

- Servicios y colaborador deben ser del mismo establecimiento y estar activos.
- `fin` es `inicio` más la suma de `duracion_minutos` de los servicios.
- Solape: el mismo colaborador no tiene dos citas activas (`pendiente` o `en_proceso`) que se crucen.
- Transiciones: `pendiente` → `en_proceso` | `cancelada`; `en_proceso` → `completada` | `cancelada`. `completada` y `cancelada` no vuelven atrás.
- Al pasar a `completada`, cada servicio descuenta sus insumos. Si dos servicios de la cita usan el mismo insumo, las cantidades se suman y queda una sola salida. Si el stock no alcanza, la cita no cambia de estado. El alta de la visita clínica la confirma el colaborador; no se inventan notas.
- La reserva pública no pide sesión. En la interfaz el orden es servicio, barbero, día y horas libres de ese profesional, y al final nombre, teléfono y correo. Nombre, teléfono y correo son obligatorios al confirmar. El teléfono es la clave de la ficha: si ya existe en la sede, se reutiliza y se actualizan nombre y correo. La categoría del servicio es obligatoria.
- El cliente consulta sus citas pendientes futuras con teléfono y correo. Solo puede cancelar o reagendar si la cita sigue `pendiente` y aún no empezó. Reagendar mantiene los servicios, cambia profesional y horario, valida solape (excepto la propia cita) y reprograma avisos pendientes.
- Los huecos públicos salen del `horario_trabajo` de la sede (o del profesional si tiene tramos propios), cada 30 minutos, y el servicio debe terminar dentro del tramo. Los `bloqueo_agenda` (recurrentes o por fecha; sede o profesional) no se ofrecen. Solo se ofrecen desde ahora y hasta 60 días. El precio y el fin se calculan en el servidor.
- Gestionar horario y bloqueos exige permiso `horarios`. Una sede nueva nace con lunes–sábado 08:00–20:00; el domingo cerrado. Un día sin tramos queda cerrado.

## Ficha

- Cada cliente tiene una ficha: preferencias, cortes habituales y fórmulas. La guarda el administrador o el profesional que ya atendió a ese cliente. Recepción solo consulta.
- La nota de una visita la escribe el colaborador de esa cita. No se crea sola al completar ni al reservar.
- Una cita que no esté cancelada admite una sola nota.

## Recordatorios

El recordatorio WhatsApp desde Agenda es solo interfaz: abre `wa.me` con texto listo; no hay endpoint ni envío automático por WhatsApp.

Al reservar (pública o interna) se envía de inmediato la confirmación por correo al cliente y, si el colaborador tiene acceso, también a él. Remitente: `atencion@3bigotesbarberie.com` (SMTP Hostinger vía `MAIL_*` en `.env`). Un fallo de correo no bloquea la reserva.

Además se guardan dos avisos en `aviso_cita` (cliente y colaborador), programados dos horas antes del inicio. Si falta menos de dos horas, quedan para el momento de la reserva. En producción el cron de hPanel ejecuta `scripts/enviar-avisos.php` cada pocos minutos; también sirve `GET|POST /api/avisos/enviar` con `MAIL_CRON_CLAVE`. Lee los `pendiente` vencidos y envía el recordatorio. Sin correo del destinatario o con cita ya no pendiente, el aviso pasa a `fallido` (cuenta como omitido). Al cancelar se borran los avisos pendientes.

## Reseñas

- El administrador puede publicar una reseña directa en la carta (`origen = admin`, `publicada = 1`).
- El cliente envía reseña pública con nombre, correo, texto y nota 1–5; queda `origen = cliente` y `publicada = 0` hasta que el administrador la apruebe.
- La carta del inicio solo lista reseñas publicadas.
- Falta: encuesta de satisfacción por correo tras una cita completada (reutilizar el correo de la reseña o del cliente).

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
- Los medios de una venta son `efectivo` y `digital`. No hay Nequi, QR, tarjeta, Daviplata ni Bancolombia como medios separados.
- Cada venta exige turno abierto y al menos un pago. La suma de pagos iguala el total de líneas. El total se calcula en el servidor con precios vigentes.
- Una cita `completada` se cobra una sola vez. Se pueden marcar solo algunos de sus servicios; cada uno usa `precio_aplicado`. Si no se envía la lista, van todos. Puede ir en la misma venta que productos. Una cita pendiente no se cobra.
- Sin cita: el cuerpo lleva `servicios` (ids del catálogo) y `colaborador_id` activo. El precio de cada servicio es el vigente en MySQL. Puede ir con productos.
- `monto_sistema` por medio = pagos del turno ± `movimiento_caja`. En `efectivo` también suma `saldo_inicial`.
- Solo el rol `administrador` crea `movimiento_caja` (`ingreso` o `egreso`) con motivo. Recepción no. El movimiento no se borra ni se edita.
- Cerrar guarda, por cada medio, `monto_sistema`, `monto_reportado` y `diferencia` (`reportado - sistema`). El turno pasa a `cerrado` y no acepta más ventas.
- Un cierre no se edita.
