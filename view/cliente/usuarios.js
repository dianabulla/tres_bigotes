import { solicitar } from './http'

export function listarUsuarios() {
  return solicitar('/api/usuarios')
}

export function guardarUsuario(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    correo: datos.correo,
    rol: datos.rol,
    clave: datos.clave,
  }
  if (datos.establecimiento_id !== undefined && datos.establecimiento_id !== null && datos.establecimiento_id !== '') {
    cuerpo.establecimiento_id = Number(datos.establecimiento_id)
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/usuarios/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/usuarios', { metodo: 'POST', cuerpo })
}
