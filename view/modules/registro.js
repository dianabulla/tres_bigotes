export const modulos = [
  {
    id: 'colaboradores',
    titulo: 'Colaboradores',
    ruta: '/gestion/colaboradores',
    permisos: ['colaboradores'],
  },
  {
    id: 'sedes',
    titulo: 'Sedes',
    ruta: '/gestion/sedes',
    permisos: ['sedes'],
  },
  {
    id: 'usuarios',
    titulo: 'Usuarios',
    ruta: '/gestion/usuarios',
    permisos: ['usuarios'],
  },
  {
    id: 'roles',
    titulo: 'Roles',
    ruta: '/gestion/roles',
    permisos: ['roles'],
  },
  {
    id: 'servicios',
    titulo: 'Servicios',
    ruta: '/gestion/servicios',
    permisos: ['servicios'],
  },
  {
    id: 'agenda',
    titulo: 'Agenda',
    ruta: '/gestion/agenda',
    permisos: ['agenda', 'agenda.propia'],
  },
  {
    id: 'clinica',
    titulo: 'Ficha clínica',
    ruta: '/gestion/clinica',
    permisos: ['clinica', 'clinica.consulta', 'clinica.propia'],
  },
  {
    id: 'comisiones',
    titulo: 'Comisiones',
    ruta: '/gestion/comisiones',
    permisos: ['comisiones', 'comisiones.propias'],
  },
  {
    id: 'inventario',
    titulo: 'Inventario',
    ruta: '/gestion/inventario',
    permisos: ['inventario', 'inventario.consulta'],
  },
  {
    id: 'caja',
    titulo: 'Caja',
    ruta: '/gestion/caja',
    permisos: ['caja'],
  },
]

export function tienePermiso(usuario, codigo) {
  return (usuario?.permisos ?? []).includes(codigo)
}

export function modulosVisibles(permisos) {
  const lista = permisos ?? []
  return modulos.filter((modulo) => modulo.permisos.some((codigo) => lista.includes(codigo)))
}
