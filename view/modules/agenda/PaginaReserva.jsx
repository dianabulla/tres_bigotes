import { lazy, Suspense, useEffect, useMemo, useRef, useState } from 'react'
import { Alert, Button, Col, Form, Row, Spinner } from 'react-bootstrap'
import { Link } from 'react-router-dom'
import { buscarReserva, crearReserva, enviarResenaPublica, horariosReserva, verReserva } from '../../cliente/reserva'
import BotonAccion, { GrupoAcciones } from '../../components/BotonAccion'
import ConsultaCita from './ConsultaCita'
import VideoFondo from './VideoFondo'
import './portada.css'

const BrilloCursor = lazy(() => import('./BrilloCursor'))
const Particulas = lazy(() => import('./Particulas'))

const CIFRAS = [
  ['5.0', 'Valoración ★'],
  ['+4.8K', 'Cortes de élite'],
  ['2020', 'Fundación MMXX'],
]

const MARQUESINA = [
  'Navaja clásica',
  'Fade de precisión',
  'Afeitado tradicional',
  'Ritual de toalla caliente',
  'Perfilado de barba',
  'Productos premium',
]

const REDES = [
  {
    nombre: 'Instagram',
    href: 'https://www.instagram.com/3bigotesbarberie',
    clase: 'red--instagram',
    icono: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 1.8H7A2.2 2.2 0 0 0 4.8 7v10A2.2 2.2 0 0 0 7 19.2h10a2.2 2.2 0 0 0 2.2-2.2V7A2.2 2.2 0 0 0 17 4.8zM12 8.1A3.9 3.9 0 1 1 8.1 12 3.9 3.9 0 0 1 12 8.1zm0 1.6a2.3 2.3 0 1 0 2.3 2.3A2.3 2.3 0 0 0 12 9.7zM17.35 6.4a1.05 1.05 0 1 1-1.05 1.05 1.05 1.05 0 0 1 1.05-1.05z" />
      </svg>
    ),
  },
  {
    nombre: 'Facebook',
    href: '',
    pendiente: true,
    clase: 'red--facebook red--pendiente',
    icono: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M14.2 21v-7.1h2.4l.4-2.8h-2.8V9.3c0-.8.2-1.4 1.4-1.4H17V5.4c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2h-2.4v2.8H11V21z" />
      </svg>
    ),
  },
  {
    nombre: 'WhatsApp',
    href: 'https://wa.me/573243149005?text=Hola%2C%20quiero%20agendar%20una%20cita%20en%203%20Bigotes',
    clase: 'red--whatsapp',
    icono: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12.1 3.2A8.7 8.7 0 0 0 4.7 16.3L3.6 20.4l4.2-1.1a8.7 8.7 0 0 0 4.3 1.1 8.7 8.7 0 0 0 0-17.4zm0 15.9a7.2 7.2 0 0 1-3.7-1l-.3-.2-2.5.7.7-2.4-.2-.3a7.2 7.2 0 1 1 6 3.2zm4-5.4c-.2-.1-1.3-.6-1.5-.7s-.3-.1-.5.1-.6.7-.7.9-.3.2-.5.1a5.9 5.9 0 0 1-1.7-1.1 6.5 6.5 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.5l.3-.4.1-.2a.5.5 0 0 0 0-.5c0-.1-.5-1.2-.7-1.6s-.3-.4-.5-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.8 4.8 0 0 0 1 2.5 11 11 0 0 0 4.2 3.7 4.7 4.7 0 0 0 2.4.7 2.6 2.6 0 0 0 1.7-.7 2.1 2.1 0 0 0 .5-1.5c0-.1 0-.3-.2-.4z" />
      </svg>
    ),
  },
]

function Redes() {
  return (
    <div className="redes">
      {REDES.map((red) => (
        red.pendiente ? (
          <span key={red.nombre} className={`red ${red.clase}`} aria-disabled="true">
            {red.icono}
            <span>{red.nombre}</span>
            <span className="red__nota">Pronto</span>
          </span>
        ) : (
          <a key={red.nombre} className={`red ${red.clase}`} href={red.href} target="_blank" rel="noopener noreferrer">
            {red.icono}
            <span>{red.nombre}</span>
          </a>
        )
      ))}
    </div>
  )
}

