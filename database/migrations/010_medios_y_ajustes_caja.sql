USE tres_bigotes;

ALTER TABLE pago
  MODIFY medio ENUM('nequi', 'daviplata', 'qr', 'efectivo') NOT NULL;

ALTER TABLE arqueo_linea
  MODIFY medio ENUM('nequi', 'daviplata', 'qr', 'efectivo') NOT NULL;

CREATE TABLE movimiento_caja (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  turno_caja_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  tipo ENUM('ingreso', 'egreso') NOT NULL,
  medio ENUM('nequi', 'qr', 'efectivo') NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  motivo VARCHAR(180) NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_movimiento_caja_turno (turno_caja_id),
  KEY idx_movimiento_caja_establecimiento (establecimiento_id, creado_en),
  KEY idx_movimiento_caja_usuario (usuario_id),
  CONSTRAINT fk_movimiento_caja_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_caja_turno FOREIGN KEY (turno_caja_id) REFERENCES turno_caja (id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_caja_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE RESTRICT,
  CONSTRAINT chk_movimiento_caja_monto CHECK (monto > 0),
  CONSTRAINT chk_movimiento_caja_motivo CHECK (CHAR_LENGTH(TRIM(motivo)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
