<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Rol
{
    public static function listar(): array
    {
        $consulta = Conexion::obtener()->query(
            'SELECT id, codigo, nombre, sistema FROM rol ORDER BY sistema DESC, nombre, id'
        );
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function opciones(): array
    {
        $opciones = [];
        foreach (self::listar() as $rol) {
            $opciones[] = [
                'codigo' => $rol['codigo'],
                'nombre' => $rol['nombre'],
            ];
        }
        return $opciones;
    }

    public static function buscar(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, codigo, nombre, sistema FROM rol WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentar($fila) : null;
    }

    public static function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, codigo, nombre, sistema FROM rol WHERE codigo = :codigo LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentar($fila) : null;
    }

    public static function crear(PDO $pdo, string $codigo, string $nombre): int
    {
        $insertar = $pdo->prepare('INSERT INTO rol (codigo, nombre, sistema) VALUES (:codigo, :nombre, 0)');
        $insertar->execute(['codigo' => $codigo, 'nombre' => $nombre]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $id, string $nombre): void
    {
        $guardar = $pdo->prepare('UPDATE rol SET nombre = :nombre WHERE id = :id');
        $guardar->execute(['nombre' => $nombre, 'id' => $id]);
    }

    public static function eliminar(PDO $pdo, int $id): void
    {
        $permisos = $pdo->prepare('DELETE FROM rol_permiso WHERE rol_id = :id');
        $permisos->execute(['id' => $id]);
        $rol = $pdo->prepare('DELETE FROM rol WHERE id = :id AND sistema = 0');
        $rol->execute(['id' => $id]);
    }

    public static function contarUsuarios(int $id): int
    {
        $consulta = Conexion::obtener()->prepare('SELECT COUNT(*) AS total FROM usuario WHERE rol_id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();
        return is_array($fila) ? (int) $fila['total'] : 0;
    }

    public static function presentar(array $fila): array
    {
        $id = (int) $fila['id'];
        return [
            'id' => $id,
            'codigo' => $fila['codigo'],
            'nombre' => $fila['nombre'] !== '' ? $fila['nombre'] : $fila['codigo'],
            'sistema' => (int) $fila['sistema'] === 1,
            'permisos' => Permiso::codigosDeRol($id),
            'bloqueados' => $fila['codigo'] === 'administrador' ? ['sedes', 'usuarios', 'roles'] : [],
        ];
    }
}
