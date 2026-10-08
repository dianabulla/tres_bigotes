import { useEffect, useMemo, useState } from 'react'
import { Alert, Button, Col, Form, Row, Spinner } from 'react-bootstrap'
import { Link } from 'react-router-dom'
import { buscarReserva, crearReserva, horariosReserva, verReserva } from '../../cliente/reserva'
import BrilloCursor from './BrilloCursor'
import Particulas from './Particulas'
import './portada.css'

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
    href: 'https://www.instagram.com/3bigotesbarberia',
    clase: 'red--instagram',
    icono: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 1.8H7A2.2 2.2 0 0 0 4.8 7v10A2.2 2.2 0 0 0 7 19.2h10a2.2 2.2 0 0 0 2.2-2.2V7A2.2 2.2 0 0 0 17 4.8zM12 8.1A3.9 3.9 0 1 1 8.1 12 3.9 3.9 0 0 1 12 8.1zm0 1.6a2.3 2.3 0 1 0 2.3 2.3A2.3 2.3 0 0 0 12 9.7zM17.35 6.4a1.05 1.05 0 1 1-1.05 1.05 1.05 1.05 0 0 1 1.05-1.05z" />
      </svg>
    ),
  },
  {
    nombre: 'Facebook',
    href: 'https://www.facebook.com/3bigotesbarberia',
    clase: 'red--facebook',
    icono: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M14.2 21v-7.1h2.4l.4-2.8h-2.8V9.3c0-.8.2-1.4 1.4-1.4H17V5.4c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2h-2.4v2.8H11V21z" />
      </svg>
    ),
  },
  {
    nombre: 'WhatsApp',
    href: 'https://wa.me/573209651928?text=Hola%2C%20quiero%20agendar%20una%20cita%20en%203%20Bigotes',
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
        <a key={red.nombre} className={`red ${red.clase}`} href={red.href} target="_blank" rel="noopener noreferrer">
          {red.icono}
          <span>{red.nombre}</span>
        </a>
      ))}
    </div>
  )
}

