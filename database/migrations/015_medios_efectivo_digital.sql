USE tres_bigotes;

ALTER TABLE pago
  MODIFY medio ENUM('nequi', 'daviplata', 'qr', 'efectivo', 'bancolombia', 'tarjeta', 'digital') NOT NULL;

ALTER TABLE arqueo_linea
  MODIFY medio ENUM('nequi', 'daviplata', 'qr', 'efectivo', 'bancolombia', 'tarjeta', 'digital') NOT NULL;

ALTER TABLE movimiento_caja
  MODIFY medio ENUM('nequi', 'daviplata', 'qr', 'efectivo', 'digital') NOT NULL;

INSERT INTO pago (venta_id, medio, monto)
SELECT venta_id, 'digital', SUM(monto)
FROM pago
WHERE medio IN ('nequi', 'daviplata', 'qr', 'bancolombia', 'tarjeta')
GROUP BY venta_id;

DELETE FROM pago
WHERE medio IN ('nequi', 'daviplata', 'qr', 'bancolombia', 'tarjeta');

INSERT INTO arqueo_linea (turno_caja_id, medio, monto_sistema, monto_reportado, diferencia)
SELECT
  turno_caja_id,
  'digital',
  SUM(monto_sistema),
  SUM(monto_reportado),
  SUM(monto_reportado) - SUM(monto_sistema)
FROM arqueo_linea
WHERE medio IN ('nequi', 'daviplata', 'qr', 'bancolombia', 'tarjeta')
GROUP BY turno_caja_id;

DELETE FROM arqueo_linea
WHERE medio IN ('nequi', 'daviplata', 'qr', 'bancolombia', 'tarjeta');

UPDATE movimiento_caja
SET medio = 'digital'
WHERE medio IN ('nequi', 'daviplata', 'qr');

ALTER TABLE pago
  MODIFY medio ENUM('efectivo', 'digital') NOT NULL;

ALTER TABLE arqueo_linea
  MODIFY medio ENUM('efectivo', 'digital') NOT NULL;

ALTER TABLE movimiento_caja
  MODIFY medio ENUM('efectivo', 'digital') NOT NULL;
