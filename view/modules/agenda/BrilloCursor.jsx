import { useEffect, useRef } from 'react'

export default function BrilloCursor() {
  const capa = useRef(null)

  useEffect(() => {
    const el = capa.current
    if (!el || window.matchMedia('(pointer: coarse)').matches) {
      return undefined
    }

    let targetX = window.innerWidth / 2
    let targetY = window.innerHeight / 2
    let x = targetX
    let y = targetY
    let rafId = 0

    const onMove = (evento) => {
      targetX = evento.clientX
      targetY = evento.clientY
    }

    const loop = () => {
      x += (targetX - x) * 0.09
      y += (targetY - y) * 0.09
      el.style.transform = `translate3d(${x}px, ${y}px, 0) translate(-50%, -50%)`
      rafId = requestAnimationFrame(loop)
    }

    window.addEventListener('pointermove', onMove, { passive: true })
    rafId = requestAnimationFrame(loop)

    return () => {
      window.removeEventListener('pointermove', onMove)
      cancelAnimationFrame(rafId)
    }
  }, [])

  return <div ref={capa} className="brillo-cursor" aria-hidden="true" />
}
