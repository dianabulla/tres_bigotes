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
| Reglas y liquidación de comisiones | sí | no | consulta la propia |
| Productos, stock y movimientos | sí | vende y consulta alerta | no |
| Apertura, venta y cierre de caja | opera el turno | opera el turno | no |

El inicio es la portada original: video, marco, brasas, marquesina y el título 3 BIGOTES. Reservar cita pide nombre, teléfono y correo, luego el servicio, y solo entonces los profesionales con hora libre.

El administrador, en Servicios, indica los insumos y la cantidad que consume cada servicio. Al pasar la cita a completada, ese consumo lo descuenta el servidor. Si falta stock, la agenda muestra el mensaje de la API y la cita sigue en proceso. En Colaboradores sube la foto del equipo y publica o quita las reseñas de la carta.

Estados de cita en la agenda: Pendiente, En proceso, Completada, Cancelada.

Medios de pago en el cobro: Nequi, Daviplata y QR. Una venta puede partirse en varios de esos medios. La caja puede cobrar una cita completada al precio que quedó en la reserva, sola o junto con productos.

El cierre muestra, por medio, monto del sistema, monto reportado y diferencia. Esos tres números los envía el backend.
