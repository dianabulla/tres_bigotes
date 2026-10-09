UPDATE establecimiento
SET nombre = REPLACE(nombre, 'Tres Bigotes', '3 Bigotes')
WHERE nombre LIKE '%Tres Bigotes%';

UPDATE establecimiento
SET nombre = REPLACE(nombre, 'tres bigotes', '3 bigotes')
WHERE nombre LIKE '%tres bigotes%';
