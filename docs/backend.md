# Backend

Única entrada HTTP: `public/index.php`. Las rutas viven en `api/rutas.php`, cada módulo en `controllers/` y las consultas en `models/`. El XAMPP de esta máquina corre PHP 8.0, así que el código no usa sintaxis de 8.1.

| Método y ruta | Acceso | Hace |
|---|---|---|
| `GET /api/salud` | público | Dice si el proceso vive y si MySQL responde |
| `POST /api/sesion` | público | Login. Cuerpo: `correo`, `clave`. Devuelve `alcance` (`sistema` o `sede`) |
| `GET /api/sesion` | token | Devuelve el usuario de la sesión |
| `PUT /api/sesion/sede` | superadministrador | Elige o limpia la sede de trabajo (`establecimiento_id`) |
| `DELETE /api/sesion` | token | Cierra esa sesión |
| `GET /api/sedes` | permiso `sedes` | Catálogo de sedes. Marca la sede de trabajo |
| `POST /api/sedes` | permiso `sedes` | Crea la sede y su primer administrador en la misma operación |
| `PUT /api/sedes/{id}` | permiso `sedes` | Nombre, dirección, teléfono y baja. No baja la sede de trabajo actual |
| `GET /api/usuarios` | permiso `usuarios` | Super: todas las cuentas + sedes. Admin de sede: solo las suyas |
| `POST /api/usuarios` | permiso `usuarios` | Alta. Super manda `establecimiento_id`; admin usa la de su sesión |
| `PUT /api/usuarios/{id}` | permiso `usuarios` | Nombre, correo, rol, sede (solo super), baja y clave opcional |
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
| `GET /api/caja` | administrador, recepción | Turno abierto, productos de venta, servicios activos, colaboradores activos, citas del día sin venta y medios (`efectivo`, `digital`) |
| `GET /api/caja/cierres?desde=&hasta=` | administrador, recepción | Cierres del rango (días `YYYY-MM-DD`, hasta tres meses) con desglose de arqueo |
| `POST /api/caja/turno` | administrador, recepción | Abre la caja con `saldo_inicial` (efectivo). Si ya hay turno abierto, `422` |
| `POST /api/caja/ventas` | administrador, recepción | Cobra productos y/o servicios. Con `cita_id`: servicios de la cita (`servicios` opcional = subset). Sin cita: `servicios` (ids de catálogo) + `colaborador_id`. Precios desde MySQL. Una cita no se cobra dos veces. Medios: `efectivo`, `digital` |
| `POST /api/caja/ajustes` | administrador | Ingreso o egreso del turno abierto: medio, monto y motivo. Recepción recibe `403` |
| `POST /api/caja/cierre` | administrador, recepción | Reporta Efectivo y Digital. El sistema suma pagos ± ajustes (y la base en efectivo). En `GET /api/caja`, el medio efectivo trae `base_apertura` y `sin_base` para el desglose. La diferencia la calcula el servidor y el turno queda cerrado |
| `GET /api/servicios` | administrador, recepción | Catálogo de la sede. Recepción solo ve activos |
| `POST /api/servicios` | administrador | Alta de servicio: nombre, categoría, descripción, duración, precio y, si viene, la receta de insumos. La descripción es opcional y llega hasta 500 caracteres |
| `PUT /api/servicios/{id}` | administrador | Edita el servicio, su categoría, su descripción, su baja y, si el cuerpo trae `insumos`, la receta. Cada línea es `producto_id` y `cantidad` de un insumo activo. Sin esa clave, la receta no cambia |
| `GET /api/clientes?q=` | administrador, recepción | Busca clientes de la sede por nombre o teléfono |
| `GET /api/citas?desde=&hasta=` | administrador, recepción, colaborador | Citas del rango. El colaborador solo ve las suyas. `colaborador_id` filtra a los otros roles |
| `POST /api/citas` | administrador, recepción | Reserva. El fin y el precio salen de MySQL. No permite solape del profesional |
| `PUT /api/citas/{id}` | administrador, recepción | Edita una cita `pendiente`: profesional, inicio, servicios y notas. Recalcula el fin y evita solape |
| `PUT /api/citas/{id}/estado` | administrador, recepción, colaborador | Cambia el estado permitido. El colaborador no cancela y solo toca la suya. Al completar, descuenta los insumos de los servicios en la misma operación. Si no hay stock, responde `422` y la cita sigue en proceso |
| `GET /api/reserva` | público | Servicios activos, con su categoría y descripción, profesionales con foto, reseñas y nombre de la sede |
| `POST /api/colaboradores/{id}/foto` | administrador | Foto JPG, PNG o WebP del profesional, hasta 2 MB |
| `GET /api/resenas` | administrador | Todas las reseñas (publicadas y pendientes) |
| `POST /api/resenas` | administrador | Publica una reseña directa en la carta |
| `PUT /api/resenas/{id}/publicar` | administrador | Aprueba una reseña enviada por el cliente |
| `DELETE /api/resenas/{id}` | administrador | Quita esa reseña de la sede |
| `POST /api/reserva/resenas` | público | El cliente envía reseña (queda pendiente de aprobación) |
| `GET /api/reserva/cliente?telefono=` | público | Si el teléfono ya existe, devuelve nombre y correo. Si no, `cliente` nulo |
| `GET /api/reserva/horarios` | público | Huecos libres según `horario_trabajo` y fuera de `bloqueo_agenda`, cada 30 min. Con `colaborador_id` responde `{ horarios }`. Sin él, `{ profesionales }` solo con quienes tienen hueco |
| `GET /api/horarios` | permiso `horarios` | Horario semanal, bloqueos y profesionales |
| `PUT /api/horarios/trabajo` | permiso `horarios` | Reemplaza los tramos de la sede (`tramos`: día 1–7, inicio, fin) |
| `POST /api/horarios/bloqueos` | permiso `horarios` | Crea bloqueo recurrente o por fecha; sede o un profesional |
| `DELETE /api/horarios/bloqueos/{id}` | permiso `horarios` | Elimina el bloqueo |
| `POST /api/reserva` | público | Crea la cita sin sesión. Exige nombre, teléfono y correo. Reutiliza la ficha del teléfono. Envía confirmación por correo |
| `GET /api/reserva/citas?telefono=&correo=` | público | Citas pendientes futuras de ese cliente |
| `POST /api/reserva/citas/{id}/cancelar` | público | Cancela si teléfono y correo coinciden y la cita sigue pendiente. Borra avisos pendientes |
| `GET\|POST /api/avisos/enviar` | clave cron | Procesa `aviso_cita` pendientes vencidos y envía recordatorios. Cabecera `X-Cron-Clave` o `clave` |
| `PUT /api/reserva/citas/{id}` | público | Reagenda profesional e inicio; mismos servicios |
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

