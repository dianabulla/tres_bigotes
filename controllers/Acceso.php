<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;

final class Acceso
{
    public static function tiene(Peticion $peticion, string $permiso): bool
    {
        $permisos = $peticion->usuario['permisos'] ?? [];
        return is_array($permisos) && in_array($permiso, $permisos, true);
    }

    public static function exigir(Peticion $peticion, string $permiso, string $mensaje): int
    {
        if (!self::tiene($peticion, $permiso)) {
            Respuesta::json(403, ['error' => $mensaje]);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    public static function exigirAlguno(Peticion $peticion, array $permisos, string $mensaje): int
    {
        foreach ($permisos as $permiso) {
            if (self::tiene($peticion, $permiso)) {
                return (int) $peticion->usuario['establecimiento_id'];
            }
        }
        Respuesta::json(403, ['error' => $mensaje]);
    }
}
