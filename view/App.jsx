import './bootstrap-tb.scss'
import { lazy, Suspense, useEffect } from 'react'
import { Navigate, Route, Routes, useLocation } from 'react-router-dom'
import CursorGlow from './components/CursorGlow'
import Fondo from './components/Fondo'
import Marco from './components/Marco'
import PaginaIngreso from './components/PaginaIngreso'
import RutaPrivada from './components/RutaPrivada'
import { useSesion } from './components/Sesion'
import { cargasPagina, prefetchInicioGestion, prefetchPagina } from './modules/cargas'
import { modulos } from './modules/registro'

const PaginaReserva = lazy(cargasPagina.reserva)
const PaginaAgenda = lazy(cargasPagina.agenda)
const PaginaCaja = lazy(cargasPagina.caja)
const PaginaClinica = lazy(cargasPagina.clinica)
const PaginaColaboradores = lazy(cargasPagina.colaboradores)
const PaginaComisiones = lazy(cargasPagina.comisiones)
const PaginaHorarios = lazy(cargasPagina.horarios)
const PaginaInventario = lazy(cargasPagina.inventario)
const PaginaRoles = lazy(cargasPagina.roles)
const PaginaSedes = lazy(cargasPagina.sedes)
const PaginaServicios = lazy(cargasPagina.servicios)
const PaginaUsuarios = lazy(cargasPagina.usuarios)

const paginas = {
  agenda: PaginaAgenda,
  horarios: PaginaHorarios,
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
  const permisos = usuario.permisos ?? []
  const agenda = modulos.find((modulo) => modulo.id === 'agenda' && modulo.permisos.some((codigo) => permisos.includes(codigo)))
  const destino = agenda
    || modulos.find((modulo) => modulo.permisos.some((codigo) => permisos.includes(codigo)))
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
    <div className="carga-ruta" role="status" aria-live="polite">
      <span className="carga-ruta-punto" aria-hidden="true" />
      <span className="visually-hidden">Cargando…</span>
    </div>
  )
}

function PrefetchGestion() {
  const { pathname } = useLocation()
  const { usuario } = useSesion()

  useEffect(() => {
    if (pathname.startsWith('/gestion') && usuario?.permisos) {
      prefetchInicioGestion(usuario.permisos)
    }
  }, [pathname, usuario])

  return null
}

export default function App() {
  const { pathname } = useLocation()
  const conAmbiente = pathname === '/ingreso'

  useEffect(() => {
    if (pathname === '/' || pathname === '/reservar') {
      prefetchPagina('reserva')
    }
  }, [pathname])

  return (
    <>
    {conAmbiente && <Fondo />}
    {conAmbiente && <CursorGlow />}
    <PrefetchGestion />
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
