# Dominio Tres Bigotes

## Multisede

Cada sucursal es un `establecimiento`. Agenda, clientes, inventario, caja y comisiones no se comparten entre establecimientos. Toda consulta operativa filtra por el establecimiento de la sesión.

## Roles

| Rol | Hace |
|---|---|
| `administrador` | Configura servicios, personal, comisiones e inventario, y opera la caja de su sede |
| `recepcion` | Agenda citas, cobra, abre y cierra caja, vende productos |
| `colaborador` | Ve su agenda, atiende la cita y escribe la ficha de la visita |

La interfaz oculta lo que el rol no usa. La API rechaza la acción igual si la pantalla se fuerza.

## Módulos

### Agendamiento

- Profesionales sin cupo máximo de licencias.
- Vista por día, por semana y por colaborador.
- Una cita guarda cliente, colaborador, uno o más servicios, inicio y fin.
- Estados: `pendiente`, `en_proceso`, `completada`, `cancelada`.
- Recordatorio al cliente y al profesional antes de la cita.
- El cliente reserva en `/reservar` sin cuenta. Deja teléfono, nombre y correo. Si el teléfono ya está en la sede, se usa esa ficha.

### Hoja clínica

- Ficha por cliente: preferencias, cortes habituales, fórmulas.
- Cada visita tiene anotaciones del profesional asignado.
- La ficha cuelga del cliente y, si nace de una cita, referencia esa cita.

### Colaboradores y comisiones

- Alta y baja de colaboradores. Un colaborador puede tener usuario para entrar al sistema.
- Comisión por tipo de servicio.
- Regla escalonada: al superar N servicios en el día o en la semana, aplica otro porcentaje.
- Liquidación por colaborador y rango de fechas, calculada en el backend, no en el navegador.

### Inventario

- Producto `insumo` (uso interno) o `venta` (se cobra).
- Stock mínimo y alerta cuando el saldo baja de ese mínimo.
- Cada entrada o salida es un movimiento. El saldo se actualiza en la misma transacción.
- Una venta de producto descuenta stock en el acto.

### Caja

- No hay venta con caja cerrada. Cada turno exige saldo inicial.
- Medios: `nequi`, `daviplata`, `qr`.
- Una cita `completada` se cobra una sola vez, con el precio aplicado de cada servicio. Puede ir en la misma venta que productos.
- Al cerrar, el sistema compara lo vendido por medio contra lo reportado y guarda la diferencia.
- El historial de cierres queda consultable con su desglose.
