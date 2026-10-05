import { useEffect, useState } from 'react'
import { Alert, Button, Card, Form, Spinner } from 'react-bootstrap'
import { Link } from 'react-router-dom'
import { buscarReserva, crearReserva, horariosReserva, verReserva } from '../../cliente/reserva'

function diaIso(fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${fecha.getFullYear()}-${mes}-${dia}`
}

export default function PaginaReserva() {
  const [catalogo, setCatalogo] = useState(null)
  const [error, setError] = useState('')
  const [telefono, setTelefono] = useState('')
  const [nombre, setNombre] = useState('')
  const [correo, setCorreo] = useState('')
  const [conocido, setConocido] = useState(false)
  const [datosListos, setDatosListos] = useState(false)
  const [servicios, setServicios] = useState([])
  const [profesionalId, setProfesionalId] = useState('')
  const [fecha, setFecha] = useState(diaIso(new Date()))
  const [horarios, setHorarios] = useState([])
  const [horario, setHorario] = useState('')
  const [cargandoHorarios, setCargandoHorarios] = useState(false)
  const [guardando, setGuardando] = useState(false)
  const [reserva, setReserva] = useState(null)

  useEffect(() => {
    verReserva()
      .then((datos) => {
        setCatalogo(datos)
        setProfesionalId(datos.profesionales[0] ? String(datos.profesionales[0].id) : '')
      })
      .catch((fallo) => setError(fallo.message))
  }, [])

  useEffect(() => {
    if (!datosListos || servicios.length === 0 || profesionalId === '' || fecha === '') {
      setHorarios([])
      setHorario('')
      return
    }
    let vigente = true
    setCargandoHorarios(true)
    horariosReserva(fecha, profesionalId, servicios)
      .then((datos) => {
        if (vigente) {
          setHorarios(datos.horarios)
          setHorario('')
        }
      })
      .catch((fallo) => {
        if (vigente) {
          setHorarios([])
          setError(fallo.message)
        }
      })
      .finally(() => {
        if (vigente) {
          setCargandoHorarios(false)
        }
      })
    return () => {
      vigente = false
    }
  }, [datosListos, servicios, profesionalId, fecha])

  async function continuar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      const datos = await buscarReserva(telefono.trim())
      if (datos.cliente) {
        setNombre(datos.cliente.nombre)
        setCorreo(datos.cliente.correo ?? '')
        setConocido(true)
      } else {
        setNombre('')
        setCorreo('')
        setConocido(false)
      }
      setDatosListos(true)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function cambiarServicio(id, marcado) {
    setServicios((actual) => (marcado ? [...actual, id] : actual.filter((item) => item !== id)))
  }

  async function confirmar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      const datos = await crearReserva({
        nombre,
        telefono: telefono.trim(),
        correo,
        colaborador_id: profesionalId,
        inicio: `${fecha} ${horario}:00`,
        servicios,
      })
      setReserva(datos.reserva)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  return (
    <div className="d-flex align-items-center justify-content-center min-vh-100 px-3 py-4">
      <Card className="marca-tarjeta w-100" style={{ maxWidth: 640 }}>
        <Card.Body>
          <img src="/media/logo.jpeg" alt="" className="marca-logo-grande" />
          <h1 className="h3 text-center mb-3">Reserva tu cita</h1>
          {error && <Alert variant="danger">{error}</Alert>}
          {catalogo === null ? (
            <div className="d-flex justify-content-center py-4">
              <Spinner animation="border" role="status">
                <span className="visually-hidden">Cargando reserva</span>
              </Spinner>
            </div>
          ) : reserva ? (
            <>
              <Alert variant="light" className="border border-dark text-black">
                Listo, {reserva.cliente}. Tu cita con {reserva.profesional} quedó para {reserva.inicio.slice(0, 16)} ({reserva.servicios.join(', ')}).
              </Alert>
              <Button className="w-100" onClick={() => { setReserva(null); setDatosListos(false); setServicios([]); setHorario('') }}>Reservar otra</Button>
            </>
          ) : (
            <Form onSubmit={datosListos ? confirmar : continuar}>
              <Form.Group className="mb-3" controlId="telefono">
                <Form.Label>Teléfono</Form.Label>
                <Form.Control
                  value={telefono}
                  required
                  minLength={7}
                  maxLength={20}
                  autoComplete="tel"
                  onChange={(evento) => { setTelefono(evento.target.value); setDatosListos(false); setConocido(false) }}
                />
              </Form.Group>
              {!datosListos ? (
                <Button type="submit" className="w-100" disabled={guardando}>{guardando ? 'Buscando…' : 'Continuar'}</Button>
              ) : (
                <>
                  {conocido && <p className="mb-3">Ya tenemos tus datos. Revísalos y elige el horario.</p>}
                  <Form.Group className="mb-3" controlId="nombre">
                    <Form.Label>Nombre</Form.Label>
                    <Form.Control value={nombre} required maxLength={120} autoComplete="name" onChange={(evento) => setNombre(evento.target.value)} />
                  </Form.Group>
                  <Form.Group className="mb-3" controlId="correo">
                    <Form.Label>Correo</Form.Label>
                    <Form.Control type="email" value={correo} maxLength={160} autoComplete="email" onChange={(evento) => setCorreo(evento.target.value)} />
                  </Form.Group>
                  <Form.Group className="mb-3">
                    <Form.Label>Servicios</Form.Label>
                    {catalogo.servicios.length === 0 ? (
                      <p className="text-secondary mb-0">Todavía no hay servicios para reservar.</p>
                    ) : catalogo.servicios.map((servicio) => (
                      <Form.Check
                        key={servicio.id}
                        id={`reserva-servicio-${servicio.id}`}
                        label={`${servicio.nombre} · ${servicio.duracion_minutos} min · ${servicio.precio}`}
                        checked={servicios.includes(servicio.id)}
                        onChange={(evento) => cambiarServicio(servicio.id, evento.target.checked)}
                      />
                    ))}
                  </Form.Group>
                  <Form.Group className="mb-3" controlId="profesional">
                    <Form.Label>Profesional</Form.Label>
                    <Form.Select required value={profesionalId} onChange={(evento) => setProfesionalId(evento.target.value)}>
                      {catalogo.profesionales.map((profesional) => (
                        <option key={profesional.id} value={profesional.id}>{profesional.nombre}</option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                  <Form.Group className="mb-3" controlId="fecha">
                    <Form.Label>Día</Form.Label>
                    <Form.Control type="date" required value={fecha} min={diaIso(new Date())} onChange={(evento) => setFecha(evento.target.value)} />
                  </Form.Group>
                  <Form.Group className="mb-3">
                    <Form.Label>Horario</Form.Label>
                    {cargandoHorarios ? (
                      <Spinner animation="border" size="sm" role="status">
                        <span className="visually-hidden">Cargando horarios</span>
                      </Spinner>
                    ) : horarios.length === 0 ? (
                      <p className="text-secondary mb-0">No hay horarios libres para esa selección.</p>
                    ) : (
                      <div className="d-flex flex-wrap gap-2">
                        {horarios.map((hora) => (
                          <Button key={hora} type="button" variant={horario === hora ? 'primary' : 'outline-primary'} onClick={() => setHorario(hora)}>
                            {hora}
                          </Button>
                        ))}
                      </div>
                    )}
                  </Form.Group>
                  <Button type="submit" className="w-100" disabled={guardando || horario === '' || servicios.length === 0}>
                    {guardando ? 'Reservando…' : 'Confirmar reserva'}
                  </Button>
                </>
              )}
            </Form>
          )}
          <p className="text-center mt-3 mb-0">
            <Link to="/ingreso">Ingreso del equipo</Link>
          </p>
        </Card.Body>
      </Card>
    </div>
  )
}
