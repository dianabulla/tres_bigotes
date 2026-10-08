import { useEffect, useState } from 'react'
import { Alert, Button, Form, Spinner } from 'react-bootstrap'
import { buscarClinica, guardarFicha, guardarNota, verClinica } from '../../cliente/clinica'
import { useSesion } from '../../components/Sesion'
import { tienePermiso } from '../registro'

const fichaVacia = {
  preferencias: '',
  cortes_habituales: '',
  formulas: '',
}

export default function PaginaClinica() {
  const { usuario } = useSesion()
  const veTodas = tienePermiso(usuario, 'clinica') || tienePermiso(usuario, 'clinica.consulta')
  const anota = tienePermiso(usuario, 'clinica.propia')
  const esProfesional = anota && !veTodas
  const puedeEditar = tienePermiso(usuario, 'clinica') || anota
  const [busqueda, setBusqueda] = useState('')
  const [clientes, setClientes] = useState([])
  const [detalle, setDetalle] = useState(null)
  const [ficha, setFicha] = useState(fichaVacia)
  const [citaId, setCitaId] = useState('')
  const [observacion, setObservacion] = useState('')
  const [cargando, setCargando] = useState(esProfesional)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [guardando, setGuardando] = useState(false)

  function aplicar(datos) {
    setDetalle(datos)
    setFicha({
      preferencias: datos.ficha?.preferencias ?? '',
      cortes_habituales: datos.ficha?.cortes_habituales ?? '',
      formulas: datos.ficha?.formulas ?? '',
    })
    setCitaId(datos.visitas[0] ? String(datos.visitas[0].id) : '')
    setObservacion('')
  }

  async function cargarProfesional() {
    if (!esProfesional) {
      setCargando(false)
      return
    }
    try {
      const datos = await buscarClinica('')
      setClientes(datos.clientes)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargarProfesional()
  }, [])

  async function buscar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      const datos = await buscarClinica(busqueda.trim())
      setClientes(datos.clientes)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function abrir(id) {
    setError('')
    setGuardando(true)
    try {
      aplicar(await verClinica(id))
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function guardar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      aplicar(await guardarFicha(detalle.cliente.id, ficha))
      setAviso('Ficha guardada.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function anotar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      aplicar(await guardarNota(detalle.cliente.id, { cita_id: citaId, observacion }))
      setAviso('Nota de la visita guardada.')
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  return (
    <>
      <h1 className="h3 mb-3">Ficha clínica</h1>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>{aviso}</Alert>}
      <Form onSubmit={buscar} className="d-flex flex-wrap gap-2 mb-3">
        <Form.Control
          value={busqueda}
          placeholder="Nombre o teléfono"
          maxLength={80}
          onChange={(evento) => setBusqueda(evento.target.value)}
          style={{ maxWidth: '20rem' }}
        />
        <Button type="submit" disabled={guardando}>Buscar</Button>
      </Form>
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando fichas</span>
        </Spinner>
      ) : (
        <div className="d-flex flex-wrap gap-2 mb-4">
          {clientes.map((cliente) => (
            <Button key={cliente.id} type="button" variant="outline-primary" onClick={() => abrir(cliente.id)}>
              {cliente.nombre}{cliente.telefono ? ` · ${cliente.telefono}` : ''}
            </Button>
          ))}
          {!esProfesional && clientes.length === 0 && <p className="text-secondary mb-0">Busca un cliente para abrir su ficha.</p>}
          {esProfesional && clientes.length === 0 && <p className="text-secondary mb-0">Todavía no tienes clientes con cita.</p>}
        </div>
      )}
      {detalle && (
        <>
          <h2 className="h4">{detalle.cliente.nombre}</h2>
          <Form onSubmit={guardar} className="mb-4">
            <Form.Group className="mb-3" controlId="preferencias">
              <Form.Label>Preferencias</Form.Label>
              <Form.Control as="textarea" rows={2} maxLength={2000} value={ficha.preferencias} disabled={!puedeEditar} onChange={(evento) => setFicha((actual) => ({ ...actual, preferencias: evento.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-3" controlId="cortes">
              <Form.Label>Cortes habituales</Form.Label>
              <Form.Control as="textarea" rows={2} maxLength={2000} value={ficha.cortes_habituales} disabled={!puedeEditar} onChange={(evento) => setFicha((actual) => ({ ...actual, cortes_habituales: evento.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-3" controlId="formulas">
              <Form.Label>Fórmulas</Form.Label>
              <Form.Control as="textarea" rows={2} maxLength={2000} value={ficha.formulas} disabled={!puedeEditar} onChange={(evento) => setFicha((actual) => ({ ...actual, formulas: evento.target.value }))} />
            </Form.Group>
            {puedeEditar && <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Guardar ficha'}</Button>}
          </Form>
          {anota && (
            <Form onSubmit={anotar} className="mb-4">
              <h2 className="h4">Nota de la visita</h2>
              {detalle.visitas.length === 0 ? (
                <p className="text-secondary">No hay visitas tuyas pendientes de nota.</p>
              ) : (
                <>
                  <Form.Group className="mb-3" controlId="visita">
                    <Form.Label>Visita</Form.Label>
                    <Form.Select required value={citaId} onChange={(evento) => setCitaId(evento.target.value)}>
                      {detalle.visitas.map((visita) => (
                        <option key={visita.id} value={visita.id}>{visita.inicio}</option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                  <Form.Group className="mb-3" controlId="observacion">
                    <Form.Label>Observación</Form.Label>
                    <Form.Control as="textarea" rows={3} required maxLength={2000} value={observacion} onChange={(evento) => setObservacion(evento.target.value)} />
                  </Form.Group>
                  <Button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Guardar nota'}</Button>
                </>
              )}
            </Form>
          )}
          <h2 className="h4">Visitas anotadas</h2>
          {detalle.notas.length === 0 ? (
            <p className="text-secondary">Todavía no hay notas.</p>
          ) : detalle.notas.map((nota) => (
            <article key={nota.id} className="border rounded p-3 mb-2">
              <p className="mb-1">{nota.creado_en} · {nota.profesional}{nota.inicio ? ` · cita ${nota.inicio}` : ''}</p>
              <p className="mb-0">{nota.observacion}</p>
            </article>
          ))}
        </>
      )}
    </>
  )
}
