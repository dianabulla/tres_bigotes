# Arquitectura

Tres carpetas. Ninguna escribe el trabajo de otra.

| Capa | Carpeta | Entra por |
|---|---|---|
| Vistas | `view/` | En local, Apache en `http://localhost/tres_bigotes/`. El código sigue en `view/` y Vite lo genera en `dist/` |
| API | `api/` | Rutas y formato JSON |
| Controladores | `controllers/` | Reglas de la petición |
| Modelos | `models/` | Consultas |
| Configuración | `config/` | `.env` |
| Entrada | `public/` | `public/index.php` |
| Datos | `database/` | `database/migrations/` |

El navegador llama a `VITE_API_URL` (en local, `http://localhost/tres_bigotes/public`). La API lee `config/.env`, abre MySQL y responde JSON. El esquema no se crea desde PHP.

En local, `.htaccess` de la raíz muestra la interfaz generada y deja `public/` para la API. Las rutas de React (`/`, `/ingreso`, `/gestion`) vuelven a `dist/index.html`. Después de cambiar `view/`, hay que volver a generar con `npm run build` para verlo en Apache. El inicio `/` es la reserva pública. `/reservar` redirige ahí.

En `3bigotesbarberie.com` el sitio vive en la raíz del dominio, no en `/tres_bigotes/`. `npm run build:sitio` genera `dist-web/` con esa base y con la API en `https://3bigotesbarberie.com/public`. En el hosting, esa carpeta se sube como `dist/`. El `.htaccess` de la raíz es `deploy/htaccess-raiz` y el de la API es `deploy/htaccess-public`. La clave de MySQL va solo en `config/.env` del servidor. El procedimiento y el checklist del corte están en `docs/deploy.md`.

## Estado — 23 de octubre de 2026 (rendimiento interfaz)

Hecho: shell más liviano (chunks lazy, vendors React/UI, Bootstrap SCSS recortado, prefetch de Agenda, esqueleto de sesión). Video de fondo se mantiene aligerado (`fondo-opt.mp4` ~771 KB; máster en `media-fuente/`). Publicado en `3bigotesbarberie.com` (`dist-web/` → `dist/`). Procedimiento en `docs/deploy.md`.

## Estado — 23 de octubre de 2026 (horarios)

Hecho: módulo Horarios en las tres capas — migración `012`, API de tramos/bloqueos y pantalla de gestión. La disponibilidad de reserva deja de ser fija 08:00–20:00. Publicado en `3bigotesbarberie.com` (archivos + MySQL `012`).

## Estado — 4 de noviembre de 2026 (caja medios y cierres)

Hecho: medios de caja solo Efectivo y Digital; consulta de cierres anteriores con filtro de fechas. Capas: `database/` (`015`), `models/Caja.php`, `controllers/CajaController.php`, `view/modules/caja/`, `view/cliente/caja.js`.

## Estado — 23 de octubre de 2026 (caja UI)

Hecho: en frontend, Caja separa cobro / cierre / ajustes y muestra ticket al cobrar. Sin cambio de API ni de MySQL.

## Estado — 23 de octubre de 2026 (caja + deploy)

Hecho: cobro por pasos en Caja, citas del día en el módulo, atajo desde Agenda. Publicado en `3bigotesbarberie.com` (`dist-web/` → `dist/`, `CajaController.php`, `Caja.php`). Documentado en `docs/deploy.md`.

El HTML y el PHP no comparten archivo. `view/` es el frontend. `public/`, `api/`, `controllers/`, `models/` y `config/` son PHP y responden JSON.

Orden cuando un cambio cruza capas: migración, luego API, luego pantalla.

## Flujo de sesión

1. `POST /api/sesion` con correo y clave. La ruta es pública.
2. La API guarda solo el hash del token y lo devuelve en claro una vez.
3. El navegador lo deja en `sessionStorage` y lo manda en `Authorization: Bearer`.
4. El resto de rutas exige ese token. El establecimiento sale del alcance del usuario (sede fija o sede de trabajo del super), no del cuerpo libre.
5. `DELETE /api/sesion` borra esa sesión.

La sesión dura 12 horas. Zona horaria de la API: `America/Bogota`.

## Estado — 23 de octubre de 2026 (alcance usuario / sede)

