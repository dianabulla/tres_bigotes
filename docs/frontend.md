# Frontend

Aplicación de gestión en `view/`. Bootstrap (solo módulos usados) se importa en `view/bootstrap-tb.scss` desde `view/App.jsx`. El menú sale de `view/modules/registro.js` y solo muestra los módulos del rol que devolvió `GET /api/sesion`.

| Ruta | Quién entra |
|---|---|
| `/` | público. Es el inicio de la barbería y la reserva del cliente |
| `/ingreso` | público, equipo |
| `/gestion/colaboradores` | permiso `colaboradores` |
| `/gestion/sedes` | permiso `sedes` |
| `/gestion/usuarios` | permiso `usuarios` |
| `/gestion/roles` | permiso `roles` |
| `/gestion/servicios` | permiso `servicios` |
| `/gestion/agenda` | permiso `agenda` o `agenda.propia` |
| `/gestion/horarios` | permiso `horarios` |
| `/gestion/clinica` | permiso `clinica`, `clinica.consulta` o `clinica.propia` |
| `/gestion/comisiones` | permiso `comisiones` o `comisiones.propias` |
| `/gestion/inventario` | permiso `inventario` o `inventario.consulta` |
| `/gestion/caja` | permiso `caja` |

## Estado — 4 de noviembre de 2026 (recordatorios WhatsApp asistidos)

Hecho: en Agenda, botón de recordatorios del día. Abre un asistente con las citas activas de la fecha: mensaje personalizado, abre WhatsApp Web/app chat por chat para que recepción confirme el envío. Sin API de Meta. Teléfonos de 10 dígitos que empiezan en 3 se normalizan a Colombia (`57`). Publicado en `3bigotesbarberie.com` (`PaginaAgenda-9GPdaBk_.js`, `index-DA-3gYKi.js`).

Archivos: `view/modules/agenda/PaginaAgenda.jsx`, `view/components/Iconos.jsx`.

## Estado — 4 de noviembre de 2026 (caja historial y medios)

