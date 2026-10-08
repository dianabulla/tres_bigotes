import { solicitar } from './http'

export function listarRoles() {
  return solicitar('/api/roles')
}

export function guardarRol(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    permisos: datos.permisos,
  }
  if (datos.id) {
    return solicitar(`/api/roles/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/roles', { metodo: 'POST', cuerpo })
}

export function eliminarRol(id) {
  return solicitar(`/api/roles/${id}`, { metodo: 'DELETE' })
}
