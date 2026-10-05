<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Servicio
{
    public static function listar(int $sede, bool $soloActivos): array
    {
        $sql = 'SELECT id, nombre, duracion_minutos, precio, activo
                FROM servicio
                WHERE establecimiento_id = :sede';
        if ($soloActivos) {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY nombre, id';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function bloquear(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, duracion_minutos, precio, activo
             FROM servicio
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO servicio (establecimiento_id, nombre, duracion_minutos, precio, activo)
             VALUES (:sede, :nombre, :duracion, :precio, 1)'
        );
        $insertar->execute([
            'sede' => $sede,
            'nombre' => $datos['nombre'],
            'duracion' => $datos['duracion_minutos'],
            'precio' => $datos['precio'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $sede, int $id, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE servicio
             SET nombre = :nombre, duracion_minutos = :duracion, precio = :precio, activo = :activo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'nombre' => $datos['nombre'],
            'duracion' => $datos['duracion_minutos'],
            'precio' => $datos['precio'],
            'activo' => $datos['activo'] ? 1 : 0,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'duracion_minutos' => (int) $fila['duracion_minutos'],
            'precio' => $fila['precio'],
            'activo' => (int) $fila['activo'] === 1,
        ];
    }
}
