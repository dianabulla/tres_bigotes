# 3 Bigotes

Gestión de la barbería: agenda, reserva pública, ficha clínica, colaboradores, comisiones, inventario y caja.

Tres capas separadas:

| Capa | Carpeta |
|---|---|
| Vistas React | `view/` |
| API PHP, solo JSON | `api/`, `controllers/`, `models/`, `config/`, `public/` |
| MySQL | `database/` |

En local, la aplicación abre en `http://localhost:5173` y la API en `http://localhost/tres_bigotes/public`.

```bash
npm install
npm run dev
```

El contexto del producto está en `docs/contexto.md`. Lo construido el 5 de octubre de 2026 está en `docs/avance-2026-10-05.md`.
