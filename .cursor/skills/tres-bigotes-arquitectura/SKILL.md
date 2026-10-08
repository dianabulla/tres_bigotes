---
name: tres-bigotes-arquitectura
description: >-
  Define los límites del sistema Tres Bigotes: React y Bootstrap 5 en el
  frontend, API PHP en el backend y MySQL relacional aparte. Úsala al empezar
  una tarea, al decidir en qué carpeta va un cambio, al documentar el avance,
  o cuando el usuario pida agendamiento, ficha clínica, comisiones, inventario,
  caja, multisede o roles.
---

# Arquitectura Tres Bigotes

Sistema de gestión para la barbería Tres Bigotes. Tres capas, tres carpetas, tres skills. Ninguna capa escribe el trabajo de otra.

## Capas

| Capa | Carpeta | Skill | Puede |
|---|---|---|---|
| Vistas | `view/` | `tres-bigotes-frontend` | `index.html`, React, Bootstrap 5, llamadas HTTP |
| API | `api/` | `tres-bigotes-backend` | Rutas, petición, respuesta JSON y guardia del token |
| Control | `controllers/` | `tres-bigotes-backend` | Validar la petición y decidir la respuesta |
| Modelos | `models/` | `tres-bigotes-backend` | Consultas MySQL |
| Configuración | `config/` | `tres-bigotes-backend` | `.env` y la conexión PDO |
| Entrada HTTP | `public/` | `tres-bigotes-backend` | `index.php`, sin HTML |
| Datos | `database/` | `tres-bigotes-mysql` | Esquema, migraciones y seeds MySQL |

`view/` es solo la aplicación de gestión. No poner lógica de negocio ahí.

## Regla de separación

Antes de editar, nombra la capa. Si el cambio cruza capas, hazlo en este orden: `database/`, luego `models/` y `controllers/`, luego `view/`.

Prohibido:

- SQL, PDO o credenciales dentro de `view/`
- HTML, JSX, CSS o Bootstrap dentro de `api/`, `controllers/`, `models/`, `config/`, `public/` o `database/`
- PHP dentro de `view/`
- Crear o alterar tablas desde PHP o desde React. El DDL vive solo en `database/migrations/`
- Un archivo que atienda HTTP y pinte interfaz

El único HTML de la aplicación es `view/index.html`. Las pantallas son JSX en `view/`. PHP solo responde JSON.

## Stack fijo

- Interfaz: React 18, Vite, Bootstrap 5 mediante `react-bootstrap`
- API: PHP 8 sobre XAMPP, respuestas JSON, token Bearer
- Datos: MySQL, InnoDB, claves foráneas, consultas preparadas
- Cada registro operativo pertenece a un `establecimiento_id`

## Alcance

Módulos: agendamiento, hoja clínica, colaboradores y comisiones, inventario, apertura y cierre de caja.

Roles: `administrador`, `recepcion` y `colaborador` son del sistema. Se pueden crear más y asignarles permisos del catálogo.

Fuera del producto: hosting, dominio y el contrato de arrendamiento entre Innovatyp y el cliente.

Detalle de módulos, estados y roles: [dominio.md](dominio.md).

## Documentación

Al empezar, lee [../../../docs/contexto.md](../../../docs/contexto.md) y [../../../docs/arquitectura.md](../../../docs/arquitectura.md).

Al terminar una tarea, actualiza el documento de cada capa tocada. Anota archivos, qué quedó hecho y qué falta. No pegues código.

| Capa | Documento |
|---|---|
| Límites y flujo | `docs/arquitectura.md` |
| Frontend | `docs/frontend.md` |
| Backend | `docs/backend.md` |
| Datos | `docs/base-de-datos.md` |

Si cambian módulos, roles o estados, actualiza [dominio.md](dominio.md) en el mismo cambio.
