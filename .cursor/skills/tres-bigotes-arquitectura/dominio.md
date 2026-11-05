# Dominio 3 Bigotes

El nombre que se escribe siempre lleva el número: 3 Bigotes. Los correos, la carpeta y el código interno conservan el identificador técnico.

## Multisede

Cada sucursal es un `establecimiento`. Agenda, clientes, inventario, caja y comisiones no se comparten entre establecimientos. Toda consulta operativa filtra por el establecimiento de la sesión (o por la sede de trabajo del super administrador).

El `superadministrador` tiene alcance de sistema: ve todas las sedes, crea una sede nueva junto con su primer administrador y asigna cada usuario a una sede. El `administrador` de una sede solo ve y opera lo de la suya. La carta pública usa la sede activa más antigua. Una sede de baja no deja entrar a sus usuarios de sede.

## Roles

| Rol | Hace |
|---|---|
| `superadministrador` | Ve todas las sedes, crea sedes, asigna usuarios a cualquier sede, roles globales; para agenda/caja elige una sede de trabajo |
| `administrador` | Usuarios, roles, servicios, personal, comisiones e inventario, y opera la caja de su sede |
| `recepcion` | Agenda citas, cobra, abre y cierra caja, vende productos |
| `colaborador` | Ve su agenda, atiende la cita y escribe la ficha de la visita |

Esos cuatro no se borran. Se pueden crear otros roles y marcar, uno por uno, los permisos del catálogo: sedes, usuarios, roles, colaboradores, servicios, agenda completa o propia, horarios, ficha completa, de consulta o propia, comisiones completas o propias, inventario completo o de consulta, y caja. La interfaz oculta lo que el rol no tiene. La API rechaza la acción igual si la pantalla se fuerza. Un permiso propio solo alcanza las citas del profesional ligado a ese usuario.

### Sedes y usuarios

- Solo el super administrador crea una sede con nombre, dirección, teléfono y el administrador que va a entrar en ella. Esa cuenta queda atada a la sede nueva (`establecimiento_id`).
- El super administrador no lleva sede (`establecimiento_id` nulo). En el menú elige la sede de trabajo para módulos operativos.
- En Usuarios, el super asigna sede y rol a cada cuenta; el administrador de sede solo ve y edita las cuentas de la suya (sin selector de sede). En Roles se marcan permisos. El acceso del profesional también se puede crear en Colaboradores.
- No puede darse de baja a sí mismo ni dejar la sede sin un usuario activo con permiso `usuarios`.

## Módulos

### Agendamiento

- Profesionales sin cupo máximo de licencias.
- Vista por día, por semana y por colaborador.
- El administrador arma el catálogo en la pantalla Servicios: nombre, categoría, duración, precio, activo y, si aplica, los insumos que consume cada cita.
- Una cita guarda cliente, colaborador, uno o más servicios, inicio y fin.
- Estados: `pendiente`, `en_proceso`, `completada`, `cancelada`.
- Horario semanal de la sede (tramos por día) y bloqueos (almuerzo recurrente, descanso o cierre de una fecha). La reserva pública y la agenda solo ofrecen huecos dentro del horario y fuera de bloqueos.
- Confirmación por correo al reservar y recordatorio dos horas antes al cliente y al profesional (`aviso_cita` + cron SMTP desde `atencion@3bigotesbarberie.com`).
- Recordatorio WhatsApp asistido desde Agenda (permiso `agenda`): botón del día abre chat por chat con mensaje listo vía `wa.me`; recepción confirma el envío. Sin API de Meta ni costo por mensaje.
- La portada pública muestra el equipo con foto, las reseñas de la sede y el catálogo agrupado por categoría.
- El cliente reserva en el inicio `/`, sin cuenta. Elige uno o más servicios (en el orden del catálogo), luego el profesional y el horario; al final deja nombre, teléfono y correo. Si el teléfono ya está en la sede, se usa esa ficha.

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
- El administrador indica, por servicio, qué insumos consume y en qué cantidad. Completar la cita registra esa salida. Si no hay stock, la cita no queda completada.

### Caja

- No hay venta con caja cerrada. Cada turno exige saldo inicial en efectivo.
- Medios: `efectivo`, `digital`.
- En Caja se ven las citas del día sin venta. Al cobrar, si aún no está `completada`, se completa primero. La cita se cobra una sola vez; en el cobro se marcan los servicios a incluir y cada uno usa su `precio_aplicado`. Puede ir en la misma venta que productos.
- También se cobra sin cita: se elige el profesional, se marcan servicios del catálogo (precio vigente) y, si aplica, productos.
- Solo el administrador registra ajustes (`ingreso` o `egreso`) del turno abierto, con medio, monto y motivo. No se borran.
- Al cerrar, el sistema compara por medio (pagos ± ajustes; en efectivo también la base) contra lo reportado y guarda la diferencia.
- El historial de cierres queda consultable con filtro de fechas y su desglose.
