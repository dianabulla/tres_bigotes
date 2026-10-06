import { useEffect, useRef } from 'react'

const alAzar = (minimo, maximo) => minimo + Math.random() * (maximo - minimo)

export default function Particulas() {
  const lienzo = useRef(null)

  useEffect(() => {
    const canvas = lienzo.current
    const ctx = canvas.getContext('2d')
    if (!ctx) {
      return undefined
    }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return undefined
    }

    let rafId = 0
    let width = 0
    let height = 0
    let particles = []
    let t = 0

    const spawn = () => ({
      x: Math.random(),
      y: 1 + Math.random() * 0.25,
      r: alAzar(0.6, 2.1),
      speed: alAzar(0.0004, 0.0013),
      drift: alAzar(-0.004, 0.004),
      phase: Math.random() * Math.PI * 2,
      alpha: alAzar(0.12, 0.55),
      flicker: alAzar(2, 6),
    })

    const resize = () => {
      const dpr = Math.min(window.devicePixelRatio || 1, 2)
      width = canvas.clientWidth
      height = canvas.clientHeight
      canvas.width = Math.max(1, Math.floor(width * dpr))
      canvas.height = Math.max(1, Math.floor(height * dpr))
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
      const count = width < 720 ? 24 : 56
      particles = Array.from({ length: count }, spawn)
    }

    const frame = () => {
      t += 1
      ctx.clearRect(0, 0, width, height)

      for (const p of particles) {
        p.y -= p.speed
        if (p.y < -0.06) {
          Object.assign(p, spawn(), { y: 1.05 })
        }

        const wave = Math.sin(t * 0.008 + p.phase) * 0.012
        const x = (p.x + wave + p.drift * Math.sin(t * 0.003 + p.phase)) * width
        const y = p.y * height
        const pulse = 0.55 + 0.45 * Math.sin(t * 0.05 * p.flicker + p.phase)
        const alpha = p.alpha * pulse

        ctx.beginPath()
        ctx.fillStyle = `rgba(233, 207, 107, ${alpha * 0.22})`
        ctx.arc(x, y, p.r * 3.4, 0, Math.PI * 2)
        ctx.fill()

        ctx.beginPath()
        ctx.fillStyle = `rgba(255, 232, 158, ${alpha})`
        ctx.arc(x, y, p.r, 0, Math.PI * 2)
        ctx.fill()
      }

      rafId = requestAnimationFrame(frame)
    }

    resize()
    frame()
    window.addEventListener('resize', resize)

    return () => {
      cancelAnimationFrame(rafId)
      window.removeEventListener('resize', resize)
    }
  }, [])

  return <canvas ref={lienzo} className="particles" aria-hidden="true" />
}