const ENLACES = [
  ['Inicio', '#inicio'],
  ['Barbería', '#barberia'],
  ['Servicios', '#servicios'],
  ['Galería', '#galeria'],
  ['Reseñas', '#resenas'],
  ['Contacto', '#contacto'],
]

function diaIso(fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${fecha.getFullYear()}-${mes}-${dia}`
}

function pesos(valor) {
  return Number(valor).toLocaleString('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 })
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

function fotoSrc(foto) {
  if (!foto) {
    return ''
  }
  const base = import.meta.env.VITE_API_URL ?? ''
  return `${base}/${foto}`
}

function estrellas(nota) {
  return '★★★★★'.slice(0, nota)
}

function ChecklistServicios({ grupos, seleccionados, onAlternar, idPrefijo = 'servicio' }) {
  if (grupos.length === 0) {
    return <p className="text-secondary mb-0">Todavía no hay servicios para reservar.</p>
  }
  return grupos.map(([categoria, lista]) => (
    <div key={categoria} className="carta-checklist-grupo">
      <h3 className="inicio-categoria">{categoria}</h3>
      <ul className="carta-checklist-servicios">
        {lista.map((servicio) => {
          const activo = seleccionados.includes(servicio.id)
          const id = `${idPrefijo}-${servicio.id}`
          return (
            <li key={servicio.id} className={activo ? 'carta-checklist-item activo' : 'carta-checklist-item'}>
              <Form.Check
                type="checkbox"
                id={id}
                checked={activo}
                onChange={() => onAlternar(servicio.id)}
                label={(
                  <span className="carta-checklist-cuerpo">
                    <span className="carta-checklist-fila">
                      <span className="carta-checklist-nombre">{servicio.nombre}</span>
                      <span className="carta-checklist-precio">{pesos(servicio.precio)}</span>
                    </span>
                    <span className="carta-checklist-meta">{servicio.duracion_minutos} min</span>
                    {servicio.descripcion ? (
                      <span className="carta-checklist-descripcion">{servicio.descripcion}</span>
                    ) : null}
                  </span>
                )}
              />
            </li>
          )
        })}
      </ul>
    </div>
  ))
}

export default function PaginaReserva() {
  const [catalogo, setCatalogo] = useState(null)
  const [error, setError] = useState('')
  const [telefono, setTelefono] = useState('')
  const [nombre, setNombre] = useState('')
  const [correo, setCorreo] = useState('')
  const [servicios, setServicios] = useState([])
  const [profesionalId, setProfesionalId] = useState('')
  const [fecha, setFecha] = useState('')
  const [horario, setHorario] = useState('')
  const [horasLibres, setHorasLibres] = useState([])
  const [cargandoHorarios, setCargandoHorarios] = useState(false)
  const [guardando, setGuardando] = useState(false)
  const [reserva, setReserva] = useState(null)
  const [menu, setMenu] = useState(false)
  const [bajado, setBajado] = useState(false)
  const [reservaLista, setReservaLista] = useState(false)
  const [modo, setModo] = useState('reservar')
  const [paso, setPaso] = useState('servicio')
  const [efectos, setEfectos] = useState(false)
  const [autorResena, setAutorResena] = useState('')
  const [correoResena, setCorreoResena] = useState('')
  const [textoResena, setTextoResena] = useState('')
  const [notaResena, setNotaResena] = useState('5')
  const [enviandoResena, setEnviandoResena] = useState(false)
  const [avisoResena, setAvisoResena] = useState('')
  const [errorResena, setErrorResena] = useState('')
  const panelReserva = useRef(null)
  const hoy = diaIso(new Date())

  function irAReserva() {
    requestAnimationFrame(() => {
      panelReserva.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
  }

  useEffect(() => {
    const alBajar = () => setBajado(window.scrollY > 24)
    alBajar()
    window.addEventListener('scroll', alBajar, { passive: true })
    return () => window.removeEventListener('scroll', alBajar)
  }, [])

  useEffect(() => {
    document.body.style.overflow = menu ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [menu])

  useEffect(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return undefined
    }
    let idleId = 0
    let timeoutId = 0
    const activar = () => setEfectos(true)
    if (typeof window.requestIdleCallback === 'function') {
      idleId = window.requestIdleCallback(activar, { timeout: 1800 })
    } else {
      timeoutId = window.setTimeout(activar, 400)
    }
    return () => {
      if (idleId && typeof window.cancelIdleCallback === 'function') {
        window.cancelIdleCallback(idleId)
      }
      if (timeoutId) {
        window.clearTimeout(timeoutId)
      }
    }
  }, [])

  useEffect(() => {
    verReserva()
      .then((datos) => {
        setCatalogo(datos)
      })
      .catch((fallo) => setError(fallo.message))
  }, [])

  useEffect(() => {
    if (!reservaLista || servicios.length === 0 || profesionalId === '' || fecha === '') {
      setHorasLibres([])
      setHorario('')
      setCargandoHorarios(false)
      return undefined
    }
    let vigente = true
    setCargandoHorarios(true)
    setHorario('')
    horariosReserva(fecha, profesionalId, servicios)
      .then((datos) => {
        if (!vigente) {
          return
        }
        setHorasLibres(datos.horarios ?? [])
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
  }, [reservaLista, servicios, profesionalId, fecha])

  const grupos = useMemo(() => {
    const mapa = new Map()
    ;(catalogo?.servicios ?? []).forEach((servicio) => {
      const categoria = servicio.categoria || 'General'
      if (!mapa.has(categoria)) {
        mapa.set(categoria, [])
      }
      mapa.get(categoria).push(servicio)
    })
    return [...mapa.entries()]
  }, [catalogo])

  async function reconocerTelefono() {
    const valor = telefono.trim()
    if (valor.length < 7) {
      return
    }
    try {
      const datos = await buscarReserva(valor)
      if (datos.cliente) {
        setNombre(datos.cliente.nombre)
        setCorreo(datos.cliente.correo ?? '')
      }
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  function reiniciarAgenda() {
    setProfesionalId('')
    setFecha('')
    setHorario('')
    setHorasLibres([])
  }

  function abrirReserva(evento) {
    evento?.preventDefault?.()
    setMenu(false)
    setReserva(null)
    setModo('reservar')
    setReservaLista(true)
    setPaso('servicio')
    irAReserva()
  }

  function abrirConsulta(evento) {
    evento?.preventDefault?.()
    setMenu(false)
    setError('')
    setModo('consultar')
    setReservaLista(true)
    irAReserva()
  }

  function alternarServicio(id) {
    setError('')
    setReserva(null)
    setReservaLista(true)
    setModo('reservar')
    setServicios((actual) => {
      if (actual.includes(id)) {
        return actual.filter((item) => item !== id)
      }
      return [...actual, id]
    })
    reiniciarAgenda()
    setPaso('servicio')
    // No hacemos scroll: en móvil el salto a #reserva duplicaba el checklist.
  }

  function continuarServicios() {
    if (servicios.length === 0) {
      setError('Elige al menos un servicio')
      return
    }
    setError('')
    setPaso('barbero')
    irAReserva()
  }

  function elegirProfesional(id) {
    setError('')
    setProfesionalId(String(id))
    setFecha('')
    setHorario('')
    setHorasLibres([])
    setPaso('agenda')
    irAReserva()
  }

  function elegirHorario(hora) {
    setHorario(hora)
    setPaso('datos')
    irAReserva()
  }

  async function confirmar(evento) {
    evento.preventDefault()
    setError('')
    setGuardando(true)
    try {
      const datos = await crearReserva({
        nombre,
        telefono: telefono.trim(),
        correo: correo.trim(),
        colaborador_id: Number(profesionalId),
        inicio: `${fecha} ${horario}:00`,
        servicios,
      })
      setReserva(datos.reserva)
      irAReserva()
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  function nuevaReserva() {
    setReserva(null)
    setServicios([])
    reiniciarAgenda()
    setError('')
    setPaso('servicio')
    irAReserva()
  }

  async function enviarResena(evento) {
    evento.preventDefault()
    setErrorResena('')
    setAvisoResena('')
    setEnviandoResena(true)
    try {
      const datos = await enviarResenaPublica({
        autor: autorResena.trim(),
        correo: correoResena.trim(),
        texto: textoResena.trim(),
        calificacion: Number(notaResena),
      })
      setAutorResena('')
      setCorreoResena('')
      setTextoResena('')
      setNotaResena('5')
      setAvisoResena(datos.mensaje || 'Gracias por tu reseña.')
    } catch (fallo) {
      setErrorResena(fallo.message)
    } finally {
      setEnviandoResena(false)
    }
  }

  const sede = catalogo?.sede || '3 Bigotes'
  const resenas = catalogo?.resenas ?? []
  const barberos = catalogo?.profesionales ?? []
  const profesional = barberos.find((item) => String(item.id) === String(profesionalId))
  const elegidos = (catalogo?.servicios ?? []).filter((item) => servicios.includes(item.id))
  const nombresServicio = elegidos.map((item) => item.nombre)
  const minutosElegidos = elegidos.reduce((suma, item) => suma + item.duracion_minutos, 0)
  const totalElegidos = elegidos.reduce((suma, item) => suma + Number(item.precio), 0)

  const medio = import.meta.env.BASE_URL

  return (
    <div className="inicio">
      {efectos && (
        <Suspense fallback={null}>
          <BrilloCursor />
        </Suspense>
      )}
      <header className={bajado ? 'nav is-scrolled' : 'nav'}>
        <div className="nav__inner">
          <a className="nav__brand" href="#inicio" onClick={() => setMenu(false)}>
            <span className="nav__badge">
              <img src={`${medio}media/logo.jpeg`} alt="Logo de 3 Bigotes" />
            </span>
            <span className="nav__name">
              3 Bigotes
              <span>Barberíe · Est. 2020</span>
            </span>
          </a>
          <nav className="nav__links" aria-label="Navegación principal">
            {ENLACES.map(([etiqueta, href], indice) => (
              <a key={href} href={href} style={{ '--i': indice }}>{etiqueta}</a>
            ))}
          </nav>
          <div className="nav__actions">
            <a className="btn btn--ghost nav__cta-secundaria" href="#reserva" onClick={abrirConsulta}>Consultar cita</a>
            <a className="btn btn--primary nav__cta" href="#reserva" onClick={abrirReserva}>Reservar cita</a>
            <button
              type="button"
              className={menu ? 'nav__burger is-open' : 'nav__burger'}
              onClick={() => setMenu((abierto) => !abierto)}
              aria-label={menu ? 'Cerrar menú' : 'Abrir menú'}
              aria-expanded={menu}
            >
              <span />
              <span />
              <span />
            </button>
          </div>
        </div>
      </header>
      <div className={menu ? 'menu is-open' : 'menu'}>
        <p className="menu__eyebrow">3 Bigotes — Menú</p>
        <nav className="menu__links" aria-label="Navegación móvil">
          {ENLACES.map(([etiqueta, href], indice) => (
            <a key={href} href={href} style={{ '--i': indice }} onClick={() => setMenu(false)}>{etiqueta}</a>
          ))}
        </nav>
        <a className="btn btn--ghost menu__cta-secundaria" href="#reserva" onClick={abrirConsulta}>Consultar cita</a>
        <a className="btn btn--primary menu__cta" href="#reserva" onClick={abrirReserva}>Reservar cita</a>
      </div>
      <section className="hero" id="inicio">
        <div className="bg" aria-hidden="true">
          <div className="bg__video-wrap">
            <VideoFondo src={`${medio}media/fondo-opt.mp4`} poster={`${medio}media/fondo-poster.jpg`} />
          </div>
          <div className="bg__tint" />
          <div className="bg__veil" />
          <div className="bg__vignette" />
          <div className="bg__scanlines" />
          <div className="bg__scanbar" />
          <div className="bg__grain" />
        </div>
        {efectos && (
          <Suspense fallback={null}>
            <Particulas />
          </Suspense>
        )}
        <div className="hero__frame" aria-hidden="true" />
        <p className="hero__side hero__side--left" aria-hidden="true">Est. 2020 — Barberíe</p>
        <p className="hero__side hero__side--right" aria-hidden="true">Navaja · Acero · Legado</p>
        <div className="hero__content">
          <div className="hero__emblem">
            <span className="hero__emblem-ring" aria-hidden="true" />
            <img src={`${medio}media/logo.jpeg`} alt="Emblema de 3 Bigotes" />
          </div>
          <p className="hero__eyebrow">Barbería de alta precisión</p>
          <h1 className="hero__title" data-text="3 BIGOTES">3 BIGOTES</h1>
          <p className="hero__serif">Barberíe — Since 2020</p>
          <p className="hero__desc">
            Más que una barbería... Un club de amigos.
          </p>
          <div className="hero__actions">
            <a className="btn btn--primary" href="#reserva" onClick={abrirReserva}>
              Reservar cita
              <span className="btn__icon" aria-hidden="true">→</span>
            </a>
            <a className="btn btn--ghost" href="#reserva" onClick={abrirConsulta}>Consultar cita</a>
          </div>
          <Redes />
          <dl className="hero__stats">
            {CIFRAS.map(([valor, etiqueta]) => (
              <div className="hero__stat" key={etiqueta}>
                <dt>{valor}</dt>
                <dd>{etiqueta}</dd>
              </div>
            ))}
          </dl>
        </div>
        <div className="hero__hud">
          <span className="hero__hud-item">© MMXXVI — 3 Bigotes</span>
          <a className="hero__scroll" href="#servicios" aria-label="Bajar a servicios">
            <span>Scroll</span>
            <i aria-hidden="true" />
          </a>
          <span className="hero__hud-item hero__online">
            <i aria-hidden="true" />
            Sistema online · PWR 100%
          </span>
        </div>
        <div className="ticker" aria-hidden="true">
          <div className="ticker__track">
            {[0, 1].map((grupo) => (
              <div className="ticker__group" key={grupo}>
                {MARQUESINA.map((item) => (
                  <span key={`${grupo}-${item}`}>
                    {item}
                    <em>✦</em>
                  </span>
                ))}
              </div>
            ))}
          </div>
        </div>
      </section>
      <div className="carta-cuerpo">

      {catalogo === null ? (
        <div className="d-flex justify-content-center py-5">
          <Spinner animation="border" role="status">
            <span className="visually-hidden">Cargando la barbería</span>
          </Spinner>
        </div>
      ) : (
        <>
          <section id="barberia" className="carta-seccion">
            <h2 className="carta-titulo">Barbería</h2>
            <p className="text-center mb-0">{sede}. Cortes de precisión, afeitado a navaja y ritual de toalla caliente.</p>
          </section>

          <section id="servicios" className="carta-seccion">
            <h2 className="carta-titulo">Servicios</h2>
            <p className="text-secondary text-center mb-3">Marca uno o varios y continúa con el barbero y el horario.</p>
            <ChecklistServicios
              idPrefijo="carta-servicio"
              grupos={grupos}
              seleccionados={reservaLista && !reserva ? servicios : []}
              onAlternar={alternarServicio}
            />
            {servicios.length > 0 && reservaLista && !reserva && (
              <div className="carta-servicios-resumen text-center mt-3">
                <p className="carta-eleccion mb-2">
                  {servicios.length} servicio{servicios.length === 1 ? '' : 's'} · {minutosElegidos} min · {pesos(totalElegidos)}
                </p>
                <BotonAccion icono="check" titulo="Continuar con la reserva" variant="primary" onClick={continuarServicios} />
              </div>
            )}
          </section>

          <section id="galeria" className="carta-seccion">
            <h2 className="carta-titulo">Galería</h2>
            <p className="text-secondary text-center mb-0">Los trabajos de la barbería se verán aquí.</p>
          </section>

        </>
      )}

      <section id="reserva" className="carta-seccion" ref={panelReserva}>
        <h2 className="carta-titulo">{modo === 'consultar' ? 'Tu cita' : 'Reserva tu cita'}</h2>
        <Redes />
        <div className="carta-reserva">
        {error && modo === 'reservar' && <Alert variant="danger">{error}</Alert>}
        {catalogo === null ? (
          <div className="d-flex justify-content-center py-4">
            <Spinner animation="border" role="status">
              <span className="visually-hidden">Cargando reserva</span>
            </Spinner>
          </div>
        ) : modo === 'consultar' ? (
          <ConsultaCita barberos={barberos} onCerrar={abrirReserva} />
        ) : !reservaLista ? (
          <div className="text-center">
            <p className="text-secondary mb-3">Pulsa Reservar cita, Consultar cita o elige un servicio arriba.</p>
            <GrupoAcciones className="justify-content-center">
              <BotonAccion icono="calendar" titulo="Reservar cita" variant="primary" onClick={abrirReserva} />
              <BotonAccion icono="search" titulo="Consultar cita" onClick={abrirConsulta} />
            </GrupoAcciones>
          </div>
        ) : reserva ? (
          <div className="carta-confirmacion" role="status">
            <p className="carta-confirmacion-eyebrow">Reserva confirmada</p>
            <h3 className="carta-confirmacion-titulo">Cita reservada</h3>
            <p className="carta-confirmacion-saludo">
              Listo, <strong>{reserva.cliente}</strong>. Tu cita quedó agendada.
            </p>
            <dl className="carta-confirmacion-datos">
              <div>
                <dt>Servicios</dt>
                <dd>{reserva.servicios.join(', ')}</dd>
              </div>
              <div>
                <dt>Barbero</dt>
                <dd>{reserva.profesional}</dd>
              </div>
              <div>
                <dt>Día y hora</dt>
                <dd>{formatearCita(reserva.inicio)}</dd>
              </div>
            </dl>
            <p className="carta-confirmacion-nota">
              Te enviamos la confirmación al correo y un recordatorio dos horas antes.
            </p>
            <BotonAccion icono="calendar" titulo="Reservar otra cita" variant="primary" onClick={nuevaReserva} />
          </div>
        ) : (
          <Form onSubmit={confirmar}>
            <ol className="carta-pasos-lista" aria-label="Pasos de la reserva">
              <li className={paso === 'servicio' ? 'activo' : servicios.length ? 'hecho' : ''}>Servicios</li>
              <li className={paso === 'barbero' ? 'activo' : profesionalId ? 'hecho' : ''}>Barbero</li>
              <li className={paso === 'agenda' ? 'activo' : horario ? 'hecho' : ''}>Día y hora</li>
              <li className={paso === 'datos' ? 'activo' : ''}>Tus datos</li>
            </ol>

            {paso === 'servicio' && (
              <div className="carta-paso">
                <h3 className="carta-agenda-titulo">Elige los servicios</h3>
                {servicios.length > 0 ? (
                  <>
                    <p className="text-secondary mb-2">
                      Ya marcaste servicios arriba. Puedes seguir o volver a la lista para cambiarlos.
                    </p>
                    <p className="carta-eleccion mb-3">
                      {nombresServicio.join(', ')} · {minutosElegidos} min · {pesos(totalElegidos)}
                    </p>
                    <GrupoAcciones>
                      <BotonAccion
                        icono="chevronLeft"
                        titulo="Cambiar servicios"
                        onClick={() => {
                          document.getElementById('servicios')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
                        }}
                      />
                      <BotonAccion icono="check" titulo="Continuar" variant="primary" onClick={continuarServicios} />
                    </GrupoAcciones>
                  </>
                ) : (
                  <>
                    <p className="text-secondary mb-3">Marca los que quieras incluir en la cita.</p>
                    <ChecklistServicios
                      idPrefijo="paso-servicio"
                      grupos={grupos}
                      seleccionados={servicios}
                      onAlternar={alternarServicio}
                    />
                  </>
                )}
              </div>
            )}

            {paso === 'barbero' && (
              <div className="carta-paso">
                <h3 className="carta-agenda-titulo">Profesionales disponibles</h3>
                <p className="carta-eleccion mb-3">
                  Servicios: {nombresServicio.join(', ')} · {minutosElegidos} min · {pesos(totalElegidos)}
                </p>
                {barberos.length === 0 ? (
                  <p className="text-secondary">Todavía no hay barberos disponibles.</p>
                ) : (
                  <div className="carta-equipo mb-3">
                    {barberos.map((item) => {
                      const inicial = item.nombre.trim().charAt(0).toUpperCase()
                      return (
                        <button
                          key={item.id}
                          type="button"
                          className="carta-pro"
                          onClick={() => elegirProfesional(item.id)}
                        >
                          {item.foto ? (
                            <img className="carta-foto" src={fotoSrc(item.foto)} alt="" />
                          ) : (
                            <span className="carta-foto carta-inicial" aria-hidden="true">{inicial}</span>
                          )}
                          <span className="carta-pro-nombre">{item.nombre}</span>
                          <span className="carta-elegir">Disponible</span>
                        </button>
                      )
                    })}
                  </div>
                )}
                <BotonAccion icono="chevronLeft" titulo="Cambiar servicio" onClick={() => { setPaso('servicio'); reiniciarAgenda(); setServicios([]); irAReserva() }} />
              </div>
            )}

            {paso === 'agenda' && (
              <div className="carta-paso">
                <h3 className="carta-agenda-titulo">Elige día y hora</h3>
                <p className="carta-eleccion mb-3">
                  {nombresServicio.join(', ')} con {profesional?.nombre}
                </p>
                <Form.Group className="mb-3" controlId="fecha">
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
                  <p className="text-secondary mb-3">Elige un día para ver las horas libres.</p>
                ) : cargandoHorarios ? (
                  <Spinner animation="border" size="sm" role="status" className="mb-3">
                    <span className="visually-hidden">Buscando horas</span>
                  </Spinner>
                ) : horasLibres.length === 0 ? (
                  <p className="text-secondary mb-3">Ese día no tiene horas libres. Prueba otra fecha.</p>
                ) : (
                  <div className="carta-horas mb-4">
                    {horasLibres.map((hora) => (
                      <Button
                        key={hora}
                        type="button"
                        size="sm"
                        variant={horario === hora ? 'primary' : 'outline-primary'}
                        onClick={() => elegirHorario(hora)}
                      >
                        {hora}
                      </Button>
                    ))}
                  </div>
                )}
                <BotonAccion icono="chevronLeft" titulo="Cambiar barbero" onClick={() => { setPaso('barbero'); setFecha(''); setHorario(''); setHorasLibres([]); setProfesionalId(''); irAReserva() }} />
              </div>
            )}

            {paso === 'datos' && (
              <div className="carta-paso">
                <h3 className="carta-agenda-titulo">Tus datos</h3>
                <p className="carta-eleccion mb-3">
                  {nombresServicio.join(', ')} con {profesional?.nombre} el {formatearCita(`${fecha} ${horario}:00`)}.
                </p>
                <Row>
                  <Col md={4}>
                    <Form.Group className="mb-3" controlId="nombre">
                      <Form.Label>Nombre</Form.Label>
                      <Form.Control value={nombre} required maxLength={120} autoComplete="name" onChange={(evento) => setNombre(evento.target.value)} />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group className="mb-3" controlId="telefono">
                      <Form.Label>Teléfono</Form.Label>
                      <Form.Control
                        value={telefono}
                        required
                        minLength={7}
                        maxLength={20}
                        autoComplete="tel"
                        onChange={(evento) => setTelefono(evento.target.value)}
                        onBlur={reconocerTelefono}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group className="mb-3" controlId="correo">
                      <Form.Label>Correo</Form.Label>
                      <Form.Control
                        type="email"
                        value={correo}
                        required
                        maxLength={160}
                        autoComplete="email"
                        onChange={(evento) => setCorreo(evento.target.value)}
                      />
                      <Form.Text className="text-secondary">
                        Lo usaremos para confirmar y recordarte la cita.
                      </Form.Text>
                    </Form.Group>
                  </Col>
                </Row>
                <GrupoAcciones>
                  <BotonAccion icono="chevronLeft" titulo="Cambiar hora" onClick={() => { setPaso('agenda'); setHorario(''); irAReserva() }} />
                  <BotonAccion
                    icono="check"
                    titulo={guardando ? 'Reservando…' : 'Confirmar reserva'}
                    type="submit"
                    variant="primary"
                    disabled={guardando}
                  />
                </GrupoAcciones>
              </div>
            )}
          </Form>
        )}
        </div>
      </section>

      <section id="resenas" className="carta-seccion carta-final">
        <h2 className="carta-titulo">Reseñas</h2>
        {resenas.length === 0 ? (
          <p className="text-secondary text-center mb-4">Todavía no hay reseñas publicadas. Sé el primero en dejar la tuya.</p>
        ) : (
          <div className="carta-resenas mb-4">
            {resenas.map((resena) => (
              <article key={resena.id} className="carta-resena">
                <p className="carta-estrellas" aria-label={`${resena.calificacion} de 5`}>{estrellas(resena.calificacion)}</p>
                <p className="carta-resena-texto">{resena.texto}</p>
                <p className="carta-resena-autor">{resena.autor}</p>
              </article>
            ))}
          </div>
        )}
        <div className="carta-reserva carta-resena-form">
          <h3 className="carta-agenda-titulo">Deja tu reseña</h3>
          <p className="text-secondary mb-3">
            Cuéntanos tu experiencia. La revisamos antes de publicarla. Más adelante podremos enviarte una encuesta de satisfacción por correo.
          </p>
          {errorResena && <Alert variant="danger">{errorResena}</Alert>}
          {avisoResena && <Alert variant="success">{avisoResena}</Alert>}
          <Form onSubmit={enviarResena}>
            <Row>
              <Col md={4}>
                <Form.Group className="mb-3" controlId="autorResenaPublica">
                  <Form.Label>Nombre</Form.Label>
                  <Form.Control
                    value={autorResena}
                    required
                    maxLength={80}
                    autoComplete="name"
                    onChange={(evento) => setAutorResena(evento.target.value)}
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group className="mb-3" controlId="correoResenaPublica">
                  <Form.Label>Correo</Form.Label>
                  <Form.Control
                    type="email"
                    value={correoResena}
                    required
                    maxLength={160}
                    autoComplete="email"
                    onChange={(evento) => setCorreoResena(evento.target.value)}
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group className="mb-3" controlId="notaResenaPublica">
                  <Form.Label>Calificación</Form.Label>
                  <Form.Select value={notaResena} onChange={(evento) => setNotaResena(evento.target.value)}>
                    {[5, 4, 3, 2, 1].map((valor) => (
                      <option key={valor} value={valor}>{valor} ★</option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
            <Form.Group className="mb-3" controlId="textoResenaPublica">
              <Form.Label>Tu reseña</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={textoResena}
                required
                minLength={8}
                maxLength={400}
                onChange={(evento) => setTextoResena(evento.target.value)}
              />
            </Form.Group>
            <BotonAccion
              icono="check"
              titulo={enviandoResena ? 'Enviando…' : 'Enviar reseña'}
              type="submit"
              variant="primary"
              disabled={enviandoResena}
            />
          </Form>
        </div>
      </section>

      <section id="contacto" className="carta-seccion carta-final">
        <h2 className="carta-titulo">Contacto</h2>
        <p className="text-center mb-2">{sede}</p>
        <p className="text-center mb-0">
          <Link to="/ingreso">Ingreso del equipo</Link>
        </p>
      </section>
      </div>
    </div>
  )
}
