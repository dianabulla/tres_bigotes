# Frontend

Aplicación de gestión en `view/`. Bootstrap se carga en `view/App.jsx`. El menú sale de `view/modules/registro.js` y solo muestra los módulos del rol que devolvió `GET /api/sesion`.

| Ruta | Quién entra |
|---|---|
| `/` | público. Es el inicio de la barbería y la reserva del cliente |
| `/ingreso` | público, equipo |
| `/gestion/colaboradores` | administrador. Es la pantalla de entrada de ese rol |
| `/gestion/servicios` | administrador |
| `/gestion/agenda` | administrador, recepción, colaborador |
| `/gestion/clinica` | administrador, recepción, colaborador |
| `/gestion/colaboradores` | administrador |
| `/gestion/comisiones` | administrador, colaborador |
| `/gestion/inventario` | administrador, recepción |
| `/gestion/caja` | administrador, recepción |

## Estado — 6 de octubre de 2026

Hecho: la pantalla se ve en `http://localhost/tres_bigotes/`. `BrowserRouter` usa la base de Vite. Logo y video de ingreso, reserva y el marco apuntan a esa base. Para publicar un cambio de `view/` en Apache: `npm run build`.

El administrador entra en Colaboradores. El menú del marco queda siempre visible: Colaboradores, Servicios, Agenda, Ficha clínica, Comisiones, Inventario y Caja.

Servicios es el catálogo de la sede. El administrador crea y edita nombre, categoría, duración, precio y la receta de insumos, y puede dar de baja. Recepción y colaborador no entran a esa pantalla. La agenda solo elige servicios activos al reservar. Si al completar falta stock, el aviso de la agenda es el de la API.

El inicio `/` usa la portada original: video con viñeta y scanlines, brasas, marco, marquesina y el título 3 BIGOTES con el corte de glitch. En pantallas angostas el menú se abre en cortina. Reservar cita abre el formulario; después se elige el servicio y ahí aparecen los profesionales con hora libre. Se ven el título 3 Bigotes y el botón Reservar cita. Debajo quedan el equipo, los servicios, la agenda y el contacto. El ingreso del equipo está en Contacto. El video fijo del login queda en `/ingreso`. `/reservar` vuelve a `/`. Apache abre la copia generada en `dist/`. Los archivos originales de esa portada no estaban en git.

Falta: una pasarela de fotos de trabajos. Hoy la carta usa el video que ya está en `view/public/media/fondo.mp4`.

## Estado — 5 de octubre de 2026

Hecho: cliente HTTP, ingreso, cierre de sesión, marco, colaboradores, inventario, caja, agenda y ficha clínica. Inventario lista productos con precio de compra y, si es de venta, precio de venta; marca los que hay que reponer y, si el rol es administrador, permite alta, edición y movimientos. Recepción solo consulta inventario. Caja abre el turno, cobra en Nequi, Daviplata y QR, y cierra con el arqueo que devuelve la API. Agenda muestra día, semana y profesional; el administrador crea servicios; administrador y recepción reservan; el colaborador ve las suyas y las pasa a en proceso o completada. `/reservar` deja que el cliente pida cita con teléfono, nombre y correo, y si el teléfono ya existe recupera la ficha. La ficha muestra preferencias, cortes y fórmulas; recepción solo consulta y el colaborador anota la visita de su cita. Comisiones deja al administrador crear reglas y liquidar; el colaborador solo consulta la suya. El aspecto usa el index inicial: video `view/public/media/fondo.mp4`, logo, fondo negro, texto crema, oro y el brillo del cursor.

Caja también cobra la cita completada junto con los productos. El precio del servicio es el que guardó la reserva. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita.

Archivos de arranque: `view/index.html`, `view/main.jsx`, `view/App.jsx`, `view/cliente/http.js`, `view/cliente/sesion.js`, `view/components/Sesion.jsx`, `view/components/Marco.jsx`, `view/components/RutaPrivada.jsx`, `view/components/PaginaIngreso.jsx`.
