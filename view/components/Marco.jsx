import { useEffect, useState } from 'react'
import { Form, Nav, Offcanvas } from 'react-bootstrap'
import { NavLink, Outlet } from 'react-router-dom'
import { listarSedes } from '../cliente/sedes'
import { prefetchPagina } from '../modules/cargas'
import { modulosVisibles } from '../modules/registro'
import BotonAccion from './BotonAccion'
import { useSesion } from './Sesion'

function SelectorSede({ sedes, sedeActiva, onCambiar, disabled }) {
  if (!sedes.length) {
    return null
  }
  return (
    <Form.Group className="marca-sede-trabajo" controlId="sedeTrabajo">
      <Form.Label className="marca-usuario-etiqueta mb-1">Sede de trabajo</Form.Label>
      <Form.Select
        size="sm"
        value={sedeActiva || ''}
        disabled={disabled}
        onChange={(evento) => onCambiar(evento.target.value ? Number(evento.target.value) : null)}
      >
        <option value="">Elige una sede</option>
        {sedes.map((sede) => (
          <option key={sede.id} value={sede.id}>{sede.nombre}</option>
        ))}
      </Form.Select>
    </Form.Group>
  )
}

function MenuLateral({
  visibles,
  usuario,
  salir,
  onNavegar,
  esSuper,
  sedes,
  cambiandoSede,
  onCambiarSede,
}) {
  return (
    <>
      <div className="marca-lateral-marca">
        <NavLink to="/gestion" className="marca-lateral-logo" onClick={onNavegar}>
          <img src={`${import.meta.env.BASE_URL}media/logo.jpeg`} alt="3 Bigotes" loading="lazy" />
        </NavLink>
        <p className="marca-lateral-nombre">3 Bigotes</p>
      </div>

      <Nav className="marca-modulos flex-column">
        {visibles.map((modulo) => (
          <Nav.Link
            as={NavLink}
            key={modulo.id}
            to={modulo.ruta}
            onClick={onNavegar}
            onMouseEnter={() => prefetchPagina(modulo.id)}
            onFocus={() => prefetchPagina(modulo.id)}
          >
            {modulo.titulo}
          </Nav.Link>
        ))}
      </Nav>

      <div className="marca-sesion">
        {esSuper && (
          <SelectorSede
            sedes={sedes}
            sedeActiva={usuario.establecimiento_activo_id}
            onCambiar={onCambiarSede}
            disabled={cambiandoSede}
          />
        )}
        <div className="marca-sesion-fila">
          <div className="marca-usuario-bloque">
            <span className="marca-usuario-etiqueta">Sesión</span>
            <span className="marca-usuario">{usuario.nombre}</span>
            <span className="marca-rol">{usuario.rol_nombre || usuario.rol}</span>
          </div>
          <BotonAccion icono="logout" titulo="Salir" onClick={salir} />
        </div>
      </div>
    </>
  )
}

export default function Marco() {
  const { usuario, salir, cambiarSedeTrabajo } = useSesion()
  const visibles = modulosVisibles(usuario.permisos)
  const esSuper = usuario?.alcance === 'sistema' || usuario?.rol === 'superadministrador'
  const [menuAbierto, setMenuAbierto] = useState(false)
  const [sedes, setSedes] = useState([])
  const [cambiandoSede, setCambiandoSede] = useState(false)
  const [errorSede, setErrorSede] = useState('')

  useEffect(() => {
    if (!esSuper) {
      return
    }
    listarSedes()
      .then((datos) => setSedes((datos.sedes || []).filter((sede) => sede.activo)))
      .catch(() => setSedes([]))
  }, [esSuper])

  function cerrarMenu() {
    setMenuAbierto(false)
  }

  async function onCambiarSede(sedeId) {
    setErrorSede('')
    setCambiandoSede(true)
    try {
      await cambiarSedeTrabajo(sedeId)
    } catch (fallo) {
      setErrorSede(fallo.message)
    } finally {
      setCambiandoSede(false)
    }
  }

  return (
    <div className="gestion-shell">
      <aside className="marca-lateral d-none d-lg-flex" aria-label="Menú de gestión">
        <MenuLateral
          visibles={visibles}
          usuario={usuario}
          salir={salir}
          esSuper={esSuper}
          sedes={sedes}
          cambiandoSede={cambiandoSede}
          onCambiarSede={onCambiarSede}
        />
      </aside>

      <header className="marca-top d-lg-none">
        <button
          type="button"
          className="marca-burger"
          aria-label="Abrir menú"
          aria-expanded={menuAbierto}
          onClick={() => setMenuAbierto(true)}
        >
          <span className="navbar-toggler-icon" />
        </button>
        <NavLink to="/gestion" className="marca-top-logo">
          <img src={`${import.meta.env.BASE_URL}media/logo.jpeg`} alt="3 Bigotes" />
        </NavLink>
        <div className="marca-top-usuario">
          <span className="marca-usuario">{usuario.nombre}</span>
          <span className="marca-rol">{usuario.rol_nombre || usuario.rol}</span>
        </div>
        <BotonAccion icono="logout" titulo="Salir" onClick={salir} />
      </header>

      <Offcanvas
        show={menuAbierto}
        onHide={cerrarMenu}
        placement="start"
        className="marca-offcanvas d-lg-none"
        aria-labelledby="menu-modulos-titulo"
      >
        <Offcanvas.Header closeButton closeVariant="white">
          <Offcanvas.Title id="menu-modulos-titulo">Menú</Offcanvas.Title>
        </Offcanvas.Header>
        <Offcanvas.Body className="marca-offcanvas-cuerpo">
          <MenuLateral
            visibles={visibles}
            usuario={usuario}
            salir={salir}
            onNavegar={cerrarMenu}
            esSuper={esSuper}
            sedes={sedes}
            cambiandoSede={cambiandoSede}
            onCambiarSede={onCambiarSede}
          />
        </Offcanvas.Body>
      </Offcanvas>

      <main className="gestion-contenido py-3 py-md-4 px-3 px-md-4">
        {errorSede && <p className="text-danger small mb-3">{errorSede}</p>}
        {esSuper && !usuario.establecimiento_activo_id && (
          <p className="text-secondary small mb-3">
            Elige una sede de trabajo en el menú para operar agenda, caja e inventario. Sedes, usuarios y roles funcionan sin elegirla.
          </p>
        )}
        <Outlet />
      </main>
    </div>
  )
}
