import { solicitar } from './http'

export function verCaja() {
  return solicitar('/api/caja')
}

export function abrirTurno(saldoInicial) {
  return solicitar('/api/caja/turno', { metodo: 'POST', cuerpo: { saldo_inicial: saldoInicial } })
}

export function cobrarVenta(lineas, pagos, citaId) {
  const cuerpo = { lineas, pagos }
  if (citaId) {
    cuerpo.cita_id = citaId
  }
  return solicitar('/api/caja/ventas', { metodo: 'POST', cuerpo })
}

export function cerrarTurno(lineas) {
  return solicitar('/api/caja/cierre', { metodo: 'POST', cuerpo: { lineas } })
}
