import { Alert, Spinner } from 'react-bootstrap'
import { Navigate, Outlet } from 'react-router-dom'
import { useSesion } from './Sesion'

export default function RutaPrivada({ roles }) {
  const { usuario, cargando } = useSesion()

  if (cargando) {
    return (
      <div className="d-flex justify-content-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando sesión</span>
        </Spinner>
      </div>
    )
  }

  if (!usuario) {
    return <Navigate to="/ingreso" replace />
  }

  if (roles && !roles.includes(usuario.rol)) {
    return <Alert variant="warning" className="m-4">No tienes permiso para esta pantalla.</Alert>
  }

  return <Outlet />
}
