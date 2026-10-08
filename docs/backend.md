# Backend

Única entrada HTTP: `public/index.php`. Las rutas viven en `api/rutas.php`, cada módulo en `controllers/` y las consultas en `models/`. El XAMPP de esta máquina corre PHP 8.0, así que el código no usa sintaxis de 8.1.

| Método y ruta | Acceso | Hace |
|---|---|---|
| `GET /api/salud` | público | Dice si el proceso vive y si MySQL responde |
| `POST /api/sesion` | público | Login. Cuerpo: `correo`, `clave` |
| `GET /api/sesion` | token | Devuelve el usuario de la sesión |
| `DELETE /api/sesion` | token | Cierra esa sesión |
| `GET /api/sedes` | permiso `sedes` | Catálogo de sedes. Marca cuál es la de la sesión |
| `POST /api/sedes` | permiso `sedes` | Crea la sede y su primer administrador en la misma operación |
| `PUT /api/sedes/{id}` | permiso `sedes` | Nombre, dirección, teléfono y baja. No baja la sede de la sesión |
| `GET /api/usuarios` | permiso `usuarios` | Usuarios de su sede y los roles que se les pueden asignar |
| `POST /api/usuarios` | permiso `usuarios` | Alta en su sede con cualquier rol existente |
| `PUT /api/usuarios/{id}` | permiso `usuarios` | Nombre, correo, rol, baja y clave opcional |
| `GET /api/roles` | permiso `roles` | Roles, sus permisos y el catálogo para marcarlos |
| `POST /api/roles` | permiso `roles` | Crea un rol con nombre y permisos |
| `PUT /api/roles/{id}` | permiso `roles` | Cambia el nombre y los permisos. El administrador conserva sedes, usuarios y roles |
| `DELETE /api/roles/{id}` | permiso `roles` | Borra un rol que no es del sistema y no tiene usuarios |
| `GET /api/colaboradores` | administrador | Lista el personal de su sede |
| `GET /api/colaboradores/{id}` | administrador | Un colaborador de su sede. Otra sede o un id inexistente: `404` |
| `POST /api/colaboradores` | administrador | Alta. Cuerpo: `nombre`, `telefono`, `fecha_ingreso`, `correo`, `clave` |
| `PUT /api/colaboradores/{id}` | administrador | Perfil y baja. El mismo cuerpo, más `activo` |
| `GET /api/productos` | administrador, recepción | Lista el inventario de su sede, con `alerta` si el stock no supera el mínimo |
| `POST /api/productos` | administrador | Alta. `precio_compra` obligatorio en insumo y venta. `precio_venta` solo en venta. `stock_inicial` queda como entrada |
| `PUT /api/productos/{id}` | administrador | Nombre, tipo, precio de compra, precio de venta, mínimo y baja. El stock no se edita aquí |
| `POST /api/productos/{id}/movimientos` | administrador | Entrada, salida o ajuste. Si la salida no alcanza, no cambia el stock |
| `GET /api/caja` | administrador, recepción | Turno abierto, productos de venta, citas completadas sin cobrar, medios y cierres |
| `POST /api/caja/turno` | administrador, recepción | Abre la caja con `saldo_inicial`. Si ya hay turno abierto, `422` |
| `POST /api/caja/ventas` | administrador, recepción | Cobra productos y, si se envía `cita_id`, los servicios de una cita completada al precio aplicado. Una cita no se cobra dos veces. El stock del producto baja en la misma operación. Medios: `nequi`, `daviplata`, `qr` |
| `POST /api/caja/cierre` | administrador, recepción | Reporta los tres medios. La diferencia la calcula el servidor y el turno queda cerrado |
| `GET /api/servicios` | administrador, recepción | Catálogo de la sede. Recepción solo ve activos |
| `POST /api/servicios` | administrador | Alta de servicio: nombre, categoría, duración, precio y, si viene, la receta de insumos |
| `PUT /api/servicios/{id}` | administrador | Edita el servicio, su categoría, su baja y, si el cuerpo trae `insumos`, la receta. Cada línea es `producto_id` y `cantidad` de un insumo activo. Sin esa clave, la receta no cambia |
| `GET /api/clientes?q=` | administrador, recepción | Busca clientes de la sede por nombre o teléfono |
| `GET /api/citas?desde=&hasta=` | administrador, recepción, colaborador | Citas del rango. El colaborador solo ve las suyas. `colaborador_id` filtra a los otros roles |
| `POST /api/citas` | administrador, recepción | Reserva. El fin y el precio salen de MySQL. No permite solape del profesional |
| `PUT /api/citas/{id}/estado` | administrador, recepción, colaborador | Cambia el estado permitido. El colaborador no cancela y solo toca la suya. Al completar, descuenta los insumos de los servicios en la misma operación. Si no hay stock, responde `422` y la cita sigue en proceso |
| `GET /api/reserva` | público | Servicios activos, con su categoría, profesionales con foto, reseñas y nombre de la sede |
| `POST /api/colaboradores/{id}/foto` | administrador | Foto JPG, PNG o WebP del profesional, hasta 2 MB |
| `GET /api/resenas` | administrador | Reseñas de la carta |
| `POST /api/resenas` | administrador | Publica una reseña: autor, texto y calificación de 1 a 5 |
| `DELETE /api/resenas/{id}` | administrador | Quita esa reseña de la sede |
| `GET /api/reserva/cliente?telefono=` | público | Si el teléfono ya existe, devuelve nombre y correo. Si no, `cliente` nulo |
| `GET /api/reserva/horarios` | público | Horarios libres de 08:00 a 20:00 según profesional, día y servicios. Sin servicios, muestra huecos de 30 minutos |
| `POST /api/reserva` | público | Crea la cita sin sesión. Reutiliza la ficha del teléfono y guarda nombre y correo |
| `GET /api/clinica/clientes?q=` | administrador, recepción, colaborador | Busca clientes. El colaborador solo ve los de sus citas |
| `GET /api/clinica/clientes/{id}` | administrador, recepción, colaborador | Ficha, notas y, si es el profesional, visitas sin nota |
| `PUT /api/clinica/clientes/{id}` | administrador, colaborador | Guarda preferencias, cortes habituales y fórmulas. Recepción recibe `403` |
| `POST /api/clinica/clientes/{id}/notas` | colaborador | Nota de una cita propia no cancelada. Una cita no admite dos notas |
| `GET /api/comisiones` | administrador, colaborador | Reglas y liquidaciones. El colaborador solo ve las suyas |
| `POST /api/comisiones/reglas` | administrador | Porcentaje base o tramo por día o semana |
| `PUT /api/comisiones/reglas/{id}` | administrador | Edita la regla |
| `DELETE /api/comisiones/reglas/{id}` | administrador | Quita la regla |
| `POST /api/comisiones/liquidaciones` | administrador | Calcula y guarda. El mismo rango devuelve la liquidación ya guardada |
| `GET /api/comisiones/liquidaciones/{id}` | administrador, colaborador | Detalle. El colaborador solo abre la suya |
| `DELETE /api/comisiones/liquidaciones/{id}` | administrador | Anula la liquidación para poder calcularla de nuevo |

