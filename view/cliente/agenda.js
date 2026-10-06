import { solicitar } from './http'

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
