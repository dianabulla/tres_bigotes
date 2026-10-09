# Contexto 3 Bigotes

Sistema web de gestión para la barbería 3 Bigotes, según la propuesta de Innovatyp del 1 de octubre de 2026. Cubre agenda, ficha clínica, colaboradores y comisiones, inventario, y apertura y cierre de caja. Cada sede ve solo sus datos.

## Cómo se documenta

Cada skill obliga a dejar el contexto por escrito al cerrar la tarea. No se pega código: se anota qué cambió, qué quedó hecho y qué falta.

| Si tocaste | Actualiza |
|---|---|
| Límites, módulos o flujo | `docs/arquitectura.md` y, si cambia el dominio, el skill de arquitectura |
| `view/` | `docs/frontend.md` |
| `api/`, `controllers/`, `models/`, `config/` o `public/` | `docs/backend.md` |
| `database/` | `docs/base-de-datos.md` y `modelo.md` si el esquema cambió |

Al empezar una tarea, el primer paso es leer este archivo y el documento de la capa.

El día 5 de octubre de 2026 quedó el primer corte funcional. El relato de ese día está en `docs/avance-2026-10-05.md`.

## Decisiones fijas

- Vistas: React 18 en `view/`, Vite, Bootstrap 5, `react-bootstrap`, `react-router-dom`
- API: PHP 8 en `public/`, `api/`, `controllers/`, `models/` y `config/`, JSON, token Bearer, PDO
- Datos: MySQL, InnoDB, claves foráneas
- El ejemplo visual anterior quedó integrado en `view/`

## Fuera del producto

Hosting, dominio y el contrato de arrendamiento entre Innovatyp y el cliente.
