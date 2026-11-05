export const cargasPagina = {
  reserva: () => import('./agenda/PaginaReserva'),
  agenda: () => import('./agenda/PaginaAgenda'),
  horarios: () => import('./horarios/PaginaHorarios'),
  clinica: () => import('./clinica/PaginaClinica'),
  colaboradores: () => import('./colaboradores/PaginaColaboradores'),
  comisiones: () => import('./colaboradores/PaginaComisiones'),
  inventario: () => import('./inventario/PaginaInventario'),
  caja: () => import('./caja/PaginaCaja'),
  servicios: () => import('./servicios/PaginaServicios'),
  sedes: () => import('./sedes/PaginaSedes'),
  usuarios: () => import('./usuarios/PaginaUsuarios'),
  roles: () => import('./roles/PaginaRoles'),
}

const permisoPorModulo = {
  agenda: ['agenda', 'agenda.propia'],
  caja: ['caja'],
  clinica: ['clinica', 'clinica.consulta', 'clinica.propia'],
  colaboradores: ['colaboradores'],
  comisiones: ['comisiones', 'comisiones.propias'],
  inventario: ['inventario', 'inventario.consulta'],
  horarios: ['horarios'],
  servicios: ['servicios'],
  sedes: ['sedes'],
  usuarios: ['usuarios'],
  roles: ['roles'],
}

export function prefetchPagina(id) {
  if (!id || !cargasPagina[id]) {
    return
  }
  void cargasPagina[id]()
}

/** Prefetch del primer módulo que verá el usuario al entrar a gestión. */
export function prefetchInicioGestion(permisos = []) {
  if (permisoPorModulo.agenda.some((codigo) => permisos.includes(codigo))) {
    prefetchPagina('agenda')
    return
  }
  const orden = [
    'caja',
    'clinica',
    'colaboradores',
    'comisiones',
    'inventario',
    'horarios',
    'servicios',
    'sedes',
    'usuarios',
    'roles',
  ]
  const primero = orden.find((id) => permisoPorModulo[id].some((codigo) => permisos.includes(codigo)))
  if (primero) {
    prefetchPagina(primero)
  }
}
