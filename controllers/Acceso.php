<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;

final class Acceso
{
    public static function esSuper(Peticion $peticion): bool
    {
        return ($peticion->usuario['rol'] ?? '') === 'superadministrador';
    }

    public static function alcance(Peticion $peticion): array
    {
        if (self::esSuper($peticion)) {
            $activa = $peticion->usuario['establecimiento_activo_id'] ?? null;
            return [
                'tipo' => 'sistema',
                'establecimiento_id' => null,
                'establecimiento_activo_id' => $activa !== null ? (int) $activa : null,
            ];
        }
        $sede = (int) ($peticion->usuario['establecimiento_id'] ?? 0);
        if ($sede < 1) {
            Respuesta::json(403, ['error' => 'Tu cuenta no tiene sede asignada']);
        }
        return [
            'tipo' => 'sede',
            'establecimiento_id' => $sede,
            'establecimiento_activo_id' => $sede,
        ];
    }

    public static function tiene(Peticion $peticion, string $permiso): bool
    {
        $permisos = $peticion->usuario['permisos'] ?? [];
        return is_array($permisos) && in_array($permiso, $permisos, true);
    }

    public static function exigirPermiso(Peticion $peticion, string $permiso, string $mensaje): void
    {
        if (!self::tiene($peticion, $permiso)) {
            Respuesta::json(403, ['error' => $mensaje]);
        }
    }

    public static function exigir(Peticion $peticion, string $permiso, string $mensaje): int
    {
        self::exigirPermiso($peticion, $permiso, $mensaje);
        return self::sedeOperativa($peticion);
    }

    public static function exigirAlguno(Peticion $peticion, array $permisos, string $mensaje): int
    {
        foreach ($permisos as $permiso) {
            if (self::tiene($peticion, $permiso)) {
                return self::sedeOperativa($peticion);
            }
        }
        Respuesta::json(403, ['error' => $mensaje]);
    }

    public static function sedeOperativa(Peticion $peticion): int
    {
        if (self::esSuper($peticion)) {
            $activa = (int) ($peticion->usuario['establecimiento_activo_id'] ?? 0);
            if ($activa < 1) {
                Respuesta::json(422, ['error' => 'Elige una sede de trabajo para continuar']);
            }
            return $activa;
        }
        $sede = (int) ($peticion->usuario['establecimiento_id'] ?? 0);
        if ($sede < 1) {
            Respuesta::json(403, ['error' => 'Tu cuenta no tiene sede asignada']);
        }
        return $sede;
    }

    public static function puedeAdministrarSede(Peticion $peticion, int $sedeId): bool
    {
        if (self::esSuper($peticion)) {
            return $sedeId > 0;
        }
        return $sedeId > 0 && $sedeId === (int) ($peticion->usuario['establecimiento_id'] ?? 0);
    }
}
