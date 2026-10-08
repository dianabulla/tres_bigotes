<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Permiso
{
    public static function catalogo(): array
    {
        $consulta = Conexion::obtener()->query(
            'SELECT codigo, titulo, grupo FROM permiso ORDER BY id'
        );
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'codigo' => $fila['codigo'],
                'titulo' => $fila['titulo'],
                'grupo' => $fila['grupo'],
            ];
        }
        return $filas;
    }

    public static function codigos(): array
    {
        $codigos = [];
        foreach (self::catalogo() as $permiso) {
            $codigos[] = $permiso['codigo'];
        }
        return $codigos;
    }

    public static function codigosDeRol(int $rolId): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT p.codigo
             FROM rol_permiso rp
             INNER JOIN permiso p ON p.id = rp.permiso_id
             WHERE rp.rol_id = :rol
             ORDER BY p.id'
        );
        $consulta->execute(['rol' => $rolId]);
        $codigos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $codigos[] = $fila['codigo'];
        }
        return $codigos;
    }

    public static function rolTiene(PDO $pdo, string $codigoRol, string $permiso): bool
    {
        $consulta = $pdo->prepare(
            'SELECT 1
             FROM rol r
             INNER JOIN rol_permiso rp ON rp.rol_id = r.id
             INNER JOIN permiso p ON p.id = rp.permiso_id
             WHERE r.codigo = :rol AND p.codigo = :permiso
             LIMIT 1'
        );
        $consulta->execute(['rol' => $codigoRol, 'permiso' => $permiso]);
        return $consulta->fetch() !== false;
    }

    public static function reemplazar(PDO $pdo, int $rolId, array $codigos): void
    {
        $borrar = $pdo->prepare('DELETE FROM rol_permiso WHERE rol_id = :rol');
        $borrar->execute(['rol' => $rolId]);
        if ($codigos === []) {
            return;
        }
        $insertar = $pdo->prepare(
            'INSERT INTO rol_permiso (rol_id, permiso_id)
             SELECT :rol, id FROM permiso WHERE codigo = :codigo'
        );
        foreach ($codigos as $codigo) {
            $insertar->execute(['rol' => $rolId, 'codigo' => $codigo]);
        }
    }
}
