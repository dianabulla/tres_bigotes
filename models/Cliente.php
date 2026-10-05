<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Cliente
{
    public static function buscar(int $sede, int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, telefono, correo
             FROM cliente
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentar($fila) : null;
    }

    public static function buscarTelefono(PDO $pdo, int $sede, string $telefono): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, telefono, correo
             FROM cliente
             WHERE establecimiento_id = :sede AND telefono = :telefono
             LIMIT 1'
        );
        $consulta->execute(['sede' => $sede, 'telefono' => $telefono]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentar($fila) : null;
    }

    public static function buscarTexto(int $sede, string $texto): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, telefono, correo
             FROM cliente
             WHERE establecimiento_id = :sede
               AND (nombre LIKE :texto OR telefono LIKE :telefono)
             ORDER BY nombre, id
             LIMIT 15'
        );
        $consulta->execute([
            'sede' => $sede,
            'texto' => '%' . $texto . '%',
            'telefono' => '%' . $texto . '%',
        ]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO cliente (establecimiento_id, nombre, telefono, correo, creado_en)
             VALUES (:sede, :nombre, :telefono, :correo, :creado_en)'
        );
        $insertar->execute([
            'sede' => $sede,
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'correo' => $datos['correo'],
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarContacto(PDO $pdo, int $sede, int $id, string $nombre, ?string $correo): void
    {
        $guardar = $pdo->prepare(
            'UPDATE cliente
             SET nombre = :nombre, correo = :correo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'nombre' => $nombre,
            'correo' => $correo,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'telefono' => $fila['telefono'],
            'correo' => $fila['correo'],
        ];
    }
}
