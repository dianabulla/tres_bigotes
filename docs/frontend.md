# Frontend

Aplicación de gestión en `view/`. Bootstrap se carga en `view/App.jsx`. El menú sale de `view/modules/registro.js` y solo muestra los módulos del rol que devolvió `GET /api/sesion`.

| Ruta | Quién entra |
|---|---|
| `/` | público. Es el inicio de la barbería y la reserva del cliente |
| `/ingreso` | público, equipo |
| `/gestion/colaboradores` | permiso `colaboradores`. Es la pantalla de entrada del administrador |
| `/gestion/sedes` | permiso `sedes` |
| `/gestion/usuarios` | permiso `usuarios` |
| `/gestion/roles` | permiso `roles` |
| `/gestion/servicios` | permiso `servicios` |
| `/gestion/agenda` | permiso `agenda` o `agenda.propia` |
| `/gestion/clinica` | permiso `clinica`, `clinica.consulta` o `clinica.propia` |
| `/gestion/comisiones` | permiso `comisiones` o `comisiones.propias` |
| `/gestion/inventario` | permiso `inventario` o `inventario.consulta` |
| `/gestion/caja` | permiso `caja` |

## Estado — 21 de octubre de 2026 (servicios)

Hecho: en Servicios el alta y la edición tienen el campo Descripción del servicio. La tabla lo muestra. En el inicio, cada servicio de la carta y del formulario de reserva muestra ese texto cuando existe. Publicado en `3bigotesbarberie.com`.

Archivos: `view/modules/servicios/PaginaServicios.jsx`, `view/cliente/servicios.js`, `view/modules/agenda/PaginaReserva.jsx`, `view/index.css`.

## Estado — 21 de octubre de 2026 (tarde)

Hecho: mensaje claro si falla la red al llamar la API. Si el usuario entra pero su rol no tiene módulos, ya no vuelve al login en bucle: muestra aviso de permisos. El login usa el mismo video diferido que la portada. `index.html` sale sin caché larga. En el hosting se quitó el MP4 pesado del `dist` público.

Archivos: `view/cliente/http.js`, `view/App.jsx`, `view/components/Fondo.jsx`, `deploy/htaccess-raiz`.

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
