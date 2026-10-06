ALTER TABLE servicio
  ADD COLUMN categoria VARCHAR(60) NOT NULL DEFAULT 'General' AFTER nombre;

ALTER TABLE servicio
  ALTER categoria DROP DEFAULT;
