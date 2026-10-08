# Pantallas

Menú según el rol que devuelve `GET /api/sesion`.

| Pantalla | administrador | recepcion | colaborador |
|---|---|---|---|
| Inicio `/` | no entra al sistema. Carta con servicios, equipo, reseñas y reserva | no entra al sistema | no entra al sistema |
| Catálogo de servicios | crea y da de baja | no | no |
| Agenda día / semana / profesional | sí | sí | solo la propia |
| Reserva y cambio de estado de cita | sí | sí | pasar a en proceso y completada la propia |
| Ficha y notas de visita | sí | consulta | escribe la de su cita |
| Colaboradores | sí | no | no |
| Sedes | crea y da de baja otras sedes | no | no |
| Usuarios | cuentas de su sede y el rol de cada una | no | no |
| Roles | crea roles y marca permisos | no | no |
| Reglas y liquidación de comisiones | sí | no | consulta la propia |
| Productos, stock y movimientos | sí | vende y consulta alerta | no |
| Apertura, venta y cierre de caja | opera el turno | opera el turno | no |

El inicio es la portada original: video, marco, brasas, marquesina y el título 3 BIGOTES. Junto a Reservar cita y encima del formulario hay Instagram, Facebook y WhatsApp. Reservar cita pide nombre, teléfono y correo, luego el servicio, y solo entonces los profesionales con hora libre.

El menú muestra una pantalla cuando la sesión trae el permiso de esa pantalla. En Roles se crea un rol y se marcan esos permisos. En Usuarios se elige el rol de cada cuenta. Los tres roles de sistema siguen abriendo lo que abrían antes, hasta que alguien les cambie los permisos. Un permiso propio muestra solo el trabajo del profesional ligado a ese usuario.

El administrador, en Servicios, indica los insumos y la cantidad que consume cada servicio. Al pasar la cita a completada, ese consumo lo descuenta el servidor. Si falta stock, la agenda muestra el mensaje de la API y la cita sigue en proceso. En Colaboradores sube la foto del equipo y publica o quita las reseñas de la carta.

Estados de cita en la agenda: Pendiente, En proceso, Completada, Cancelada.

Medios de pago en el cobro: Nequi, Daviplata y QR. Una venta puede partirse en varios de esos medios. La caja puede cobrar una cita completada al precio que quedó en la reserva, sola o junto con productos.

El cierre muestra, por medio, monto del sistema, monto reportado y diferencia. Esos tres números los envía el backend.
