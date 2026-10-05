import { solicitar } from './http'

export function verComisiones() {
  return solicitar('/api/comisiones')
}

export function guardarRegla(datos) {
  const cuerpo = {
    servicio_id: datos.servicio_id || null,
    porcentaje: datos.porcentaje,
    umbral_cantidad: datos.umbral_cantidad,
    periodo: datos.periodo,
  }
  if (datos.id) {
    return solicitar(`/api/comisiones/reglas/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/comisiones/reglas', { metodo: 'POST', cuerpo })
}

export function eliminarRegla(id) {
  return solicitar(`/api/comisiones/reglas/${id}`, { metodo: 'DELETE' })
}

export function liquidarComision(datos) {
  return solicitar('/api/comisiones/liquidaciones', { metodo: 'POST', cuerpo: datos })
}

export function verLiquidacion(id) {
  return solicitar(`/api/comisiones/liquidaciones/${id}`)
}

export function anularLiquidacion(id) {
  return solicitar(`/api/comisiones/liquidaciones/${id}`, { metodo: 'DELETE' })
}