const ENLACES = [
  ['Inicio', '#inicio'],
  ['Barbería', '#barberia'],
  ['Servicios', '#servicios'],
  ['Galería', '#galeria'],
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

export default function PaginaReserva() {
  const [catalogo, setCatalogo] = useState(null)
  const [error, setError] = useState('')
  const [telefono, setTelefono] = useState('')
  const [nombre, setNombre] = useState('')
  const [correo, setCorreo] = useState('')
  const [servicios, setServicios] = useState([])
  const [profesionalId, setProfesionalId] = useState('')
  const [fecha, setFecha] = useState(diaIso(new Date()))
  const [horario, setHorario] = useState('')
  const [disponibles, setDisponibles] = useState([])
  const [cargandoHorarios, setCargandoHorarios] = useState(false)
  const [guardando, setGuardando] = useState(false)
  const [reserva, setReserva] = useState(null)
  const [menu, setMenu] = useState(false)
  const [bajado, setBajado] = useState(false)
  const [reservaLista, setReservaLista] = useState(false)

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
    verReserva()
      .then((datos) => {
        setCatalogo(datos)
      })
      .catch((fallo) => setError(fallo.message))
  }, [])

  useEffect(() => {
    if (!reservaLista || servicios.length === 0 || fecha === '' || catalogo === null) {
      setDisponibles([])
      setProfesionalId('')
      setHorario('')
      return undefined
    }
    let vigente = true
    setCargandoHorarios(true)
    Promise.all(catalogo.profesionales.map(async (item) => {
      const datos = await horariosReserva(fecha, item.id, servicios)
      return datos.horarios.length > 0 ? { ...item, horarios: datos.horarios } : null
    }))
      .then((lista) => {
        if (!vigente) {
          return
        }
        const libres = lista.filter(Boolean)
        setDisponibles(libres)
        setProfesionalId((actual) => (libres.some((item) => String(item.id) === String(actual)) ? actual : ''))
        setHorario('')
      })
      .catch((fallo) => {
        if (vigente) {
          setDisponibles([])
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
  }, [reservaLista, servicios, fecha, catalogo])

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
        colaborador_id: Number(profesionalId),
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

  function abrirReserva(evento) {
    evento.preventDefault()
    setMenu(false)
    setReservaLista(true)
    document.getElementById('reserva')?.scrollIntoView({ behavior: 'smooth' })
  }

  const sede = catalogo?.sede || 'Tres Bigotes'
  const resenas = catalogo?.resenas ?? []
  const profesional = disponibles.find((item) => String(item.id) === String(profesionalId))
  const horasLibres = profesional?.horarios ?? []

  const medio = import.meta.env.BASE_URL

  return (
    <div className="inicio">
      <BrilloCursor />
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
        <a className="btn btn--primary menu__cta" href="#reserva" onClick={abrirReserva}>Reservar cita</a>
      </div>
      <section className="hero" id="inicio">
        <div className="bg" aria-hidden="true">
          <div className="bg__video-wrap">
            <video className="bg__video" src={`${medio}media/fondo.mp4`} autoPlay muted loop playsInline preload="auto" />
          </div>
          <div className="bg__tint" />
          <div className="bg__veil" />
          <div className="bg__vignette" />
          <div className="bg__scanlines" />
          <div className="bg__scanbar" />
          <div className="bg__grain" />
        </div>
        <Particulas />
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
            Cortes de precisión quirúrgica, afeitado clásico a navaja y ritual de toalla caliente. Aquí no vienes a cortarte el pelo: <strong>vienes a forjar tu presencia</strong>.
          </p>
          <div className="hero__actions">
            <a className="btn btn--primary" href="#reserva" onClick={abrirReserva}>
              Reservar cita
              <span className="btn__icon" aria-hidden="true">→</span>
            </a>
            <a className="btn btn--ghost" href="#servicios">Ver servicios</a>
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
            {grupos.length === 0 ? (
              <p className="text-secondary text-center mb-0">Todavía no hay servicios para reservar.</p>
            ) : grupos.map(([categoria, lista]) => (
              <div key={categoria} className="mb-4">
                <h3 className="inicio-categoria">{categoria}</h3>
                <div className="carta-servicios">
                  {lista.map((servicio) => {
                    const activo = servicios.includes(servicio.id)
                    return (
                      <button
                        key={servicio.id}
                        type="button"
                        className={activo ? 'carta-servicio activo' : 'carta-servicio'}
                        onClick={() => cambiarServicio(servicio.id, !activo)}
                      >
                        <span className="carta-servicio-nombre">{servicio.nombre}</span>
                        <span className="carta-servicio-meta">{servicio.duracion_minutos} min</span>
                        <span className="carta-servicio-precio">{pesos(servicio.precio)}</span>
                        <span className="carta-elegir">{activo ? 'Elegido' : 'Elegir'}</span>
                      </button>
                    )
                  })}
                </div>
              </div>
            ))}
          </section>

          <section id="galeria" className="carta-seccion">
            <h2 className="carta-titulo">Galería</h2>
            <p className="text-secondary text-center mb-0">Los trabajos de la barbería se verán aquí.</p>
          </section>

        </>
      )}

      <section id="reserva" className="carta-seccion">
        <h2 className="carta-titulo">Reserva tu cita</h2>
        <Redes />
        <div className="carta-reserva">
        {error && <Alert variant="danger">{error}</Alert>}
        {catalogo === null ? (
          <div className="d-flex justify-content-center py-4">
            <Spinner animation="border" role="status">
              <span className="visually-hidden">Cargando reserva</span>
            </Spinner>
          </div>
        ) : !reservaLista ? (
          <p className="text-secondary text-center mb-0">Pulsa Reservar cita para abrir el formulario.</p>
        ) : reserva ? (
          <Alert variant="light" className="border border-dark text-black mb-0">
            Listo, {reserva.cliente}. Tu cita con {reserva.profesional} quedó para {reserva.inicio.slice(0, 16)} ({reserva.servicios.join(', ')}).
          </Alert>
        ) : (
          <Form onSubmit={confirmar}>
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
                  <Form.Control type="email" value={correo} maxLength={160} autoComplete="email" onChange={(evento) => setCorreo(evento.target.value)} />
                </Form.Group>
              </Col>
            </Row>

            <h3 className="carta-agenda-titulo">Servicio</h3>
            {grupos.length === 0 ? (
              <p className="text-secondary">Todavía no hay servicios para reservar. El administrador los crea en la agenda.</p>
            ) : grupos.map(([categoria, lista]) => (
              <div key={categoria} className="mb-3">
                <h4 className="inicio-categoria">{categoria}</h4>
                <div className="carta-servicios">
                  {lista.map((servicio) => {
                    const activo = servicios.includes(servicio.id)
                    return (
                      <button
                        key={servicio.id}
                        type="button"
                        className={activo ? 'carta-servicio activo' : 'carta-servicio'}
                        onClick={() => cambiarServicio(servicio.id, !activo)}
                      >
                        <span className="carta-servicio-nombre">{servicio.nombre}</span>
                        <span className="carta-servicio-meta">{servicio.duracion_minutos} min</span>
                        <span className="carta-servicio-precio">{pesos(servicio.precio)}</span>
                        <span className="carta-elegir">{activo ? 'Elegido' : 'Elegir'}</span>
                      </button>
                    )
                  })}
                </div>
              </div>
            ))}

            {servicios.length > 0 && (
              <>
                <h3 className="carta-agenda-titulo mt-4">Profesional disponible</h3>
                <Form.Group className="mb-3" controlId="fecha">
                  <Form.Label>Día</Form.Label>
                  <Form.Control type="date" required value={fecha} min={diaIso(new Date())} onChange={(evento) => setFecha(evento.target.value)} />
                </Form.Group>
                {cargandoHorarios ? (
                  <Spinner animation="border" size="sm" role="status">
                    <span className="visually-hidden">Buscando profesionales</span>
                  </Spinner>
                ) : disponibles.length === 0 ? (
                  <p className="text-secondary">Ningún profesional tiene hora libre ese día para ese servicio.</p>
                ) : (
                  <div className="carta-equipo mb-3">
                    {disponibles.map((item) => {
                      const activo = String(item.id) === String(profesionalId)
                      const inicial = item.nombre.trim().charAt(0).toUpperCase()
                      return (
                        <button
                          key={item.id}
                          type="button"
                          className={activo ? 'carta-pro activo' : 'carta-pro'}
                          onClick={() => {
                            setProfesionalId(String(item.id))
                            setHorario('')
                          }}
                        >
                          {item.foto ? (
                            <img className="carta-foto" src={fotoSrc(item.foto)} alt="" />
                          ) : (
                            <span className="carta-foto carta-inicial" aria-hidden="true">{inicial}</span>
                          )}
                          <span className="carta-pro-nombre">{item.nombre}</span>
                          <span className="carta-elegir">{activo ? 'Elegido' : 'Disponible'}</span>
                        </button>
                      )
                    })}
                  </div>
                )}
                {profesional && (
                  <div className="carta-horas mb-4">
                    {horasLibres.map((hora) => (
                      <Button key={hora} type="button" size="sm" variant={horario === hora ? 'primary' : 'outline-primary'} onClick={() => setHorario(hora)}>
                        {hora}
                      </Button>
                    ))}
                  </div>
                )}
              </>
            )}

            {servicios.length === 0 ? (
              <p className="text-secondary mb-3">Elige un servicio para ver quién está disponible.</p>
            ) : horario === '' ? (
              <p className="text-secondary mb-3">Elige profesional y hora para confirmar.</p>
            ) : null}

            <Button type="submit" disabled={guardando || horario === '' || servicios.length === 0 || profesionalId === ''}>
              {guardando ? 'Reservando…' : 'Confirmar reserva'}
            </Button>
          </Form>
        )}
        </div>
      </section>

      <section id="resenas" className="carta-seccion carta-final">
        <h2 className="carta-titulo">Reseñas</h2>
        {resenas.length === 0 ? (
          <p className="text-secondary text-center mb-0">Todavía no hay reseñas.</p>
        ) : (
          <div className="carta-resenas">
            {resenas.map((resena) => (
              <article key={resena.id} className="carta-resena">
                <p className="carta-estrellas" aria-label={`${resena.calificacion} de 5`}>{estrellas(resena.calificacion)}</p>
                <p className="carta-resena-texto">{resena.texto}</p>
                <p className="carta-resena-autor">{resena.autor}</p>
              </article>
            ))}
          </div>
        )}
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
