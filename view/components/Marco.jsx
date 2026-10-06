import { Button, Container, Nav, Navbar } from 'react-bootstrap'
import { NavLink, Outlet } from 'react-router-dom'
import { modulosVisibles } from '../modules/registro'
import { useSesion } from './Sesion'

export default function Marco() {
  const { usuario, salir } = useSesion()
  const visibles = modulosVisibles(usuario.rol)

  return (
    <>
      <Navbar className="marca px-3 py-2">
        <Navbar.Brand as={NavLink} to="/gestion" className="py-1 me-3">
          <img src={`${import.meta.env.BASE_URL}media/logo.jpeg`} alt="3 Bigotes" className="marca-logo" />
        </Navbar.Brand>
        <Nav className="marca-modulos flex-row flex-wrap">
          {visibles.map((modulo) => (
            <Nav.Link as={NavLink} key={modulo.id} to={modulo.ruta}>
              {modulo.titulo}
            </Nav.Link>
          ))}
        </Nav>
        <div className="d-flex align-items-center gap-3 ms-lg-auto">
          <Navbar.Text className="mb-0">
            {usuario.nombre} · {usuario.rol}
          </Navbar.Text>
          <Button variant="outline-primary" size="sm" onClick={salir}>
            Salir
          </Button>
        </div>
      </Navbar>
      <Container fluid className="py-4">
        <Outlet />
      </Container>
    </>
  )
}
