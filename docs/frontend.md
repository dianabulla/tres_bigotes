# Frontend

Aplicación de gestión en `view/`. Bootstrap se carga en `view/App.jsx`. El menú sale de `view/modules/registro.js` y solo muestra los módulos del rol que devolvió `GET /api/sesion`.

| Ruta | Quién entra |
|---|---|
| `/ingreso` | público |
| `/reservar` | público, sin cuenta |
| `/gestion/colaboradores` | administrador. Es la pantalla de entrada de ese rol |
| `/gestion/agenda` | administrador, recepción, colaborador |
| `/gestion/clinica` | administrador, recepción, colaborador |
| `/gestion/colaboradores` | administrador |
| `/gestion/comisiones` | administrador, colaborador |
| `/gestion/inventario` | administrador, recepción |
| `/gestion/caja` | administrador, recepción |

## Estado — 5 de octubre de 2026

Hecho: cliente HTTP, ingreso, cierre de sesión, marco, colaboradores, inventario, caja, agenda y ficha clínica. Inventario lista productos con precio de compra y, si es de venta, precio de venta; marca los que hay que reponer y, si el rol es administrador, permite alta, edición y movimientos. Recepción solo consulta inventario. Caja abre el turno, cobra en Nequi, Daviplata y QR, y cierra con el arqueo que devuelve la API. Agenda muestra día, semana y profesional; el administrador crea servicios; administrador y recepción reservan; el colaborador ve las suyas y las pasa a en proceso o completada. `/reservar` deja que el cliente pida cita con teléfono, nombre y correo, y si el teléfono ya existe recupera la ficha. La ficha muestra preferencias, cortes y fórmulas; recepción solo consulta y el colaborador anota la visita de su cita. Comisiones deja al administrador crear reglas y liquidar; el colaborador solo consulta la suya. El aspecto usa el index inicial: video `view/public/media/fondo.mp4`, logo, fondo negro, texto crema, oro y el brillo del cursor.

Caja también cobra la cita completada junto con los productos. El precio del servicio es el que guardó la reserva. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita.

Archivos de arranque: `view/index.html`, `view/main.jsx`, `view/App.jsx`, `view/cliente/http.js`, `view/cliente/sesion.js`, `view/components/Sesion.jsx`, `view/components/Marco.jsx`, `view/components/RutaPrivada.jsx`, `view/components/PaginaIngreso.jsx`.
