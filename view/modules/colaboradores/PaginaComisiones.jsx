import { useEffect, useState } from 'react'
import { Alert, Button, Col, Form, Modal, Row, Spinner, Table } from 'react-bootstrap'
import { anularLiquidacion, eliminarRegla, guardarRegla, liquidarComision, verComisiones, verLiquidacion } from '../../cliente/comisiones'
import { useSesion } from '../../components/Sesion'
import { tienePermiso } from '../registro'

const reglaVacia = {
  id: null,
  servicio_id: '',
  porcentaje: '',
  umbral_cantidad: '',
  periodo: '',
}

export default function PaginaComisiones() {
  const { usuario } = useSesion()
  const esAdmin = tienePermiso(usuario, 'comisiones')
  const [datos, setDatos] = useState(null)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [regla, setRegla] = useState(null)
  const [errorRegla, setErrorRegla] = useState('')
  const [profesionalId, setProfesionalId] = useState('')
  const [desde, setDesde] = useState('')
  const [hasta, setHasta] = useState('')
  const [detalle, setDetalle] = useState(null)
  const [guardando, setGuardando] = useState(false)

  async function cargar() {
    setError('')
    try {
      const respuesta = await verComisiones()
      setDatos(respuesta)
      if (!profesionalId && respuesta.profesionales[0]) {
        setProfesionalId(String(respuesta.profesionales[0].id))
      }
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  async function guardar(evento) {
    evento.preventDefault()
    setErrorRegla('')
    setGuardando(true)
    try {
      setDatos(await guardarRegla(regla))
      setRegla(null)
      setAviso(regla.id ? 'Regla actualizada.' : 'Regla creada.')
    } catch (fallo) {
      setErrorRegla(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function quitarRegla(id) {
    setError('')
    try {
      setDatos(await eliminarRegla(id))
      setAviso('Regla eliminada.')
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  async function liquidar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      const respuesta = await liquidarComision({ colaborador_id: profesionalId, desde, hasta })
      setDetalle(respuesta.liquidacion)
      setAviso('Liquidación lista.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function abrir(id) {
    setError('')
    try {
      const respuesta = await verLiquidacion(id)
      setDetalle(respuesta.liquidacion)
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  async function anular(id) {
    setError('')
    setGuardando(true)
    try {
      setDatos(await anularLiquidacion(id))
      setDetalle(null)
      setAviso('Liquidación anulada.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Comisiones</h1>
        {esAdmin && <Button onClick={() => { setErrorRegla(''); setRegla({ ...reglaVacia }) }}>Nueva regla</Button>}
      </div>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      {datos === null ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando comisiones</span>
        </Spinner>
      ) : (
        <>
          {esAdmin && (
            <>
              <h2 className="h4">Reglas</h2>
              {datos.reglas.length === 0 ? (
                <p className="text-secondary">Todavía no hay reglas. Sin un porcentaje no se puede liquidar.</p>
              ) : (
                <Table responsive hover className="mb-4">
                  <thead>
                    <tr>
                      <th>Servicio</th>
                      <th>Porcentaje</th>
                      <th>Tramo</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {datos.reglas.map((item) => (
                      <tr key={item.id}>
                        <td>{item.servicio}</td>
                        <td>{item.porcentaje}%</td>
                        <td>{item.umbral_cantidad ? `Más de ${item.umbral_cantidad} por ${item.periodo === 'dia' ? 'día' : 'semana'}` : 'Base'}</td>
                        <td className="text-end text-nowrap">
                          <Button size="sm" variant="outline-primary" className="me-2" onClick={() => setRegla({ ...item, servicio_id: item.servicio_id ?? '', umbral_cantidad: item.umbral_cantidad ?? '', periodo: item.periodo ?? '' })}>Editar</Button>
                          <Button size="sm" variant="outline-primary" onClick={() => quitarRegla(item.id)}>Quitar</Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              )}
              <h2 className="h4">Liquidar</h2>
              <Form onSubmit={liquidar} className="mb-4">
                <Row className="g-2 align-items-end">
                  <Col md={4}>
                    <Form.Label htmlFor="profesional">Profesional</Form.Label>
                    <Form.Select id="profesional" required value={profesionalId} onChange={(evento) => setProfesionalId(evento.target.value)}>
                      {datos.profesionales.map((profesional) => (
                        <option key={profesional.id} value={profesional.id}>{profesional.nombre}</option>
                      ))}
                    </Form.Select>
                  </Col>
                  <Col md={3}>
                    <Form.Label htmlFor="desde">Desde</Form.Label>
                    <Form.Control id="desde" type="date" required value={desde} onChange={(evento) => setDesde(evento.target.value)} />
                  </Col>
                  <Col md={3}>
                    <Form.Label htmlFor="hasta">Hasta</Form.Label>
                    <Form.Control id="hasta" type="date" required value={hasta} onChange={(evento) => setHasta(evento.target.value)} />
                  </Col>
                  <Col md={2}>
                    <Button type="submit" className="w-100" disabled={guardando}>{guardando ? 'Calculando…' : 'Liquidar'}</Button>
                  </Col>
                </Row>
              </Form>
            </>
          )}
          <h2 className="h4">Liquidaciones</h2>
          {datos.liquidaciones.length === 0 ? (
            <p className="text-secondary">Todavía no hay liquidaciones.</p>
          ) : (
            <Table responsive hover>
              <thead>
                <tr>
                  <th>Profesional</th>
                  <th>Desde</th>
                  <th>Hasta</th>
                  <th>Total</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                {datos.liquidaciones.map((item) => (
                  <tr key={item.id}>
                    <td>{item.colaborador}</td>
                    <td>{String(item.desde).slice(0, 10)}</td>
                    <td>{String(item.hasta).slice(0, 10)}</td>
                    <td>{item.total}</td>
                    <td className="text-end">
                      <Button size="sm" variant="outline-primary" onClick={() => abrir(item.id)}>Ver</Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </>
      )}

      <Modal show={regla !== null} onHide={() => setRegla(null)} centered>
        <Form onSubmit={guardar}>
          <Modal.Header closeButton>
            <Modal.Title>{regla?.id ? 'Editar regla' : 'Nueva regla'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorRegla && <Alert variant="danger">{errorRegla}</Alert>}
            <Form.Group className="mb-3" controlId="servicioRegla">
              <Form.Label>Servicio</Form.Label>
              <Form.Select value={regla?.servicio_id ?? ''} onChange={(evento) => setRegla((actual) => ({ ...actual, servicio_id: evento.target.value }))}>
                <option value="">General de la sede</option>
                {datos?.servicios.map((servicio) => (
                  <option key={servicio.id} value={servicio.id}>{servicio.nombre}</option>
                ))}
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-3" controlId="porcentaje">
              <Form.Label>Porcentaje</Form.Label>
              <Form.Control type="number" min="0" max="100" step="0.01" required value={regla?.porcentaje ?? ''} onChange={(evento) => setRegla((actual) => ({ ...actual, porcentaje: evento.target.value }))} />
            </Form.Group>
            <Row>
              <Col>
                <Form.Group className="mb-3" controlId="umbral">
                  <Form.Label>A partir de más de</Form.Label>
                  <Form.Control type="number" min="1" step="1" value={regla?.umbral_cantidad ?? ''} placeholder="Vacío = base" onChange={(evento) => setRegla((actual) => ({ ...actual, umbral_cantidad: evento.target.value }))} />
                </Form.Group>
              </Col>
              <Col>
                <Form.Group className="mb-3" controlId="periodo">
                  <Form.Label>Periodo</Form.Label>
                  <Form.Select value={regla?.periodo ?? ''} onChange={(evento) => setRegla((actual) => ({ ...actual, periodo: evento.target.value }))}>
                    <option value="">Base</option>
                    <option value="dia">Día</option>
                    <option value="semana">Semana</option>
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setRegla(null)}>Cancelar</Button>
            <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Guardar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      <Modal show={detalle !== null} onHide={() => setDetalle(null)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Liquidación · {detalle?.colaborador}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>Del {String(detalle?.desde ?? '').slice(0, 10)} al {String(detalle?.hasta ?? '').slice(0, 10)}. Total {detalle?.total}.</p>
          {detalle?.lineas.length === 0 ? (
            <p className="text-secondary mb-0">No hubo servicios completados en ese rango.</p>
          ) : (
            <Table responsive size="sm">
              <thead>
                <tr>
                  <th>Servicio</th>
                  <th>Base</th>
                  <th>%</th>
                  <th>Monto</th>
                </tr>
              </thead>
              <tbody>
                {detalle?.lineas.map((linea) => (
                  <tr key={linea.id}>
                    <td>{linea.servicio}</td>
                    <td>{linea.base}</td>
                    <td>{linea.porcentaje}</td>
                    <td>{linea.monto}</td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Modal.Body>
        <Modal.Footer>
          {esAdmin && <Button variant="outline-primary" disabled={guardando} onClick={() => anular(detalle.id)}>Anular</Button>}
          <Button variant="secondary" onClick={() => setDetalle(null)}>Cerrar</Button>
        </Modal.Footer>
      </Modal>
    </>
  )
}