Hecho: Caja cobra solo en Efectivo o Digital. Vista “Cierres anteriores” con filtro desde/hasta (disponible con caja abierta o cerrada). El listado sale de `GET /api/caja/cierres`. El cierre de turno ya no lista el historial embebido.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/cliente/caja.js`, `view/index.css`.

## Estado — 5 de noviembre de 2026 (descripción en checklist móvil)

Hecho: en el checklist de servicios de la carta/reserva la descripción vuelve a verse en móvil, en tipografía más pequeña y como máximo dos líneas. El checklist (checkbox) se mantiene. Publicado en `3bigotesbarberie.com` (`index-Dm65Ea-t.js`, `index-CXXjZiQn.css`, `PaginaReserva-BNONBrr4.js`).

Archivos: `view/index.css`.

## Estado — 3 de noviembre de 2026 (checklist de servicios)

Hecho: en la portada y en el paso de reserva los servicios son checklist (checkbox). Al marcar un servicio ya no salta al formulario de abajo (evita el duplicado en móvil). Publicado en `3bigotesbarberie.com` (`PaginaReserva-BUIzz_gR.js`, `index-DSKpjxX3.js`).

Archivos: `view/modules/agenda/PaginaReserva.jsx`, `view/index.css`.

## Estado — 30 de octubre de 2026 (reserva multi-servicio)

Hecho: en la portada el cliente marca varios servicios, ve minutos y total, y continúa. El catálogo respeta `servicio.orden`. Publicado en `3bigotesbarberie.com` (`PaginaReserva-E7f36mRX.js`, migración `014`).

Archivos: `view/modules/agenda/PaginaReserva.jsx`, `models/Servicio.php`, `database/migrations/014_orden_servicio.sql`.

## Estado — 23 de octubre de 2026 (alcance usuario / sede)

Hecho: en Usuarios el super ve todas las cuentas y asigna sede; el admin de sede sigue viendo solo la suya. El menú del super incluye selector de sede de trabajo. Ajustes de caja también para super. Publicado en `3bigotesbarberie.com` (`PaginaUsuarios-DOPGOcu1.js`, `index-BfVGSiXr.js`).

Archivos: `view/modules/usuarios/PaginaUsuarios.jsx`, `view/cliente/usuarios.js`, `view/cliente/sesion.js`, `view/components/Sesion.jsx`, `view/components/Marco.jsx`, `view/modules/sedes/PaginaSedes.jsx`, `view/modules/caja/PaginaCaja.jsx`, `view/index.css`.

## Estado — 23 de octubre de 2026 (carga más rápida)

Hecho: el shell ya no arrastra la portada pública; cada pantalla (incluida la reserva) va en chunk lazy. React y react-bootstrap van en vendors aparte. Al validar sesión se prefetchea Agenda (o el primer módulo del rol) y el menú prefetchea al pasar el mouse. Mientras llega la sesión se muestra un esqueleto del marco, no un spinner vacío. Bootstrap CSS pasó a un SCSS recortado. El video de fondo se mantiene (`fondo-opt.mp4`, ~771 KB, antes ~2.1 MB); el máster queda en `media-fuente/fondo.mp4` y no se publica. En redes 2G / ahorro de datos solo se ve el poster.

Builds: `npm run build` y `npm run build:sitio` (`index-DJ7liVBQ.js`, `react-DSVHYivw.js`, `PaginaReserva-B9JMQzag.js`, CSS `index-i7UJ9LPr.css`). Publicado en `3bigotesbarberie.com` (`dist-web/` → `dist/` por FTP). Detalle en `docs/deploy.md`.

Archivos: `view/App.jsx`, `view/bootstrap-tb.scss`, `view/modules/cargas.js`, `view/components/Sesion.jsx`, `view/components/RutaPrivada.jsx`, `view/components/Marco.jsx`, `view/modules/agenda/VideoFondo.jsx`, `view/index.css`, `view/index.html`, `vite.config.js`, `view/public/media/fondo-opt.mp4`, `media-fuente/fondo.mp4`.

## Estado — 23 de octubre de 2026 (horarios)

Hecho: pantalla `/gestion/horarios` para el horario semanal de la sede y bloqueos (almuerzo / descanso). Menú con permiso `horarios`. Documentado en `pantallas.md`. Publicado en `3bigotesbarberie.com` (`PaginaHorarios-D5xV_TL-.js`, `index-CdS4uswY.js`).

Archivos: `view/modules/horarios/PaginaHorarios.jsx`, `view/cliente/horarios.js`, `view/modules/registro.js`, `view/App.jsx`, `view/index.css`.

## Estado — 23 de octubre de 2026 (cierre efectivo + base)

Hecho: el efectivo del sistema en cierre ya incluía la base; la UI lo deja claro con el desglose “efectivo del turno + base”. La API añade `base_apertura` y `sin_base` en el medio efectivo del turno.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `models/Caja.php`, `view/index.css`.

## Estado — 23 de octubre de 2026 (caja servicios/productos)

Hecho: en el paso de ítems, el cajero toca Servicios y/o Productos; cada lista solo aparece al elegirla y se pueden marcar las dos a la vez. “Solo productos” dejó de ser un camino aparte: va dentro de Sin cita.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/index.css`.

## Estado — 23 de octubre de 2026 (caja más clara)

Hecho: Caja deja de mezclar cobro, cierre y ajustes. La pantalla principal es solo cobrar; “Cerrar turno” y “Ajustes” abren vistas aparte. Apertura y cierre usan botones con texto (“Abrir caja”, “Cerrar caja”). Tras cobrar sale un resumen tipo ticket. Documentado en `pantallas.md`.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/index.css`.

## Estado — 23 de octubre de 2026 (cobro rápido desde agenda)

Hecho: el icono de caja en Agenda abre `/gestion/caja?cita=…&listo=1` con la venta ya armada (cliente, servicios y total). En Caja salta al pago; al tocar Nequi/QR/Efectivo cobra de una.

Archivos: `view/modules/agenda/PaginaAgenda.jsx`, `view/modules/caja/PaginaCaja.jsx`.

## Estado — 23 de octubre de 2026 (caja sin cita)

Hecho: en Cobrar hay “Sin cita” para facturar servicios del catálogo a un cliente que llegó sin agenda: se elige profesional, se marcan servicios (y productos si aplica), pago y confirmar.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/cliente/caja.js`.

