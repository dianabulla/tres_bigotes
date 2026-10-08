<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Establecimiento
{
    public static function listar(): array
    {
        $consulta = Conexion::obtener()->query(
            'SELECT id, nombre, direccion, telefono, activo
             FROM establecimiento
             ORDER BY nombre, id'
        );
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function buscar(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, direccion, telefono, activo
             FROM establecimiento
             WHERE id = :id
             LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentar($fila) : null;
    }

    public static function crear(PDO $pdo, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO establecimiento (nombre, direccion, telefono, activo)
             VALUES (:nombre, :direccion, :telefono, 1)'
        );
        $insertar->execute([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'telefono' => $datos['telefono'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $id, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE establecimiento
             SET nombre = :nombre, direccion = :direccion, telefono = :telefono, activo = :activo
             WHERE id = :id'
        );
        $guardar->execute([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'telefono' => $datos['telefono'],
            'activo' => $datos['activo'] ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'direccion' => $fila['direccion'],
            'telefono' => $fila['telefono'],
            'activo' => (int) $fila['activo'] === 1,
        ];
    }
}
