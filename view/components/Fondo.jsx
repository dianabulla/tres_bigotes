import VideoFondo from '../modules/agenda/VideoFondo'

export default function Fondo() {
  const medio = import.meta.env.BASE_URL
  return (
    <div className="fondo" aria-hidden="true">
      <VideoFondo src={`${medio}media/fondo-opt.mp4`} poster={`${medio}media/fondo-poster.jpg`} />
      <div className="fondo-velo" />
    </div>
  )
}