## Estado — 23 de octubre de 2026 (alcance usuario / sede)

Hecho: `Acceso` resuelve alcance `sistema` / `sede`. El super no lleva `establecimiento_id`; elige sede de trabajo con `PUT /api/sesion/sede`. Usuarios permiten asignar sede; Sedes queda con permiso `sedes` (solo super). Publicado en `3bigotesbarberie.com` con migración `013`.

Archivos: `controllers/Acceso.php`, `SesionController.php`, `UsuariosController.php`, `SedesController.php`, `RolesController.php`, `CajaController.php`, `models/Sesion.php`, `Usuario.php`, `Rol.php`.

## Estado — 23 de octubre de 2026 (horarios)

Hecho: `HorariosController` y `Horario` gestionan horario semanal y bloqueos. La reserva pública y las citas usan esos tramos (ya no el 08:00–20:00 fijo). Una sede nueva recibe lunes–sábado 08:00–20:00. Publicado en `3bigotesbarberie.com` con migración `012` aplicada.

Archivos: `controllers/HorariosController.php`, `models/Horario.php`, `controllers/CitasController.php`, `controllers/SedesController.php`, `api/rutas.php`.

## Estado — 21 de octubre de 2026 (editar cita)

Hecho: `PUT /api/citas/{id}` edita citas `pendiente` (profesional, inicio, servicios, notas). Recalcula fin, evita solape y reprograma avisos. Publicado en `3bigotesbarberie.com` con el resto de controladores/modelos tocados (citas, caja, colaboradores, reseña).

Archivos: `controllers/CitasController.php`, `models/Cita.php`.

## Estado — 21 de octubre de 2026 (reseña pública)

Hecho: `POST /api/reserva/resenas` recibe reseña del cliente (nombre, correo, texto, nota). Queda sin publicar. El admin lista, aprueba con `PUT /api/resenas/{id}/publicar` o elimina. El catálogo público solo muestra publicadas. Publicado en `3bigotesbarberie.com` con migración `011`.

Archivos: `models/Resena.php`, `controllers/CitasController.php`, `controllers/ColaboradoresController.php`.

Falta: encuesta de satisfacción por correo tras cita completada.

## Estado — 21 de octubre de 2026 (consulta pública)

Hecho: el cliente consulta citas pendientes con teléfono y correo, puede cancelarlas o reagendarlas (mismo servicio, otro barbero/horario). Los huecos pueden excluir la propia cita con `excepto_cita_id`. Publicado en `3bigotesbarberie.com`.

Archivos: `models/Cita.php`, `controllers/CitasController.php`.

## Estado — 5 de noviembre de 2026 (correo de citas en producción)

Hecho: confirmación y recordatorio por correo operativos en `3bigotesbarberie.com`. Remitente `atencion@3bigotesbarberie.com`. Confirmación al crear cita (pública e interna). Recordatorio dos horas antes con cron PHP sobre `scripts/enviar-avisos.php` (también `GET|POST /api/avisos/enviar` con `MAIL_CRON_CLAVE`). `MAIL_*` en el `.env` del hosting. Migración `016` aplicada. Prueba manual OK.

Archivos: `config/Correo.php`, `models/Aviso.php`, `controllers/AvisosController.php`, `controllers/CitasController.php`, `api/rutas.php`, `scripts/enviar-avisos.php`.

