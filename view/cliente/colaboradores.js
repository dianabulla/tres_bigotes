import { solicitar } from './http'

export function listarColaboradores() {
  return solicitar('/api/colaboradores')
}

export function guardarColaborador(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    telefono: datos.telefono,
    fecha_ingreso: datos.fecha_ingreso,
    correo: datos.correo,
    clave: datos.clave,
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/colaboradores/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/colaboradores', { metodo: 'POST', cuerpo })
}
