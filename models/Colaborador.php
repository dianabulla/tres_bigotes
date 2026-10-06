<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Colaborador
{
    public static function listar(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT c.id, c.nombre, c.foto, c.telefono, c.fecha_ingreso, c.activo, c.usuario_id, u.correo
             FROM colaborador c
             LEFT JOIN usuario u ON u.id = c.usuario_id
             WHERE c.establecimiento_id = :sede
             ORDER BY c.nombre, c.id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function buscar(int $sede, int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT c.id, c.nombre, c.foto, c.telefono, c.fecha_ingreso, c.activo, c.usuario_id, u.correo
             FROM colaborador c
             LEFT JOIN usuario u ON u.id = c.usuario_id
             WHERE c.id = :id AND c.establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function crear(PDO $pdo, int $sede, array $datos, ?int $usuarioId): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO colaborador (establecimiento_id, usuario_id, nombre, telefono, activo, fecha_ingreso)
             VALUES (:sede, :usuario_id, :nombre, :telefono, 1, :fecha_ingreso)'
        );
        $insertar->execute([
            'sede' => $sede,
            'usuario_id' => $usuarioId,
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'fecha_ingreso' => $datos['fecha_ingreso'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $sede, int $id, array $datos, ?int $usuarioId): void
    {
        $guardar = $pdo->prepare(
            'UPDATE colaborador
             SET usuario_id = :usuario_id, nombre = :nombre, telefono = :telefono,
                 activo = :activo, fecha_ingreso = :fecha_ingreso
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'usuario_id' => $usuarioId,
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'activo' => $datos['activo'] ? 1 : 0,
            'fecha_ingreso' => $datos['fecha_ingreso'],
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function guardarFoto(PDO $pdo, int $sede, int $id, string $foto): void
    {
        $guardar = $pdo->prepare(
            'UPDATE colaborador SET foto = :foto WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'foto' => $foto,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'foto' => $fila['foto'],
            'telefono' => $fila['telefono'],
            'fecha_ingreso' => $fila['fecha_ingreso'],
            'activo' => (int) $fila['activo'] === 1,
            'usuario_id' => $fila['usuario_id'] !== null ? (int) $fila['usuario_id'] : null,
            'correo' => $fila['correo'],
        ];
    }
}
