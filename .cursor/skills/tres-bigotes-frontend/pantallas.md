# Pantallas

Menú según el rol que devuelve `GET /api/sesion`.

| Pantalla | superadministrador | administrador | recepcion | colaborador |
|---|---|---|---|---|
| Inicio `/` | no entra al sistema | no entra al sistema | no entra al sistema | no entra al sistema |
| Catálogo de servicios | sede de trabajo | crea y da de baja | no | no |
| Agenda día / semana / profesional | sede de trabajo | sí | sí | solo la propia |
| Horarios y bloqueos de agenda | sede de trabajo | sí | sí | no |
| Reserva, edición de pendiente y cambio de estado de cita | sede de trabajo | sí | sí | pasar a en proceso y completada la propia |
| Ficha y notas de visita | sede de trabajo | sí | consulta | escribe la de su cita |
| Colaboradores | sede de trabajo | sí | no | no |
| Sedes | crea y da de baja sedes | no | no | no |
| Usuarios | todas las cuentas; asigna sede y rol | cuentas de su sede y el rol | no | no |
| Roles | crea roles y marca permisos | crea roles y marca permisos | no | no |
| Reglas y liquidación de comisiones | sede de trabajo | sí | no | consulta la propia |
| Productos, stock y movimientos | sede de trabajo | sí | vende y consulta alerta | no |
| Apertura, venta y cierre de caja | sede de trabajo | opera el turno | opera el turno | no |

El inicio es la portada original: video, marco, brasas, marquesina y el título 3 BIGOTES. Junto a Reservar cita hay Consultar cita. Encima del formulario están Instagram y WhatsApp; Facebook queda marcado como Pronto. La reserva es un asistente: uno o más servicios → barberos → día y hora → datos → confirmación. Tras reservar se indica que llega confirmación y recordatorio por correo. El catálogo se muestra por `orden` (corte, ritual de barba, cejas, spa facial, barba express, combo). Consultar cita pide teléfono y correo, lista las pendientes futuras y permite cancelar o reagendar.

El menú de gestión es lateral (en móvil, hamburguesa). Abajo se ve el usuario y el rol de la sesión. El super administrador elige además la sede de trabajo. Tras el login, `/gestion` abre Agenda si el rol la tiene; si no, el primer módulo con permiso. El menú muestra una pantalla cuando la sesión trae el permiso de esa pantalla. En Roles se crea un rol y se marcan esos permisos. En Usuarios se elige el rol (y la sede, si quien edita es el super). Los roles de sistema siguen abriendo lo que abrían antes, hasta que alguien les cambie los permisos. Un permiso propio muestra solo el trabajo del profesional ligado a ese usuario.

En Horarios se edita el horario semanal de la sede (varios tramos por día) y se crean bloqueos: recurrentes (almuerzo) o de una fecha (descanso / cierre), para toda la sede o un profesional. Esos huecos dejan de salir en la reserva pública.

En catálogos hay crear, editar y eliminar (baja lógica o borrado según el módulo). En Agenda: nueva cita, editar pendiente y eliminar (cancelar). Quien tiene permiso de caja ve además el icono de cobro: abre Caja con la venta lista (servicios marcados) y solo pide el medio de pago para cobrar (si hace falta, marca la cita completada antes). Con permiso de agenda completa hay además recordatorios WhatsApp del día: asistente que abre chat por chat con mensaje listo; recepción confirma el envío. Las acciones van en botones solo con icono.

El administrador, en Servicios, indica los insumos y la cantidad que consume cada servicio. Al pasar la cita a completada, ese consumo lo descuenta el servidor. Si falta stock, la agenda muestra el mensaje de la API y la cita sigue en proceso. En Colaboradores sube la foto del equipo, publica reseñas propias, aprueba las que envían los clientes desde el inicio o las quita.

Estados de cita en la agenda: Pendiente, En proceso, Completada, Cancelada.

Medios de pago en el cobro: Efectivo y Digital. Una venta puede partirse entre esos dos medios. La caja puede cobrar una cita completada al precio que quedó en la reserva, sola o junto con productos.

Caja se organiza en cuatro vistas: cobro (pantalla principal), cierre de turno, ajustes y cierres anteriores. Apertura y cierre usan botones con texto claro (“Abrir caja”, “Cerrar turno”, “Cerrar caja”). Los ajustes viven en una vista aparte (solo administrador registra; el historial del turno se ve ahí). Los cierres anteriores se consultan con filtro desde/hasta (también con la caja cerrada). Tras un cobro exitoso aparece un resumen tipo ticket (cliente, ítems, medios y total) antes de seguir.

El cobro es un asistente: cita del día o sin cita; luego se abre Servicios o Productos (una lista a la vez; lo marcado se conserva al cambiar); medio de pago (o partida) y confirmación. Sin cita con servicios hay que indicar el profesional. Si la cita aún no está completada, Caja la completa al elegirla. La venta también entra en “Ventas del turno”.

El cierre muestra, por medio, monto del sistema, monto reportado y diferencia. Esos tres números los envía el backend. En efectivo el sistema ya incluye la base de apertura y la UI lo desglosa como “efectivo del turno + base”.
