# Deploy 3 Bigotes

## Dónde vive cada entorno

| Entorno | URL | Interfaz | API |
|---|---|---|---|
| Local (Apache) | `http://localhost/tres_bigotes/` | carpeta `dist/` | `public/` en el mismo repo |
| Producción | `https://3bigotesbarberie.com/` | `dist/` en la raíz del hosting | `public/` en el hosting |

El código fuente de pantallas está en `view/`. Apache y el dominio **no** leen `view/` directamente.

## Local

Después de cambiar `view/`:

```bash
npm run build
```

Eso regenera `dist/`. Recarga forzada en el navegador (`Ctrl+F5`) por si quedó un JS viejo en caché.

Los cambios de PHP (`controllers/`, `models/`) en local se ven al instante: XAMPP ya sirve esos archivos.

Migraciones nuevas en local:

```text
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 tres_bigotes < database\migrations\NNN_….sql
```

Opcional en desarrollo: `npm run dev` (Vite en `:5173`) contra la API de `public/`.

## Producción (`3bigotesbarberie.com`)

1. Generar el sitio con base `/` y API del dominio:

```bash
npm run build:sitio
```

Salida: carpeta `dist-web/`. Comprobar:

```bash
node scripts/verificar-build-web.mjs
```

2. En el hosting, subir el contenido de `dist-web/` como `dist/` (reemplazar assets e `index.html`).

3. Subir la API tocada en el mismo corte (PHP nuevo o modificado).

4. Aplicar migraciones SQL nuevas en la base de producción (phpMyAdmin o cliente MySQL remoto). No subir `config/.env` del local.

5. `.htaccess`: raíz = `deploy/htaccess-raiz`; API = `deploy/htaccess-public`.

6. Tras publicar, hard refresh o ventana privada. Si el `index.html` viejo queda en caché, se sigue viendo el JS anterior.

## Corte 5 de noviembre de 2026 (descripción en checklist móvil)

Solo frontend. La descripción de cada servicio en el checklist de reserva vuelve a verse en móvil (tipografía pequeña, máx. 2 líneas).

### Build

```bash
npm run build:sitio
node scripts/verificar-build-web.mjs
```

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |

### Estado de publicación (5 nov 2026)

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/` (26 archivos).
- Bundles: `index-Dm65Ea-t.js`, `index-CXXjZiQn.css`, `PaginaReserva-BNONBrr4.js`.
- API intacta (`/public/api/salud` OK). Hard refresh / ventana privada para ver el cambio.

## Corte 4 de noviembre de 2026 (recordatorios WhatsApp)

Solo frontend. Recordatorios asistidos del día en Agenda (`wa.me`, envío manual).

### Build

```bash
npm run build:sitio
node scripts/verificar-build-web.mjs
```

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |

### Estado de publicación (4 nov 2026)

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/` (26 archivos).
- Bundles: `index-DA-3gYKi.js`, `PaginaAgenda-9GPdaBk_.js`.
- API intacta (`/public/api/salud` OK). Hard refresh / ventana privada para ver el cambio.

## Corte 23 de octubre de 2026 (caja + horarios)

Incluye todo el trabajo del día:

- Caja más clara: cobro principal, cierre y ajustes en vistas aparte, botones con texto, ticket al cobrar, paneles Servicios/Productos excluyentes, desglose efectivo + base.
- Módulo Horarios: horario semanal y bloqueos (almuerzo / descanso) que ocultan huecos en la reserva pública.
- Migración `012_horarios_y_bloqueos.sql` (tablas + permiso `horarios`).

### Build

```bash
npm run build
npm run build:sitio
```

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |
| `api/rutas.php` | `api/rutas.php` |
| `controllers/CajaController.php` | `controllers/CajaController.php` |
| `controllers/CitasController.php` | `controllers/CitasController.php` |
| `controllers/HorariosController.php` | `controllers/HorariosController.php` |
| `controllers/SedesController.php` | `controllers/SedesController.php` |
| `models/Caja.php` | `models/Caja.php` |
| `models/Horario.php` | `models/Horario.php` |
| `database/migrations/012_horarios_y_bloqueos.sql` | (ejecutar en MySQL de producción, no hace falta dejarlo en `public_html`) |

