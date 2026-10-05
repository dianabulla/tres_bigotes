ALTER TABLE pago
  MODIFY medio ENUM('nequi', 'daviplata', 'qr') NOT NULL;

ALTER TABLE arqueo_linea
  MODIFY medio ENUM('nequi', 'daviplata', 'qr') NOT NULL;
