# Arquitectura

Tres carpetas. Ninguna escribe el trabajo de otra.

| Capa | Carpeta | Entra por |
|---|---|---|
| Vistas | `view/` | Vite, puerto 5173. Ahí está `index.html` y React |
| API | `api/` | Rutas y formato JSON |
| Controladores | `controllers/` | Reglas de la petición |
| Modelos | `models/` | Consultas |
| Configuración | `config/` | `.env` |
| Entrada | `public/` | `public/index.php` |
| Datos | `database/` | `database/migrations/` |

El navegador llama a `VITE_API_URL` (en local, `http://localhost/tres_bigotes/public`). La API lee `config/.env`, abre MySQL y responde JSON. El esquema no se crea desde PHP.

El HTML y el PHP no comparten archivo. `view/` es el frontend. `public/`, `api/`, `controllers/`, `models/` y `config/` son PHP y responden JSON.

Orden cuando un cambio cruza capas: migración, luego API, luego pantalla.

## Flujo de sesión

1. `POST /api/sesion` con correo y clave. La ruta es pública.
2. La API guarda solo el hash del token y lo devuelve en claro una vez.
3. El navegador lo deja en `sessionStorage` y lo manda en `Authorization: Bearer`.
4. El resto de rutas exige ese token. El establecimiento sale del usuario, no del cuerpo.
5. `DELETE /api/sesion` borra esa sesión.

La sesión dura 12 horas. Zona horaria de la API: `America/Bogota`.

## Estado — 5 de octubre de 2026

Hecho: carpetas de las tres capas, migración inicial, semilla de desarrollo, núcleo HTTP, login, marco de la aplicación, colaboradores, inventario, caja, agenda, reserva pública, ficha clínica y comisiones. La caja cobra la cita completada al precio aplicado. El detalle del día está en `docs/avance-2026-10-05.md`.

Falta: descontar insumos al completar una cita. El modelo no tiene esa relación.
