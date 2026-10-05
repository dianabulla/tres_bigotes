const CLAVE_TOKEN = 'tres_bigotes_token'

export function leerToken() {
  return sessionStorage.getItem(CLAVE_TOKEN)
}

export function guardarToken(token) {
  if (token) {
    sessionStorage.setItem(CLAVE_TOKEN, token)
    return
  }
  sessionStorage.removeItem(CLAVE_TOKEN)
}

export async function solicitar(ruta, { metodo = 'GET', cuerpo, publica = false } = {}) {
  const headers = { Accept: 'application/json' }
  if (cuerpo !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  if (!publica) {
    const token = leerToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }
  }

  const base = import.meta.env.VITE_API_URL ?? ''
  const respuesta = await fetch(`${base}${ruta}`, {
    method: metodo,
    headers,
    body: cuerpo !== undefined ? JSON.stringify(cuerpo) : undefined,
  })

  if (respuesta.status === 204) {
    return null
  }

  const datos = await respuesta.json().catch(() => ({}))
  if (!respuesta.ok) {
    const error = new Error(datos.error || 'No se pudo completar la solicitud')
    error.estado = respuesta.status
    throw error
  }
  return datos
}
