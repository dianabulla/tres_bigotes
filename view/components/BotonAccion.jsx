import { Button } from 'react-bootstrap'
import Icono from './Iconos'

export default function BotonAccion({
  icono,
  titulo,
  variant = 'outline-primary',
  size = 'sm',
  type = 'button',
  className = '',
  children,
  ...props
}) {
  return (
    <Button
      type={type}
      size={size}
      variant={variant}
      className={`btn-accion ${className}`.trim()}
      title={titulo}
      aria-label={titulo}
      {...props}
    >
      <Icono nombre={icono} />
      {children ? <span className="visually-hidden">{children}</span> : null}
    </Button>
  )
}

export function GrupoAcciones({ children, className = '' }) {
  return <div className={`acciones ${className}`.trim()}>{children}</div>
}
