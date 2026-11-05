INSERT INTO permiso (codigo, titulo, grupo) VALUES
  ('horarios', 'Gestionar horarios y bloqueos de agenda', 'Agenda');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM rol r
INNER JOIN permiso p ON p.codigo = 'horarios'
WHERE r.codigo IN ('administrador', 'recepcion');

CREATE TABLE horario_trabajo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  colaborador_id INT UNSIGNED NULL,
  dia_semana TINYINT UNSIGNED NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_horario_sede_dia (establecimiento_id, dia_semana),
  KEY idx_horario_colaborador (establecimiento_id, colaborador_id, dia_semana),
  CONSTRAINT fk_horario_trabajo_sede FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_horario_trabajo_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT,
  CONSTRAINT chk_horario_trabajo_dia CHECK (dia_semana BETWEEN 1 AND 7),
  CONSTRAINT chk_horario_trabajo_rango CHECK (hora_fin > hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bloqueo_agenda (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  colaborador_id INT UNSIGNED NULL,
  titulo VARCHAR(120) NOT NULL,
  dia_semana TINYINT UNSIGNED NULL,
  fecha DATE NULL,
  hora_inicio TIME NULL,
  hora_fin TIME NULL,
  PRIMARY KEY (id),
  KEY idx_bloqueo_sede_fecha (establecimiento_id, fecha),
  KEY idx_bloqueo_sede_dia (establecimiento_id, dia_semana),
  KEY idx_bloqueo_colaborador (establecimiento_id, colaborador_id),
  CONSTRAINT fk_bloqueo_agenda_sede FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_bloqueo_agenda_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT,
  CONSTRAINT chk_bloqueo_agenda_alcance CHECK (
    (fecha IS NOT NULL AND dia_semana IS NULL)
    OR (fecha IS NULL AND dia_semana IS NOT NULL AND dia_semana BETWEEN 1 AND 7)
  ),
  CONSTRAINT chk_bloqueo_agenda_horas CHECK (
    (hora_inicio IS NULL AND hora_fin IS NULL)
    OR (hora_inicio IS NOT NULL AND hora_fin IS NOT NULL AND hora_fin > hora_inicio)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO horario_trabajo (establecimiento_id, colaborador_id, dia_semana, hora_inicio, hora_fin)
SELECT e.id, NULL, d.dia, '08:00:00', '20:00:00'
FROM establecimiento e
CROSS JOIN (
  SELECT 1 AS dia UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
) AS d;
