import { useEffect, useState } from 'react'
import { Alert, Badge, Col, Form, Row, Spinner, Table } from 'react-bootstrap'
import BotonAccion, { GrupoAcciones } from '../../components/BotonAccion'
import { crearBloqueo, eliminarBloqueo, guardarHorarioTrabajo, verHorarios } from '../../cliente/horarios'

const DIAS = [
  { id: 1, nombre: 'Lunes' },
  { id: 2, nombre: 'Martes' },
  { id: 3, nombre: 'Miércoles' },
  { id: 4, nombre: 'Jueves' },
  { id: 5, nombre: 'Viernes' },
  { id: 6, nombre: 'Sábado' },
  { id: 7, nombre: 'Domingo' },
]

const bloqueoVacio = {
  tipo: 'recurrente',
  titulo: '',
  dia_semana: '1',
  fecha: '',
  hora_inicio: '12:00',
  hora_fin: '14:00',
  todo_el_dia: false,
  colaborador_id: '',
}

function tramosPorDia(horarios) {
  const mapa = {}
  DIAS.forEach((dia) => {
    mapa[dia.id] = []
  })
  ;(horarios ?? []).forEach((tramo) => {
    if (!mapa[tramo.dia_semana]) {
      mapa[tramo.dia_semana] = []
    }
    mapa[tramo.dia_semana].push({
      hora_inicio: tramo.hora_inicio,
      hora_fin: tramo.hora_fin,
    })
  })
  return mapa
}

function etiquetaBloqueo(bloqueo) {
  if (bloqueo.fecha) {
    if (bloqueo.todo_el_dia) {
      return `${bloqueo.fecha} · todo el día`
    }
    return `${bloqueo.fecha} · ${bloqueo.hora_inicio}–${bloqueo.hora_fin}`
  }
  const dia = DIAS.find((item) => item.id === bloqueo.dia_semana)
  return `Cada ${dia?.nombre ?? 'día'} · ${bloqueo.hora_inicio}–${bloqueo.hora_fin}`
}