La baja no borra la fila: `activo` pasa a falso y, si tiene usuario, ese usuario tampoco puede entrar. Un correo repetido responde `422`. Crear acceso exige clave de al menos 8 caracteres. Otro rol recibe `403`.

Errores: `401` sesión inválida, `403` sin permiso de rol, `422` cuerpo inválido, `500` fallo interno sin detalle de SQL.

Todas las carpetas de módulo ya tienen rutas.

## Estado — 8 de octubre de 2026

Hecho: sedes y usuarios sobre las tablas que ya existían. Crear una sede crea su administrador. Una sede de baja no deja entrar. El usuario no se baja a sí mismo y la sede no se queda sin alguien que pueda administrar usuarios. Los permisos de cada rol salen de `rol_permiso`. Se pueden crear roles y marcar permisos. El menú y cada acción usan esa lista.

## Estado — 6 de octubre de 2026

Hecho: completar una cita registra una salida por cada insumo de sus servicios. El administrador guarda esa receta al crear o editar el servicio. Si el stock no alcanza, la cita no pasa a completada. Alta y edición de servicio exigen categoría, y el catálogo público la devuelve.

Falta: nada de este corte. El precio y el stock se leen en MySQL.

## Estado — 5 de octubre de 2026

Hecho: configuración por `.env`, PDO, enrutador, CORS hacia `http://localhost:5173`, sesión, colaboradores, inventario, caja, agenda, reserva pública, ficha clínica y comisiones. La caja cobra una cita completada una sola vez, al precio aplicado, y puede sumar productos en la misma venta. La liquidación usa ese mismo precio y redondea a pesos. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta, en ese corte: descontar insumos al completar una cita. Quedó resuelto el 6 de octubre.
