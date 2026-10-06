<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Resena
{
    public static function listar(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, autor, texto, calificacion, creado_en
             FROM resena
             WHERE establecimiento_id = :sede
             ORDER BY creado_en DESC, id DESC'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO resena (establecimiento_id, autor, texto, calificacion, creado_en)
             VALUES (:sede, :autor, :texto, :nota, :creado)'
        );
        $insertar->execute([
            'sede' => $sede,
            'autor' => $datos['autor'],
            'texto' => $datos['texto'],
            'nota' => $datos['calificacion'],
            'creado' => $datos['creado_en'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function eliminar(PDO $pdo, int $sede, int $id): bool
    {
        $borrar = $pdo->prepare(
            'DELETE FROM resena WHERE id = :id AND establecimiento_id = :sede'
        );
        $borrar->execute(['id' => $id, 'sede' => $sede]);
        return $borrar->rowCount() > 0;
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'autor' => $fila['autor'],
            'texto' => $fila['texto'],
            'calificacion' => (int) $fila['calificacion'],
            'creado_en' => $fila['creado_en'],
        ];
    }
}
