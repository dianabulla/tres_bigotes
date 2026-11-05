-- Orden de presentación del catálogo (reserva pública y gestión).

ALTER TABLE servicio
  ADD COLUMN orden INT UNSIGNED NOT NULL DEFAULT 100 AFTER categoria,
  ADD KEY idx_servicio_orden (establecimiento_id, orden, nombre);

-- Orden pedido: corte, ritual barba, cejas, spa facial, barba expres, combo al final.
UPDATE servicio
SET orden = CASE
  WHEN nombre LIKE '%Corte%' AND nombre NOT LIKE '%combo%' AND nombre NOT LIKE '%Experiencia%' THEN 10
  WHEN nombre LIKE '%ritual%' OR nombre LIKE '%Ritual%' THEN 20
  WHEN nombre LIKE '%ceja%' OR nombre LIKE '%Ceja%' THEN 30
  WHEN nombre LIKE '%Spa facial%' OR nombre LIKE '%spa facial%' THEN 40
  WHEN nombre LIKE '%express%' OR nombre LIKE '%expres%' THEN 50
  WHEN nombre LIKE '%combo%' OR nombre LIKE '%Experiencia%' THEN 60
  ELSE orden
END;
