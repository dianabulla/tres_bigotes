<?php

declare(strict_types=1);

namespace TresBigotes\Api;

use TresBigotes\Models\Sesion;

final class Guardia
{
    public static function exigir(Peticion $peticion): void
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', Peticion::autorizacion(), $coincidencia)) {
            Respuesta::json(401, ['error' => 'Sesión no válida']);
        }
        $hash = hash('sha256', $coincidencia[1]);
        $usuario = Sesion::buscarActiva($hash);
        if ($usuario === null) {
            Respuesta::json(401, ['error' => 'Sesión no válida']);
        }
        $peticion->tokenHash = $hash;
        $peticion->usuario = $usuario;
    }
}
