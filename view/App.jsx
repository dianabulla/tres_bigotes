import 'bootstrap/dist/css/bootstrap.min.css'
import { Navigate, Route, Routes, useLocation } from 'react-router-dom'
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
import PaginaRoles from './modules/roles/PaginaRoles'
import PaginaSedes from './modules/sedes/PaginaSedes'
import PaginaServicios from './modules/servicios/PaginaServicios'
import PaginaUsuarios from './modules/usuarios/PaginaUsuarios'
import { modulos } from './modules/registro'

const paginas = {
  agenda: PaginaAgenda,
  clinica: PaginaClinica,
  colaboradores: PaginaColaboradores,
  comisiones: PaginaComisiones,
  inventario: PaginaInventario,
  caja: PaginaCaja,
  servicios: PaginaServicios,
  sedes: PaginaSedes,
  usuarios: PaginaUsuarios,
  roles: PaginaRoles,
}

function RedirigirInicio() {
  const { usuario } = useSesion()
  const destino = modulos.find((modulo) => modulo.permisos.some((codigo) => (usuario.permisos ?? []).includes(codigo)))
  return <Navigate to={destino ? destino.ruta : '/ingreso'} replace />
}

export default function App() {
  const { pathname } = useLocation()
  const conAmbiente = pathname === '/ingreso'

  return (
    <>
    {conAmbiente && <Fondo />}
    {conAmbiente && <CursorGlow />}
    <div className="app-capa">
    <Routes>
      <Route path="/" element={<PaginaReserva />} />
      <Route path="/reservar" element={<Navigate to="/" replace />} />
      <Route path="/ingreso" element={<PaginaIngreso />} />
      <Route element={<RutaPrivada />}>
        <Route element={<Marco />}>
          <Route path="gestion" element={<RedirigirInicio />} />
          {modulos.map((modulo) => {
            const Pagina = paginas[modulo.id]
            return (
              <Route key={modulo.id} path={modulo.ruta.slice(1)} element={<RutaPrivada permisos={modulo.permisos} />}>
                <Route index element={<Pagina />} />
              </Route>
            )
          })}
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
    </div>
    </>
  )
}
