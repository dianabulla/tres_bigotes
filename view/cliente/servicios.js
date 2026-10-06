import { solicitar } from './http'

export function listarServicios() {
  return solicitar('/api/servicios')
}

export function guardarServicio(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    categoria: datos.categoria,
    duracion_minutos: datos.duracion_minutos,
    precio: datos.precio,
    insumos: (datos.insumos ?? []).map((linea) => ({
      producto_id: Number(linea.producto_id),
      cantidad: Number(linea.cantidad),
    })),
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/servicios/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/servicios', { metodo: 'POST', cuerpo })
}
