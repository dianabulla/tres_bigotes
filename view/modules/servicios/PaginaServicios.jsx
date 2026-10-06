import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Col, Form, Modal, Row, Spinner, Table } from 'react-bootstrap'
import { listarProductos } from '../../cliente/inventario'
import { guardarServicio, listarServicios } from '../../cliente/servicios'

const vacio = {
  id: null,
  nombre: '',
  categoria: '',
  duracion_minutos: '30',
  precio: '',
  activo: true,
  insumos: [],
}

function recetaDe(servicio) {
  return (servicio.insumos ?? []).map((linea) => ({
    producto_id: String(linea.producto_id),
    cantidad: String(linea.cantidad),
  }))
}

export default function PaginaServicios() {
  const [lista, setLista] = useState([])
  const [insumos, setInsumos] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)

  async function cargar() {
    setError('')
    try {
      const [datos, inventario] = await Promise.all([listarServicios(), listarProductos()])
      setLista(datos.servicios)
      setInsumos(inventario.productos.filter((item) => item.tipo === 'insumo' && item.activo))
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
    setFormulario({ ...vacio, insumos: [] })
  }

  function abrirEdicion(servicio) {
    setErrorFormulario('')
    setFormulario({
      ...servicio,
      duracion_minutos: String(servicio.duracion_minutos),
      precio: String(servicio.precio),
      insumos: recetaDe(servicio),
    })
  }

  function cambiar(campo, valor) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  function cambiarInsumo(indice, campo, valor) {
    setFormulario((actual) => ({
      ...actual,
      insumos: actual.insumos.map((linea, posicion) => (posicion === indice ? { ...linea, [campo]: valor } : linea)),
    }))
  }

  async function guardar(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    const receta = formulario.insumos ?? []
    const ids = receta.map((linea) => String(linea.producto_id))
    if (receta.some((linea) => linea.producto_id === '' || Number(linea.cantidad) < 1)) {
      setErrorFormulario('Cada insumo necesita producto y una cantidad mayor que cero.')
      return
    }
    if (new Set(ids).size !== ids.length) {
      setErrorFormulario('Un servicio no puede repetir el mismo insumo.')
      return
    }
    setGuardando(true)
    try {
      await guardarServicio(formulario)
      setFormulario(null)
      setAviso(formulario.id ? 'Servicio actualizado.' : 'Servicio creado.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function cambiarActivo(servicio, activo) {
    setError('')
    setAviso('')
    try {
      await guardarServicio({
        ...servicio,
        activo,
        insumos: recetaDe(servicio),
      })
      setAviso(activo ? 'Servicio activado.' : 'Servicio dado de baja.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Servicios</h1>
        <Button onClick={abrirNuevo}>Nuevo servicio</Button>
      </div>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando servicios</span>
        </Spinner>
      ) : lista.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay servicios en esta sede.</p>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Minutos</th>
              <th>Precio</th>
              <th>Insumos</th>
              <th>Estado</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {lista.map((servicio) => (
              <tr key={servicio.id}>
                <td>{servicio.nombre}</td>
                <td>{servicio.categoria}</td>
                <td>{servicio.duracion_minutos}</td>
                <td>{servicio.precio}</td>
                <td>
                  {(servicio.insumos ?? []).length === 0
                    ? '—'
                    : servicio.insumos.map((linea) => `${linea.nombre} × ${linea.cantidad}`).join(', ')}
                </td>
                <td>
                  <Badge bg="dark" className={servicio.activo ? 'marca-activo' : 'marca-baja'}>
                    {servicio.activo ? 'Activo' : 'De baja'}
                  </Badge>
                </td>
                <td className="text-end text-nowrap">
                  <Button size="sm" variant="outline-primary" className="me-2" onClick={() => abrirEdicion(servicio)}>
                    Editar
                  </Button>
                  {servicio.activo ? (
                    <Button size="sm" variant="outline-primary" onClick={() => cambiarActivo(servicio, false)}>
                      Dar de baja
                    </Button>
                  ) : (
                    <Button size="sm" variant="primary" onClick={() => cambiarActivo(servicio, true)}>
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
            <Modal.Title>{formulario?.id ? 'Editar servicio' : 'Nuevo servicio'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombreServicio">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                required
                maxLength={120}
                value={formulario?.nombre ?? ''}
                onChange={(evento) => cambiar('nombre', evento.target.value)}
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="categoriaServicio">
              <Form.Label>Categoría</Form.Label>
              <Form.Control
                required
                maxLength={60}
                value={formulario?.categoria ?? ''}
                onChange={(evento) => cambiar('categoria', evento.target.value)}
              />
            </Form.Group>
            <Row>
              <Col>
                <Form.Group className="mb-3" controlId="duracionServicio">
                  <Form.Label>Minutos</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    max="720"
                    required
                    value={formulario?.duracion_minutos ?? ''}
                    onChange={(evento) => cambiar('duracion_minutos', evento.target.value)}
                  />
                </Form.Group>
              </Col>
              <Col>
                <Form.Group className="mb-3" controlId="precioServicio">
                  <Form.Label>Precio</Form.Label>
                  <Form.Control
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value={formulario?.precio ?? ''}
                    onChange={(evento) => cambiar('precio', evento.target.value)}
                  />
                </Form.Group>
              </Col>
            </Row>
            {formulario?.id && (
              <Form.Check
                id="servicioActivo"
                className="mb-3"
                label="Servicio activo"
                checked={Boolean(formulario.activo)}
                onChange={(evento) => cambiar('activo', evento.target.checked)}
              />
            )}
            <div className="d-flex justify-content-between align-items-center mb-2">
              <Form.Label className="mb-0">Insumos por cita</Form.Label>
              <Button
                size="sm"
                variant="outline-primary"
                type="button"
                onClick={() => cambiar('insumos', [...(formulario?.insumos ?? []), { producto_id: '', cantidad: '1' }])}
              >
                Agregar
              </Button>
            </div>
            {(formulario?.insumos ?? []).length === 0 ? (
              <p className="text-secondary small mb-0">Este servicio no descuenta inventario al completarse.</p>
            ) : formulario.insumos.map((linea, indice) => (
              <Row key={indice} className="g-2 mb-2">
                <Col>
                  <Form.Select
                    value={linea.producto_id}
                    onChange={(evento) => cambiarInsumo(indice, 'producto_id', evento.target.value)}
                    aria-label="Insumo"
                  >
                    <option value="">Insumo</option>
                    {insumos.map((item) => (
                      <option key={item.id} value={item.id}>{item.nombre}</option>
                    ))}
                  </Form.Select>
                </Col>
                <Col xs={3}>
                  <Form.Control
                    type="number"
                    min="1"
                    max="9999"
                    value={linea.cantidad}
                    onChange={(evento) => cambiarInsumo(indice, 'cantidad', evento.target.value)}
                    aria-label="Cantidad"
                  />
                </Col>
                <Col xs="auto">
                  <Button
                    variant="outline-secondary"
                    type="button"
                    onClick={() => cambiar('insumos', formulario.insumos.filter((_, posicion) => posicion !== indice))}
                  >
                    Quitar
                  </Button>
                </Col>
              </Row>
            ))}
            {insumos.length === 0 && (
              <p className="text-secondary small mb-0 mt-2">No hay insumos activos en inventario. El servicio se guarda igual.</p>
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
