ALTER TABLE resena
  ADD COLUMN correo VARCHAR(160) NULL AFTER autor,
  ADD COLUMN origen ENUM('admin', 'cliente') NOT NULL DEFAULT 'admin' AFTER calificacion,
  ADD COLUMN publicada TINYINT(1) NOT NULL DEFAULT 1 AFTER origen;

UPDATE resena
SET origen = 'admin', publicada = 1
WHERE origen = 'admin';