export default function PaginaHorarios() {
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [profesionales, setProfesionales] = useState([])
  const [dias, setDias] = useState(tramosPorDia([]))
  const [bloqueos, setBloqueos] = useState([])
  const [formBloqueo, setFormBloqueo] = useState(bloqueoVacio)

  function aplicar(datos) {
    setDias(tramosPorDia(datos.horarios ?? []))
    setBloqueos(datos.bloqueos ?? [])
    setProfesionales(datos.profesionales ?? [])
  }

  async function cargar() {
    setError('')
    try {
      aplicar(await verHorarios())
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  function agregarTramo(diaId) {
    setDias((actual) => ({
      ...actual,
      [diaId]: [...(actual[diaId] ?? []), { hora_inicio: '08:00', hora_fin: '20:00' }],
    }))
  }

  function quitarTramo(diaId, indice) {
    setDias((actual) => ({
      ...actual,
      [diaId]: (actual[diaId] ?? []).filter((_, i) => i !== indice),
    }))
  }

  function cambiarTramo(diaId, indice, campo, valor) {
    setDias((actual) => ({
      ...actual,
      [diaId]: (actual[diaId] ?? []).map((tramo, i) => (i === indice ? { ...tramo, [campo]: valor } : tramo)),
    }))
  }

  async function guardarSemana(evento) {
    evento.preventDefault()
    setError('')
    setAviso('')
    const tramos = []
    DIAS.forEach((dia) => {
      ;(dias[dia.id] ?? []).forEach((tramo) => {
        tramos.push({
          dia_semana: dia.id,
          hora_inicio: tramo.hora_inicio,
          hora_fin: tramo.hora_fin,
        })
      })
    })
    setGuardando(true)
    try {
      aplicar(await guardarHorarioTrabajo(tramos))
      setAviso('Horario semanal guardado. Los días sin tramos quedan cerrados.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function guardarBloqueo(evento) {
    evento.preventDefault()
    setError('')
    setAviso('')
    const cuerpo = {
      tipo: formBloqueo.tipo,
      titulo: formBloqueo.titulo,
      colaborador_id: formBloqueo.colaborador_id || null,
      todo_el_dia: formBloqueo.tipo === 'fecha' && formBloqueo.todo_el_dia,
      hora_inicio: formBloqueo.todo_el_dia && formBloqueo.tipo === 'fecha' ? undefined : formBloqueo.hora_inicio,
      hora_fin: formBloqueo.todo_el_dia && formBloqueo.tipo === 'fecha' ? undefined : formBloqueo.hora_fin,
    }
    if (formBloqueo.tipo === 'recurrente') {
      cuerpo.dia_semana = Number(formBloqueo.dia_semana)
    } else {
      cuerpo.fecha = formBloqueo.fecha
    }
    setGuardando(true)
    try {
      aplicar(await crearBloqueo(cuerpo))
      setFormBloqueo(bloqueoVacio)
      setAviso('Bloqueo creado. Ya no aparece como disponible al cliente.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function borrarBloqueo(id) {
    setError('')
    setAviso('')
    setGuardando(true)
    try {
      aplicar(await eliminarBloqueo(id))
      setAviso('Bloqueo eliminado.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  if (cargando) {
    return (
      <Spinner animation="border" role="status">
        <span className="visually-hidden">Cargando horarios</span>
      </Spinner>
    )
  }

  return (
    <>
      <h1 className="h3 mb-3">Horarios</h1>
      <p className="text-secondary mb-4">
        Define cuándo abre la sede y bloquea almuerzos o descansos. Esos huecos no salen en la reserva del cliente.
      </p>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && (
        <Alert variant="light" className="border border-dark text-black" dismissible onClose={() => setAviso('')}>
          {aviso}
        </Alert>
      )}

      <section className="mb-5">
        <h2 className="h4 mb-3">Horario semanal</h2>
        <p className="text-secondary mb-3">
          Puedes partir un día en varios tramos (por ejemplo mañana y tarde). Un día sin tramos queda cerrado.
        </p>
        <Form onSubmit={guardarSemana}>
          {DIAS.map((dia) => (
            <div key={dia.id} className="horario-dia mb-3">
              <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <strong>{dia.nombre}</strong>
                <BotonAccion icono="plus" titulo={`Agregar tramo ${dia.nombre}`} onClick={() => agregarTramo(dia.id)} />
              </div>
              {(dias[dia.id] ?? []).length === 0 ? (
                <p className="text-secondary mb-0">Cerrado</p>
              ) : (
                (dias[dia.id] ?? []).map((tramo, indice) => (
                  <Row key={`${dia.id}-${indice}`} className="g-2 align-items-end mb-2">
                    <Col xs={5} md={3}>
                      <Form.Label className="visually-hidden">Inicio {dia.nombre}</Form.Label>
                      <Form.Control
                        type="time"
                        step="1800"
                        value={tramo.hora_inicio}
                        onChange={(evento) => cambiarTramo(dia.id, indice, 'hora_inicio', evento.target.value)}
                        required
                      />
                    </Col>
                    <Col xs={5} md={3}>
                      <Form.Label className="visually-hidden">Fin {dia.nombre}</Form.Label>
                      <Form.Control
                        type="time"
                        step="1800"
                        value={tramo.hora_fin}
                        onChange={(evento) => cambiarTramo(dia.id, indice, 'hora_fin', evento.target.value)}
                        required
                      />
                    </Col>
                    <Col xs="auto">
                      <BotonAccion icono="trash" titulo="Quitar tramo" onClick={() => quitarTramo(dia.id, indice)} />
                    </Col>
                  </Row>
                ))
              )}
            </div>
          ))}
          <BotonAccion
            icono="save"
            titulo={guardando ? 'Guardando…' : 'Guardar horario semanal'}
            type="submit"
            variant="primary"
            disabled={guardando}
          />
        </Form>
      </section>

      <section>
        <h2 className="h4 mb-3">Bloqueos</h2>
        <p className="text-secondary mb-3">
          Usa un bloqueo recurrente para el almuerzo cada semana, o una fecha concreta para un descanso o cierre puntual.
        </p>
        <Form onSubmit={guardarBloqueo} className="mb-4" style={{ maxWidth: '36rem' }}>
          <Form.Group className="mb-3" controlId="bloqueo-titulo">
            <Form.Label>Motivo</Form.Label>
            <Form.Control
              maxLength={120}
              required
              placeholder="Almuerzo, descanso, capacitación…"
              value={formBloqueo.titulo}
              onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, titulo: evento.target.value }))}
            />
          </Form.Group>
          <Form.Group className="mb-3" controlId="bloqueo-tipo">
            <Form.Label>Tipo</Form.Label>
            <Form.Select
              value={formBloqueo.tipo}
              onChange={(evento) => setFormBloqueo((actual) => ({
                ...actual,
                tipo: evento.target.value,
                todo_el_dia: evento.target.value === 'fecha' ? actual.todo_el_dia : false,
              }))}
            >
              <option value="recurrente">Cada semana (ej. almuerzo)</option>
              <option value="fecha">Fecha concreta</option>
            </Form.Select>
          </Form.Group>
          {formBloqueo.tipo === 'recurrente' ? (
            <Form.Group className="mb-3" controlId="bloqueo-dia">
              <Form.Label>Día</Form.Label>
              <Form.Select
                value={formBloqueo.dia_semana}
                onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, dia_semana: evento.target.value }))}
              >
                {DIAS.map((dia) => (
                  <option key={dia.id} value={dia.id}>{dia.nombre}</option>
                ))}
              </Form.Select>
            </Form.Group>
          ) : (
            <>
              <Form.Group className="mb-3" controlId="bloqueo-fecha">
                <Form.Label>Fecha</Form.Label>
                <Form.Control
                  type="date"
                  required
                  value={formBloqueo.fecha}
                  onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, fecha: evento.target.value }))}
                />
              </Form.Group>
              <Form.Check
                className="mb-3"
                type="checkbox"
                id="bloqueo-todo"
                label="Todo el día"
                checked={formBloqueo.todo_el_dia}
                onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, todo_el_dia: evento.target.checked }))}
              />
            </>
          )}
          {!(formBloqueo.tipo === 'fecha' && formBloqueo.todo_el_dia) && (
            <Row className="g-2 mb-3">
              <Col>
                <Form.Label htmlFor="bloqueo-inicio">Desde</Form.Label>
                <Form.Control
                  id="bloqueo-inicio"
                  type="time"
                  step="1800"
                  required
                  value={formBloqueo.hora_inicio}
                  onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, hora_inicio: evento.target.value }))}
                />
              </Col>
              <Col>
                <Form.Label htmlFor="bloqueo-fin">Hasta</Form.Label>
                <Form.Control
                  id="bloqueo-fin"
                  type="time"
                  step="1800"
                  required
                  value={formBloqueo.hora_fin}
                  onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, hora_fin: evento.target.value }))}
                />
              </Col>
            </Row>
          )}
          <Form.Group className="mb-3" controlId="bloqueo-profesional">
            <Form.Label>Aplica a</Form.Label>
            <Form.Select
              value={formBloqueo.colaborador_id}
              onChange={(evento) => setFormBloqueo((actual) => ({ ...actual, colaborador_id: evento.target.value }))}
            >
              <option value="">Toda la sede</option>
              {profesionales.map((profesional) => (
                <option key={profesional.id} value={profesional.id}>{profesional.nombre}</option>
              ))}
            </Form.Select>
          </Form.Group>
          <BotonAccion
            icono="plus"
            titulo={guardando ? 'Guardando…' : 'Crear bloqueo'}
            type="submit"
            variant="primary"
            disabled={guardando}
          />
        </Form>

        {bloqueos.length === 0 ? (
          <p className="text-secondary mb-0">No hay bloqueos. El cliente ve todos los huecos del horario semanal.</p>
        ) : (
          <Table responsive hover size="sm">
            <thead>
              <tr>
                <th>Motivo</th>
                <th>Cuándo</th>
                <th>Alcance</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {bloqueos.map((bloqueo) => (
                <tr key={bloqueo.id}>
                  <td>{bloqueo.titulo}</td>
                  <td>{etiquetaBloqueo(bloqueo)}</td>
                  <td>
                    {bloqueo.colaborador
                      ? bloqueo.colaborador
                      : <Badge className="marca-activo">Sede</Badge>}
                  </td>
                  <td className="text-end">
                    <GrupoAcciones>
                      <BotonAccion
                        icono="trash"
                        titulo="Eliminar bloqueo"
                        disabled={guardando}
                        onClick={() => borrarBloqueo(bloqueo.id)}
                      />
                    </GrupoAcciones>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        )}
      </section>
    </>
  )
}
