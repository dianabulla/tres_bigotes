import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Col, Form, Modal, Row, Spinner, Table } from 'react-bootstrap'
import { crearResena, eliminarResena, guardarColaborador, listarColaboradores, listarResenas, subirFotoColaborador } from '../../cliente/colaboradores'

const formularioVacio = {
  id: null,
  nombre: '',
  telefono: '',
  fecha_ingreso: '',
  activo: true,
  correo: '',
  clave: '',
  tieneAcceso: false,
  yaTieneAcceso: false,
  fotoArchivo: null,
}

export default function PaginaColaboradores() {
  const [lista, setLista] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)
  const [resenas, setResenas] = useState([])
  const [autor, setAutor] = useState('')
  const [texto, setTexto] = useState('')
  const [nota, setNota] = useState('5')

  async function cargar() {
    setError('')
    try {
      const [datos, opiniones] = await Promise.all([listarColaboradores(), listarResenas()])
      setLista(datos.colaboradores)
      setResenas(opiniones.resenas)
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
    setFormulario({ ...formularioVacio })
  }

  function abrirEdicion(colaborador) {
    setErrorFormulario('')
    setFormulario({
      id: colaborador.id,
      nombre: colaborador.nombre,
      telefono: colaborador.telefono ?? '',
      fecha_ingreso: colaborador.fecha_ingreso ?? '',
      activo: colaborador.activo,
      correo: colaborador.correo ?? '',
      clave: '',
      tieneAcceso: Boolean(colaborador.correo),
      yaTieneAcceso: Boolean(colaborador.correo),
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
      const respuesta = await guardarColaborador({
        id: formulario.id,
        nombre: formulario.nombre,
        telefono: formulario.telefono,
        fecha_ingreso: formulario.fecha_ingreso,
        activo: formulario.activo,
        correo: formulario.tieneAcceso ? formulario.correo : '',
        clave: formulario.tieneAcceso ? formulario.clave : '',
      })
      const id = formulario.id || respuesta?.colaborador?.id
      if (formulario.fotoArchivo && id) {
        await subirFotoColaborador(id, formulario.fotoArchivo)
      }
      setFormulario(null)
      setAviso(formulario.id ? 'Colaborador actualizado.' : 'Colaborador creado.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function cambiarActivo(colaborador, activo) {
    setError('')
    setAviso('')
    try {
      await guardarColaborador({
        id: colaborador.id,
        nombre: colaborador.nombre,
        telefono: colaborador.telefono ?? '',
        fecha_ingreso: colaborador.fecha_ingreso ?? '',
        activo,
        correo: colaborador.correo ?? '',
        clave: '',
      })
      setAviso(activo ? 'Colaborador activado.' : 'Colaborador dado de baja.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  async function guardarResena(evento) {
    evento.preventDefault()
    setError('')
    try {
      await crearResena({ autor, texto, calificacion: Number(nota) })
      setAutor('')
      setTexto('')
      setNota('5')
      setAviso('Reseña publicada en la carta.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  async function quitarResena(id) {
    setError('')
    try {
      await eliminarResena(id)
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Colaboradores</h1>
        <Button onClick={abrirNuevo}>Nuevo colaborador</Button>
      </div>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando colaboradores</span>
        </Spinner>
      ) : lista.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay colaboradores en esta sede.</p>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Teléfono</th>
              <th>Ingreso</th>
              <th>Acceso</th>
              <th>Estado</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {lista.map((colaborador) => (
              <tr key={colaborador.id}>
                <td>{colaborador.nombre}</td>
                <td>{colaborador.telefono || '—'}</td>
                <td>{colaborador.fecha_ingreso || '—'}</td>
                <td>{colaborador.correo || 'Sin acceso'}</td>
                <td>
                  <Badge bg="dark" className={colaborador.activo ? 'marca-activo' : 'marca-baja'}>
                    {colaborador.activo ? 'Activo' : 'De baja'}
                  </Badge>
                </td>
                <td className="text-end text-nowrap">
                  <Button size="sm" variant="outline-primary" className="me-2" onClick={() => abrirEdicion(colaborador)}>
                    Editar
                  </Button>
                  {colaborador.activo ? (
                    <Button size="sm" variant="outline-primary" onClick={() => cambiarActivo(colaborador, false)}>
                      Dar de baja
                    </Button>
                  ) : (
                    <Button size="sm" variant="primary" onClick={() => cambiarActivo(colaborador, true)}>
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
            <Modal.Title>{formulario?.id ? 'Editar colaborador' : 'Nuevo colaborador'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombre">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                value={formulario?.nombre ?? ''}
                onChange={(evento) => cambiar('nombre', evento.target.value)}
                required
                maxLength={120}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="telefono">
              <Form.Label>Teléfono</Form.Label>
              <Form.Control
                value={formulario?.telefono ?? ''}
                onChange={(evento) => cambiar('telefono', evento.target.value)}
                maxLength={20}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="foto">
              <Form.Label>Foto para la carta</Form.Label>
              <Form.Control
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={(evento) => cambiar('fotoArchivo', evento.target.files?.[0] ?? null)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="fecha">
              <Form.Label>Fecha de ingreso</Form.Label>
              <Form.Control
                type="date"
                value={formulario?.fecha_ingreso ?? ''}
                onChange={(evento) => cambiar('fecha_ingreso', evento.target.value)}
              />
            </Form.Group>
            <Form.Check
              className="mb-3"
              id="tieneAcceso"
              label="Puede entrar al sistema"
              checked={Boolean(formulario?.tieneAcceso)}
              disabled={Boolean(formulario?.yaTieneAcceso)}
              onChange={(evento) => cambiar('tieneAcceso', evento.target.checked)}
            />
            {formulario?.tieneAcceso && (
              <>
                <Form.Group className="mb-3" controlId="correo">
                  <Form.Label>Correo</Form.Label>
                  <Form.Control
                    type="email"
                    value={formulario.correo}
                    onChange={(evento) => cambiar('correo', evento.target.value)}
                    required
                  />
                </Form.Group>
                <Form.Group controlId="clave">
                  <Form.Label>{formulario.yaTieneAcceso ? 'Nueva clave' : 'Clave'}</Form.Label>
                  <Form.Control
                    type="password"
                    value={formulario.clave}
                    autoComplete="new-password"
                    minLength={formulario.clave ? 8 : undefined}
                    required={!formulario.yaTieneAcceso}
                    placeholder={formulario.yaTieneAcceso ? 'Dejar en blanco para no cambiar' : ''}
                    onChange={(evento) => cambiar('clave', evento.target.value)}
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

      <h2 className="h4 mt-5">Reseñas de la carta</h2>
      <Form className="mb-3" onSubmit={guardarResena}>
        <Row>
          <Col md={4}>
            <Form.Group className="mb-3" controlId="autorResena">
              <Form.Label>Nombre</Form.Label>
              <Form.Control value={autor} maxLength={80} required onChange={(evento) => setAutor(evento.target.value)} />
            </Form.Group>
          </Col>
          <Col md={2}>
            <Form.Group className="mb-3" controlId="notaResena">
              <Form.Label>Nota</Form.Label>
              <Form.Select value={nota} onChange={(evento) => setNota(evento.target.value)}>
                {[5, 4, 3, 2, 1].map((valor) => (
                  <option key={valor} value={valor}>{valor}</option>
                ))}
              </Form.Select>
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group className="mb-3" controlId="textoResena">
              <Form.Label>Reseña</Form.Label>
              <Form.Control value={texto} maxLength={400} required onChange={(evento) => setTexto(evento.target.value)} />
            </Form.Group>
          </Col>
        </Row>
        <Button type="submit">Publicar reseña</Button>
      </Form>
      {resenas.length === 0 ? (
        <p className="text-secondary">Todavía no hay reseñas en la carta.</p>
      ) : (
        <Table responsive size="sm">
          <tbody>
            {resenas.map((resena) => (
              <tr key={resena.id}>
                <td>{resena.autor}</td>
                <td>{resena.calificacion}</td>
                <td>{resena.texto}</td>
                <td className="text-end">
                  <Button size="sm" variant="outline-primary" type="button" onClick={() => quitarResena(resena.id)}>Quitar</Button>
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </>
  )
}
