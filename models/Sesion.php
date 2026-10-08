<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use TresBigotes\Config\Conexion;

final class Sesion
{
    public static function buscarActiva(string $tokenHash): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.establecimiento_id, u.rol_id, r.codigo AS rol, r.nombre AS rol_nombre
             FROM sesion s
             INNER JOIN usuario u ON u.id = s.usuario_id
             INNER JOIN rol r ON r.id = u.rol_id
             INNER JOIN establecimiento e ON e.id = u.establecimiento_id
             WHERE s.token_hash = :hash AND s.expira_en > :ahora AND u.activo = 1 AND e.activo = 1
             LIMIT 1'
        );
        $consulta->execute([
            'hash' => $tokenHash,
            'ahora' => date('Y-m-d H:i:s'),
        ]);
        $usuario = $consulta->fetch();
        if (!is_array($usuario)) {
            return null;
        }
        return [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'rol_nombre' => $usuario['rol_nombre'] !== '' ? $usuario['rol_nombre'] : $usuario['rol'],
            'establecimiento_id' => (int) $usuario['establecimiento_id'],
            'permisos' => Permiso::codigosDeRol((int) $usuario['rol_id']),
        ];
    }

    public static function abrir(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $consulta = Conexion::obtener()->prepare(
            'INSERT INTO sesion (usuario_id, token_hash, expira_en, creado_en)
             VALUES (:usuario_id, :token_hash, :expira_en, :creado_en)'
        );
        $consulta->execute([
            'usuario_id' => $usuarioId,
            'token_hash' => hash('sha256', $token),
            'expira_en' => date('Y-m-d H:i:s', time() + 12 * 3600),
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public static function cerrar(string $tokenHash): void
    {
        $consulta = Conexion::obtener()->prepare('DELETE FROM sesion WHERE token_hash = :hash');
        $consulta->execute(['hash' => $tokenHash]);
    }
}
