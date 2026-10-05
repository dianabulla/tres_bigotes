import { solicitar } from './http'

export function buscarClinica(texto) {
  const params = new URLSearchParams()
  if (texto) {
    params.set('q', texto)
  }
  const consulta = params.toString()
  return solicitar(`/api/clinica/clientes${consulta ? `?${consulta}` : ''}`)
}

export function verClinica(id) {
  return solicitar(`/api/clinica/clientes/${id}`)
}

export function guardarFicha(id, datos) {
  return solicitar(`/api/clinica/clientes/${id}`, { metodo: 'PUT', cuerpo: datos })
}

export function guardarNota(id, datos) {
  return solicitar(`/api/clinica/clientes/${id}/notas`, { metodo: 'POST', cuerpo: datos })
}
