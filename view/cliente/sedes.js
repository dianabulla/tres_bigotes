import { solicitar } from './http'

export function listarSedes() {
  return solicitar('/api/sedes')
}

export function guardarSede(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    direccion: datos.direccion,
    telefono: datos.telefono,
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/sedes/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  cuerpo.administrador = {
    nombre: datos.admin_nombre,
    correo: datos.admin_correo,
    clave: datos.admin_clave,
  }
  return solicitar('/api/sedes', { metodo: 'POST', cuerpo })
}
