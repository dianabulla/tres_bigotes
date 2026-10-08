# Dominio Tres Bigotes

## Multisede

Cada sucursal es un `establecimiento`. Agenda, clientes, inventario, caja y comisiones no se comparten entre establecimientos. Toda consulta operativa filtra por el establecimiento de la sesión.

El administrador ve el catálogo de sedes y crea una sede nueva junto con su primer administrador. Esa cuenta entra a su propia sede; desde la sede actual no se opera la agenda ni la caja de la otra. La carta pública usa la sede activa más antigua. Una sede de baja no deja entrar a sus usuarios.

## Roles

| Rol | Hace |
|---|---|
| `administrador` | Crea sedes, usuarios, roles, servicios, personal, comisiones e inventario, y opera la caja de su sede |
| `recepcion` | Agenda citas, cobra, abre y cierra caja, vende productos |
| `colaborador` | Ve su agenda, atiende la cita y escribe la ficha de la visita |

Esos tres no se borran. Se pueden crear otros roles y marcar, uno por uno, los permisos del catálogo: sedes, usuarios, roles, colaboradores, servicios, agenda completa o propia, ficha completa, de consulta o propia, comisiones completas o propias, inventario completo o de consulta, y caja. La interfaz oculta lo que el rol no tiene. La API rechaza la acción igual si la pantalla se fuerza. Un permiso propio solo alcanza las citas del profesional ligado a ese usuario.

### Sedes y usuarios

- El administrador crea una sede con nombre, dirección, teléfono y el administrador que va a entrar en ella.
- En Usuarios da de alta o de baja cuentas de su sede y les asigna cualquier rol. En Roles crea roles y marca sus permisos. El acceso del profesional también se puede crear en Colaboradores.
- No puede darse de baja a sí mismo ni dejar la sede sin un administrador activo.

## Módulos

### Agendamiento

- Profesionales sin cupo máximo de licencias.
- Vista por día, por semana y por colaborador.
- El administrador arma el catálogo en la pantalla Servicios: nombre, categoría, duración, precio, activo y, si aplica, los insumos que consume cada cita.
- Una cita guarda cliente, colaborador, uno o más servicios, inicio y fin.
- Estados: `pendiente`, `en_proceso`, `completada`, `cancelada`.
- Recordatorio al cliente y al profesional antes de la cita.
- La portada pública muestra el equipo con foto, las reseñas de la sede y el catálogo agrupado por categoría.
- El cliente reserva en el inicio `/`, sin cuenta. Primero deja nombre, teléfono y correo, luego elige el servicio y ahí ve los profesionales con hora libre ese día. Si el teléfono ya está en la sede, se usa esa ficha.

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

- No hay venta con caja cerrada. Cada turno exige saldo inicial.
- Medios: `nequi`, `daviplata`, `qr`.
- Una cita `completada` se cobra una sola vez, con el precio aplicado de cada servicio. Puede ir en la misma venta que productos.
- Al cerrar, el sistema compara lo vendido por medio contra lo reportado y guarda la diferencia.
- El historial de cierres queda consultable con su desglose.
