import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Col, Form, Modal, Row, Spinner, Table } from 'react-bootstrap'
import { buscarClientes, cambiarEstadoCita, crearCita, guardarServicio, listarCitas, listarServicios } from '../../cliente/agenda'
import { useSesion } from '../../components/Sesion'

const estados = {
  pendiente: 'Pendiente',
  en_proceso: 'En proceso',
  completada: 'Completada',
  cancelada: 'Cancelada',
}

const servicioVacio = {
  id: null,
  nombre: '',
  duracion_minutos: '30',
  precio: '',
  activo: true,
}

function diaIso(fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${fecha.getFullYear()}-${mes}-${dia}`
}

function sumarDias(iso, dias) {
  const fecha = new Date(`${iso}T12:00:00`)
  fecha.setDate(fecha.getDate() + dias)
  return diaIso(fecha)
}

function lunesDe(iso) {
  const fecha = new Date(`${iso}T12:00:00`)
  const distancia = (fecha.getDay() + 6) % 7
  fecha.setDate(fecha.getDate() - distancia)
  return diaIso(fecha)
}

function hora(valor) {
  return String(valor).slice(11, 16)
}

export default function PaginaAgenda() {
  const { usuario } = useSesion()
  const esAdmin = usuario.rol === 'administrador'
  const puedeReservar = esAdmin || usuario.rol === 'recepcion'
  const hoy = diaIso(new Date())
  const [vista, setVista] = useState('dia')
  const [fecha, setFecha] = useState(hoy)
  const [profesionalId, setProfesionalId] = useState('')
  const [profesionales, setProfesionales] = useState([])
  const [citas, setCitas] = useState([])
  const [servicios, setServicios] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [guardando, setGuardando] = useState(false)
  const [reserva, setReserva] = useState(null)
  const [errorReserva, setErrorReserva] = useState('')
  const [busqueda, setBusqueda] = useState('')
  const [clientes, setClientes] = useState([])
  const [servicio, setServicio] = useState(null)
  const [errorServicio, setErrorServicio] = useState('')

  const inicioRango = vista === 'semana' ? lunesDe(fecha) : fecha
  const finRango = sumarDias(inicioRango, vista === 'semana' ? 7 : 1)
  const dias = vista === 'semana'
    ? Array.from({ length: 7 }, (_, indice) => sumarDias(inicioRango, indice))
    : [fecha]

  async function cargarServicios() {
    if (!puedeReservar) {
      setServicios([])
      return
    }
    const datos = await listarServicios()
    setServicios(datos.servicios)
  }

  async function cargar() {
    setError('')
    try {
      const datos = await listarCitas(`${inicioRango} 00:00:00`, `${finRango} 00:00:00`, profesionalId)
      setCitas(datos.citas)
      setProfesionales(datos.profesionales)
      await cargarServicios()
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [inicioRango, finRango, profesionalId])

  async function mover(id, estado) {
    setError('')
    setGuardando(true)
    try {
      await cambiarEstadoCita(id, estado)
      setAviso('Cita actualizada.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function buscar(evento) {
    evento.preventDefault()
    setErrorReserva('')
    try {
      const datos = await buscarClientes(busqueda)
      setClientes(datos.clientes)
    } catch (fallo) {
      setErrorReserva(fallo.message)
    }
  }

  async function reservar(evento) {
    evento.preventDefault()
    setErrorReserva('')
    setGuardando(true)
    const cuerpo = {
      colaborador_id: reserva.colaborador_id,
      inicio: reserva.inicio.replace('T', ' '),
      servicios: reserva.servicios,
      notas: reserva.notas,
    }
    if (reserva.cliente_id) {
      cuerpo.cliente_id = reserva.cliente_id
    } else {
      cuerpo.cliente = {
        nombre: reserva.nombre,
        telefono: reserva.telefono,
        correo: reserva.correo,
      }
    }
    try {
      await crearCita(cuerpo)
      setReserva(null)
      setAviso('Cita reservada.')
      await cargar()
    } catch (fallo) {
      setErrorReserva(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function guardarCatalogo(evento) {
    evento.preventDefault()
    setErrorServicio('')
    setGuardando(true)
    try {
      await guardarServicio(servicio)
      setServicio(null)
      setAviso(servicio.id ? 'Servicio actualizado.' : 'Servicio creado.')
      await cargarServicios()
    } catch (fallo) {
      setErrorServicio(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function abrirReserva() {
    setErrorReserva('')
    setBusqueda('')
    setClientes([])
    setReserva({
      cliente_id: null,
      cliente_nombre: '',
      nombre: '',
      telefono: '',
      correo: '',
      colaborador_id: profesionalId || profesionales[0]?.id || '',
      inicio: `${fecha}T09:00`,
      servicios: [],
      notas: '',
    })
  }

  function acciones(cita) {
    if (cita.estado === 'pendiente') {
      return (
        <>
          <Button size="sm" className="me-2" disabled={guardando} onClick={() => mover(cita.id, 'en_proceso')}>En proceso</Button>
          {puedeReservar && <Button size="sm" variant="outline-primary" disabled={guardando} onClick={() => mover(cita.id, 'cancelada')}>Cancelar</Button>}
        </>
      )
    }
    if (cita.estado === 'en_proceso') {
      return (
        <>
          <Button size="sm" className="me-2" disabled={guardando} onClick={() => mover(cita.id, 'completada')}>Completar</Button>
          {puedeReservar && <Button size="sm" variant="outline-primary" disabled={guardando} onClick={() => mover(cita.id, 'cancelada')}>Cancelar</Button>}
        </>
      )
    }
    return null
  }

  function filaCita(cita) {
    return (
      <tr key={cita.id}>
        <td>{hora(cita.inicio)}–{hora(cita.fin)}</td>
        <td>{cita.cliente.nombre}</td>
        {puedeReservar && <td>{cita.colaborador.nombre}</td>}
        <td>{cita.servicios.map((item) => item.nombre).join(', ')}</td>
        <td><Badge className={cita.estado === 'cancelada' ? 'marca-baja' : 'marca-activo'}>{estados[cita.estado]}</Badge></td>
        <td className="text-end text-nowrap">{acciones(cita)}</td>
      </tr>
    )
  }

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Agenda</h1>
        <div className="d-flex flex-wrap gap-2">
          {esAdmin && <Button variant="outline-primary" onClick={() => { setErrorServicio(''); setServicio({ ...servicioVacio }) }}>Servicios</Button>}
          {puedeReservar && <Button onClick={abrirReserva}>Nueva cita</Button>}
        </div>
      </div>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      <div className="d-flex flex-wrap gap-2 align-items-end mb-3">
        <Button variant={vista === 'dia' ? 'primary' : 'outline-primary'} onClick={() => setVista('dia')}>Día</Button>
        <Button variant={vista === 'semana' ? 'primary' : 'outline-primary'} onClick={() => setVista('semana')}>Semana</Button>
        <Form.Control type="date" value={fecha} onChange={(evento) => setFecha(evento.target.value)} style={{ maxWidth: '12rem' }} />
        <Button variant="outline-primary" onClick={() => setFecha(sumarDias(fecha, vista === 'semana' ? -7 : -1))}>Anterior</Button>
        <Button variant="outline-primary" onClick={() => setFecha(hoy)}>Hoy</Button>
        <Button variant="outline-primary" onClick={() => setFecha(sumarDias(fecha, vista === 'semana' ? 7 : 1))}>Siguiente</Button>
        {puedeReservar && (
          <Form.Select value={profesionalId} onChange={(evento) => setProfesionalId(evento.target.value)} style={{ maxWidth: '16rem' }}>
            <option value="">Todos los profesionales</option>
            {profesionales.map((profesional) => (
              <option key={profesional.id} value={profesional.id}>{profesional.nombre}</option>
            ))}
          </Form.Select>
        )}
      </div>
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando agenda</span>
        </Spinner>
      ) : vista === 'dia' ? (
        citas.length === 0 ? (
          <p className="text-secondary">No hay citas en este día.</p>
        ) : (
          <Table responsive hover>
            <thead>
              <tr>
                <th>Hora</th>
                <th>Cliente</th>
                {puedeReservar && <th>Profesional</th>}
                <th>Servicios</th>
                <th>Estado</th>
                <th />
              </tr>
            </thead>
            <tbody>{citas.map(filaCita)}</tbody>
          </Table>
        )
      ) : (
        <Row className="g-2 flex-nowrap overflow-auto pb-2">
          {dias.map((dia) => {
            const delDia = citas.filter((cita) => String(cita.inicio).slice(0, 10) === dia)
            return (
              <Col key={dia} xs={10} md={4} lg={3}>
                <h2 className="h5">{dia}</h2>
                {delDia.length === 0 ? <p className="text-secondary">Sin citas</p> : delDia.map((cita) => (
                  <div key={cita.id} className="border rounded p-2 mb-2">
                    <div>{hora(cita.inicio)} {cita.cliente.nombre}</div>
                    <div className="text-secondary">{cita.servicios.map((item) => item.nombre).join(', ')}</div>
                    <div className="my-2"><Badge className={cita.estado === 'cancelada' ? 'marca-baja' : 'marca-activo'}>{estados[cita.estado]}</Badge></div>
                    {acciones(cita)}
                  </div>
                ))}
              </Col>
            )
          })}
        </Row>
      )}

      <Modal show={reserva !== null} onHide={() => setReserva(null)} centered>
        <Form onSubmit={reservar}>
          <Modal.Header closeButton>
            <Modal.Title>Nueva cita</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorReserva && <Alert variant="danger">{errorReserva}</Alert>}
            <Form.Label>Cliente</Form.Label>
            {reserva?.cliente_id ? (
              <div className="d-flex justify-content-between align-items-center mb-3">
                <span>{reserva.cliente_nombre}</span>
                <Button size="sm" variant="outline-primary" type="button" onClick={() => setReserva((actual) => ({ ...actual, cliente_id: null, cliente_nombre: '' }))}>Cambiar</Button>
              </div>
            ) : (
              <>
                <div className="d-flex gap-2 mb-2">
                  <Form.Control value={busqueda} placeholder="Buscar por nombre o teléfono" onChange={(evento) => setBusqueda(evento.target.value)} />
                  <Button type="button" variant="outline-primary" onClick={buscar}>Buscar</Button>
                </div>
                {clientes.length > 0 && (
                  <div className="mb-3">
                    {clientes.map((cliente) => (
                      <Button
                        key={cliente.id}
                        type="button"
                        size="sm"
                        variant="outline-primary"
                        className="me-2 mb-2"
                        onClick={() => setReserva((actual) => ({ ...actual, cliente_id: cliente.id, cliente_nombre: cliente.nombre }))}
                      >
                        {cliente.nombre}{cliente.telefono ? ` · ${cliente.telefono}` : ''}
                      </Button>
                    ))}
                  </div>
                )}
                <Form.Group className="mb-3" controlId="clienteNombre">
                  <Form.Label>O crear cliente</Form.Label>
                  <Form.Control value={reserva?.nombre ?? ''} required={!reserva?.cliente_id} maxLength={120} placeholder="Nombre" onChange={(evento) => setReserva((actual) => ({ ...actual, nombre: evento.target.value }))} />
                </Form.Group>
                <Row className="mb-3">
                  <Col>
                    <Form.Control value={reserva?.telefono ?? ''} maxLength={20} placeholder="Teléfono" onChange={(evento) => setReserva((actual) => ({ ...actual, telefono: evento.target.value }))} />
                  </Col>
                  <Col>
                    <Form.Control value={reserva?.correo ?? ''} maxLength={160} placeholder="Correo" onChange={(evento) => setReserva((actual) => ({ ...actual, correo: evento.target.value }))} />
                  </Col>
                </Row>
              </>
            )}
            <Form.Group className="mb-3" controlId="profesional">
              <Form.Label>Profesional</Form.Label>
              <Form.Select required value={reserva?.colaborador_id ?? ''} onChange={(evento) => setReserva((actual) => ({ ...actual, colaborador_id: evento.target.value }))}>
                <option value="">Elegir</option>
                {profesionales.map((profesional) => (
                  <option key={profesional.id} value={profesional.id}>{profesional.nombre}</option>
                ))}
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-3" controlId="inicio">
              <Form.Label>Inicio</Form.Label>
              <Form.Control type="datetime-local" required value={reserva?.inicio ?? ''} onChange={(evento) => setReserva((actual) => ({ ...actual, inicio: evento.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label>Servicios</Form.Label>
              {servicios.filter((item) => item.activo).length === 0 ? (
                <p className="text-secondary mb-0">Primero crea un servicio activo.</p>
              ) : servicios.filter((item) => item.activo).map((item) => (
                <Form.Check
                  key={item.id}
                  id={`servicio-${item.id}`}
                  label={`${item.nombre} · ${item.duracion_minutos} min · ${item.precio}`}
                  checked={reserva?.servicios.includes(item.id) ?? false}
                  onChange={(evento) => setReserva((actual) => ({
                    ...actual,
                    servicios: evento.target.checked
                      ? [...actual.servicios, item.id]
                      : actual.servicios.filter((id) => id !== item.id),
                  }))}
                />
              ))}
            </Form.Group>
            <Form.Group controlId="notas">
              <Form.Label>Notas</Form.Label>
              <Form.Control as="textarea" rows={2} maxLength={500} value={reserva?.notas ?? ''} onChange={(evento) => setReserva((actual) => ({ ...actual, notas: evento.target.value }))} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setReserva(null)}>Cancelar</Button>
            <Button type="submit" disabled={guardando || (reserva?.servicios.length ?? 0) === 0}>{guardando ? 'Guardando…' : 'Reservar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      <Modal show={servicio !== null} onHide={() => setServicio(null)} centered>
        <Form onSubmit={guardarCatalogo}>
          <Modal.Header closeButton>
            <Modal.Title>{servicio?.id ? 'Editar servicio' : 'Servicios'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorServicio && <Alert variant="danger">{errorServicio}</Alert>}
            {!servicio?.id && servicios.length > 0 && (
              <Table responsive size="sm" className="mb-3">
                <tbody>
                  {servicios.map((item) => (
                    <tr key={item.id}>
                      <td>{item.nombre}</td>
                      <td>{item.duracion_minutos} min</td>
                      <td>{item.precio}</td>
                      <td>{item.activo ? '' : 'De baja'}</td>
                      <td className="text-end">
                        <Button size="sm" variant="outline-primary" type="button" onClick={() => setServicio({ ...item, duracion_minutos: String(item.duracion_minutos) })}>Editar</Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            )}
            <Form.Group className="mb-3" controlId="nombreServicio">
              <Form.Label>Nombre</Form.Label>
              <Form.Control required maxLength={120} value={servicio?.nombre ?? ''} onChange={(evento) => setServicio((actual) => ({ ...actual, nombre: evento.target.value }))} />
            </Form.Group>
            <Row>
              <Col>
                <Form.Group className="mb-3" controlId="duracion">
                  <Form.Label>Minutos</Form.Label>
                  <Form.Control type="number" min="1" max="720" required value={servicio?.duracion_minutos ?? ''} onChange={(evento) => setServicio((actual) => ({ ...actual, duracion_minutos: evento.target.value }))} />
                </Form.Group>
              </Col>
              <Col>
                <Form.Group className="mb-3" controlId="precioServicio">
                  <Form.Label>Precio</Form.Label>
                  <Form.Control type="number" min="0" step="0.01" required value={servicio?.precio ?? ''} onChange={(evento) => setServicio((actual) => ({ ...actual, precio: evento.target.value }))} />
                </Form.Group>
              </Col>
            </Row>
            {servicio?.id && (
              <Form.Check id="servicioActivo" label="Servicio activo" checked={Boolean(servicio.activo)} onChange={(evento) => setServicio((actual) => ({ ...actual, activo: evento.target.checked }))} />
            )}
          </Modal.Body>
          <Modal.Footer>
            {servicio?.id && <Button variant="outline-primary" type="button" onClick={() => setServicio({ ...servicioVacio })}>Nuevo</Button>}
            <Button variant="secondary" type="button" onClick={() => setServicio(null)}>Cerrar</Button>
            <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Guardar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </>
  )
}
