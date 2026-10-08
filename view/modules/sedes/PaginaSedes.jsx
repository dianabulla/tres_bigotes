import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Form, Modal, Spinner, Table } from 'react-bootstrap'
import { guardarSede, listarSedes } from '../../cliente/sedes'

const vacio = {
  id: null,
  nombre: '',
  direccion: '',
  telefono: '',
  activo: true,
  es_actual: false,
  admin_nombre: '',
  admin_correo: '',
  admin_clave: '',
}

export default function PaginaSedes() {
  const [lista, setLista] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)

  async function cargar() {
    setError('')
    try {
      const datos = await listarSedes()
      setLista(datos.sedes)
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
    setFormulario({ ...vacio })
  }

  function abrirEdicion(sede) {
    setErrorFormulario('')
    setFormulario({
      ...vacio,
      ...sede,
      direccion: sede.direccion ?? '',
      telefono: sede.telefono ?? '',
    })
  }

  function cambiar(campo, valor) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function guardar(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    setGuardando(true)
    try {
      await guardarSede(formulario)
      setFormulario(null)
      setAviso(formulario.id ? 'Sede actualizada.' : 'Sede creada. Entra con el administrador de esa sede para configurarla.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function cambiarActivo(sede, activo) {
    setError('')
    setAviso('')
    try {
      await guardarSede({
        id: sede.id,
        nombre: sede.nombre,
        direccion: sede.direccion ?? '',
        telefono: sede.telefono ?? '',
        activo,
      })
      setAviso(activo ? 'Sede activada.' : 'Sede dada de baja.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Sedes</h1>
        <Button onClick={abrirNuevo}>Nueva sede</Button>
      </div>
      <p className="text-secondary">
        Cada sede tiene su agenda, sus clientes y su caja. La carta pública usa la sede activa más antigua.
        Para trabajar en una sede nueva, entra con el administrador que se crea aquí.
      </p>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && (
        <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>
          {aviso}
        </Alert>
      )}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando sedes</span>
        </Spinner>
      ) : lista.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay sedes.</p>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Dirección</th>
              <th>Teléfono</th>
              <th>Estado</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {lista.map((sede) => (
              <tr key={sede.id}>
                <td>
                  {sede.nombre}
                  {sede.es_actual && (
                    <Badge bg="dark" className="ms-2 marca-activo">Tu sede</Badge>
                  )}
                </td>
                <td>{sede.direccion || '—'}</td>
                <td>{sede.telefono || '—'}</td>
                <td>
                  <Badge bg="dark" className={sede.activo ? 'marca-activo' : 'marca-baja'}>
                    {sede.activo ? 'Activa' : 'De baja'}
                  </Badge>
                </td>
                <td className="text-end text-nowrap">
                  <Button size="sm" variant="outline-primary" className="me-2" onClick={() => abrirEdicion(sede)}>
                    Editar
                  </Button>
                  {sede.activo && !sede.es_actual && (
                    <Button size="sm" variant="outline-primary" onClick={() => cambiarActivo(sede, false)}>
                      Dar de baja
                    </Button>
                  )}
                  {!sede.activo && (
                    <Button size="sm" variant="primary" onClick={() => cambiarActivo(sede, true)}>
                      Activar
                    </Button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      <Modal show={formulario !== null} onHide={() => setFormulario(null)} centered>
        <Form onSubmit={guardar}>
          <Modal.Header closeButton>
            <Modal.Title>{formulario?.id ? 'Editar sede' : 'Nueva sede'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombreSede">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                required
                maxLength={120}
                value={formulario?.nombre ?? ''}
                onChange={(evento) => cambiar('nombre', evento.target.value)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="direccionSede">
              <Form.Label>Dirección</Form.Label>
              <Form.Control
                maxLength={180}
                value={formulario?.direccion ?? ''}
                onChange={(evento) => cambiar('direccion', evento.target.value)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="telefonoSede">
              <Form.Label>Teléfono</Form.Label>
              <Form.Control
                maxLength={20}
                value={formulario?.telefono ?? ''}
                onChange={(evento) => cambiar('telefono', evento.target.value)}
              />
            </Form.Group>
            {formulario?.id && (
              <Form.Check
                id="sedeActiva"
                className="mb-0"
                label="Sede activa"
                checked={Boolean(formulario.activo)}
                disabled={formulario.es_actual}
                onChange={(evento) => cambiar('activo', evento.target.checked)}
              />
            )}
            {!formulario?.id && (
              <>
                <hr />
                <p className="mb-3">Administrador de esta sede</p>
                <Form.Group className="mb-3" controlId="adminNombre">
                  <Form.Label>Nombre</Form.Label>
                  <Form.Control
                    required
                    maxLength={120}
                    value={formulario?.admin_nombre ?? ''}
                    onChange={(evento) => cambiar('admin_nombre', evento.target.value)}
                  />
                </Form.Group>
                <Form.Group className="mb-3" controlId="adminCorreo">
                  <Form.Label>Correo</Form.Label>
                  <Form.Control
                    type="email"
                    required
                    maxLength={160}
                    value={formulario?.admin_correo ?? ''}
                    onChange={(evento) => cambiar('admin_correo', evento.target.value)}
                  />
                </Form.Group>
                <Form.Group className="mb-0" controlId="adminClave">
                  <Form.Label>Clave</Form.Label>
                  <Form.Control
                    type="password"
                    required
                    minLength={8}
                    value={formulario?.admin_clave ?? ''}
                    onChange={(evento) => cambiar('admin_clave', evento.target.value)}
                  />
                </Form.Group>
              </>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setFormulario(null)}>
              Cancelar
            </Button>
            <Button type="submit" disabled={guardando}>
              {guardando ? 'Guardando…' : 'Guardar'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </>
  )
}