Hecho: jerarquía de aislamiento alineada con refrigeracioncyc — super (sistema), admin de sede y roles inferiores. Migración `013`, API de alcance y UI de asignación de sede. Publicado en `3bigotesbarberie.com`.

## Estado — 21 de octubre de 2026 (gestión UI)

Hecho: menú lateral compacto con usuario y rol de la sesión; al entrar (`/gestion`) abre Agenda si el rol la tiene. Acciones de catálogo y agenda con iconos (crear / editar / eliminar o baja). `PUT /api/citas/{id}` edita citas pendientes. Publicado en `3bigotesbarberie.com` (`dist-web/` → `dist/` + controladores/modelos de citas, caja, colaboradores y reseña).

## Estado — 21 de octubre de 2026 (tarde)

Hecho: en el hosting `APP_ORIGEN` apuntaba a Vite local y rompía CORS en parte de los navegadores. Quedó el dominio de producción. La API acepta el origen del propio host. Se documentó que el DNS del dominio a veces falla por IPv6 en algunos equipos: si no carga o no entra, probar red/DNS o otro navegador.

## Estado — 21 de octubre de 2026

Hecho: la portada pública carga más rápido sin cambiar el video del cliente. La interfaz usa `fondo-opt.mp4` (mismo clip comprimido), poster y carga diferida; el original queda en `view/public/media/fondo.mp4`. Las pantallas de gestión van en chunks con `React.lazy`. `GET /api/reserva/horarios` sin profesional responde de una vez a todos los disponibles. Publicado en `3bigotesbarberie.com` con `npm run build:sitio` (carpeta `dist-web/` como `dist/`) y el controlador de citas actualizado.

## Estado — 8 de octubre de 2026

Hecho: pantallas Sedes, Usuarios y Roles. La migración `007_roles_permisos.sql` agrega el nombre del rol y las tablas `permiso` y `rol_permiso`. Cada acción exige un permiso de esa lista. El corte está publicado en `3bigotesbarberie.com` con `deploy/htaccess-raiz`, `deploy/htaccess-public` y la interfaz de `npm run build:sitio`.

## Estado — 6 de octubre de 2026

Hecho: la interfaz se abre en Apache en `http://localhost/tres_bigotes/`. `vite.config.js` publica en `dist/` con base `/tres_bigotes/`. Completar una cita descuenta los insumos definidos en `servicio_insumo`. Si no hay stock, la cita no cambia de estado. El catálogo se administra en la pantalla Servicios y usa los endpoints que ya existían; no hay tabla nueva.

Falta: nada de este corte.

## Estado — 4 de noviembre de 2026 (recordatorios WhatsApp)

Hecho: en Agenda, recordatorios asistidos del día por WhatsApp (`wa.me`, sin Meta ni API). Solo capa `view/` (`PaginaAgenda.jsx`, icono en `Iconos.jsx`). Publicado en `3bigotesbarberie.com`. Detalle en `docs/frontend.md` y `docs/deploy.md`; dominio en `dominio.md`.

## Estado — 5 de noviembre de 2026 (correo de citas)

Hecho: envío SMTP desde `atencion@3bigotesbarberie.com` — confirmación al reservar y recordatorio dos horas antes. Capas: `config/Correo.php`, `models/Aviso.php`, `controllers/AvisosController.php`, migración `016`, mensaje en `PaginaReserva.jsx`. Publicado en `3bigotesbarberie.com` con cron PHP en hPanel; prueba real OK.

## Estado — 21 de octubre de 2026 (caja)

Hecho: caja con medios Nequi, QR y Efectivo; ajustes solo para administrador; arqueo con base y movimientos; UI de cobro con aviso verde, total de referencia y “Ventas del turno”. Capas: `database/` (`010`), `models/Caja.php`, `controllers/CajaController.php`, `view/modules/caja/`, `view/index.css`. Publicado en `3bigotesbarberie.com`.

## Estado — 5 de octubre de 2026

Hecho: carpetas de las tres capas, migración inicial, semilla de desarrollo, núcleo HTTP, login, marco de la aplicación, colaboradores, inventario, caja, agenda, reserva pública, ficha clínica y comisiones. La caja cobra la cita completada al precio aplicado. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita. El modelo no tiene esa relación.
