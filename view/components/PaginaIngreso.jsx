import { useState } from 'react'
import { Alert, Button, Card, Form, Spinner } from 'react-bootstrap'
import { Link, Navigate } from 'react-router-dom'
import { useSesion } from './Sesion'

export default function PaginaIngreso() {
  const { usuario, cargando, entrar } = useSesion()
  const [correo, setCorreo] = useState('')
  const [clave, setClave] = useState('')
  const [error, setError] = useState('')
  const [enviando, setEnviando] = useState(false)

  if (cargando) {
    return (
      <div className="d-flex justify-content-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando sesión</span>
        </Spinner>
      </div>
    )
  }

  if (usuario) {
    return <Navigate to="/gestion" replace />
  }

  async function enviar(evento) {
    evento.preventDefault()
    setError('')
    setEnviando(true)
    try {
      await entrar(correo, clave)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setEnviando(false)
    }
  }

  return (
    <div className="d-flex align-items-center justify-content-center min-vh-100 px-3">
      <Card className="marca-tarjeta w-100" style={{ maxWidth: 420 }}>
        <Card.Body>
          <h1 className="visually-hidden">Tres Bigotes</h1>
          <img src={`${import.meta.env.BASE_URL}media/logo.jpeg`} alt="" className="marca-logo-grande" />
          {error && <Alert variant="danger">{error}</Alert>}
          <Form onSubmit={enviar}>
            <Form.Group className="mb-3" controlId="correo">
              <Form.Label>Correo</Form.Label>
              <Form.Control
                type="email"
                value={correo}
                autoComplete="username"
                onChange={(evento) => setCorreo(evento.target.value)}
                required
              />
            </Form.Group>
            <Form.Group className="mb-3" controlId="clave">
              <Form.Label>Clave</Form.Label>
              <Form.Control
                type="password"
                value={clave}
                autoComplete="current-password"
                onChange={(evento) => setClave(evento.target.value)}
                required
              />
            </Form.Group>
            <Button type="submit" className="w-100" disabled={enviando}>
              {enviando ? 'Entrando…' : 'Entrar'}
            </Button>
          </Form>
          <p className="text-center mt-3 mb-0">
            <Link to="/">Volver al inicio</Link>
          </p>
        </Card.Body>
      </Card>
    </div>
  )
}
