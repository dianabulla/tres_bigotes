import { solicitar } from './http'

export function verCaja() {
  return solicitar('/api/caja')
}

export function listarCierres(desde, hasta) {
  const params = new URLSearchParams({ desde, hasta })
  return solicitar(`/api/caja/cierres?${params}`)
}

export function abrirTurno(saldoInicial) {
  return solicitar('/api/caja/turno', { metodo: 'POST', cuerpo: { saldo_inicial: saldoInicial } })
}

export function cobrarVenta(lineas, pagos, opciones = {}) {
  const cuerpo = { lineas, pagos }
  if (opciones.citaId) {
    cuerpo.cita_id = opciones.citaId
    if (Array.isArray(opciones.servicios)) {
      cuerpo.servicios = opciones.servicios
    }
  } else if (Array.isArray(opciones.servicios) && opciones.servicios.length > 0) {
    cuerpo.servicios = opciones.servicios
    cuerpo.colaborador_id = opciones.colaboradorId
  }
  return solicitar('/api/caja/ventas', { metodo: 'POST', cuerpo })
}

export function cerrarTurno(lineas) {
  return solicitar('/api/caja/cierre', { metodo: 'POST', cuerpo: { lineas } })
}

export function registrarAjuste(tipo, medio, monto, motivo) {
  return solicitar('/api/caja/ajustes', {
    metodo: 'POST',
    cuerpo: { tipo, medio, monto, motivo },
  })
}
