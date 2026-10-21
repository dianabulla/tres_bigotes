import 'bootstrap/dist/css/bootstrap.min.css'
import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes, useLocation } from 'react-router-dom'
import CursorGlow from './components/CursorGlow'
import Fondo from './components/Fondo'
import Marco from './components/Marco'
import PaginaIngreso from './components/PaginaIngreso'
import RutaPrivada from './components/RutaPrivada'
import { useSesion } from './components/Sesion'
import PaginaReserva from './modules/agenda/PaginaReserva'
import { modulos } from './modules/registro'

const PaginaAgenda = lazy(() => import('./modules/agenda/PaginaAgenda'))
const PaginaCaja = lazy(() => import('./modules/caja/PaginaCaja'))
const PaginaClinica = lazy(() => import('./modules/clinica/PaginaClinica'))
const PaginaColaboradores = lazy(() => import('./modules/colaboradores/PaginaColaboradores'))
const PaginaComisiones = lazy(() => import('./modules/colaboradores/PaginaComisiones'))
const PaginaInventario = lazy(() => import('./modules/inventario/PaginaInventario'))
const PaginaRoles = lazy(() => import('./modules/roles/PaginaRoles'))
const PaginaSedes = lazy(() => import('./modules/sedes/PaginaSedes'))
const PaginaServicios = lazy(() => import('./modules/servicios/PaginaServicios'))
const PaginaUsuarios = lazy(() => import('./modules/usuarios/PaginaUsuarios'))

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
  if (!destino) {
    return (
      <div className="m-4 alert alert-warning" role="alert">
        Tu usuario entró, pero no tiene permisos de ningún módulo. Pide al administrador que revise el rol.
      </div>
    )
  }
  return <Navigate to={destino.ruta} replace />
}

function CargaRuta() {
  return (
    <div className="d-flex justify-content-center align-items-center py-5" role="status" aria-live="polite">
      <span className="visually-hidden">Cargando…</span>
    </div>
  )
}

export default function App() {
  const { pathname } = useLocation()
  const conAmbiente = pathname === '/ingreso'

  return (
    <>
    {conAmbiente && <Fondo />}
    {conAmbiente && <CursorGlow />}
    <div className="app-capa">
    <Suspense fallback={<CargaRuta />}>
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
    </Suspense>
    </div>
    </>
  )
}
