USE tres_bigotes;

INSERT INTO rol (id, codigo) VALUES
  (1, 'administrador'),
  (2, 'recepcion'),
  (3, 'colaborador');

INSERT INTO establecimiento (id, nombre, direccion, telefono, activo) VALUES
  (1, 'Barbería Tres Bigotes', 'Sede principal', '3000000000', 1);

INSERT INTO usuario (id, establecimiento_id, rol_id, nombre, correo, password_hash, activo) VALUES
  (1, 1, 1, 'Administrador', 'admin@tresbigotes.local', '$2y$10$/AkFgMo1vCOnYIJGmRj8Vudf5Vh138qclEGVV2MSA34voCshpV/2i', 1),
  (2, 1, 2, 'Recepción', 'recepcion@tresbigotes.local', '$2y$10$/AkFgMo1vCOnYIJGmRj8Vudf5Vh138qclEGVV2MSA34voCshpV/2i', 1),
  (3, 1, 3, 'Barbero', 'barbero@tresbigotes.local', '$2y$10$/AkFgMo1vCOnYIJGmRj8Vudf5Vh138qclEGVV2MSA34voCshpV/2i', 1);

INSERT INTO colaborador (id, establecimiento_id, usuario_id, nombre, telefono, activo, fecha_ingreso) VALUES
  (1, 1, 3, 'Barbero', '3000000001', 1, '2026-10-05');