### MySQL producción

Ejecutar una sola vez:

```text
database/migrations/012_horarios_y_bloqueos.sql
```

Si ya corrió, no repetir (falla por tablas/permiso duplicados).

### Tras publicar

- Hard refresh / ventana privada.
- Cerrar sesión y volver a entrar (admin/recepción) para cargar el permiso `horarios` y ver el menú.
- Probar: Caja (cobro + ticket + cierre con desglose) y Horarios (bloquear almuerzo → no sale en `/`).

Estado de publicación (23 oct 2026, tarde):

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/`, PHP del corte, migración `012` aplicada en MySQL.
- Bundles: `index-CdS4uswY.js`, `PaginaCaja-D2-2P5_V.js`, `PaginaHorarios-D5xV_TL-.js`, `PaginaAgenda-5Nqy8Ty9.js`.
- Nota FTP: `ftp.3bigotesbarberie.com` a veces no resuelve en curl; usar la IP del FTP. No guardar claves FTP en el repo.

## Corte 23 de octubre de 2026 (rendimiento interfaz)

Solo frontend. Regenerar y subir el contenido de `dist-web/` como `dist/` (incluye `media/fondo-opt.mp4` ~771 KB). No hace falta PHP ni SQL.

### Build

```bash
npm run build:sitio
node scripts/verificar-build-web.mjs
```

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |

FTP: host `ftp.3bigotesbarberie.com` (si no resuelve, IP `212.85.6.246`), usuario del panel Hostinger, destino relativo `dist/…`. No guardar la clave en el repo.

### Tras publicar

- Hard refresh / ventana privada (el CDN a veces cachea el MP4 unos minutos).
- Comprobar en el HTML: `index-DJ7liVBQ.js`, `react-DSVHYivw.js`, `ui-KHD30Nd3.js`, `index-i7UJ9LPr.css`.
- Video publicado: `media/fondo-opt.mp4` ~789 KB. El máster `media-fuente/fondo.mp4` no se sube.

### Estado de publicación (23 oct 2026, rendimiento)

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/` (26 archivos).
- Bundles en vivo: `index-DJ7liVBQ.js`, `react-DSVHYivw.js`, `ui-KHD30Nd3.js`, `PaginaReserva-B9JMQzag.js`, `index-i7UJ9LPr.css`.
- `dist/media/fondo.mp4` (máster) no está en el servidor; solo `fondo-opt.mp4`, poster y logo.
- API intacta (`/public/api/salud` OK).

## Corte 30 de octubre de 2026 (reserva multi-servicio + orden)

- Orden del catálogo: corte → ritual barba → cejas → spa facial → barba express → combo (`014_orden_servicio.sql`).
- El cliente puede marcar varios servicios y continuar.
- Publicado: `dist-web/` → `dist/`, `models/Servicio.php`, migración `014`. Bundle `PaginaReserva-E7f36mRX.js`.

## Corte 23 de octubre de 2026 (alcance usuario / sede)

### Build

```bash
npm run build:sitio
node scripts/verificar-build-web.mjs
```

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |
| `controllers/Acceso.php` | `controllers/Acceso.php` |
| `controllers/SesionController.php` | `controllers/SesionController.php` |
| `controllers/UsuariosController.php` | `controllers/UsuariosController.php` |
| `controllers/SedesController.php` | `controllers/SedesController.php` |
| `controllers/RolesController.php` | `controllers/RolesController.php` |
| `controllers/CajaController.php` | `controllers/CajaController.php` |
| `models/Sesion.php` | `models/Sesion.php` |
| `models/Usuario.php` | `models/Usuario.php` |
| `models/Rol.php` | `models/Rol.php` |
| `database/migrations/013_alcance_usuario.sql` | (ejecutar en MySQL; no hace falta dejarlo público) |

### MySQL producción

Ejecutar una sola vez: `013_alcance_usuario.sql` (rol `superadministrador`, sede nula en super, `sesion.establecimiento_activo_id`).

### Tras publicar

