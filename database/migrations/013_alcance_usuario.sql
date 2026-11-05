-- Alcance de cuenta: superadministrador (sin sede) ve todo;
-- el resto queda atado a un establecimiento.

ALTER TABLE usuario
  MODIFY establecimiento_id INT UNSIGNED NULL;

ALTER TABLE sesion
  ADD COLUMN establecimiento_activo_id INT UNSIGNED NULL AFTER usuario_id,
  ADD KEY idx_sesion_establecimiento_activo (establecimiento_activo_id),
  ADD CONSTRAINT fk_sesion_establecimiento_activo
    FOREIGN KEY (establecimiento_activo_id) REFERENCES establecimiento (id) ON DELETE RESTRICT;

INSERT INTO rol (codigo, nombre, sistema) VALUES
  ('superadministrador', 'Super administrador', 1);

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
INNER JOIN permiso p
WHERE r.codigo = 'superadministrador';

DELETE rp
FROM rol_permiso rp
INNER JOIN rol r ON r.id = rp.rol_id
INNER JOIN permiso p ON p.id = rp.permiso_id
WHERE r.codigo = 'administrador'
  AND p.codigo = 'sedes';

-- Cuenta de desarrollo / primer super. Clave: tresbigotes
INSERT INTO usuario (establecimiento_id, rol_id, nombre, correo, password_hash, activo)
SELECT NULL, r.id, 'Super administrador', 'super@tresbigotes.local',
       '$2y$10$/AkFgMo1vCOnYIJGmRj8Vudf5Vh138qclEGVV2MSA34voCshpV/2i', 1
FROM rol r
WHERE r.codigo = 'superadministrador'
  AND NOT EXISTS (
    SELECT 1 FROM usuario u WHERE u.correo = 'super@tresbigotes.local'
  );
