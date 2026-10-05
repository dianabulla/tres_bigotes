import { useEffect, useRef } from 'react'

export default function CursorGlow() {
  const capa = useRef(null)

  useEffect(() => {
    const elemento = capa.current
    if (!elemento || window.matchMedia('(pointer: coarse)').matches) {
      return undefined
    }
    const mover = (evento) => {
      elemento.style.opacity = '1'
      elemento.style.transform = `translate(${evento.clientX}px, ${evento.clientY}px)`
    }
    window.addEventListener('pointermove', mover)
    return () => window.removeEventListener('pointermove', mover)
  }, [])

  return <div ref={capa} className="cursor-glow" aria-hidden="true" />
}
