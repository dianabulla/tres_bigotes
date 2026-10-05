<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Usuario
{
    public static function buscarPorCorreo(string $correo): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.password_hash, u.activo, u.establecimiento_id, r.codigo AS rol
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             WHERE u.correo = :correo
             LIMIT 1'
        );
        $consulta->execute(['correo' => $correo]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'rol' => $fila['rol'],
            'establecimiento_id' => (int) $fila['establecimiento_id'],
        ];
    }

    public static function crearAcceso(PDO $pdo, int $sede, array $datos): int
    {
        $rol = $pdo->prepare('SELECT id FROM rol WHERE codigo = :codigo LIMIT 1');
        $rol->execute(['codigo' => 'colaborador']);
        $filaRol = $rol->fetch();
        if (!is_array($filaRol)) {
            return 0;
        }
        $insertar = $pdo->prepare(
            'INSERT INTO usuario (establecimiento_id, rol_id, nombre, correo, password_hash, activo)
             VALUES (:sede, :rol_id, :nombre, :correo, :password_hash, :activo)'
        );
        $insertar->execute([
            'sede' => $sede,
            'rol_id' => $filaRol['id'],
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'password_hash' => password_hash($datos['clave'], PASSWORD_DEFAULT),
            'activo' => $datos['activo'] ? 1 : 0,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarAcceso(PDO $pdo, int $sede, int $usuarioId, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE usuario
             SET nombre = :nombre, correo = :correo, activo = :activo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'activo' => $datos['activo'] ? 1 : 0,
            'id' => $usuarioId,
            'sede' => $sede,
        ]);
        if ($datos['clave'] === '') {
            return;
        }
        $clave = $pdo->prepare(
            'UPDATE usuario SET password_hash = :password_hash WHERE id = :id AND establecimiento_id = :sede'
        );
        $clave->execute([
            'password_hash' => password_hash($datos['clave'], PASSWORD_DEFAULT),
            'id' => $usuarioId,
            'sede' => $sede,
        ]);
    }
}
