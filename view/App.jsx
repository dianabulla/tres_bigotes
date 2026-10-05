import 'bootstrap/dist/css/bootstrap.min.css'
import { Navigate, Route, Routes } from 'react-router-dom'
import CursorGlow from './components/CursorGlow'
import Fondo from './components/Fondo'
import Marco from './components/Marco'
import PaginaIngreso from './components/PaginaIngreso'
import RutaPrivada from './components/RutaPrivada'
import { useSesion } from './components/Sesion'
import PaginaAgenda from './modules/agenda/PaginaAgenda'
import PaginaReserva from './modules/agenda/PaginaReserva'
import PaginaCaja from './modules/caja/PaginaCaja'
import PaginaClinica from './modules/clinica/PaginaClinica'
import PaginaColaboradores from './modules/colaboradores/PaginaColaboradores'
import PaginaComisiones from './modules/colaboradores/PaginaComisiones'
import PaginaInventario from './modules/inventario/PaginaInventario'
import { modulos } from './modules/registro'

const paginas = {
  agenda: PaginaAgenda,
  clinica: PaginaClinica,
  colaboradores: PaginaColaboradores,
  comisiones: PaginaComisiones,
  inventario: PaginaInventario,
  caja: PaginaCaja,
}

function RedirigirInicio() {
  const { usuario } = useSesion()
  const destino = modulos.find((modulo) => modulo.roles.includes(usuario.rol))
  return <Navigate to={destino ? destino.ruta : '/ingreso'} replace />
}

export default function App() {
  return (
    <>
    <Fondo />
    <CursorGlow />
    <div className="app-capa">
    <Routes>
      <Route path="/ingreso" element={<PaginaIngreso />} />
      <Route path="/reservar" element={<PaginaReserva />} />
      <Route element={<RutaPrivada />}>
        <Route element={<Marco />}>
          <Route path="gestion" element={<RedirigirInicio />} />
          {modulos.map((modulo) => {
            const Pagina = paginas[modulo.id]
            return (
              <Route key={modulo.id} path={modulo.ruta.slice(1)} element={<RutaPrivada roles={modulo.roles} />}>
                <Route index element={<Pagina />} />
              </Route>
            )
          })}
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/ingreso" replace />} />
    </Routes>
    </div>
    </>
  )
}
