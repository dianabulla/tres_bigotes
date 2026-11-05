<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Config;
use TresBigotes\Config\Correo;
use TresBigotes\Models\Aviso;

final class AvisosController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('POST', '/api/avisos/enviar', [self::class, 'enviar'], true);
        $router->registrar('GET', '/api/avisos/enviar', [self::class, 'enviar'], true);
    }

    public static function enviar(Peticion $peticion): void
    {
        self::exigirClaveCron($peticion);
        if (!Correo::listo()) {
            Respuesta::json(503, ['error' => 'El correo no está configurado']);
        }
        $resultado = Aviso::procesarPendientes(50);
        Respuesta::json(200, [
            'ok' => true,
            'procesados' => $resultado['procesados'],
            'enviados' => $resultado['enviados'],
            'fallidos' => $resultado['fallidos'],
            'omitidos' => $resultado['omitidos'],
        ]);
    }

    private static function exigirClaveCron(Peticion $peticion): void
    {
        $esperada = trim((string) Config::obtener('MAIL_CRON_CLAVE', ''));
        if ($esperada === '') {
            Respuesta::json(503, ['error' => 'Falta MAIL_CRON_CLAVE']);
        }
        $recibida = trim((string) (
            $_SERVER['HTTP_X_CRON_CLAVE']
            ?? $peticion->cuerpo['clave']
            ?? $_GET['clave']
            ?? ''
        ));
        if ($recibida === '' || !hash_equals($esperada, $recibida)) {
            Respuesta::json(401, ['error' => 'Clave de cron inválida']);
        }
    }
}
