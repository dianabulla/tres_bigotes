<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use TresBigotes\Config\Conexion;

final class Sesion
{
    public static function buscarActiva(string $tokenHash): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.establecimiento_id, u.rol_id, r.codigo AS rol, r.nombre AS rol_nombre,
                    s.establecimiento_activo_id, e.activo AS sede_cuenta_activa, ea.activo AS sede_activa_ok
             FROM sesion s
             INNER JOIN usuario u ON u.id = s.usuario_id
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             LEFT JOIN establecimiento ea ON ea.id = s.establecimiento_activo_id
             WHERE s.token_hash = :hash AND s.expira_en > :ahora AND u.activo = 1
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
        $esSuper = $usuario['rol'] === 'superadministrador';
        if (!$esSuper) {
            if ($usuario['establecimiento_id'] === null || (int) $usuario['sede_cuenta_activa'] !== 1) {
                return null;
            }
        }
        $activa = $usuario['establecimiento_activo_id'] !== null ? (int) $usuario['establecimiento_activo_id'] : null;
        if ($activa !== null && (int) ($usuario['sede_activa_ok'] ?? 0) !== 1) {
            $activa = null;
        }
        if (!$esSuper) {
            $activa = (int) $usuario['establecimiento_id'];
        }
        return [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'rol_nombre' => $usuario['rol_nombre'] !== '' ? $usuario['rol_nombre'] : $usuario['rol'],
            'establecimiento_id' => $usuario['establecimiento_id'] !== null ? (int) $usuario['establecimiento_id'] : null,
            'establecimiento_activo_id' => $activa,
            'alcance' => $esSuper ? 'sistema' : 'sede',
            'permisos' => Permiso::codigosDeRol((int) $usuario['rol_id']),
        ];
    }

    public static function abrir(int $usuarioId, ?int $sedeActiva = null): string
    {
        $token = bin2hex(random_bytes(32));
        $consulta = Conexion::obtener()->prepare(
            'INSERT INTO sesion (usuario_id, establecimiento_activo_id, token_hash, expira_en, creado_en)
             VALUES (:usuario_id, :sede_activa, :token_hash, :expira_en, :creado_en)'
        );
        $consulta->execute([
            'usuario_id' => $usuarioId,
            'sede_activa' => $sedeActiva,
            'token_hash' => hash('sha256', $token),
            'expira_en' => date('Y-m-d H:i:s', time() + 12 * 3600),
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public static function elegirSede(string $tokenHash, ?int $sedeId): void
    {
        $consulta = Conexion::obtener()->prepare(
            'UPDATE sesion SET establecimiento_activo_id = :sede WHERE token_hash = :hash'
        );
        $consulta->execute([
            'sede' => $sedeId,
            'hash' => $tokenHash,
        ]);
    }

    public static function cerrar(string $tokenHash): void
    {
        $consulta = Conexion::obtener()->prepare('DELETE FROM sesion WHERE token_hash = :hash');
        $consulta->execute(['hash' => $tokenHash]);
    }
}
