import { useEffect, useRef, useState } from 'react'
import { Alert, Badge, Button, Col, Form, Modal, Row, Spinner, Table } from 'react-bootstrap'
import { useSearchParams } from 'react-router-dom'
import { cambiarEstadoCita } from '../../cliente/agenda'
import BotonAccion, { GrupoAcciones } from '../../components/BotonAccion'
import Icono from '../../components/Iconos'
import { abrirTurno, cerrarTurno, cobrarVenta, listarCierres, registrarAjuste, verCaja } from '../../cliente/caja'
import { useSesion } from '../../components/Sesion'

const PASOS = [
  { id: 'cita', etiqueta: 'Cliente' },
  { id: 'items', etiqueta: 'Servicios' },
  { id: 'pago', etiqueta: 'Pago' },
  { id: 'confirmar', etiqueta: 'Confirmar' },
]

const etiquetasEstado = {
  pendiente: 'Pendiente',
  en_proceso: 'En proceso',
  completada: 'Por cobrar',
}

const CLAVE_COBRO_AGENDA = 'caja_cobro_agenda'

function dinero(valor) {
  const n = Number(valor)
  if (!Number.isFinite(n)) {
    return '0.00'
  }
  return n.toFixed(2)
}

function claveServicio(servicio, indice) {
  return String(servicio.servicio_id ?? servicio.id ?? `${servicio.nombre}-${indice}`)
}

function leerCobroAgenda() {
  try {
    const crudo = sessionStorage.getItem(CLAVE_COBRO_AGENDA)
    if (!crudo) {
      return null
    }
    const datos = JSON.parse(crudo)
    if (!datos?.citaId) {
      return null
    }
    return { citaId: String(datos.citaId), listo: Boolean(datos.listo) }
  } catch {
    return null
  }
}

function guardarCobroAgenda(citaId, listo) {
  sessionStorage.setItem(CLAVE_COBRO_AGENDA, JSON.stringify({ citaId: String(citaId), listo: Boolean(listo) }))
}

function limpiarCobroAgenda() {
  sessionStorage.removeItem(CLAVE_COBRO_AGENDA)
}

