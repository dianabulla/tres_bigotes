ALTER TABLE rol
  ADD COLUMN nombre VARCHAR(80) NOT NULL DEFAULT '' AFTER codigo,
  ADD COLUMN sistema TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre;

UPDATE rol SET nombre = 'Administrador', sistema = 1 WHERE codigo = 'administrador';
UPDATE rol SET nombre = 'Recepción', sistema = 1 WHERE codigo = 'recepcion';
UPDATE rol SET nombre = 'Colaborador', sistema = 1 WHERE codigo = 'colaborador';

CREATE TABLE permiso (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  titulo VARCHAR(80) NOT NULL,
  grupo VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_permiso_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permiso (
  rol_id INT UNSIGNED NOT NULL,
  permiso_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE RESTRICT,
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permiso (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permiso (codigo, titulo, grupo) VALUES
  ('sedes', 'Administrar sedes', 'Sedes'),
  ('usuarios', 'Administrar usuarios', 'Usuarios'),
  ('roles', 'Crear roles y asignar permisos', 'Usuarios'),
  ('colaboradores', 'Administrar colaboradores', 'Personal'),
  ('servicios', 'Administrar el catálogo de servicios', 'Agenda'),
  ('agenda', 'Ver toda la agenda, reservar y cancelar', 'Agenda'),
  ('agenda.propia', 'Ver y atender solo la agenda propia', 'Agenda'),
  ('clinica', 'Ver y editar todas las fichas', 'Clínica'),
  ('clinica.consulta', 'Consultar fichas', 'Clínica'),
  ('clinica.propia', 'Ver y anotar las fichas de sus citas', 'Clínica'),
  ('comisiones', 'Administrar reglas y liquidaciones', 'Comisiones'),
  ('comisiones.propias', 'Consultar las comisiones propias', 'Comisiones'),
  ('inventario', 'Administrar inventario', 'Inventario'),
  ('inventario.consulta', 'Consultar inventario', 'Inventario'),
  ('caja', 'Abrir, cobrar y cerrar la caja', 'Caja');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
INNER JOIN permiso p ON p.codigo IN (
  'sedes', 'usuarios', 'roles', 'colaboradores', 'servicios',
  'agenda', 'clinica', 'comisiones', 'inventario', 'caja'
)
WHERE r.codigo = 'administrador';

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
INNER JOIN permiso p ON p.codigo IN ('agenda', 'clinica.consulta', 'inventario.consulta', 'caja')
WHERE r.codigo = 'recepcion';

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
INNER JOIN permiso p ON p.codigo IN ('agenda.propia', 'clinica.propia', 'comisiones.propias')
WHERE r.codigo = 'colaborador';
