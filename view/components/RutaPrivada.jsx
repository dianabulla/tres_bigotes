import { Alert } from 'react-bootstrap'
import { Navigate, Outlet } from 'react-router-dom'
import { useSesion } from './Sesion'

function EsqueletoSesion() {
  return (
    <div className="gestion-shell carga-sesion" role="status" aria-live="polite">
      <aside className="marca-lateral d-none d-lg-flex" aria-hidden="true">
        <div className="carga-sesion-bloque carga-sesion-logo" />
        <div className="carga-sesion-bloque" />
        <div className="carga-sesion-bloque" />
        <div className="carga-sesion-bloque" />
        <div className="carga-sesion-bloque carga-sesion-pie" />
      </aside>
      <div className="gestion-contenido py-3 py-md-4 px-3 px-md-4">
        <div className="carga-sesion-panel">
          <div className="carga-sesion-bloque carga-sesion-titulo" />
          <div className="carga-sesion-bloque carga-sesion-linea" />
          <div className="carga-sesion-bloque carga-sesion-linea" />
        </div>
        <span className="visually-hidden">Cargando sesión</span>
      </div>
    </div>
  )
}

export default function RutaPrivada({ permisos }) {
  const { usuario, cargando } = useSesion()

  if (cargando) {
    return <EsqueletoSesion />
  }

  if (!usuario) {
    return <Navigate to="/ingreso" replace />
  }

  const concedidos = usuario.permisos ?? []
  if (permisos && !permisos.some((codigo) => concedidos.includes(codigo))) {
    return <Alert variant="warning" className="m-4">No tienes permiso para esta pantalla.</Alert>
  }

  return <Outlet />
}
