ALTER TABLE colaborador
  ADD COLUMN foto VARCHAR(160) NULL AFTER nombre;

CREATE TABLE resena (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  autor VARCHAR(80) NOT NULL,
  texto VARCHAR(400) NOT NULL,
  calificacion TINYINT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_resena_sede (establecimiento_id, creado_en),
  CONSTRAINT fk_resena_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT chk_resena_nota CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
