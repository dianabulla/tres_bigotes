<?php

declare(strict_types=1);

namespace TresBigotes\Api;

final class Respuesta
{
    public static function json(int $estado, array $cuerpo): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function vacia(int $estado): void
    {
        http_response_code($estado);
        exit;
    }
}
