---
name: tres-bigotes-frontend
description: >-
  Construye la interfaz Tres Bigotes con React, Vite, Bootstrap 5 y
  react-bootstrap. Úsala al crear pantallas, componentes, formularios, agenda,
  ficha, caja, inventario o estilos. No escribe SQL ni reglas de negocio.
---

# Frontend Tres Bigotes

Solo `view/`. La interfaz muestra datos y envía intenciones. Quien calcula comisiones, stock, arqueo y permisos es la API.

Lee el dominio en [../tres-bigotes-arquitectura/dominio.md](../tres-bigotes-arquitectura/dominio.md). Pantallas por rol en [pantallas.md](pantallas.md).

## Stack

- React 18, Vite y `react-router-dom`
- Bootstrap 5 y `react-bootstrap`. Sin jQuery y sin otra librería de UI
- El CSS de Bootstrap se importa en `view/App.jsx`. `view/main.jsx` solo arranca React
- Aspecto de la interfaz inicial: fondo `#050505`, texto `#ece9e2`, oro `#d4af37`, fuentes Bebas Neue, Cinzel, Rajdhani e Inter. El logo va en círculo. Los overrides viven en `view/index.css`

## Dónde va el código

```
view/
  index.html
  cliente/        llamadas HTTP, un archivo por módulo
  modules/        pantallas del sistema
    agenda/
    clinica/
    colaboradores/
    inventario/
    caja/
  components/     marco, sesión y rutas privadas
  public/         logo y video del diseño
```

Un componente no abre `mysql`, no concatena SQL y no importa archivos de `api/`, `controllers/`, `models/` ni `database/`.

## Datos

- Base URL: `import.meta.env.VITE_API_URL`
- El token Bearer sale de `sessionStorage` y viaja en `Authorization`
- El cliente HTTP solo arma JSON, estados de carga y el mensaje de error que devuelva la API
- Precios, totales, comisiones, stock y diferencia de caja se muestran como llegan. No se recalculan en React para decidir un cobro

## Interfaz

- Formularios con componentes de `react-bootstrap`
- Agenda usable en día, semana y por colaborador
- El rol llega del endpoint de sesión. Oculta menús que ese rol no usa
- Cualquier `4xx` de permiso muestra el mensaje de la API, sin reintentar la acción por otra ruta
- Layout usable en escritorio, tableta y móvil

## Documentación

Al empezar, lee [../../../docs/contexto.md](../../../docs/contexto.md) y [../../../docs/frontend.md](../../../docs/frontend.md).

Al terminar, actualiza `docs/frontend.md`: pantallas o archivos tocados, qué quedó hecho y qué falta. No pegues componentes. Si cambia qué ve cada rol, actualiza [pantallas.md](pantallas.md).