function hoyLocal() {
  const ahora = new Date()
  const y = ahora.getFullYear()
  const m = String(ahora.getMonth() + 1).padStart(2, '0')
  const d = String(ahora.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function haceDiasLocal(dias) {
  const fecha = new Date()
  fecha.setDate(fecha.getDate() - dias)
  const y = fecha.getFullYear()
  const m = String(fecha.getMonth() + 1).padStart(2, '0')
  const d = String(fecha.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function BotonTexto({ icono, children, variant = 'primary', className = '', ...props }) {
  return (
    <Button variant={variant} className={`caja-btn-texto ${className}`.trim()} {...props}>
      {icono ? <Icono nombre={icono} /> : null}
      <span>{children}</span>
    </Button>
  )
}

export default function PaginaCaja() {
  const { usuario } = useSesion()
  const [parametros, setParametros] = useSearchParams()
  const esAdmin = usuario?.rol === 'administrador' || usuario?.rol === 'superadministrador'
  const avisoRef = useRef(null)
  const cobroRef = useRef(null)
  const cobroGuardado = leerCobroAgenda()
  const citaPendiente = parametros.get('cita') || cobroGuardado?.citaId || ''
  const listoPendiente = parametros.get('listo') === '1' || Boolean(cobroGuardado?.listo)
  const [medios, setMedios] = useState([])
  const [turno, setTurno] = useState(null)
  const [productos, setProductos] = useState([])
  const [catalogoServicios, setCatalogoServicios] = useState([])
  const [colaboradores, setColaboradores] = useState([])
  const [citas, setCitas] = useState([])
  const [citaId, setCitaId] = useState('')
  const [modoCobro, setModoCobro] = useState('cita')
  const [colaboradorId, setColaboradorId] = useState('')
  const [serviciosMarcados, setServiciosMarcados] = useState({})
  const [productosMarcados, setProductosMarcados] = useState({})
  const [paso, setPaso] = useState('cita')
  const [cobroListo, setCobroListo] = useState(false)
  const [partirPago, setPartirPago] = useState(false)
  const [cierres, setCierres] = useState([])
  const [filtroDesde, setFiltroDesde] = useState(() => haceDiasLocal(30))
  const [filtroHasta, setFiltroHasta] = useState(() => hoyLocal())
  const [cargandoCierres, setCargandoCierres] = useState(false)
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [base, setBase] = useState('0')
  const [pagos, setPagos] = useState({})
  const [reportados, setReportados] = useState({})
  const [guardando, setGuardando] = useState(false)
  const [ajusteTipo, setAjusteTipo] = useState('ingreso')
  const [ajusteMedio, setAjusteMedio] = useState('')
  const [ajusteMonto, setAjusteMonto] = useState('')
  const [ajusteMotivo, setAjusteMotivo] = useState('')
  const [vista, setVista] = useState('cobro')
  const [ticket, setTicket] = useState(null)
  const [panelItems, setPanelItems] = useState('')

  function mostrarAviso(texto) {
    setAviso(texto)
    requestAnimationFrame(() => {
      avisoRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    })
  }

  function reiniciarCobro() {
    limpiarCobroAgenda()
    setCitaId('')
    setModoCobro('cita')
    setColaboradorId('')
    setServiciosMarcados({})
    setProductosMarcados({})
    setPagos({})
    setPartirPago(false)
    setCobroListo(false)
    setPanelItems('')
    setPaso('cita')
  }

  function marcarServiciosDeCita(cita) {
    const siguiente = {}
    ;(cita?.servicios ?? []).forEach((servicio, indice) => {
      siguiente[claveServicio(servicio, indice)] = true
    })
    setServiciosMarcados(siguiente)
  }

  function aplicarDatos(datos) {
    const listaCitas = datos.citas ?? []
    setMedios(datos.medios ?? [])
    setTurno(datos.turno)
    setProductos(datos.productos ?? [])
    setCatalogoServicios(datos.servicios ?? [])
    setColaboradores(datos.colaboradores ?? [])
    setCitas(listaCitas)
    setReportados((actual) => {
      const siguiente = {}
      ;(datos.medios ?? []).forEach((medio) => {
        siguiente[medio.codigo] = actual[medio.codigo] ?? ''
      })
      return siguiente
    })
    if (!ajusteMedio && (datos.medios ?? []).length > 0) {
      setAjusteMedio(datos.medios[0].codigo)
    }
    return listaCitas
  }

  function usarCita(cita, opciones = {}) {
    const listo = Boolean(opciones.listo)
    setModoCobro('cita')
    setColaboradorId('')
    setCitaId(String(cita.id))
    marcarServiciosDeCita(cita)
    setProductosMarcados({})
    setPagos({})
    setPartirPago(false)
    setCobroListo(listo)
    if (listo) {
      guardarCobroAgenda(cita.id, true)
    } else {
      limpiarCobroAgenda()
    }
    setVista('cobro')
    setPanelItems('servicios')
    setPaso(listo ? 'pago' : 'items')
  }

  async function prepararParaCobro(cita) {
    if (cita.estado === 'pendiente') {
      await cambiarEstadoCita(cita.id, 'en_proceso')
    }
    if (cita.estado === 'pendiente' || cita.estado === 'en_proceso') {
      await cambiarEstadoCita(cita.id, 'completada')
    }
    const lista = aplicarDatos(await verCaja())
    const actualizada = lista.find((item) => String(item.id) === String(cita.id))
    if (!actualizada) {
      throw new Error('Esa cita no está disponible para cobro.')
    }
    return actualizada
  }

  async function elegirCita(cita) {
    setError('')
    setGuardando(true)
    try {
      usarCita(await prepararParaCobro(cita))
    } catch (fallo) {
      setError(fallo.message)
      try {
        aplicarDatos(await verCaja())
      } catch {
        /* el error principal ya se muestra */
      }
    } finally {
      setGuardando(false)
    }
  }

  function elegirSinCita() {
    setError('')
    limpiarCobroAgenda()
    setCitaId('')
    setModoCobro('sin_cita')
    setColaboradorId(colaboradores[0] ? String(colaboradores[0].id) : '')
    setServiciosMarcados({})
    setProductosMarcados({})
    setCobroListo(false)
    setPanelItems('')
    setPaso('items')
  }

  async function elegirCitaDesdeAgenda(lista) {
    if (!citaPendiente) {
      return
    }
    const venirListo = listoPendiente
    const id = String(citaPendiente)
    guardarCobroAgenda(id, venirListo)
    setParametros({}, { replace: true })
    let cita = (lista ?? []).find((item) => String(item.id) === id)
    setGuardando(true)
    try {
      if (!cita) {
        const fresca = aplicarDatos(await verCaja())
        cita = fresca.find((item) => String(item.id) === id)
      }
      if (!cita) {
        limpiarCobroAgenda()
        setError('Esa cita no está disponible para cobro.')
        return
      }
      const preparada = await prepararParaCobro(cita)
      usarCita(preparada, { listo: venirListo })
      requestAnimationFrame(() => {
        cobroRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      })
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function aplicar(datos) {
    const listaCitas = aplicarDatos(datos)
    void elegirCitaDesdeAgenda(listaCitas)
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

  const citaElegida = citas.find((cita) => String(cita.id) === String(citaId))
  const esSinCita = modoCobro === 'sin_cita'
  const profesionalElegido = colaboradores.find((item) => String(item.id) === String(colaboradorId))
  const serviciosElegidos = esSinCita
    ? catalogoServicios.filter((servicio) => serviciosMarcados[String(servicio.id)])
    : (citaElegida?.servicios ?? []).filter((servicio, indice) => (
      serviciosMarcados[claveServicio(servicio, indice)]
    ))
  const lineas = productos
    .filter((producto) => productosMarcados[producto.id]?.activo)
    .map((producto) => ({
      producto_id: producto.id,
      nombre: producto.nombre,
      precio_venta: producto.precio_venta,
      cantidad: Number(productosMarcados[producto.id]?.cantidad) || 1,
    }))
  const totalServicios = serviciosElegidos.reduce(
    (suma, servicio) => suma + Number(servicio.precio_aplicado ?? servicio.precio),
    0,
  )
  const totalLineas = lineas.reduce((suma, linea) => suma + Number(linea.precio_venta) * linea.cantidad, 0)
  const totalReferencia = dinero(totalServicios + totalLineas)
  const ventas = turno?.ventas ?? []
  const hayQueCobrar = serviciosElegidos.length > 0 || lineas.length > 0
  const panelServicios = panelItems === 'servicios'
  const panelProductos = panelItems === 'productos'
  const pideProfesional = esSinCita && serviciosElegidos.length > 0
  const medioUnico = medios.find((medio) => String(pagos[medio.codigo] ?? '').trim() !== '' && Number(pagos[medio.codigo]) > 0)
  const sumaPagos = medios.reduce((suma, medio) => suma + (Number(pagos[medio.codigo]) || 0), 0)
  const resumenElegido = [
    serviciosElegidos.length > 0 ? `${serviciosElegidos.length} servicio${serviciosElegidos.length === 1 ? '' : 's'}` : null,
    lineas.length > 0 ? `${lineas.length} producto${lineas.length === 1 ? '' : 's'}` : null,
  ].filter(Boolean).join(' · ')

  function elegirPanelItems(tipo) {
    setPanelItems((actual) => (actual === tipo ? '' : tipo))
  }

  function armarTicket(mapaPagos) {
    const cliente = citaElegida?.cliente
      || (esSinCita
        ? (serviciosElegidos.length > 0 ? 'Cliente sin cita' : 'Solo productos')
        : 'Cliente')
    const profesional = citaElegida?.profesional || (pideProfesional ? (profesionalElegido?.nombre || '—') : '—')
    const items = [
      ...serviciosElegidos.map((servicio) => ({
        nombre: servicio.nombre,
        monto: dinero(servicio.precio_aplicado ?? servicio.precio),
      })),
      ...lineas.map((linea) => ({
        nombre: `${linea.nombre} × ${linea.cantidad}`,
        monto: dinero(Number(linea.precio_venta) * linea.cantidad),
      })),
    ]
    const detallePagos = medios
      .filter((medio) => String(mapaPagos[medio.codigo] ?? '').trim() !== '')
      .map((medio) => ({
        nombre: medio.nombre,
        monto: dinero(mapaPagos[medio.codigo]),
      }))
    return {
      cliente,
      profesional,
      items,
      pagos: detallePagos,
      total: totalReferencia,
      hora: new Date().toLocaleString('es-CO', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }),
    }
  }

  function alternarServicio(clave) {
    setServiciosMarcados((actual) => ({ ...actual, [clave]: !actual[clave] }))
  }

  function alternarProducto(producto) {
    setProductosMarcados((actual) => {
      const actualProducto = actual[producto.id]
      if (actualProducto?.activo) {
        return { ...actual, [producto.id]: { ...actualProducto, activo: false } }
      }
      return {
        ...actual,
        [producto.id]: { activo: true, cantidad: actualProducto?.cantidad || '1' },
      }
    })
  }

  function cambiarCantidadProducto(productoId, cantidad) {
    setProductosMarcados((actual) => ({
      ...actual,
      [productoId]: {
        activo: true,
        cantidad,
      },
    }))
  }

  function irAPago() {
    setError('')
    if (!hayQueCobrar) {
      setError(panelItems
        ? 'Marca al menos un servicio o un producto.'
        : 'Elige Servicios o Productos.')
      return
    }
    if (pideProfesional && !colaboradorId) {
      setError('Elige el profesional que atendió.')
      return
    }
    const invalidos = lineas.filter((linea) => !Number.isInteger(linea.cantidad) || linea.cantidad < 1)
    if (invalidos.length > 0) {
      setError('La cantidad de cada producto debe ser un entero mayor que cero.')
      return
    }
    setPagos({})
    setPartirPago(false)
    setPaso('pago')
  }

  function elegirMedio(codigo) {
    setError('')
    setPartirPago(false)
    const siguiente = {}
    medios.forEach((medio) => {
      siguiente[medio.codigo] = medio.codigo === codigo ? totalReferencia : ''
    })
    setPagos(siguiente)
    if (cobroListo) {
      void confirmarCobro(siguiente)
      return
    }
    setPaso('confirmar')
  }

  function irAConfirmarPagoPartido() {
    setError('')
    const cuerpoPagos = medios
      .map((medio) => ({ medio: medio.codigo, monto: pagos[medio.codigo] ?? '' }))
      .filter((pago) => String(pago.monto).trim() !== '')
    if (cuerpoPagos.length === 0) {
      setError('Indica el monto en al menos un medio de pago.')
      return
    }
    if (dinero(sumaPagos) !== totalReferencia) {
      setError(`Los pagos deben sumar ${totalReferencia}.`)
      return
    }
    if (cobroListo) {
      void confirmarCobro(pagos)
      return
    }
    setPaso('confirmar')
  }

  async function abrir(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      aplicar(await abrirTurno(base))
      setVista('cobro')
      mostrarAviso('Caja abierta. Ya puedes cobrar.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function confirmarCobro(pagosUsar = null) {
    setError('')
    setAviso('')
    if (!hayQueCobrar) {
      setError('Marca al menos un servicio o un producto.')
      return
    }
    const mapaPagos = pagosUsar ?? pagos
    const cuerpoLineas = lineas.map((linea) => ({ producto_id: linea.producto_id, cantidad: linea.cantidad }))
    const cuerpoPagos = medios
      .map((medio) => ({ medio: medio.codigo, monto: mapaPagos[medio.codigo] ?? '' }))
      .filter((pago) => String(pago.monto).trim() !== '')
    if (cuerpoPagos.length === 0) {
      setError('Elige un medio de pago.')
      setPaso('pago')
      return
    }
    if (pideProfesional && !colaboradorId) {
      setError('Elige el profesional que atendió.')
      setPaso('items')
      return
    }
    const opciones = {}
    if (Boolean(citaElegida) && serviciosElegidos.length > 0) {
      opciones.citaId = citaId
      opciones.servicios = serviciosElegidos.map((servicio) => servicio.servicio_id).filter((id) => id != null)
    } else if (esSinCita && serviciosElegidos.length > 0) {
      opciones.servicios = serviciosElegidos.map((servicio) => servicio.id)
      opciones.colaboradorId = colaboradorId
    }
    const resumenTicket = armarTicket(mapaPagos)
    setGuardando(true)
    try {
      aplicar(await cobrarVenta(cuerpoLineas, cuerpoPagos, opciones))
      reiniciarCobro()
      setTicket(resumenTicket)
      setVista('cobro')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function ajustar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      aplicar(await registrarAjuste(ajusteTipo, ajusteMedio, ajusteMonto, ajusteMotivo))
      setAjusteMonto('')
      setAjusteMotivo('')
      mostrarAviso('Ajuste registrado.')
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
      reiniciarCobro()
      setReportados({})
      setVista('cobro')
      mostrarAviso('Caja cerrada. El turno terminó.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function clasePaso(id) {
    const orden = PASOS.findIndex((item) => item.id === id)
    const actual = PASOS.findIndex((item) => item.id === paso)
    if (orden === actual) {
      return 'activo'
    }
    if (orden < actual) {
      return 'hecho'
    }
    return ''
  }

  function irACobro() {
    setError('')
    setVista('cobro')
  }

  function irACierre() {
    setError('')
    setVista('cierre')
  }

  function irAAjustes() {
    setError('')
    setVista('ajustes')
  }

  async function cargarCierres(desde = filtroDesde, hasta = filtroHasta) {
    setError('')
    setCargandoCierres(true)
    try {
      const datos = await listarCierres(desde, hasta)
      setCierres(datos.cierres ?? [])
      if (datos.desde) {
        setFiltroDesde(datos.desde)
      }
      if (datos.hasta) {
        setFiltroHasta(datos.hasta)
      }
    } catch (fallo) {
      setError(fallo.message)
      setCierres([])
    } finally {
      setCargandoCierres(false)
    }
  }

  function irAHistorial() {
    setError('')
    setVista('historial')
    void cargarCierres()
  }

  async function consultarCierres(evento) {
    evento.preventDefault()
    await cargarCierres(filtroDesde, filtroHasta)
  }

  function cerrarTicket() {
    setTicket(null)
  }

  function nuevoCobroDesdeTicket() {
    setTicket(null)
    setPaso('cita')
  }

  return (
    <>
      <div className="caja-cabecera mb-3">
        <h1 className="h3 mb-0">Caja</h1>
        {turno && (
          <div className="caja-estado">
            <span className="caja-estado__punto" aria-hidden="true" />
            <div className="caja-estado__texto">
              <strong>Abierta</strong>
              <span>{turno.usuario}</span>
              <span>Base {turno.saldo_inicial}</span>
            </div>
          </div>
        )}
      </div>

      <div ref={avisoRef}>
        {error && <Alert variant="danger" className="caja-alerta">{error}</Alert>}
        {aviso && (
          <Alert variant="success" className="caja-alerta caja-alerta-ok" onClose={() => setAviso('')} dismissible>
            {aviso}
          </Alert>
        )}
      </div>

      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando caja</span>
        </Spinner>
      ) : vista === 'historial' ? (
        <section className="caja-panel">
          <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 className="h4 mb-0">Cierres anteriores</h2>
            <BotonTexto icono="chevronLeft" variant="outline-primary" onClick={irACobro}>
              Volver
            </BotonTexto>
          </div>
          <p className="text-secondary mb-3">
            Consulta los cierres de esta sede por rango de fechas (hasta tres meses).
          </p>
          <Form onSubmit={consultarCierres} className="caja-filtro-cierres mb-4">
            <Row className="g-2 align-items-end">
              <Col xs={6} md={3}>
                <Form.Label htmlFor="cierre-desde">Desde</Form.Label>
                <Form.Control
                  id="cierre-desde"
                  type="date"
                  required
                  value={filtroDesde}
                  onChange={(evento) => setFiltroDesde(evento.target.value)}
                />
              </Col>
              <Col xs={6} md={3}>
                <Form.Label htmlFor="cierre-hasta">Hasta</Form.Label>
                <Form.Control
                  id="cierre-hasta"
                  type="date"
                  required
                  value={filtroHasta}
                  onChange={(evento) => setFiltroHasta(evento.target.value)}
                />
              </Col>
              <Col xs={12} md="auto">
                <BotonTexto type="submit" icono="list" variant="primary" disabled={cargandoCierres}>
                  {cargandoCierres ? 'Consultando…' : 'Consultar'}
                </BotonTexto>
              </Col>
            </Row>
          </Form>
          {cargandoCierres ? (
            <Spinner animation="border" role="status" size="sm">
              <span className="visually-hidden">Cargando cierres</span>
            </Spinner>
          ) : cierres.length === 0 ? (
            <p className="text-secondary mb-0">No hay cierres en ese rango.</p>
          ) : (
            cierres.map((cierre) => (
              <div key={cierre.id} className="caja-cierre-card mb-4">
                <p className="mb-2">
                  <strong>{cierre.cerrado_en}</strong>
                  <span className="text-secondary"> · {cierre.usuario} · base {cierre.saldo_inicial}</span>
                </p>
                <Table responsive size="sm" className="mb-0">
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
        </section>
      ) : turno === null ? (
        <section className="caja-panel caja-panel--apertura">
          <h2 className="h4 mb-2">Abrir caja</h2>
          <p className="text-secondary mb-3">
            Indica el efectivo de base para empezar el turno. Sin caja abierta no se pueden cobrar ventas.
          </p>
          {(cobroListo || citaPendiente) && (
            <Alert variant="light" className="border border-dark text-black mb-3">
              Hay una venta desde Agenda lista. Abre la caja y luego solo elige el medio de pago.
            </Alert>
          )}
          <Form onSubmit={abrir} style={{ maxWidth: '22rem' }}>
            <Form.Group className="mb-3" controlId="base">
              <Form.Label>Efectivo de base</Form.Label>
              <Form.Control type="number" min="0" step="0.01" value={base} required onChange={(evento) => setBase(evento.target.value)} />
            </Form.Group>
            <GrupoAcciones>
              <BotonTexto type="submit" icono="power" variant="primary" disabled={guardando}>
                {guardando ? 'Abriendo…' : 'Abrir caja'}
              </BotonTexto>
              <BotonTexto type="button" icono="list" variant="outline-primary" onClick={irAHistorial}>
                Cierres anteriores
              </BotonTexto>
            </GrupoAcciones>
          </Form>
        </section>
      ) : (
        <>
          {vista === 'cobro' && (
            <>
              <div className="caja-acciones-turno mb-4">
                <BotonTexto icono="power" variant="outline-danger" onClick={irACierre}>
                  Cerrar turno
                </BotonTexto>
                <BotonTexto icono="list" variant="outline-primary" onClick={irAAjustes}>
                  Ajustes
                </BotonTexto>
                <BotonTexto icono="list" variant="outline-primary" onClick={irAHistorial}>
                  Cierres anteriores
                </BotonTexto>
              </div>

              <section className="caja-cobro" ref={cobroRef}>
                <h2 className="h4 mb-3">Cobrar</h2>
                <ol className="caja-pasos-lista" aria-label="Pasos del cobro">
                  {PASOS.map((item) => (
                    <li key={item.id} className={clasePaso(item.id)}>{item.etiqueta}</li>
                  ))}
                </ol>

                {paso === 'cita' && (
                  <div className="caja-paso">
                    <p className="text-secondary mb-3">Elige la cita del día o un cobro sin cita.</p>
                    <div className="caja-citas mb-3">
                      <button type="button" className="caja-cita-card caja-cita-card--extra" disabled={guardando} onClick={elegirSinCita}>
                        <strong>Sin cita</strong>
                        <span>Servicios, productos o ambos</span>
                      </button>
                    </div>
                    {citas.length === 0 ? (
                      <p className="text-secondary mt-3 mb-0">No hay citas del día para cobrar.</p>
                    ) : (
                      <div className="caja-citas">
                        {citas.map((cita) => (
                          <button
                            key={cita.id}
                            type="button"
                            className={`caja-cita-card${String(cita.id) === String(citaId) ? ' activa' : ''}`}
                            disabled={guardando}
                            onClick={() => elegirCita(cita)}
                          >
                            <span className="caja-cita-card__top">
                              <strong>{cita.cliente}</strong>
                              <Badge className="marca-activo">{etiquetasEstado[cita.estado] ?? cita.estado}</Badge>
                            </span>
                            <span>{cita.inicio.slice(0, 16)} · {cita.profesional}</span>
                            <span>{cita.servicios.map((servicio) => servicio.nombre).join(', ')}</span>
                            <em>{cita.total}</em>
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {paso === 'items' && (
                  <div className="caja-paso">
                    <p className="mb-3">
                      {citaElegida
                        ? <>¿Qué cobras a <strong>{citaElegida.cliente}</strong>? Abre Servicios o Productos.</>
                        : 'Abre Servicios o Productos. Si necesitas de los dos, marca uno, luego el otro: lo marcado se conserva.'}
                    </p>
                    <div className="caja-tipo-opciones mb-3" role="group" aria-label="Qué vas a cobrar">
                      <button
                        type="button"
                        className={`caja-tipo${panelServicios ? ' activa' : ''}`}
                        aria-pressed={panelServicios}
                        onClick={() => elegirPanelItems('servicios')}
                      >
                        Servicios
                        {serviciosElegidos.length > 0 ? ` (${serviciosElegidos.length})` : ''}
                      </button>
                      <button
                        type="button"
                        className={`caja-tipo${panelProductos ? ' activa' : ''}`}
                        aria-pressed={panelProductos}
                        onClick={() => elegirPanelItems('productos')}
                      >
                        Productos
                        {lineas.length > 0 ? ` (${lineas.length})` : ''}
                      </button>
                    </div>
                    {!panelItems && (
                      <p className="text-secondary mb-3">Elige una opción para ver la lista.</p>
                    )}
                    {panelServicios && esSinCita && (
                      <Form.Group className="mb-3" controlId="profesional-caja">
                        <Form.Label>Profesional que atendió</Form.Label>
                        <Form.Select value={colaboradorId} onChange={(evento) => setColaboradorId(evento.target.value)}>
                          <option value="">Elegir</option>
                          {colaboradores.map((colaborador) => (
                            <option key={colaborador.id} value={colaborador.id}>{colaborador.nombre}</option>
                          ))}
                        </Form.Select>
                      </Form.Group>
                    )}
                    {panelServicios && citaElegida && (
                      <div className="caja-checklist mb-4">
                        <h3 className="h6">Servicios de la cita</h3>
                        {citaElegida.servicios.map((servicio, indice) => {
                          const clave = claveServicio(servicio, indice)
                          return (
                            <label key={clave} className="caja-check">
                              <input
                                type="checkbox"
                                checked={Boolean(serviciosMarcados[clave])}
                                onChange={() => alternarServicio(clave)}
                              />
                              <span>{servicio.nombre}</span>
                              <em>{servicio.precio_aplicado}</em>
                            </label>
                          )
                        })}
                      </div>
                    )}
                    {panelServicios && esSinCita && (
                      <div className="caja-checklist mb-4">
                        <h3 className="h6">Servicios</h3>
                        {catalogoServicios.length === 0 ? (
                          <p className="text-secondary mb-0">No hay servicios activos.</p>
                        ) : (
                          catalogoServicios.map((servicio) => {
                            const clave = String(servicio.id)
                            return (
                              <label key={clave} className="caja-check">
                                <input
                                  type="checkbox"
                                  checked={Boolean(serviciosMarcados[clave])}
                                  onChange={() => alternarServicio(clave)}
                                />
                                <span>{servicio.nombre}</span>
                                <em>{servicio.precio}</em>
                              </label>
                            )
                          })
                        )}
                      </div>
                    )}
                    {panelProductos && (
                      <div className="caja-checklist mb-4">
                        <h3 className="h6">Productos</h3>
                        {productos.length === 0 ? (
                          <p className="text-secondary mb-0">No hay productos de venta.</p>
                        ) : (
                          productos.map((producto) => {
                            const marcado = productosMarcados[producto.id]
                            const sinStock = producto.stock < 1
                            return (
                              <div key={producto.id} className="caja-check caja-check--producto">
                                <label>
                                  <input
                                    type="checkbox"
                                    checked={Boolean(marcado?.activo)}
                                    disabled={sinStock}
                                    onChange={() => alternarProducto(producto)}
                                  />
                                  <span>{producto.nombre}</span>
                                  <em>{producto.precio_venta}</em>
                                </label>
                                {marcado?.activo && (
                                  <Form.Control
                                    type="number"
                                    min="1"
                                    step="1"
                                    aria-label={`Cantidad ${producto.nombre}`}
                                    value={marcado.cantidad}
                                    onChange={(evento) => cambiarCantidadProducto(producto.id, evento.target.value)}
                                  />
                                )}
                              </div>
                            )
                          })
                        )}
                      </div>
                    )}
                    <p className="mb-3">
                      {resumenElegido ? <>Elegido: {resumenElegido}. </> : null}
                      Total: <strong>{totalReferencia}</strong>
                    </p>
                    <GrupoAcciones>
                      <BotonAccion icono="chevronLeft" titulo="Volver" onClick={() => setPaso('cita')} />
                      <BotonTexto icono="chevronRight" variant="primary" onClick={irAPago}>
                        Continuar al pago
                      </BotonTexto>
                    </GrupoAcciones>
                  </div>
                )}

                {paso === 'pago' && (
                  <div className="caja-paso">
                    {cobroListo && citaElegida && (
                      <div className="caja-resumen mb-3">
                        <h3 className="h6">Cobro desde agenda</h3>
                        <p className="mb-2">
                          <strong>{citaElegida.cliente}</strong>
                          <span className="text-secondary"> · {citaElegida.profesional}</span>
                        </p>
                        <ul className="caja-resumen-lista">
                          {serviciosElegidos.map((servicio, indice) => (
                            <li key={claveServicio(servicio, indice)}>
                              <span>{servicio.nombre}</span>
                              <em>{servicio.precio_aplicado}</em>
                            </li>
                          ))}
                        </ul>
                        <p className="mb-0">
                          Total: <strong>{totalReferencia}</strong>
                        </p>
                      </div>
                    )}
                    {cobroListo && !citaElegida && (
                      <Alert variant="danger" className="caja-alerta">Preparando la venta… Si no aparece, vuelve a Agenda y pulsa otra vez el icono de caja.</Alert>
                    )}
                    {!cobroListo && (
                      <p className="mb-3">
                        Total a cobrar: <strong>{totalReferencia}</strong>
                      </p>
                    )}
                    {cobroListo && citaElegida && (
                      <p className="mb-3">
                        Elige cómo paga. Al tocar el medio se cobra de inmediato.
                      </p>
                    )}
                    {!partirPago ? (
                      <>
                        <div className="caja-medios">
                          {medios.map((medio) => (
                            <button
                              key={medio.codigo}
                              type="button"
                              className="caja-medio"
                              disabled={guardando}
                              onClick={() => elegirMedio(medio.codigo)}
                            >
                              {medio.nombre}
                            </button>
                          ))}
                        </div>
                        <button type="button" className="caja-link mt-3" onClick={() => setPartirPago(true)}>
                          Partir entre varios medios
                        </button>
                      </>
                    ) : (
                      <>
                        <Row className="g-2 mb-3">
                          {medios.map((medio) => (
                            <Col md={4} key={medio.codigo}>
                              <Form.Label htmlFor={`pago-${medio.codigo}`}>{medio.nombre}</Form.Label>
                              <Form.Control
                                id={`pago-${medio.codigo}`}
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                value={pagos[medio.codigo] ?? ''}
                                onChange={(evento) => setPagos((actual) => ({ ...actual, [medio.codigo]: evento.target.value }))}
                              />
                            </Col>
                          ))}
                        </Row>
                        <p className="text-secondary mb-3">Suma actual: {dinero(sumaPagos)}</p>
                        <GrupoAcciones>
                          <BotonAccion icono="x" titulo="Un solo medio" onClick={() => { setPartirPago(false); setPagos({}) }} />
                          <BotonTexto
                            icono={cobroListo ? 'cash' : 'chevronRight'}
                            variant="primary"
                            disabled={guardando}
                            onClick={irAConfirmarPagoPartido}
                          >
                            {cobroListo ? (guardando ? 'Cobrando…' : 'Cobrar') : 'Continuar'}
                          </BotonTexto>
                        </GrupoAcciones>
                      </>
                    )}
                    <div className="mt-3">
                      <BotonAccion
                        icono="chevronLeft"
                        titulo={cobroListo ? 'Revisar servicios' : 'Volver a servicios'}
                        onClick={() => { setCobroListo(false); setPaso('items') }}
                      />
                    </div>
                  </div>
                )}

                {paso === 'confirmar' && (
                  <div className="caja-paso">
                    <div className="caja-resumen">
                      <h3 className="h6">Resumen</h3>
                      {citaElegida && (
                        <p className="mb-2">
                          <strong>{citaElegida.cliente}</strong>
                          <span className="text-secondary"> · {citaElegida.profesional}</span>
                        </p>
                      )}
                      {esSinCita && (
                        <p className="mb-2">
                          <strong>Sin cita</strong>
                          <span className="text-secondary"> · {profesionalElegido?.nombre ?? '—'}</span>
                        </p>
                      )}
                      <ul className="caja-resumen-lista">
                        {serviciosElegidos.map((servicio, indice) => (
                          <li key={esSinCita ? String(servicio.id) : claveServicio(servicio, indice)}>
                            <span>{servicio.nombre}</span>
                            <em>{servicio.precio_aplicado ?? servicio.precio}</em>
                          </li>
                        ))}
                        {lineas.map((linea) => (
                          <li key={linea.producto_id}>
                            <span>{linea.nombre} × {linea.cantidad}</span>
                            <em>{dinero(Number(linea.precio_venta) * linea.cantidad)}</em>
                          </li>
                        ))}
                      </ul>
                      <p className="mb-2">
                        Pago:{' '}
                        {medios
                          .filter((medio) => String(pagos[medio.codigo] ?? '').trim() !== '')
                          .map((medio) => `${medio.nombre} ${pagos[medio.codigo]}`)
                          .join(' · ') || (medioUnico ? medioUnico.nombre : '—')}
                      </p>
                      <p className="mb-0">
                        Total: <strong>{totalReferencia}</strong>
                      </p>
                    </div>
                    <GrupoAcciones className="mt-3">
                      <BotonAccion icono="chevronLeft" titulo="Volver al pago" onClick={() => setPaso('pago')} />
                      <BotonTexto
                        icono="check"
                        variant="primary"
                        disabled={guardando}
                        onClick={() => confirmarCobro()}
                      >
                        {guardando ? 'Confirmando…' : 'Confirmar cobro'}
                      </BotonTexto>
                    </GrupoAcciones>
                  </div>
                )}
              </section>

              <section className="mt-5">
                <h2 className="h5 mb-2">Ventas del turno</h2>
                {ventas.length === 0 ? (
                  <p className="text-secondary mb-0">Todavía no hay ventas en este turno.</p>
                ) : (
                  <Table responsive size="sm" className="mt-2">
                    <thead>
                      <tr>
                        <th>Hora</th>
                        <th>Detalle</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {ventas.map((venta) => (
                        <tr key={venta.id}>
                          <td>{venta.creado_en}</td>
                          <td className="text-wrap">
                            {(venta.items ?? []).map((item) => `${item.nombre} × ${item.cantidad}`).join(', ')}
                            {' · '}
                            {(venta.pagos ?? []).map((pago) => `${pago.nombre} ${pago.monto}`).join(', ')}
                          </td>
                          <td>{venta.total}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                )}
              </section>
            </>
          )}

          {vista === 'cierre' && (
            <section className="caja-panel">
              <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 className="h4 mb-0">Cerrar turno</h2>
                <BotonTexto icono="chevronLeft" variant="outline-primary" onClick={irACobro}>
                  Volver a cobrar
                </BotonTexto>
              </div>
              <p className="text-secondary mb-3">
                Cuenta el dinero real por medio y escríbelo abajo. En efectivo el sistema ya suma la base de apertura. Al cerrar termina el turno y no se podrán cobrar más ventas hasta abrir de nuevo.
              </p>
              <Form onSubmit={cerrar} style={{ maxWidth: '40rem' }}>
                <Table responsive size="sm">
                  <thead>
                    <tr>
                      <th>Medio</th>
                      <th>Sistema</th>
                      <th>Reportado</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(turno.medios ?? []).map((medio) => (
                      <tr key={medio.medio}>
                        <td>{medio.nombre}</td>
                        <td>
                          <strong>{medio.monto_sistema}</strong>
                          {medio.medio === 'efectivo' && (
                            <div className="caja-cierre-detalle">
                              Efectivo del turno {medio.sin_base ?? dinero(Number(medio.monto_sistema) - Number(turno.saldo_inicial))}
                              {' + '}
                              base {medio.base_apertura ?? turno.saldo_inicial}
                            </div>
                          )}
                        </td>
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
                <GrupoAcciones>
                  <BotonTexto type="submit" icono="power" variant="danger" disabled={guardando}>
                    {guardando ? 'Cerrando…' : 'Cerrar caja'}
                  </BotonTexto>
                  <BotonTexto type="button" icono="list" variant="outline-primary" onClick={irAHistorial}>
                    Ver cierres anteriores
                  </BotonTexto>
                </GrupoAcciones>
              </Form>
            </section>
          )}

          {vista === 'ajustes' && (
            <section className="caja-panel">
              <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 className="h4 mb-0">Ajustes de caja</h2>
                <BotonTexto icono="chevronLeft" variant="outline-primary" onClick={irACobro}>
                  Volver a cobrar
                </BotonTexto>
              </div>
              <p className="text-secondary mb-3">
                Usa esta pantalla solo para ingresos o egresos que no son una venta (fondo, retiro, corrección).
              </p>
              {esAdmin ? (
                <Form onSubmit={ajustar} style={{ maxWidth: '28rem' }}>
                  <Row className="g-2 mb-3">
                    <Col xs={6}>
                      <Form.Label htmlFor="ajuste-tipo">Tipo</Form.Label>
                      <Form.Select id="ajuste-tipo" value={ajusteTipo} onChange={(evento) => setAjusteTipo(evento.target.value)}>
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                      </Form.Select>
                    </Col>
                    <Col xs={6}>
                      <Form.Label htmlFor="ajuste-medio">Medio</Form.Label>
                      <Form.Select id="ajuste-medio" value={ajusteMedio} onChange={(evento) => setAjusteMedio(evento.target.value)}>
                        {medios.map((medio) => (
                          <option key={medio.codigo} value={medio.codigo}>{medio.nombre}</option>
                        ))}
                      </Form.Select>
                    </Col>
                    <Col xs={12}>
                      <Form.Label htmlFor="ajuste-monto">Monto</Form.Label>
                      <Form.Control
                        id="ajuste-monto"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                        value={ajusteMonto}
                        onChange={(evento) => setAjusteMonto(evento.target.value)}
                      />
                    </Col>
                    <Col xs={12}>
                      <Form.Label htmlFor="ajuste-motivo">Motivo</Form.Label>
                      <Form.Control
                        id="ajuste-motivo"
                        maxLength={180}
                        required
                        value={ajusteMotivo}
                        onChange={(evento) => setAjusteMotivo(evento.target.value)}
                      />
                    </Col>
                  </Row>
                  <BotonTexto type="submit" icono="save" variant="primary" disabled={guardando}>
                    {guardando ? 'Guardando…' : 'Registrar ajuste'}
                  </BotonTexto>
                </Form>
              ) : (
                <p className="text-secondary">Solo el administrador puede registrar ajustes.</p>
              )}

              <h3 className="h5 mt-4 mb-2">Ajustes del turno</h3>
              {(turno.ajustes ?? []).length === 0 ? (
                <p className="text-secondary mb-0">No hay ajustes en este turno.</p>
              ) : (
                <Table responsive size="sm">
                  <thead>
                    <tr>
                      <th>Hora</th>
                      <th>Detalle</th>
                      <th>Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    {turno.ajustes.map((ajuste) => (
                      <tr key={ajuste.id}>
                        <td>{ajuste.creado_en}</td>
                        <td className="text-wrap">
                          {ajuste.tipo === 'ingreso' ? 'Ingreso' : 'Egreso'} · {ajuste.nombre_medio}
                          {' · '}
                          {ajuste.motivo}
                          {' · '}
                          {ajuste.usuario}
                        </td>
                        <td>{ajuste.monto}</td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              )}
            </section>
          )}
        </>
      )}

      <Modal show={ticket !== null} onHide={cerrarTicket} centered className="caja-ticket-modal">
        <Modal.Body>
          {ticket && (
            <div className="caja-ticket">
              <p className="caja-ticket__marca">3 Bigotes</p>
              <h2 className="caja-ticket__titulo">Venta registrada</h2>
              <p className="caja-ticket__meta">{ticket.hora}</p>
              <p className="caja-ticket__cliente">
                <strong>{ticket.cliente}</strong>
                <span>{ticket.profesional}</span>
              </p>
              <ul className="caja-ticket__lista">
                {ticket.items.map((item, indice) => (
                  <li key={`${item.nombre}-${indice}`}>
                    <span>{item.nombre}</span>
                    <em>{item.monto}</em>
                  </li>
                ))}
              </ul>
              <div className="caja-ticket__pagos">
                {ticket.pagos.map((pago) => (
                  <span key={pago.nombre}>{pago.nombre} {pago.monto}</span>
                ))}
              </div>
              <p className="caja-ticket__total">
                Total
                <strong>{ticket.total}</strong>
              </p>
              <div className="caja-ticket__acciones">
                <BotonTexto variant="primary" icono="cash" onClick={nuevoCobroDesdeTicket}>
                  Nuevo cobro
                </BotonTexto>
                <BotonTexto variant="outline-primary" onClick={cerrarTicket}>
                  Cerrar
                </BotonTexto>
              </div>
            </div>
          )}
        </Modal.Body>
      </Modal>
    </>
  )
}
