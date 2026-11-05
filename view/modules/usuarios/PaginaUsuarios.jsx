import { useEffect, useState } from 'react'
import { Alert, Badge, Form, Modal, Spinner, Table } from 'react-bootstrap'
import BotonAccion, { GrupoAcciones } from '../../components/BotonAccion'
import { useSesion } from '../../components/Sesion'
import { guardarUsuario, listarUsuarios } from '../../cliente/usuarios'

const vacio = {
  id: null,
  nombre: '',
  correo: '',
  clave: '',
  rol: 'recepcion',
  establecimiento_id: '',
  activo: true,
}

export default function PaginaUsuarios() {
  const { usuario } = useSesion()
  const esSuper = usuario?.alcance === 'sistema' || usuario?.rol === 'superadministrador'
  const [lista, setLista] = useState([])
  const [roles, setRoles] = useState([])
  const [sedes, setSedes] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)

  async function cargar() {
    setError('')
    try {
      const datos = await listarUsuarios()
      setLista(datos.usuarios)
      setRoles(datos.roles)
      setSedes((datos.sedes || []).filter((sede) => sede.activo))
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  function abrirNuevo() {
    setErrorFormulario('')
    setFormulario({
      ...vacio,
      establecimiento_id: esSuper ? (usuario?.establecimiento_activo_id || '') : '',
    })
  }

  function abrirEdicion(cuenta) {
    setErrorFormulario('')
    setFormulario({
      ...cuenta,
      clave: '',
      establecimiento_id: cuenta.establecimiento_id ?? '',
    })
  }

  function cambiar(campo, valor) {
    setFormulario((actual) => {
      const siguiente = { ...actual, [campo]: valor }
      if (campo === 'rol' && valor === 'superadministrador') {
        siguiente.establecimiento_id = ''
      }
      return siguiente
    })
  }

  async function guardar(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    setGuardando(true)
    try {
      await guardarUsuario(formulario)
      setFormulario(null)
      setAviso('Usuario guardado.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function cambiarActivo(cuenta, activo) {
    if (cuenta.id === usuario?.id) {
      setError('No puedes darte de baja a ti mismo.')
      return
    }
    setError('')
    setAviso('')
    try {
      await guardarUsuario({
        id: cuenta.id,
        nombre: cuenta.nombre,
        correo: cuenta.correo,
        rol: cuenta.rol,
        establecimiento_id: cuenta.establecimiento_id,
        clave: '',
        activo,
      })
      setAviso(activo ? 'Usuario activado.' : 'Usuario dado de baja.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  const esPropio = formulario?.id === usuario?.id
  const esSuperForm = formulario?.rol === 'superadministrador'
  const sedesActivas = sedes.filter((sede) => sede.activo)

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Usuarios</h1>
        <BotonAccion icono="plus" titulo="Nuevo usuario" variant="primary" onClick={abrirNuevo} />
      </div>
      <p className="text-secondary">
        {esSuper
          ? 'El super administrador ve todas las cuentas y asigna cada una a una sede. El administrador de una sede solo gestiona la suya.'
          : 'Estas cuentas son de esta sede. El rol se elige aquí y los permisos de cada rol se marcan en Roles. El acceso de un profesional también puede crearse en Colaboradores.'}
      </p>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && (
        <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>
          {aviso}
        </Alert>
      )}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando usuarios</span>
        </Spinner>
      ) : lista.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay usuarios{esSuper ? '' : ' en esta sede'}.</p>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              {esSuper && <th>Sede</th>}
              <th>Rol</th>
              <th>Estado</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {lista.map((cuenta) => (
              <tr key={cuenta.id}>
                <td>{cuenta.nombre}</td>
                <td>{cuenta.correo}</td>
                {esSuper && (
                  <td>{cuenta.sede_nombre || (cuenta.rol === 'superadministrador' ? 'Todas' : 'Sin sede')}</td>
                )}
                <td>{cuenta.rol_nombre}</td>
                <td>
                  <Badge bg="dark" className={cuenta.activo ? 'marca-activo' : 'marca-baja'}>
                    {cuenta.activo ? 'Activo' : 'De baja'}
                  </Badge>
                </td>
                <td className="text-end">
                  <GrupoAcciones>
                    <BotonAccion icono="pencil" titulo="Editar" onClick={() => abrirEdicion(cuenta)} />
                    {cuenta.id !== usuario?.id && (
                      cuenta.activo ? (
                        <BotonAccion icono="trash" titulo="Eliminar" onClick={() => cambiarActivo(cuenta, false)} />
                      ) : (
                        <BotonAccion icono="check" titulo="Activar" variant="primary" onClick={() => cambiarActivo(cuenta, true)} />
                      )
                    )}
                  </GrupoAcciones>
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      <Modal show={formulario !== null} onHide={() => setFormulario(null)} centered>
        <Form onSubmit={guardar}>
          <Modal.Header closeButton>
            <Modal.Title>{formulario?.id ? 'Editar usuario' : 'Nuevo usuario'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombreUsuario">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                required
                maxLength={120}
                value={formulario?.nombre ?? ''}
                onChange={(evento) => cambiar('nombre', evento.target.value)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="correoUsuario">
              <Form.Label>Correo</Form.Label>
              <Form.Control
                type="email"
                required
                maxLength={160}
                value={formulario?.correo ?? ''}
                onChange={(evento) => cambiar('correo', evento.target.value)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="rolUsuario">
              <Form.Label>Rol</Form.Label>
              <Form.Select
                value={formulario?.rol ?? 'recepcion'}
                disabled={esPropio}
                onChange={(evento) => cambiar('rol', evento.target.value)}
              >
                {roles.map((rol) => (
                  <option key={rol.codigo} value={rol.codigo}>{rol.nombre}</option>
                ))}
              </Form.Select>
            </Form.Group>
            {esSuper && !esSuperForm && (
              <Form.Group className="mb-3" controlId="sedeUsuario">
                <Form.Label>Sede</Form.Label>
                <Form.Select
                  required
                  value={formulario?.establecimiento_id ?? ''}
                  disabled={esPropio}
                  onChange={(evento) => cambiar('establecimiento_id', evento.target.value)}
                >
                  <option value="">Elige una sede</option>
                  {sedesActivas.map((sede) => (
                    <option key={sede.id} value={sede.id}>{sede.nombre}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            )}
            {esSuper && esSuperForm && (
              <p className="text-secondary small">El super administrador ve todas las sedes y no se asigna a una.</p>
            )}
            <Form.Group className="mb-3" controlId="claveUsuario">
              <Form.Label>{formulario?.id ? 'Nueva clave' : 'Clave'}</Form.Label>
              <Form.Control
                type="password"
                required={!formulario?.id}
                minLength={formulario?.clave ? 8 : undefined}
                value={formulario?.clave ?? ''}
                placeholder={formulario?.id ? 'Déjala vacía para no cambiarla' : ''}
                onChange={(evento) => cambiar('clave', evento.target.value)}
              />
            </Form.Group>
            {formulario?.id && (
              <Form.Check
                id="usuarioActivo"
                label="Usuario activo"
                checked={Boolean(formulario.activo)}
                disabled={esPropio}
                onChange={(evento) => cambiar('activo', evento.target.checked)}
              />
            )}
          </Modal.Body>
          <Modal.Footer>
            <BotonAccion icono="x" titulo="Cancelar" onClick={() => setFormulario(null)} />
            <BotonAccion icono="save" titulo={guardando ? 'Guardando…' : 'Guardar'} type="submit" variant="primary" disabled={guardando} />
          </Modal.Footer>
        </Form>
      </Modal>
    </>
  )
}