Falta: encuesta de satisfacción por correo tras cita completada.

## Estado — 4 de noviembre de 2026 (correo de citas)

Hecho: implementación SMTP + `aviso_cita` + endpoint/cron. Publicado el 4–5 nov; ver estado del 5 nov.

## Estado — 21 de octubre de 2026 (reserva correo)

Hecho: `POST /api/reserva` exige correo válido (además de nombre y teléfono) para dejar lista la ficha de cara a confirmaciones y recordatorios por correo. Publicado en `3bigotesbarberie.com`.

Archivos: `controllers/CitasController.php`.

## Estado — 21 de octubre de 2026 (servicios)

Hecho: alta y edición de servicio aceptan `descripcion`. Vacía se guarda nula. Más de 500 caracteres responde `422`. El catálogo y la reserva pública la devuelven. Publicado en `3bigotesbarberie.com`. La columna llega con `009_descripcion_servicio.sql`.

Archivos: `models/Servicio.php`, `controllers/CitasController.php`.

## Estado — 21 de octubre de 2026 (tarde)

Hecho: CORS acepta el origen del propio host y una lista en `APP_ORIGEN` (dominio y www). En el servidor se corrigió `config/.env`: antes tenía `APP_ORIGEN=http://localhost:5173`, ahora el dominio de producción. Eso bloqueaba el preflight en algunos navegadores.

Archivos: `public/index.php`, `config/.env` del hosting.

## Estado — 21 de octubre de 2026

Hecho: `GET /api/reserva/horarios` sin `colaborador_id` devuelve de una vez los profesionales con huecos libres para ese día y esos servicios. Con `colaborador_id` sigue respondiendo solo `{ horarios }`. Publicado en `3bigotesbarberie.com`.

Archivos: `controllers/CitasController.php`.

Falta: nada de este corte.

## Estado — 8 de octubre de 2026

Hecho: sedes y usuarios sobre las tablas que ya existían. Crear una sede crea su administrador. Una sede de baja no deja entrar. El usuario no se baja a sí mismo y la sede no se queda sin alguien que pueda administrar usuarios. Los permisos de cada rol salen de `rol_permiso`. Se pueden crear roles y marcar permisos. El menú y cada acción usan esa lista.

## Estado — 4 de noviembre de 2026 (caja medios y cierres)

Hecho: medios operativos solo `efectivo` y `digital`. `GET /api/caja` ya no embebe cierres. Nuevo `GET /api/caja/cierres?desde=&hasta=` lista cierres del rango con desglose. Migración `015_medios_efectivo_digital.sql` consolida Nequi/QR/Daviplata (y medios viejos) en `digital`.

Archivos: `models/Caja.php`, `controllers/CajaController.php`. Migración: `database/migrations/015_medios_efectivo_digital.sql`.

## Estado — 23 de octubre de 2026 (caja sin cita)

Hecho: `GET /api/caja` incluye `servicios` y `colaboradores` activos. `POST /api/caja/ventas` sin `cita_id` acepta `servicios` (ids de catálogo) + `colaborador_id` y cobra al precio vigente.

Archivos: `models/Caja.php`, `controllers/CajaController.php`.

## Estado — 23 de octubre de 2026 (caja servicios marcados)

Hecho: `GET /api/caja` trae citas del día en `pendiente`, `en_proceso` o `completada` sin venta, con `estado` y `servicio_id`. `POST /api/caja/ventas` acepta `servicios` (lista de ids de la cita); si no viene, cobra todos. La cita sigue cobrándose una sola vez (sigue exigiendo `completada` al vender).

Archivos: `models/Caja.php`, `controllers/CajaController.php`.

## Estado — 21 de octubre de 2026 (caja)

Hecho: medios operativos `nequi`, `qr` y `efectivo`. `GET /api/caja` incluye `turno.ventas` y `turno.ajustes`. `POST /api/caja/ajustes` solo rol `administrador` (`403` a recepción). El arqueo suma pagos ± ajustes y la base en efectivo. `daviplata` solo en ENUM por historia. Publicado en `3bigotesbarberie.com`.

Archivos: `models/Caja.php`, `controllers/CajaController.php`. Migración: `database/migrations/010_medios_y_ajustes_caja.sql`.

## Estado — 6 de octubre de 2026

Hecho: completar una cita registra una salida por cada insumo de sus servicios. El administrador guarda esa receta al crear o editar el servicio. Si el stock no alcanza, la cita no pasa a completada. Alta y edición de servicio exigen categoría, y el catálogo público la devuelve.

Falta: nada de este corte. El precio y el stock se leen en MySQL.

## Estado — 5 de octubre de 2026

Hecho: configuración por `.env`, PDO, enrutador, CORS hacia `http://localhost:5173`, sesión, colaboradores, inventario, caja, agenda, reserva pública, ficha clínica y comisiones. La caja cobra una cita completada una sola vez, al precio aplicado, y puede sumar productos en la misma venta. La liquidación usa ese mismo precio y redondea a pesos. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta, en ese corte: descontar insumos al completar una cita. Quedó resuelto el 6 de octubre.