## Estado — 23 de octubre de 2026 (caja por pasos)

Hecho: el cobro en Caja es un asistente por clics: cliente/cita → checklist de servicios y productos → medio de pago → confirmar. En Caja salen las citas del día (pendiente, en proceso o por cobrar); al tocarlas se completan si hace falta y se sigue el cobro. Desde Agenda también llega con la cita precargada. Se puede partir el pago entre medios.

Builds: `npm run build` (local → `dist/`) y `npm run build:sitio` (→ `dist-web/`). Publicado en `3bigotesbarberie.com` (`index-D1uSLzFy.js`, `PaginaCaja-DhuVt5bS.js`, API caja). Cómo publicar: `docs/deploy.md`.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/cliente/caja.js`, `view/index.css`.

## Estado — 23 de octubre de 2026 (cobrar desde agenda)

Hecho: en Agenda, quien tiene permiso de caja ve un botón con icono de cobro en citas pendiente, en proceso o completada. Lleva a `/gestion/caja?cita=…` y Caja deja seleccionada esa cita. Si aún no estaba completada, la agenda la avanza hasta completada antes de abrir caja.

Archivos: `view/modules/agenda/PaginaAgenda.jsx`, `view/modules/caja/PaginaCaja.jsx`.

## Estado — 21 de octubre de 2026 (inicio en agenda)

Hecho: al entrar al sistema (`/gestion` tras el login) la primera pantalla es Agenda si el rol la tiene; si no, el primer módulo con permiso. Menú lateral + usuario en sesión. Publicado en `3bigotesbarberie.com` (`index-BOEIEzfF.js`, `index-Dt5Cvujh.css`) junto con API de citas, caja, colaboradores y reseña.

Archivos: `view/App.jsx`, `view/components/Marco.jsx`, `view/index.css`.

## Estado — 21 de octubre de 2026 (menú lateral)

Hecho: menú lateral compacto; usuario y rol visibles; hamburguesa en móvil. Quedó incluido en el corte «inicio en agenda» publicado.

Archivos: `view/components/Marco.jsx`, `view/index.css`.

## Estado — 21 de octubre de 2026 (crear / editar / eliminar en módulos)

Hecho: catálogos con crear/editar/eliminar; Agenda con editar pendiente y cancelar. Publicado junto con el corte de agenda e iconos.

Archivos: `view/modules/*`, `controllers/CitasController.php`, `models/Cita.php`, `view/cliente/agenda.js`.

## Estado — 21 de octubre de 2026 (botones de acción con iconos)

Hecho: acciones con icono SVG (`BotonAccion` + `GrupoAcciones`). Publicado en `3bigotesbarberie.com`.

Archivos: `view/components/Iconos.jsx`, `view/components/BotonAccion.jsx`, `view/index.css`, pantallas de gestión y reserva.

## Estado — 21 de octubre de 2026 (reseña en inicio)

Hecho: en Reseñas del inicio hay formulario para que el cliente deje nombre, correo, nota y texto. En Colaboradores el admin ve pendientes y puede Publicar o Quitar. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/agenda/PaginaReserva.jsx`, `view/modules/colaboradores/PaginaColaboradores.jsx`, `view/cliente/reserva.js`, `view/cliente/colaboradores.js`.

Falta: encuesta de satisfacción por correo (futuro).

## Estado — 21 de octubre de 2026 (consultar cita)

Hecho: en el inicio hay botón Consultar cita. El cliente busca con teléfono y correo, ve sus citas pendientes y puede cancelar o reagendar (barbero, día y hora). Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/agenda/ConsultaCita.jsx`, `view/modules/agenda/PaginaReserva.jsx`, `view/cliente/reserva.js`, `view/index.css`, `view/modules/agenda/portada.css`.

## Estado — 21 de octubre de 2026 (reserva asistente)

Hecho: la reserva del inicio es un asistente de un paso visible a la vez: servicio → barberos → día/hora → datos → «Cita reservada». Al elegir servicio (en la carta o en el formulario) salta al paso de barberos y centra el panel, sin obligar a deslizar entre bloques apilados. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/agenda/PaginaReserva.jsx`, `view/index.css`.

## Estado — 5 de noviembre de 2026 (correo de citas)

Hecho: tras reservar, el mensaje indica confirmación y recordatorio por correo (la API envía; recordatorio 2 h antes). Publicado en `3bigotesbarberie.com` (`PaginaReserva-vO6cP2N9.js`, `index-DA-3gYKi.js`). Prueba real OK.

Archivos: `view/modules/agenda/PaginaReserva.jsx`.

## Estado — 21 de octubre de 2026 (reserva paso a paso)

Hecho: flujo ordenado y correo obligatorio. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/agenda/PaginaReserva.jsx`, `view/index.css`, `controllers/CitasController.php`.

## Estado — 21 de octubre de 2026 (UI responsive)

Hecho: botones, formularios, alertas y tarjetas con bordes redondeados. Publicado en `3bigotesbarberie.com`. El menú de gestión pasó después a lateral (ver entrada «menú lateral»).

Archivos: `view/components/Marco.jsx`, `view/index.css`, `view/modules/agenda/portada.css`.

Nota: en `http://localhost/tres_bigotes/` Apache sirve `dist/`, no `view/`. Tras cambiar la interfaz hay que correr `npm run build` (o `npm run dev` en Vite) para ver el cambio.

## Estado — 21 de octubre de 2026 (caja UI)

Hecho: el aviso de venta exitosa ya no usa texto negro (quedaba invisible en el tema oscuro). Caja muestra total a cobrar, exige medio de pago antes de llamar la API, sección fija “Ventas del turno” y alerta verde visible. Documentado en `pantallas.md`. Publicado en `3bigotesbarberie.com` (`PaginaCaja-nDYOEnHH.js`, `index-C7T7w72S.js`, `index-CECYJrcD.css`).

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/cliente/caja.js`, `view/index.css`.

## Estado — 21 de octubre de 2026 (caja)

Hecho: cobro y arqueo con Nequi, QR y Efectivo (la lista viene de la API). La base de apertura se etiqueta como efectivo. Solo el administrador ve el formulario de ajustes (ingreso/egreso con motivo); recepción no. El historial de ajustes del turno abierto se muestra a quien opera la caja. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/caja/PaginaCaja.jsx`, `view/cliente/caja.js`.

## Estado — 21 de octubre de 2026 (servicios)

Hecho: en Servicios el alta y la edición tienen el campo Descripción del servicio. La tabla lo muestra. En el inicio, cada servicio de la carta y del formulario de reserva muestra ese texto cuando existe. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/servicios/PaginaServicios.jsx`, `view/cliente/servicios.js`, `view/modules/agenda/PaginaReserva.jsx`, `view/index.css`.

## Estado — 21 de octubre de 2026 (tarde)

Hecho: mensaje claro si falla la red al llamar la API. Si el usuario entra pero su rol no tiene módulos, ya no vuelve al login en bucle: muestra aviso de permisos. El login usa el mismo video diferido que la portada. `index.html` sale sin caché larga. En el hosting se quitó el MP4 pesado del `dist` público.

Archivos: `view/cliente/http.js`, `view/App.jsx`, `view/components/Fondo.jsx`, `deploy/htaccess-raiz`.

## Estado — 21 de octubre de 2026 (leyenda hero)

Hecho: en la portada `/`, la descripción del hero pasó a «Más que una barbería... Un club de amigos.»

Archivos: `view/modules/agenda/PaginaReserva.jsx`.

## Estado — 21 de octubre de 2026

Hecho: la portada `/` carga más rápido. El hero usa `fondo-opt.mp4` (mismo clip comprimido) con poster y carga diferida; el original `fondo.mp4` se conserva en el repo. Las pantallas de gestión van con `React.lazy`. Partículas y brillo del cursor arrancan en idle y no corren en móvil. Las fuentes de Google dejan de bloquear el primer pintado. Al elegir servicio y día, los horarios salen en una sola llamada a `GET /api/reserva/horarios`. Publicado en `3bigotesbarberie.com` con `npm run build:sitio`.

Archivos: `view/App.jsx`, `view/index.html`, `view/cliente/reserva.js`, `view/modules/agenda/PaginaReserva.jsx`, `view/modules/agenda/VideoFondo.jsx`, `view/modules/agenda/Particulas.jsx`, `view/components/Fondo.jsx`, `view/public/media/fondo-opt.mp4`, `view/public/media/fondo-poster.jpg`.

Falta: nada de este corte.

## Estado — 8 de octubre de 2026

Hecho: pantallas Sedes, Usuarios y Roles. El menú usa los permisos que devuelve la sesión. Sedes crea otra sucursal y el administrador que entra a ella. Usuarios asigna un rol a cada cuenta de esta sede. Roles crea un rol y marca sus permisos. El acceso del profesional también se puede crear en Colaboradores. En el inicio, junto a Reservar cita y encima del formulario, hay Instagram (`instagram.com/3bigotesbarberie`) y WhatsApp (`324 314 9005`). Facebook se ve con la marca Pronto hasta que exista la página. Este corte está publicado en `3bigotesbarberie.com`.

## Estado — 6 de octubre de 2026

Hecho: la pantalla se ve en `http://localhost/tres_bigotes/`. `BrowserRouter` usa la base de Vite. Logo y video de ingreso, reserva y el marco apuntan a esa base. Para publicar un cambio de `view/` en Apache: `npm run build`. Para el dominio `3bigotesbarberie.com`: `npm run build:sitio` y se sube `dist-web/` como `dist/`. El `.htaccess` de ese dominio está en `deploy/`.

El administrador entra en Colaboradores. En ese corte el menú era Colaboradores, Servicios, Agenda, Ficha clínica, Comisiones, Inventario y Caja.

Servicios es el catálogo de la sede. El administrador crea y edita nombre, categoría, descripción, duración, precio y la receta de insumos, y puede dar de baja. Recepción y colaborador no entran a esa pantalla. La agenda solo elige servicios activos al reservar. Si al completar falta stock, el aviso de la agenda es el de la API.

El nombre visible se escribe siempre con el número: 3 Bigotes. El inicio `/` usa la portada original: video con viñeta y scanlines, brasas, marco, marquesina y el título 3 BIGOTES con el corte de glitch. En pantallas angostas el menú se abre en cortina. Reservar cita abre el formulario; después se elige el servicio y ahí aparecen los profesionales con hora libre. Se ven el título 3 Bigotes y el botón Reservar cita. Debajo quedan el equipo, los servicios, la agenda y el contacto. El ingreso del equipo está en Contacto. El video fijo del login queda en `/ingreso`. `/reservar` vuelve a `/`. Apache abre la copia generada en `dist/`. Los archivos originales de esa portada no estaban en git.

Falta: una pasarela de fotos de trabajos. Hoy la carta usa el video que ya está en `view/public/media/fondo.mp4`.

## Estado — 5 de octubre de 2026

Hecho: cliente HTTP, ingreso, cierre de sesión, marco, colaboradores, inventario, caja, agenda y ficha clínica. Inventario lista productos con precio de compra y, si es de venta, precio de venta; marca los que hay que reponer y, si el rol es administrador, permite alta, edición y movimientos. Recepción solo consulta inventario. Caja abre el turno, cobra en Nequi, Daviplata y QR, y cierra con el arqueo que devuelve la API. Agenda muestra día, semana y profesional; el administrador crea servicios; administrador y recepción reservan; el colaborador ve las suyas y las pasa a en proceso o completada. `/reservar` deja que el cliente pida cita con teléfono, nombre y correo, y si el teléfono ya existe recupera la ficha. La ficha muestra preferencias, cortes y fórmulas; recepción solo consulta y el colaborador anota la visita de su cita. Comisiones deja al administrador crear reglas y liquidar; el colaborador solo consulta la suya. El aspecto usa el index inicial: video `view/public/media/fondo.mp4`, logo, fondo negro, texto crema, oro y el brillo del cursor.

Caja también cobra la cita completada junto con los productos. El precio del servicio es el que guardó la reserva. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita.

Archivos de arranque: `view/index.html`, `view/main.jsx`, `view/App.jsx`, `view/cliente/http.js`, `view/cliente/sesion.js`, `view/components/Sesion.jsx`, `view/components/Marco.jsx`, `view/components/RutaPrivada.jsx`, `view/components/PaginaIngreso.jsx`.
