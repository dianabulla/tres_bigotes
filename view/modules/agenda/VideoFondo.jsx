import { useEffect, useRef } from 'react'

export default function VideoFondo({ src, poster }) {
  const video = useRef(null)

  useEffect(() => {
    const el = video.current
    if (!el) {
      return undefined
    }

    let cancelado = false
    let idleId = 0
    let timeoutId = 0

    const activar = () => {
      if (cancelado || el.dataset.cargado === '1') {
        return
      }
      el.dataset.cargado = '1'
      el.src = src
      el.load()
      const intento = el.play()
      if (intento && typeof intento.catch === 'function') {
        intento.catch(() => {})
      }
    }

    const programar = () => {
      if (typeof window.requestIdleCallback === 'function') {
        idleId = window.requestIdleCallback(activar, { timeout: 1200 })
      } else {
        timeoutId = window.setTimeout(activar, 200)
      }
    }

    const observador = new IntersectionObserver((entradas) => {
      if (entradas.some((entrada) => entrada.isIntersecting)) {
        observador.disconnect()
        programar()
      }
    }, { rootMargin: '120px' })

    observador.observe(el)

    return () => {
      cancelado = true
      observador.disconnect()
      if (idleId && typeof window.cancelIdleCallback === 'function') {
        window.cancelIdleCallback(idleId)
      }
      if (timeoutId) {
        window.clearTimeout(timeoutId)
      }
    }
  }, [src])

  return (
    <video
      ref={video}
      className="bg__video"
      poster={poster}
      muted
      loop
      playsInline
      preload="none"
      aria-hidden="true"
    />
  )
}
