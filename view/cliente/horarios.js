import { solicitar } from './http'

export function verHorarios() {
  return solicitar('/api/horarios')
}

export function guardarHorarioTrabajo(tramos) {
  return solicitar('/api/horarios/trabajo', { metodo: 'PUT', cuerpo: { tramos } })
}

export function crearBloqueo(cuerpo) {
  return solicitar('/api/horarios/bloqueos', { metodo: 'POST', cuerpo })
}

export function eliminarBloqueo(id) {
  return solicitar(`/api/horarios/bloqueos/${id}`, { metodo: 'DELETE' })
}
