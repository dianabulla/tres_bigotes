# Pantallas

Menú según el rol que devuelve `GET /api/sesion`.

| Pantalla | administrador | recepcion | colaborador |
|---|---|---|---|
| Reserva pública `/reservar` | no entra al sistema | no entra al sistema | no entra al sistema |
| Agenda día / semana / profesional | sí | sí | solo la propia |
| Reserva y cambio de estado de cita | sí | sí | pasar a en proceso y completada la propia |
| Ficha y notas de visita | sí | consulta | escribe la de su cita |
| Colaboradores | sí | no | no |
| Reglas y liquidación de comisiones | sí | no | consulta la propia |
| Productos, stock y movimientos | sí | vende y consulta alerta | no |
| Apertura, venta y cierre de caja | opera el turno | opera el turno | no |

Estados de cita en la agenda: Pendiente, En proceso, Completada, Cancelada.

Medios de pago en el cobro: Nequi, Daviplata y QR. Una venta puede partirse en varios de esos medios. La caja puede cobrar una cita completada al precio que quedó en la reserva, sola o junto con productos.

El cierre muestra, por medio, monto del sistema, monto reportado y diferencia. Esos tres números los envía el backend.
