import { solicitar } from './http'

export function verReserva() {
  return solicitar('/api/reserva', { publica: true })
}

export function buscarReserva(telefono) {
  return solicitar(`/api/reserva/cliente?telefono=${encodeURIComponent(telefono)}`, { publica: true })
}

export function horariosReserva(fecha, colaboradorId, servicios) {
  const params = new URLSearchParams({
    fecha,
    servicios: servicios.join(','),
  })
  if (colaboradorId != null && colaboradorId !== '') {
    params.set('colaborador_id', String(colaboradorId))
  }
  return solicitar(`/api/reserva/horarios?${params.toString()}`, { publica: true })
}

export function crearReserva(datos) {
  return solicitar('/api/reserva', { metodo: 'POST', publica: true, cuerpo: datos })
}
