import { solicitar } from './http'

export function listarServicios() {
  return solicitar('/api/servicios')
}

export function guardarServicio(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    duracion_minutos: datos.duracion_minutos,
    precio: datos.precio,
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/servicios/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/servicios', { metodo: 'POST', cuerpo })
}

export function buscarClientes(texto) {
  return solicitar(`/api/clientes?q=${encodeURIComponent(texto)}`)
}

export function listarCitas(desde, hasta, colaboradorId) {
  const params = new URLSearchParams({ desde, hasta })
  if (colaboradorId) {
    params.set('colaborador_id', String(colaboradorId))
  }
  return solicitar(`/api/citas?${params.toString()}`)
}

export function crearCita(datos) {
  return solicitar('/api/citas', { metodo: 'POST', cuerpo: datos })
}

export function cambiarEstadoCita(id, estado) {
  return solicitar(`/api/citas/${id}/estado`, { metodo: 'PUT', cuerpo: { estado } })
}
