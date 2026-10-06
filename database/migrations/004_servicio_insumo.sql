CREATE TABLE servicio_insumo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_servicio_insumo (servicio_id, producto_id),
  KEY idx_servicio_insumo_producto (producto_id),
  CONSTRAINT fk_servicio_insumo_servicio FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE RESTRICT,
  CONSTRAINT fk_servicio_insumo_producto FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT,
  CONSTRAINT chk_servicio_insumo_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
