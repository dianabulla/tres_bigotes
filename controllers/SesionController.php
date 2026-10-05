<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Models\Sesion;
use TresBigotes\Models\Usuario;

final class SesionController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('POST', '/api/sesion', [self::class, 'entrar'], true);
        $router->registrar('GET', '/api/sesion', [self::class, 'actual']);
        $router->registrar('DELETE', '/api/sesion', [self::class, 'salir']);
    }

    public static function entrar(Peticion $peticion): void
    {
        $correo = strtolower(trim((string) ($peticion->cuerpo['correo'] ?? '')));
        $clave = (string) ($peticion->cuerpo['clave'] ?? '');
        if ($correo === '' || $clave === '') {
            Respuesta::json(422, ['error' => 'Correo y clave son obligatorios']);
        }

        $fila = Usuario::buscarPorCorreo($correo);
        $claveValida = is_array($fila) && password_verify($clave, (string) $fila['password_hash']);
        if (!is_array($fila) || (int) $fila['activo'] !== 1 || !$claveValida) {
            Respuesta::json(401, ['error' => 'Correo o clave incorrectos']);
        }

        Respuesta::json(200, [
            'token' => Sesion::abrir((int) $fila['id']),
            'usuario' => Usuario::presentar($fila),
        ]);
    }

    public static function actual(Peticion $peticion): void
    {
        Respuesta::json(200, ['usuario' => $peticion->usuario]);
    }

    public static function salir(Peticion $peticion): void
    {
        Sesion::cerrar((string) $peticion->tokenHash);
        Respuesta::json(200, ['ok' => true]);
    }
}
