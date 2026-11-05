import { useEffect, useState } from 'react'
import { Alert, Button, Col, Form, Row, Spinner } from 'react-bootstrap'
import {
  cancelarCitaPublica,
  consultarCitas,
  horariosReserva,
  reagendarCitaPublica,
} from '../../cliente/reserva'
import BotonAccion, { GrupoAcciones } from '../../components/BotonAccion'

function diaIso(fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${fecha.getFullYear()}-${mes}-${dia}`
}

function formatearCita(inicio) {
  const fecha = new Date(String(inicio).replace(' ', 'T'))
  if (Number.isNaN(fecha.getTime())) {
    return String(inicio).slice(0, 16)
  }
  return fecha.toLocaleString('es-CO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export default function ConsultaCita({ barberos, onCerrar }) {
  const hoy = diaIso(new Date())
  const [telefono, setTelefono] = useState('')
  const [correo, setCorreo] = useState('')
  const [citas, setCitas] = useState(null)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [cargando, setCargando] = useState(false)
  const [accionId, setAccionId] = useState(null)
  const [reagendar, setReagendar] = useState(null)
  const [profesionalId, setProfesionalId] = useState('')
  const [fecha, setFecha] = useState('')
  const [horario, setHorario] = useState('')
  const [horasLibres, setHorasLibres] = useState([])
  const [cargandoHorarios, setCargandoHorarios] = useState(false)

  async function buscar(evento) {
    evento.preventDefault()
    setError('')
    setAviso('')
    setReagendar(null)
    setCargando(true)
    try {
      const datos = await consultarCitas(telefono.trim(), correo.trim())
      setCitas(datos.citas)
      if (datos.citas.length === 0) {
        setAviso('No hay citas pendientes con esos datos.')
      }
    } catch (fallo) {
      setCitas(null)
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    if (!reagendar || profesionalId === '' || fecha === '') {
      setHorasLibres([])
      setHorario('')
      return undefined
    }
    let vigente = true
    setCargandoHorarios(true)
    setHorario('')
    horariosReserva(fecha, profesionalId, reagendar.servicio_ids, reagendar.id)
      .then((datos) => {
        if (vigente) {
          setHorasLibres(datos.horarios ?? [])
        }
      })
      .catch((fallo) => {
        if (vigente) {
          setHorasLibres([])
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
  }, [reagendar, profesionalId, fecha])

  async function cancelar(cita) {
    if (!window.confirm(`¿Cancelar la cita del ${formatearCita(cita.inicio)}?`)) {
      return
    }
    setError('')
    setAviso('')
    setAccionId(cita.id)
    try {
      await cancelarCitaPublica(cita.id, telefono.trim(), correo.trim())
      setCitas((actual) => (actual ?? []).filter((item) => item.id !== cita.id))
      setAviso('Cita cancelada.')
      if (reagendar?.id === cita.id) {
        setReagendar(null)
      }
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setAccionId(null)
    }
  }

  function abrirReagendar(cita) {
    setError('')
    setAviso('')
    setReagendar(cita)
    setProfesionalId(String(cita.colaborador_id))
    setFecha('')
    setHorario('')
    setHorasLibres([])
  }

  async function confirmarReagendar(evento) {
    evento.preventDefault()
    if (!reagendar) {
      return
    }
    setError('')
    setAviso('')
    setAccionId(reagendar.id)
    try {
      const datos = await reagendarCitaPublica(reagendar.id, {
        telefono: telefono.trim(),
        correo: correo.trim(),
        colaborador_id: Number(profesionalId),
        inicio: `${fecha} ${horario}:00`,
      })
      setCitas((actual) => (actual ?? []).map((item) => (item.id === reagendar.id ? { ...item, ...datos.cita } : item)))
      setReagendar(null)
      setAviso(`Cita reagendada para ${formatearCita(datos.cita.inicio)}.`)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setAccionId(null)
    }
  }

  return (
    <div className="carta-consulta">
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 className="carta-agenda-titulo mb-0">Consultar cita</h3>
        <BotonAccion icono="chevronLeft" titulo="Volver a reservar" onClick={onCerrar} />
      </div>
      <p className="text-secondary mb-3">
        Escribe el teléfono y el correo con los que reservaste.
      </p>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="success">{aviso}</Alert>}
      <Form onSubmit={buscar} className="mb-4">
        <Row>
          <Col md={5}>
            <Form.Group className="mb-3" controlId="consultaTelefono">
              <Form.Label>Teléfono</Form.Label>
              <Form.Control
                value={telefono}
                required
                minLength={7}
                maxLength={20}
                autoComplete="tel"
                onChange={(evento) => setTelefono(evento.target.value)}
              />
            </Form.Group>
          </Col>
          <Col md={5}>
            <Form.Group className="mb-3" controlId="consultaCorreo">
              <Form.Label>Correo</Form.Label>
              <Form.Control
                type="email"
                value={correo}
                required
                maxLength={160}
                autoComplete="email"
                onChange={(evento) => setCorreo(evento.target.value)}
              />
            </Form.Group>
          </Col>
          <Col md={2} className="d-flex align-items-end">
            <BotonAccion
              icono="search"
              titulo={cargando ? 'Buscando…' : 'Buscar'}
              type="submit"
              variant="primary"
              className="w-100 mb-3"
              disabled={cargando}
            />
          </Col>
        </Row>
      </Form>

      {citas && citas.length > 0 && (
        <div className="carta-consulta-lista">
          {citas.map((cita) => (
            <article key={cita.id} className="carta-consulta-item">
              <p className="carta-eleccion mb-1">{formatearCita(cita.inicio)}</p>
              <p className="mb-1"><strong>Servicio:</strong> {cita.servicios.join(', ')}</p>
              <p className="mb-3"><strong>Barbero:</strong> {cita.profesional}</p>
              <GrupoAcciones>
                <BotonAccion
                  icono="calendar"
                  titulo="Reagendar"
                  disabled={accionId === cita.id}
                  onClick={() => abrirReagendar(cita)}
                />
                <BotonAccion
                  icono="x"
                  titulo={accionId === cita.id ? 'Cancelando…' : 'Cancelar cita'}
                  disabled={accionId === cita.id}
                  onClick={() => cancelar(cita)}
                />
              </GrupoAcciones>
            </article>
          ))}
        </div>
      )}

      {reagendar && (
        <Form onSubmit={confirmarReagendar} className="carta-paso mt-4">
          <h3 className="carta-agenda-titulo">Reagendar</h3>
          <p className="carta-eleccion mb-3">
            {reagendar.servicios.join(', ')} · cita actual {formatearCita(reagendar.inicio)}
          </p>
          <h4 className="inicio-categoria">Barbero</h4>
          <div className="carta-equipo mb-3">
            {barberos.map((item) => {
              const activo = String(item.id) === String(profesionalId)
              const inicial = item.nombre.trim().charAt(0).toUpperCase()
              return (
                <button
                  key={item.id}
                  type="button"
                  className={activo ? 'carta-pro activo' : 'carta-pro'}
                  onClick={() => {
                    setProfesionalId(String(item.id))
                    setFecha('')
                    setHorario('')
                  }}
                >
                  {item.foto ? (
                    <img className="carta-foto" src={`${import.meta.env.VITE_API_URL ?? ''}/${item.foto}`} alt="" />
                  ) : (
                    <span className="carta-foto carta-inicial" aria-hidden="true">{inicial}</span>
                  )}
                  <span className="carta-pro-nombre">{item.nombre}</span>
                  <span className="carta-elegir">{activo ? 'Elegido' : 'Elegir'}</span>
                </button>
              )
            })}
          </div>
          <Form.Group className="mb-3" controlId="reagendarFecha">
            <Form.Label>Día</Form.Label>
            <Form.Control
              type="date"
              required
              value={fecha}
              min={hoy}
              onChange={(evento) => {
                setFecha(evento.target.value)
                setHorario('')
              }}
            />
          </Form.Group>
          {fecha === '' ? (
            <p className="text-secondary mb-3">Elige un día para ver horas libres.</p>
          ) : cargandoHorarios ? (
            <Spinner animation="border" size="sm" className="mb-3" />
          ) : horasLibres.length === 0 ? (
            <p className="text-secondary mb-3">Sin horas ese día. Prueba otra fecha.</p>
          ) : (
            <div className="carta-horas mb-3">
              {horasLibres.map((hora) => (
                <Button
                  key={hora}
                  type="button"
                  size="sm"
                  variant={horario === hora ? 'primary' : 'outline-primary'}
                  onClick={() => setHorario(hora)}
                >
                  {hora}
                </Button>
              ))}
            </div>
          )}
          <GrupoAcciones>
            <BotonAccion icono="x" titulo="Cancelar reagendo" onClick={() => setReagendar(null)} />
            <BotonAccion
              icono="save"
              titulo={accionId === reagendar.id ? 'Guardando…' : 'Guardar nuevo horario'}
              type="submit"
              variant="primary"
              disabled={accionId === reagendar.id || horario === '' || profesionalId === ''}
            />
          </GrupoAcciones>
        </Form>
      )}
    </div>
  )
}
