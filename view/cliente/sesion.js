import { solicitar } from './http'

export function iniciarSesion(correo, clave) {
  return solicitar('/api/sesion', {
    metodo: 'POST',
    cuerpo: { correo, clave },
    publica: true,
  })
}

export function leerSesion() {
  return solicitar('/api/sesion')
}

export function cerrarSesion() {
  return solicitar('/api/sesion', { metodo: 'DELETE' })
}
