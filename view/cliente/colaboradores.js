import { leerToken, solicitar } from './http'

export function listarColaboradores() {
  return solicitar('/api/colaboradores')
}

export function guardarColaborador(datos) {
  const cuerpo = {
    nombre: datos.nombre,
    telefono: datos.telefono,
    fecha_ingreso: datos.fecha_ingreso,
    correo: datos.correo,
    clave: datos.clave,
  }
  if (datos.id) {
    cuerpo.activo = datos.activo
    return solicitar(`/api/colaboradores/${datos.id}`, { metodo: 'PUT', cuerpo })
  }
  return solicitar('/api/colaboradores', { metodo: 'POST', cuerpo })
}

export async function subirFotoColaborador(id, archivo) {
  const datos = new FormData()
  datos.append('foto', archivo)
  const base = import.meta.env.VITE_API_URL ?? ''
  const respuesta = await fetch(`${base}/api/colaboradores/${id}/foto`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${leerToken() ?? ''}`,
    },
    body: datos,
  })
  const cuerpo = await respuesta.json().catch(() => ({}))
  if (!respuesta.ok) {
    throw new Error(cuerpo.error || 'No se pudo guardar la foto')
  }
  return cuerpo
}

export function listarResenas() {
  return solicitar('/api/resenas')
}

export function crearResena(datos) {
  return solicitar('/api/resenas', { metodo: 'POST', cuerpo: datos })
}

export function eliminarResena(id) {
  return solicitar(`/api/resenas/${id}`, { metodo: 'DELETE' })
}
