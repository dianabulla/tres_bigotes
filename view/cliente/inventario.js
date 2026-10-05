import { solicitar } from './http'

export function listarProductos() {
  return solicitar('/api/productos')
}

export function guardarProducto(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    tipo: datos.tipo,
    precio_compra: datos.precio_compra,
    precio_venta: datos.tipo === 'venta' ? datos.precio_venta : '',
    stock_minimo: datos.stock_minimo,
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/productos/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  cuerpo.stock_inicial = datos.stock_inicial
  return solicitar('/api/productos', { metodo: 'POST', cuerpo })
}

export function moverProducto(id, datos) {
  return solicitar(`/api/productos/${id}/movimientos`, { metodo: 'POST', cuerpo: datos })
}

export function listarMovimientos(id) {
  return solicitar(`/api/productos/${id}/movimientos`)
}
