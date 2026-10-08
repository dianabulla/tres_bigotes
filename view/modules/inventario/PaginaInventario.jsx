import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Form, Modal, Spinner, Table } from 'react-bootstrap'
import { guardarProducto, listarMovimientos, listarProductos, moverProducto } from '../../cliente/inventario'
import { useSesion } from '../../components/Sesion'
import { tienePermiso } from '../registro'

const formularioVacio = {
  id: null,
  nombre: '',
  tipo: 'insumo',
  precio_compra: '',
  precio_venta: '',
  stock_minimo: '0',
  stock_inicial: '0',
  activo: true,
}

const movimientoVacio = {
  tipo: 'entrada',
  efecto: 'aumenta',
  cantidad: '',
  motivo: '',
}

export default function PaginaInventario() {
  const { usuario } = useSesion()
  const esAdmin = tienePermiso(usuario, 'inventario')
  const [lista, setLista] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)
  const [productoMovimiento, setProductoMovimiento] = useState(null)
  const [movimiento, setMovimiento] = useState(movimientoVacio)
  const [historial, setHistorial] = useState([])

  async function cargar() {
    setError('')
    try {
      const datos = await listarProductos()
      setLista(datos.productos)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  function cambiar(campo, valor) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function guardar(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    setGuardando(true)
    try {
      await guardarProducto(formulario)
      setFormulario(null)
      setAviso(formulario.id ? 'Producto actualizado.' : 'Producto creado.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function abrirMovimiento(producto) {
    setErrorFormulario('')
    setMovimiento(movimientoVacio)
    setProductoMovimiento(producto)
    try {
      const datos = await listarMovimientos(producto.id)
      setHistorial(datos.movimientos)
    } catch (fallo) {
      setHistorial([])
      setErrorFormulario(fallo.message)
    }
  }

  async function registrarMovimiento(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    setGuardando(true)
    try {
      const datos = await moverProducto(productoMovimiento.id, movimiento)
      setAviso('Movimiento registrado.')
      setProductoMovimiento(datos.producto)
      const historialNuevo = await listarMovimientos(datos.producto.id)
      setHistorial(historialNuevo.movimientos)
      setMovimiento(movimientoVacio)
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Inventario</h1>
        {esAdmin && <Button onClick={() => { setErrorFormulario(''); setFormulario({ ...formularioVacio }) }}>Nuevo producto</Button>}
      </div>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando inventario</span>
        </Spinner>
      ) : lista.length === 0 ? (
        <p className="text-secondary mb-0">Todavía no hay productos en esta sede.</p>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Compra</th>
              <th>Venta</th>
              <th>Stock</th>
              <th>Mínimo</th>
              <th>Estado</th>
              {esAdmin && <th />}
            </tr>
          </thead>
          <tbody>
            {lista.map((producto) => (
              <tr key={producto.id}>
                <td>{producto.nombre}</td>
                <td>{producto.tipo === 'venta' ? 'Venta' : 'Insumo'}</td>
                <td>{producto.precio_compra}</td>
                <td>{producto.precio_venta ?? '—'}</td>
                <td>{producto.stock}</td>
                <td>{producto.stock_minimo}</td>
                <td>
                  {!producto.activo && <Badge className="marca-baja me-1">De baja</Badge>}
                  {producto.alerta && <Badge className="marca-activo">Reponer</Badge>}
                  {producto.activo && !producto.alerta && <Badge className="marca-baja">En stock</Badge>}
                </td>
                {esAdmin && (
                  <td className="text-end text-nowrap">
                    <Button size="sm" variant="outline-primary" className="me-2" onClick={() => { setErrorFormulario(''); setFormulario({ ...producto, precio_compra: producto.precio_compra ?? '', precio_venta: producto.precio_venta ?? '', stock_inicial: '0' }) }}>
                      Editar
                    </Button>
                    <Button size="sm" variant="outline-primary" onClick={() => abrirMovimiento(producto)}>
                      Movimiento
                    </Button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      <Modal show={formulario !== null} onHide={() => setFormulario(null)} centered>
        <Form onSubmit={guardar}>
          <Modal.Header closeButton>
            <Modal.Title>{formulario?.id ? 'Editar producto' : 'Nuevo producto'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombre">
              <Form.Label>Nombre</Form.Label>
              <Form.Control value={formulario?.nombre ?? ''} maxLength={120} required onChange={(evento) => cambiar('nombre', evento.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3" controlId="tipo">
              <Form.Label>Tipo</Form.Label>
              <Form.Select value={formulario?.tipo ?? 'insumo'} onChange={(evento) => cambiar('tipo', evento.target.value)}>
                <option value="insumo">Insumo</option>
                <option value="venta">Venta</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-3" controlId="compra">
              <Form.Label>Precio de compra</Form.Label>
              <Form.Control type="number" min="0" step="0.01" value={formulario?.precio_compra ?? ''} required onChange={(evento) => cambiar('precio_compra', evento.target.value)} />
            </Form.Group>
            {formulario?.tipo === 'venta' && (
              <Form.Group className="mb-3" controlId="precio">
                <Form.Label>Precio de venta</Form.Label>
                <Form.Control type="number" min="0" step="0.01" value={formulario.precio_venta} required onChange={(evento) => cambiar('precio_venta', evento.target.value)} />
              </Form.Group>
            )}
            <Form.Group className="mb-3" controlId="minimo">
              <Form.Label>Stock mínimo</Form.Label>
              <Form.Control type="number" min="0" step="1" value={formulario?.stock_minimo ?? '0'} required onChange={(evento) => cambiar('stock_minimo', evento.target.value)} />
            </Form.Group>
            {!formulario?.id && (
              <Form.Group className="mb-3" controlId="inicial">
                <Form.Label>Stock inicial</Form.Label>
                <Form.Control type="number" min="0" step="1" value={formulario?.stock_inicial ?? '0'} required onChange={(evento) => cambiar('stock_inicial', evento.target.value)} />
              </Form.Group>
            )}
            {formulario?.id && (
              <Form.Check
                id="activo"
                label="Producto activo"
                checked={Boolean(formulario.activo)}
                onChange={(evento) => cambiar('activo', evento.target.checked)}
              />
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setFormulario(null)}>Cancelar</Button>
            <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Guardar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      <Modal show={productoMovimiento !== null} onHide={() => setProductoMovimiento(null)} centered>
        <Form onSubmit={registrarMovimiento}>
          <Modal.Header closeButton>
            <Modal.Title>Movimiento · {productoMovimiento?.nombre}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <p className="mb-3">Stock actual: {productoMovimiento?.stock}</p>
            <Form.Group className="mb-3" controlId="tipoMovimiento">
              <Form.Label>Tipo</Form.Label>
              <Form.Select value={movimiento.tipo} onChange={(evento) => setMovimiento((actual) => ({ ...actual, tipo: evento.target.value }))}>
                <option value="entrada">Entrada</option>
                <option value="salida">Salida</option>
                <option value="ajuste">Ajuste</option>
              </Form.Select>
            </Form.Group>
            {movimiento.tipo === 'ajuste' && (
              <Form.Group className="mb-3" controlId="efecto">
                <Form.Label>Efecto</Form.Label>
                <Form.Select value={movimiento.efecto} onChange={(evento) => setMovimiento((actual) => ({ ...actual, efecto: evento.target.value }))}>
                  <option value="aumenta">Aumenta</option>
                  <option value="disminuye">Disminuye</option>
                </Form.Select>
              </Form.Group>
            )}
            <Form.Group className="mb-3" controlId="cantidad">
              <Form.Label>Cantidad</Form.Label>
              <Form.Control type="number" min="1" step="1" value={movimiento.cantidad} required onChange={(evento) => setMovimiento((actual) => ({ ...actual, cantidad: evento.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-3" controlId="motivo">
              <Form.Label>Motivo</Form.Label>
              <Form.Control maxLength={180} value={movimiento.motivo} onChange={(evento) => setMovimiento((actual) => ({ ...actual, motivo: evento.target.value }))} />
            </Form.Group>
            {historial.length > 0 && (
              <Table size="sm" responsive>
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                  </tr>
                </thead>
                <tbody>
                  {historial.map((item) => (
                    <tr key={item.id}>
                      <td>{item.creado_en}</td>
                      <td>{item.tipo}</td>
                      <td>{item.cantidad}</td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setProductoMovimiento(null)}>Cerrar</Button>
            <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Registrar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </>
  )
}