- Hard refresh / ventana privada.
- Cerrar sesión y volver a entrar.
- Super: `super@tresbigotes.local` / `tresbigotes` — menú Sedes + selector de sede de trabajo + Usuarios con asignación de sede.
- Admin de sede: ya no ve Sedes (`403`).

### Estado de publicación (23 oct 2026, alcance)

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/`, PHP del corte, migración `013` aplicada.
- Bundles: `index-BfVGSiXr.js`, `PaginaUsuarios-DOPGOcu1.js`, `react-DSVHYivw.js`, `index-BPb21K0k.css`.
- Verificado: login super (`alcance=sistema`), listado global de usuarios, admin sin permiso `sedes`.

## Corte 4 de noviembre de 2026 (caja medios + cierres)

- Medios: solo Efectivo y Digital.
- Vista de cierres anteriores con filtro de fechas (`GET /api/caja/cierres`).
- Migración `015_medios_efectivo_digital.sql`.

### Subir al hosting (`public_html`)

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |
| `controllers/CajaController.php` | `controllers/CajaController.php` |
| `models/Caja.php` | `models/Caja.php` |
| `database/migrations/015_medios_efectivo_digital.sql` | (ejecutar en MySQL; no dejar migrador público) |

### Estado de publicación (4 nov 2026)

- Publicado en `3bigotesbarberie.com` por FTP: `dist-web/` → `dist/`, PHP de caja, migración `015` aplicada.
- Bundles: `index-DJ2L6UFX.js`, `PaginaCaja-C4wCu8HT.js`, `index-Djx4WL3k.css`.
- API `/public/api/salud` OK. Migrador one-shot borrado tras aplicar.

## Corte 4–5 de noviembre de 2026 (correo de citas)

- Confirmación SMTP al reservar + recordatorio 2 h antes (`aviso_cita`).
- Remitente: `atencion@3bigotesbarberie.com`. Variables `MAIL_*` en `config/.env` del hosting (no subir el `.env` local).
- Endpoint manual/prueba: `GET|POST /public/api/avisos/enviar` con `MAIL_CRON_CLAVE`.
- Migración `016_indice_aviso_envio.sql`.

### Subir

| Origen local | Destino |
|---|---|
| Contenido de `dist-web/` | `dist/` |
| `config/Correo.php` | `config/Correo.php` |
| `models/Aviso.php` | `models/Aviso.php` |
| `controllers/AvisosController.php` | `controllers/AvisosController.php` |
| `controllers/CitasController.php` | `controllers/CitasController.php` |
| `api/rutas.php` | `api/rutas.php` |
| `scripts/enviar-avisos.php` | `scripts/enviar-avisos.php` |
| `config/.env.produccion` (MAIL_* + DB) | `config/.env` |

### Cron en hPanel (ya creado)

Tipo **PHP**, cada 5 minutos (o cada hora si el panel no deja `*` en hora con minuto `*/5`):

```text
/usr/bin/php /home/u694856656/domains/3bigotesbarberie.com/public_html/scripts/enviar-avisos.php
```

En el formulario PHP de Hostinger el prefijo gris ya trae `/usr/bin/php /home/u694856656/`; el campo editable es:

```text
domains/3bigotesbarberie.com/public_html/scripts/enviar-avisos.php
```

Prueba manual: `https://3bigotesbarberie.com/public/api/avisos/enviar?clave=<MAIL_CRON_CLAVE>` → `{"ok":true,...}`.

### Estado de publicación (5 nov 2026)

- Publicado por FTP: `dist-web/` → `dist/`, PHP del correo, `config/.env` con `MAIL_*`, migración `016` aplicada.
- Bundles: `index-DA-3gYKi.js`, `PaginaReserva-vO6cP2N9.js`.
- Cron PHP activo en hPanel. Confirmación de reserva verificada en prueba real.

## Checklist rápido

- [ ] `npm run build` si miras `localhost/tres_bigotes`
- [ ] `npm run build:sitio` si publicas el dominio
- [ ] Subir `dist-web/` → `dist/`
- [ ] Subir PHP del corte
- [ ] Aplicar migraciones SQL en producción
- [ ] Hard refresh / ventana privada
- [ ] Reingresar sesión si hay permisos nuevos
- [ ] Probar cobro en Caja y bloqueo en Horarios
