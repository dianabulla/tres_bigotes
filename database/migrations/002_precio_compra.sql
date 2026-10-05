ALTER TABLE producto
  ADD COLUMN precio_compra DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tipo,
  ADD CONSTRAINT chk_producto_compra CHECK (precio_compra >= 0);

ALTER TABLE producto
  ALTER precio_compra DROP DEFAULT;
