import { useEffect, useState } from 'react'
import { Alert, Badge, Button, Form, Modal, Spinner, Table } from 'react-bootstrap'
import { eliminarRol, guardarRol, listarRoles } from '../../cliente/roles'

const vacio = {
  id: null,
  nombre: '',
  permisos: [],
  bloqueados: [],
  sistema: false,
}

function agrupar(permisos) {
  const grupos = []
  permisos.forEach((permiso) => {
    let grupo = grupos.find((item) => item.nombre === permiso.grupo)
    if (!grupo) {
      grupo = { nombre: permiso.grupo, permisos: [] }
      grupos.push(grupo)
    }
    grupo.permisos.push(permiso)
  })
  return grupos
}

export default function PaginaRoles() {
  const [lista, setLista] = useState([])
  const [permisos, setPermisos] = useState([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [aviso, setAviso] = useState('')
  const [formulario, setFormulario] = useState(null)
  const [errorFormulario, setErrorFormulario] = useState('')
  const [guardando, setGuardando] = useState(false)

  async function cargar() {
    setError('')
    try {
      const datos = await listarRoles()
      setLista(datos.roles)
      setPermisos(datos.permisos)
    } catch (fallo) {
      setError(fallo.message)
    } finally {
      setCargando(false)
    }
  }

  useEffect(() => {
    cargar()
  }, [])

  function abrirNuevo() {
    setErrorFormulario('')
    setFormulario({ ...vacio, permisos: [] })
  }

  function abrirEdicion(rol) {
    setErrorFormulario('')
    setFormulario({
      id: rol.id,
      nombre: rol.nombre,
      permisos: [...rol.permisos],
      bloqueados: rol.bloqueados,
      sistema: rol.sistema,
    })
  }

  function cambiarPermiso(codigo, marcado) {
    setFormulario((actual) => {
      if (actual.bloqueados.includes(codigo)) {
        return actual
      }
      const siguiente = marcado
        ? [...actual.permisos, codigo]
        : actual.permisos.filter((item) => item !== codigo)
      return { ...actual, permisos: siguiente }
    })
  }

  async function guardar(evento) {
    evento.preventDefault()
    setErrorFormulario('')
    setGuardando(true)
    try {
      await guardarRol(formulario)
      setFormulario(null)
      setAviso('Rol guardado. Si cambiaste el rol con el que entraste, vuelve a entrar para ver el menú.')
      await cargar()
    } catch (fallo) {
      setErrorFormulario(fallo.message)
    } finally {
      setGuardando(false)
    }
  }

  async function borrar(rol) {
    setError('')
    setAviso('')
    try {
      await eliminarRol(rol.id)
      setAviso('Rol eliminado.')
      await cargar()
    } catch (fallo) {
      setError(fallo.message)
    }
  }

  const tituloDe = (codigo) => permisos.find((permiso) => permiso.codigo === codigo)?.titulo ?? codigo

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 className="h3 mb-0">Roles</h1>
        <Button onClick={abrirNuevo}>Nuevo rol</Button>
      </div>
      <p className="text-secondary">
        Cada rol lleva los permisos que marques. Los de agenda, ficha o comisiones propias solo aplican si la persona está en Colaboradores.
        Administrador, recepción y colaborador no se pueden borrar.
      </p>
      {error && <Alert variant="danger">{error}</Alert>}
      {aviso && (
        <Alert variant="light" className="border border-dark text-black" onClose={() => setAviso('')} dismissible>
          {aviso}
        </Alert>
      )}
      {cargando ? (
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando roles</span>
        </Spinner>
      ) : (
        <Table responsive hover>
          <thead>
            <tr>
              <th>Rol</th>
              <th>Permisos</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {lista.map((rol) => (
              <tr key={rol.id}>
                <td>
                  {rol.nombre}
                  {rol.sistema && <Badge bg="dark" className="ms-2 marca-activo">Sistema</Badge>}
                </td>
                <td>{rol.permisos.map(tituloDe).join(', ')}</td>
                <td className="text-end text-nowrap">
                  <Button size="sm" variant="outline-primary" className="me-2" onClick={() => abrirEdicion(rol)}>
                    Editar
                  </Button>
                  {!rol.sistema && (
                    <Button size="sm" variant="outline-primary" onClick={() => borrar(rol)}>
                      Eliminar
                    </Button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      <Modal show={formulario !== null} onHide={() => setFormulario(null)} centered scrollable>
        <Form onSubmit={guardar}>
          <Modal.Header closeButton>
            <Modal.Title>{formulario?.id ? 'Editar rol' : 'Nuevo rol'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {errorFormulario && <Alert variant="danger">{errorFormulario}</Alert>}
            <Form.Group className="mb-3" controlId="nombreRol">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                required
                maxLength={80}
                value={formulario?.nombre ?? ''}
                onChange={(evento) => setFormulario((actual) => ({ ...actual, nombre: evento.target.value }))}
              />
            </Form.Group>
            {agrupar(permisos).map((grupo) => (
              <fieldset key={grupo.nombre} className="mb-3">
                <legend className="h6">{grupo.nombre}</legend>
                {grupo.permisos.map((permiso) => {
                  const bloqueado = (formulario?.bloqueados ?? []).includes(permiso.codigo)
                  const marcado = bloqueado || (formulario?.permisos ?? []).includes(permiso.codigo)
                  return (
                    <Form.Check
                      key={permiso.codigo}
                      id={`permiso-${permiso.codigo}`}
                      label={permiso.titulo}
                      checked={marcado}
                      disabled={bloqueado}
                      onChange={(evento) => cambiarPermiso(permiso.codigo, evento.target.checked)}
                    />
                  )
                })}
              </fieldset>
            ))}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" type="button" onClick={() => setFormulario(null)}>
              Cancelar
            </Button>
            <Button type="submit" disabled={guardando}>
              {guardando ? 'Guardando…' : 'Guardar'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </>
  )
}
