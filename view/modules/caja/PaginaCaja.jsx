import { useEffect, useState } from 'react'
import { Alert, Button, Col, Form, Row, Spinner, Table } from 'react-bootstrap'
import { abrirTurno, cerrarTurno, cobrarVenta, verCaja } from '../../cliente/caja'

export default function PaginaCaja() {
  const [medios, setMedios] = useState([])
  const [turno, setTurno] = useState(null)
  const [productos, setProductos] = useState([])
  const [citas, setCitas] = useState([])
  const [citaId, setCitaId] = useState('')
  const [cierres, setCierres] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [base, setBase] = useState('0')
  const [productoId, setProductoId] = useState('')
  const [cantidad, setCantidad] = useState('1')
  const [lineas, setLineas] = useState([])
  const [pagos, setPagos] = useState({})
  const [reportados, setReportados] = useState({})
  const [guardando, setGuardando] = useState(false)

  function aplicar(datos) {
    setMedios(datos.medios)
    setTurno(datos.turno)
    setProductos(datos.productos)
    setCitas(datos.citas)
    setCierres(datos.cierres)
    setReportados((actual) => {
      const siguiente = {}
      datos.medios.forEach((medio) => {
        siguiente[medio.codigo] = actual[medio.codigo] ?? ''
      })
      return siguiente
    })
  }

  async function cargar() {
    setError('')
    try {
      aplicar(await verCaja())
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  async function abrir(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      aplicar(await abrirTurno(base))
      setAviso('Caja abierta.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function agregarLinea() {
    const producto = productos.find((item) => String(item.id) === productoId)
    const unidades = Number(cantidad)
    if (!producto || !Number.isInteger(unidades) || unidades < 1) {
      setError('Elige un producto y una cantidad entera.')
      return
    }
    setError('')
    setLineas((actual) => {
      const existe = actual.find((linea) => linea.producto_id === producto.id)
      if (existe) {
        return actual.map((linea) => (
          linea.producto_id === producto.id ? { ...linea, cantidad: linea.cantidad + unidades } : linea
        ))
      }
      return [...actual, {
        producto_id: producto.id,
        nombre: producto.nombre,
        precio_venta: producto.precio_venta,
        cantidad: unidades,
      }]
    })
    setCantidad('1')
  }

  async function cobrar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    const cuerpoLineas = lineas.map((linea) => ({ producto_id: linea.producto_id, cantidad: linea.cantidad }))
    const cuerpoPagos = medios
      .map((medio) => ({ medio: medio.codigo, monto: pagos[medio.codigo] ?? '' }))
      .filter((pago) => String(pago.monto).trim() !== '')
    try {
      aplicar(await cobrarVenta(cuerpoLineas, cuerpoPagos, citaId))
      setLineas([])
      setPagos({})
      setCitaId('')
      setAviso('Venta registrada.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function cerrar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    const cuerpo = medios.map((medio) => ({ medio: medio.codigo, monto_reportado: reportados[medio.codigo] ?? '' }))
    try {
      aplicar(await cerrarTurno(cuerpo))
      setLineas([])
      setPagos({})
      setReportados({})
      setAviso('Caja cerrada.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  return (
    <>
      <h1 className="h3 mb-3">Caja</h1>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando caja</span>
        </Spinner>
      ) : turno === null ? (
        <Form onSubmit={abrir} className="mb-4" style={{ maxWidth: '24rem' }}>
          <Form.Group className="mb-3" controlId="base">
            <Form.Label>Base de apertura</Form.Label>
            <Form.Control type="number" min="0" step="0.01" value={base} required onChange={(evento) => setBase(evento.target.value)} />
          </Form.Group>
          <Button type="submit" disabled={guardando}>{guardando ? 'Abriendo…' : 'Abrir caja'}</Button>
        </Form>
      ) : (
        <>
          <p className="mb-3">
            Abierta desde {turno.abierto_en} por {turno.usuario}. Base {turno.saldo_inicial}.
          </p>
          <Row className="g-4">
            <Col lg={7}>
              <h2 className="h4">Cobrar</h2>
              <Form onSubmit={cobrar}>
                  <Form.Group className="mb-3" controlId="cita">
                    <Form.Label>Cita completada</Form.Label>
                    <Form.Select value={citaId} onChange={(evento) => setCitaId(evento.target.value)}>
                      <option value="">Sin cita</option>
                      {citas.map((cita) => (
                        <option key={cita.id} value={cita.id}>
                          {cita.inicio.slice(0, 16)} · {cita.cliente} · {cita.profesional} · {cita.total}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                  {citas.find((cita) => String(cita.id) === String(citaId)) && (
                    <ul className="mb-3">
                      {citas.find((cita) => String(cita.id) === String(citaId)).servicios.map((servicio) => (
                        <li key={servicio.nombre}>{servicio.nombre} · {servicio.precio_aplicado}</li>
                      ))}
                    </ul>
                  )}
                  <Row className="g-2 align-items-end mb-3">
                    <Col md={6}>
                      <Form.Label htmlFor="producto">Producto</Form.Label>
                      <Form.Select id="producto" value={productoId} onChange={(evento) => setProductoId(evento.target.value)}>
                        <option value="">Elegir</option>
                        {productos.map((producto) => (
                          <option key={producto.id} value={producto.id} disabled={producto.stock < 1}>
                            {producto.nombre} · {producto.precio_venta} · stock {producto.stock}
                          </option>
                        ))}
                      </Form.Select>
                    </Col>
                    <Col xs={6} md={3}>
                      <Form.Label htmlFor="cantidad">Cantidad</Form.Label>
                      <Form.Control id="cantidad" type="number" min="1" step="1" value={cantidad} onChange={(evento) => setCantidad(evento.target.value)} />
                    </Col>
                    <Col xs={6} md={3}>
                      <Button type="button" variant="outline-primary" className="w-100" onClick={agregarLinea}>Agregar</Button>
                    </Col>
                  </Row>
                  {lineas.length > 0 && (
                    <Table responsive size="sm" className="mb-3">
                      <thead>
                        <tr>
                          <th>Producto</th>
                          <th>Cantidad</th>
                          <th>Precio</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {lineas.map((linea) => (
                          <tr key={linea.producto_id}>
                            <td>{linea.nombre}</td>
                            <td>{linea.cantidad}</td>
                            <td>{linea.precio_venta}</td>
                            <td className="text-end">
                              <Button size="sm" variant="outline-primary" type="button" onClick={() => setLineas((actual) => actual.filter((item) => item.producto_id !== linea.producto_id))}>
                                Quitar
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  )}
                  <Row className="g-2 mb-3">
                    {medios.map((medio) => (
                      <Col md={4} key={medio.codigo}>
                        <Form.Label htmlFor={`pago-${medio.codigo}`}>{medio.nombre}</Form.Label>
                        <Form.Control
                          id={`pago-${medio.codigo}`}
                          type="number"
                          min="0"
                          step="0.01"
                          value={pagos[medio.codigo] ?? ''}
                          onChange={(evento) => setPagos((actual) => ({ ...actual, [medio.codigo]: evento.target.value }))}
                        />
                      </Col>
                    ))}
                  </Row>
                  <Button type="submit" disabled={guardando || (lineas.length === 0 && citaId === '')}>{guardando ? 'Cobrando…' : 'Cobrar'}</Button>
                </Form>
              {turno.ventas.length > 0 && (
                <Table responsive size="sm" className="mt-4">
                  <thead>
                    <tr>
                      <th>Hora</th>
                      <th>Detalle</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {turno.ventas.map((venta) => (
                      <tr key={venta.id}>
                        <td>{venta.creado_en}</td>
                        <td>
                          {venta.items.map((item) => `${item.nombre} × ${item.cantidad}`).join(', ')}
                          {' · '}
                          {venta.pagos.map((pago) => `${pago.nombre} ${pago.monto}`).join(', ')}
                        </td>
                        <td>{venta.total}</td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              )}
            </Col>
            <Col lg={5}>
              <h2 className="h4">Cerrar turno</h2>
              <Form onSubmit={cerrar}>
                <Table responsive size="sm">
                  <thead>
                    <tr>
                      <th>Medio</th>
                      <th>Sistema</th>
                      <th>Reportado</th>
                    </tr>
                  </thead>
                  <tbody>
                    {turno.medios.map((medio) => (
                      <tr key={medio.medio}>
                        <td>{medio.nombre}</td>
                        <td>{medio.monto_sistema}</td>
                        <td>
                          <Form.Control
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            aria-label={`Reportado ${medio.nombre}`}
                            value={reportados[medio.medio] ?? ''}
                            onChange={(evento) => setReportados((actual) => ({ ...actual, [medio.medio]: evento.target.value }))}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
                <Button type="submit" variant="outline-primary" disabled={guardando}>{guardando ? 'Cerrando…' : 'Cerrar caja'}</Button>
              </Form>
            </Col>
          </Row>
        </>
      )}

      <h2 className="h4 mt-5">Cierres</h2>
      {cierres.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay cierres en esta sede.</p>
      ) : (
        cierres.map((cierre) => (
          <div key={cierre.id} className="mb-4">
            <p className="mb-2">
              {cierre.cerrado_en} · {cierre.usuario} · base {cierre.saldo_inicial}
            </p>
            <Table responsive size="sm">
              <thead>
                <tr>
                  <th>Medio</th>
                  <th>Sistema</th>
                  <th>Reportado</th>
                  <th>Diferencia</th>
                </tr>
              </thead>
              <tbody>
                {cierre.lineas.map((linea) => (
                  <tr key={linea.medio}>
                    <td>{linea.nombre}</td>
                    <td>{linea.monto_sistema}</td>
                    <td>{linea.monto_reportado}</td>
                    <td>{linea.diferencia}</td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>
        ))
      )}
    </>
  )
}
