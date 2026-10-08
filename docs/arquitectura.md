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

En `3bigotesbarberie.com` el sitio vive en la raíz del dominio, no en `/tres_bigotes/`. `npm run build:sitio` genera `dist-web/` con esa base y con la API en `https://3bigotesbarberie.com/public`. En el hosting, esa carpeta se sube como `dist/`. El `.htaccess` de la raíz es `deploy/htaccess-raiz` y el de la API es `deploy/htaccess-public`. La clave de MySQL va solo en `config/.env` del servidor.

El HTML y el PHP no comparten archivo. `view/` es el frontend. `public/`, `api/`, `controllers/`, `models/` y `config/` son PHP y responden JSON.

Orden cuando un cambio cruza capas: migración, luego API, luego pantalla.

## Flujo de sesión

1. `POST /api/sesion` con correo y clave. La ruta es pública.
2. La API guarda solo el hash del token y lo devuelve en claro una vez.
3. El navegador lo deja en `sessionStorage` y lo manda en `Authorization: Bearer`.
4. El resto de rutas exige ese token. El establecimiento sale del usuario, no del cuerpo.
5. `DELETE /api/sesion` borra esa sesión.

La sesión dura 12 horas. Zona horaria de la API: `America/Bogota`.

## Estado — 8 de octubre de 2026

Hecho: pantallas Sedes, Usuarios y Roles. La migración `007_roles_permisos.sql` agrega el nombre del rol y las tablas `permiso` y `rol_permiso`. Cada acción exige un permiso de esa lista. El corte está publicado en `3bigotesbarberie.com` con `deploy/htaccess-raiz`, `deploy/htaccess-public` y la interfaz de `npm run build:sitio`.

## Estado — 6 de octubre de 2026

Hecho: la interfaz se abre en Apache en `http://localhost/tres_bigotes/`. `vite.config.js` publica en `dist/` con base `/tres_bigotes/`. Completar una cita descuenta los insumos definidos en `servicio_insumo`. Si no hay stock, la cita no cambia de estado. El catálogo se administra en la pantalla Servicios y usa los endpoints que ya existían; no hay tabla nueva.

Falta: nada de este corte.

## Estado — 5 de octubre de 2026

Hecho: carpetas de las tres capas, migración inicial, semilla de desarrollo, núcleo HTTP, login, marco de la aplicación, colaboradores, inventario, caja, agenda, reserva pública, ficha clínica y comisiones. La caja cobra la cita completada al precio aplicado. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita. El modelo no tiene esa relación.
