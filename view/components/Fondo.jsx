export default function Fondo() {
  return (
    <div className="fondo" aria-hidden="true">
      <video className="fondo-video" autoPlay muted loop playsInline>
        <source src="/media/fondo.mp4" type="video/mp4" />
      </video>
      <div className="fondo-velo" />
    </div>
  )
}
