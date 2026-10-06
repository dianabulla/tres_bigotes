export const modulos = [
  {
    id: 'colaboradores',
    titulo: 'Colaboradores',
    ruta: '/gestion/colaboradores',
    roles: ['administrador'],
  },
  {
    id: 'servicios',
    titulo: 'Servicios',
    ruta: '/gestion/servicios',
    roles: ['administrador'],
  },
  {
    id: 'agenda',
    titulo: 'Agenda',
    ruta: '/gestion/agenda',
    roles: ['administrador', 'recepcion', 'colaborador'],
  },
  {
    id: 'clinica',
    titulo: 'Ficha clínica',
    ruta: '/gestion/clinica',
    roles: ['administrador', 'recepcion', 'colaborador'],
  },
  {
    id: 'comisiones',
    titulo: 'Comisiones',
    ruta: '/gestion/comisiones',
    roles: ['administrador', 'colaborador'],
  },
  {
    id: 'inventario',
    titulo: 'Inventario',
    ruta: '/gestion/inventario',
    roles: ['administrador', 'recepcion'],
  },
  {
    id: 'caja',
    titulo: 'Caja',
    ruta: '/gestion/caja',
    roles: ['administrador', 'recepcion'],
  },
]

export function modulosVisibles(rol) {
  return modulos.filter((modulo) => modulo.roles.includes(rol))
}
