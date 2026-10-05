import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { guardarToken, leerToken } from '../cliente/http'
import { cerrarSesion, iniciarSesion, leerSesion } from '../cliente/sesion'

const Contexto = createContext(null)

export function ProveedorSesion({ children }) {
  const navigate = useNavigate()
  const [usuario, setUsuario] = useState(null)
  const [cargando, setCargando] = useState(Boolean(leerToken()))

  useEffect(() => {
    if (!leerToken()) {
      setCargando(false)
      return
    }
    leerSesion()
      .then((datos) => setUsuario(datos.usuario))
      .catch(() => {
        guardarToken(null)
        setUsuario(null)
      })
      .finally(() => setCargando(false))
  }, [])

  const valor = useMemo(() => ({
    usuario,
    cargando,
    async entrar(correo, clave) {
      const datos = await iniciarSesion(correo, clave)
      guardarToken(datos.token)
      setUsuario(datos.usuario)
      navigate('/gestion', { replace: true })
    },
    async salir() {
      try {
        await cerrarSesion()
      } catch {
        guardarToken(null)
      }
      guardarToken(null)
      setUsuario(null)
      navigate('/ingreso', { replace: true })
    },
  }), [usuario, cargando, navigate])

  return <Contexto.Provider value={valor}>{children}</Contexto.Provider>
}

export function useSesion() {
  const contexto = useContext(Contexto)
  if (!contexto) {
    throw new Error('useSesion debe usarse dentro de ProveedorSesion')
  }
  return contexto
}
