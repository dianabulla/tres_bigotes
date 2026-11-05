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
            'SELECT u.id, u.nombre, u.password_hash, u.activo, u.establecimiento_id, u.rol_id,
                    r.codigo AS rol, r.nombre AS rol_nombre, e.activo AS sede_activa
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             WHERE u.correo = :correo
             LIMIT 1'
        );
        $consulta->execute(['correo' => $correo]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function presentar(array $fila): array
    {
        $esSuper = ($fila['rol'] ?? '') === 'superadministrador';
        $sede = array_key_exists('establecimiento_id', $fila) && $fila['establecimiento_id'] !== null
            ? (int) $fila['establecimiento_id']
            : null;
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'rol' => $fila['rol'],
            'rol_nombre' => ($fila['rol_nombre'] ?? '') !== '' ? $fila['rol_nombre'] : $fila['rol'],
            'establecimiento_id' => $sede,
            'establecimiento_activo_id' => array_key_exists('establecimiento_activo_id', $fila)
                ? ($fila['establecimiento_activo_id'] !== null ? (int) $fila['establecimiento_activo_id'] : null)
                : ($esSuper ? null : $sede),
            'alcance' => $esSuper ? 'sistema' : 'sede',
            'permisos' => $fila['permisos'] ?? [],
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

    public static function listarPorSede(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.correo, u.activo, u.establecimiento_id, r.codigo AS rol, r.nombre AS rol_nombre,
                    e.nombre AS sede_nombre
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             WHERE u.establecimiento_id = :sede
             ORDER BY u.nombre, u.id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentarCuenta($fila);
        }
        return $filas;
    }

    public static function listarTodos(): array
    {
        $consulta = Conexion::obtener()->query(
            'SELECT u.id, u.nombre, u.correo, u.activo, u.establecimiento_id, r.codigo AS rol, r.nombre AS rol_nombre,
                    e.nombre AS sede_nombre
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             ORDER BY e.nombre IS NULL DESC, e.nombre, u.nombre, u.id'
        );
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentarCuenta($fila);
        }
        return $filas;
    }

    public static function buscarEnSede(int $sede, int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.correo, u.activo, u.establecimiento_id, r.codigo AS rol, r.nombre AS rol_nombre,
                    e.nombre AS sede_nombre
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             WHERE u.id = :id AND u.establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentarCuenta($fila) : null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT u.id, u.nombre, u.correo, u.activo, u.establecimiento_id, r.codigo AS rol, r.nombre AS rol_nombre,
                    e.nombre AS sede_nombre
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();
        return is_array($fila) ? self::presentarCuenta($fila) : null;
    }

    public static function crearConRol(PDO $pdo, ?int $sede, string $rol, array $datos): int
    {
        $rolId = self::idRol($pdo, $rol);
        if ($rolId === 0) {
            return 0;
        }
        $insertar = $pdo->prepare(
            'INSERT INTO usuario (establecimiento_id, rol_id, nombre, correo, password_hash, activo)
             VALUES (:sede, :rol_id, :nombre, :correo, :password_hash, 1)'
        );
        $insertar->execute([
            'sede' => $sede,
            'rol_id' => $rolId,
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'password_hash' => password_hash($datos['clave'], PASSWORD_DEFAULT),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarCuenta(PDO $pdo, int $id, array $datos, ?int $sedeFiltro = null): void
    {
        $rolId = self::idRol($pdo, $datos['rol']);
        $sql = 'UPDATE usuario
                SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo,
                    establecimiento_id = :sede
                WHERE id = :id';
        $params = [
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'rol_id' => $rolId,
            'activo' => $datos['activo'] ? 1 : 0,
            'sede' => $datos['establecimiento_id'],
            'id' => $id,
        ];
        if ($sedeFiltro !== null) {
            $sql .= ' AND establecimiento_id = :sede_filtro';
            $params['sede_filtro'] = $sedeFiltro;
        }
        $guardar = $pdo->prepare($sql);
        $guardar->execute($params);
        if ($datos['clave'] === '') {
            return;
        }
        $claveSql = 'UPDATE usuario SET password_hash = :password_hash WHERE id = :id';
        $claveParams = [
            'password_hash' => password_hash($datos['clave'], PASSWORD_DEFAULT),
            'id' => $id,
        ];
        if ($sedeFiltro !== null) {
            $claveSql .= ' AND establecimiento_id = :sede_filtro';
            $claveParams['sede_filtro'] = $sedeFiltro;
        }
        $clave = $pdo->prepare($claveSql);
        $clave->execute($claveParams);
    }

    public static function otrosConPermiso(PDO $pdo, ?int $sede, int $exceptoId, string $permiso): int
    {
        if ($sede === null) {
            $consulta = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM usuario u
                 INNER JOIN rol_permiso rp ON rp.rol_id = u.rol_id
                 INNER JOIN permiso p ON p.id = rp.permiso_id
                 WHERE u.establecimiento_id IS NULL
                   AND u.activo = 1
                   AND p.codigo = :permiso
                   AND u.id <> :id'
            );
            $consulta->execute(['id' => $exceptoId, 'permiso' => $permiso]);
        } else {
            $consulta = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM usuario u
                 INNER JOIN rol_permiso rp ON rp.rol_id = u.rol_id
                 INNER JOIN permiso p ON p.id = rp.permiso_id
                 WHERE u.establecimiento_id = :sede
                   AND u.activo = 1
                   AND p.codigo = :permiso
                   AND u.id <> :id'
            );
            $consulta->execute(['sede' => $sede, 'id' => $exceptoId, 'permiso' => $permiso]);
        }
        $fila = $consulta->fetch();
        return is_array($fila) ? (int) $fila['total'] : 0;
    }

    public static function presentarCuenta(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'correo' => $fila['correo'],
            'rol' => $fila['rol'],
            'rol_nombre' => ($fila['rol_nombre'] ?? '') !== '' ? $fila['rol_nombre'] : $fila['rol'],
            'activo' => (int) $fila['activo'] === 1,
            'establecimiento_id' => $fila['establecimiento_id'] !== null ? (int) $fila['establecimiento_id'] : null,
            'sede_nombre' => $fila['sede_nombre'] ?? null,
        ];
    }

    private static function idRol(PDO $pdo, string $codigo): int
    {
        $rol = $pdo->prepare('SELECT id FROM rol WHERE codigo = :codigo LIMIT 1');
        $rol->execute(['codigo' => $codigo]);
        $fila = $rol->fetch();
        return is_array($fila) ? (int) $fila['id'] : 0;
    }
}
