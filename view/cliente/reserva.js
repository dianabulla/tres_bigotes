import { solicitar } from './http'

export function verReserva() {
  return solicitar('/api/reserva', { publica: true })
}

export function buscarReserva(telefono) {
  return solicitar(`/api/reserva/cliente?telefono=${encodeURIComponent(telefono)}`, { publica: true })
}

export function horariosReserva(fecha, colaboradorId, servicios, exceptoCitaId = null) {
  const params = new URLSearchParams({
    fecha,
    servicios: servicios.join(','),
  })
  if (colaboradorId != null && colaboradorId !== '') {
    params.set('colaborador_id', String(colaboradorId))
  }
  if (exceptoCitaId != null && exceptoCitaId !== '') {
    params.set('excepto_cita_id', String(exceptoCitaId))
  }
  return solicitar(`/api/reserva/horarios?${params.toString()}`, { publica: true })
}

export function crearReserva(datos) {
  return solicitar('/api/reserva', { metodo: 'POST', publica: true, cuerpo: datos })
}

export function consultarCitas(telefono, correo) {
  const params = new URLSearchParams({ telefono, correo })
  return solicitar(`/api/reserva/citas?${params.toString()}`, { publica: true })
}

export function cancelarCitaPublica(id, telefono, correo) {
  return solicitar(`/api/reserva/citas/${id}/cancelar`, {
    metodo: 'POST',
    publica: true,
    cuerpo: { telefono, correo },
  })
}

export function reagendarCitaPublica(id, datos) {
  return solicitar(`/api/reserva/citas/${id}`, {
    metodo: 'PUT',
    publica: true,
    cuerpo: datos,
  })
}

export function enviarResenaPublica(datos) {
  return solicitar('/api/reserva/resenas', {
    metodo: 'POST',
    publica: true,
    cuerpo: datos,
  })
}
