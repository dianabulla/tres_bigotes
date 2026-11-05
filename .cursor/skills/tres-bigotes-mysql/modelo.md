# Modelo relacional

Orden de creación: catálogos de sede, personas, agenda, dinero, inventario.

## Sede y acceso

`establecimiento`: `id`, `nombre`, `direccion`, `telefono`, `activo`.

`rol`: `id`, `codigo` único, `nombre`, `sistema`. Los de sistema son `superadministrador`, `administrador`, `recepcion` y `colaborador`. Se pueden crear otros.

`permiso`: `id`, `codigo` único, `titulo`, `grupo`. Catálogo fijo de lo que una pantalla permite hacer.

`rol_permiso`: `rol_id`, `permiso_id`. Un rol tiene varios permisos.

`usuario`: `id`, `establecimiento_id` nulo (solo `superadministrador`), `rol_id`, `nombre`, `correo` único, `password_hash`, `activo`. El resto de roles exige sede.

`sesion`: `id`, `usuario_id`, `establecimiento_activo_id` nulo (sede de trabajo del super), `token_hash` único, `expira_en`, `creado_en`.

`colaborador`: `id`, `establecimiento_id`, `usuario_id` nulo y único, `nombre`, `foto` nula, `telefono`, `activo`, `fecha_ingreso` (día, tipo `DATE`).

`resena`: `id`, `establecimiento_id`, `autor`, `correo` nulo, `texto`, `calificacion` de 1 a 5, `origen` (`admin`, `cliente`), `publicada`, `creado_en`. La carta pública solo muestra `publicada = 1`.

## Clientes y ficha

`cliente`: `id`, `establecimiento_id`, `nombre`, `telefono`, `correo`, `creado_en`. Único (`establecimiento_id`, `telefono`).

`ficha_cliente`: `id`, `cliente_id` único, `preferencias`, `cortes_habituales`, `formulas`.

`nota_visita`: `id`, `cliente_id`, `cita_id` nulo, `colaborador_id`, `observacion`, `creado_en`.

## Agenda

`servicio`: `id`, `establecimiento_id`, `nombre`, `categoria`, `orden` (menor primero), `descripcion` nula, `duracion_minutos`, `precio`, `activo`.

`servicio_insumo`: `id`, `servicio_id`, `producto_id`, `cantidad` positiva. Único (`servicio_id`, `producto_id`). Cuelga de `servicio`. El producto es un insumo de la misma sede.

`cita`: `id`, `establecimiento_id`, `cliente_id`, `colaborador_id`, `inicio`, `fin`, `estado` (`pendiente`, `en_proceso`, `completada`, `cancelada`), `notas`.

`cita_servicio`: `id`, `cita_id`, `servicio_id`, `precio_aplicado`. Único (`cita_id`, `servicio_id`).

`aviso_cita`: `id`, `cita_id`, `destinatario` (`cliente`, `colaborador`), `programado_para`, `estado` (`pendiente`, `enviado`, `fallido`). Índice `idx_aviso_envio (estado, programado_para)` para el cron.

## Horarios

`horario_trabajo`: `id`, `establecimiento_id`, `colaborador_id` nulo, `dia_semana` 1–7 (lunes–domingo), `hora_inicio`, `hora_fin`. `colaborador_id` nulo es el horario de la sede. Varios tramos el mismo día permiten partir la jornada. Un día sin filas queda cerrado.

`bloqueo_agenda`: `id`, `establecimiento_id`, `colaborador_id` nulo, `titulo`, `dia_semana` nulo, `fecha` nulo, `hora_inicio` nulo, `hora_fin` nulo. O bien `fecha` (bloqueo puntual) o `dia_semana` (recurrente semanal). Horas nulas con `fecha` = día completo. `colaborador_id` nulo aplica a toda la sede.

## Comisiones

`regla_comision`: `id`, `establecimiento_id`, `servicio_id` nulo, `porcentaje`, `umbral_cantidad` nulo, `periodo` nulo (`dia`, `semana`). `servicio_id` nulo significa la regla general de la sede.

`liquidacion`: `id`, `establecimiento_id`, `colaborador_id`, `desde`, `hasta`, `total`, `creado_en`.

`liquidacion_linea`: `id`, `liquidacion_id`, `cita_servicio_id`, `base`, `porcentaje`, `monto`.

## Inventario

`producto`: `id`, `establecimiento_id`, `nombre`, `tipo` (`insumo`, `venta`), `precio_compra`, `precio_venta` nulo en insumo, `stock`, `stock_minimo`, `activo`.

`movimiento_inventario`: `id`, `producto_id`, `tipo` (`entrada`, `salida`, `venta`, `ajuste`), `cantidad` positiva, `venta_item_id` nulo, `usuario_id`, `motivo`, `creado_en`.

## Caja

`turno_caja`: `id`, `establecimiento_id`, `usuario_id`, `saldo_inicial`, `abierto_en`, `cerrado_en` nulo, `estado` (`abierto`, `cerrado`). `abierto_clave` es generada: vale `establecimiento_id` solo si el turno está abierto, y es única, así una sede no tiene dos cajas abiertas.

`venta`: `id`, `establecimiento_id`, `turno_caja_id`, `cliente_id` nulo, `cita_id` nulo, `total`, `creado_en`.

`venta_item`: `id`, `venta_id`, `tipo` (`servicio`, `producto`), `servicio_id` nulo, `producto_id` nulo, `colaborador_id` nulo, `cantidad`, `precio_unitario`, `subtotal`.

`pago`: `id`, `venta_id`, `medio` (`efectivo`, `digital`), `monto`.

`arqueo_linea`: `id`, `turno_caja_id`, `medio` (mismos valores que `pago`), `monto_sistema`, `monto_reportado`, `diferencia`. Único (`turno_caja_id`, `medio`).

`movimiento_caja`: `id`, `establecimiento_id`, `turno_caja_id`, `usuario_id`, `tipo` (`ingreso`, `egreso`), `medio` (`efectivo`, `digital`), `monto`, `motivo`, `creado_en`. Ajuste del turno abierto; no se borra.
