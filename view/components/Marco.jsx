import { Button, Container, Nav, Navbar } from 'react-bootstrap'
import { NavLink, Outlet } from 'react-router-dom'
import { modulosVisibles } from '../modules/registro'
import { useSesion } from './Sesion'

export default function Marco() {
  const { usuario, salir } = useSesion()
  const visibles = modulosVisibles(usuario.rol)

  return (
    <>
      <Navbar expand="lg" className="marca px-3">
        <Navbar.Brand as={NavLink} to="/gestion" className="py-1">
          <img src="/media/logo.jpeg" alt="3 Bigotes" className="marca-logo" />
        </Navbar.Brand>
        <Navbar.Toggle aria-controls="menu-gestion" />
        <Navbar.Collapse id="menu-gestion">
          <Nav className="me-auto">
            {visibles.map((modulo) => (
              <Nav.Link as={NavLink} key={modulo.id} to={modulo.ruta}>
                {modulo.titulo}
              </Nav.Link>
            ))}
          </Nav>
          <Navbar.Text className="me-3">
            {usuario.nombre} · {usuario.rol}
          </Navbar.Text>
          <Button variant="outline-primary" size="sm" onClick={salir}>
            Salir
          </Button>
        </Navbar.Collapse>
      </Navbar>
      <Container fluid className="py-4">
        <Outlet />
      </Container>
    </>
  )
}
