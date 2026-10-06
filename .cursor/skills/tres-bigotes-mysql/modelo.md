# Modelo relacional

Orden de creación: catálogos de sede, personas, agenda, dinero, inventario.

## Sede y acceso

`establecimiento`: `id`, `nombre`, `direccion`, `telefono`, `activo`.

`rol`: `id`, `codigo` único (`administrador`, `recepcion`, `colaborador`).

`usuario`: `id`, `establecimiento_id`, `rol_id`, `nombre`, `correo` único, `password_hash`, `activo`.

`sesion`: `id`, `usuario_id`, `token_hash` único, `expira_en`, `creado_en`.

`colaborador`: `id`, `establecimiento_id`, `usuario_id` nulo y único, `nombre`, `foto` nula, `telefono`, `activo`, `fecha_ingreso` (día, tipo `DATE`).

`resena`: `id`, `establecimiento_id`, `autor`, `texto`, `calificacion` de 1 a 5, `creado_en`.

## Clientes y ficha

`cliente`: `id`, `establecimiento_id`, `nombre`, `telefono`, `correo`, `creado_en`. Único (`establecimiento_id`, `telefono`).

`ficha_cliente`: `id`, `cliente_id` único, `preferencias`, `cortes_habituales`, `formulas`.

`nota_visita`: `id`, `cliente_id`, `cita_id` nulo, `colaborador_id`, `observacion`, `creado_en`.

## Agenda

`servicio`: `id`, `establecimiento_id`, `nombre`, `categoria`, `duracion_minutos`, `precio`, `activo`.

`servicio_insumo`: `id`, `servicio_id`, `producto_id`, `cantidad` positiva. Único (`servicio_id`, `producto_id`). Cuelga de `servicio`. El producto es un insumo de la misma sede.

`cita`: `id`, `establecimiento_id`, `cliente_id`, `colaborador_id`, `inicio`, `fin`, `estado` (`pendiente`, `en_proceso`, `completada`, `cancelada`), `notas`.

`cita_servicio`: `id`, `cita_id`, `servicio_id`, `precio_aplicado`. Único (`cita_id`, `servicio_id`).

`aviso_cita`: `id`, `cita_id`, `destinatario` (`cliente`, `colaborador`), `programado_para`, `estado` (`pendiente`, `enviado`, `fallido`).

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

`pago`: `id`, `venta_id`, `medio` (`nequi`, `daviplata`, `qr`), `monto`.

`arqueo_linea`: `id`, `turno_caja_id`, `medio` (`nequi`, `daviplata`, `qr`), `monto_sistema`, `monto_reportado`, `diferencia`. Único (`turno_caja_id`, `medio`).
