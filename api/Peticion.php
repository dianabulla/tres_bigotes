<?php

declare(strict_types=1);

namespace TresBigotes\Api;

final class Peticion
{
    public function __construct(
        public string $metodo,
        public string $ruta,
        public array $cuerpo,
        public array $params,
        public ?array $usuario = null,
        public ?string $tokenHash = null,
    ) {
    }

    public static function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function ruta(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = is_string($uri) ? $uri : '/';
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($base !== '/' && $base !== '.' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public static function actual(array $params): self
    {
        return new self(self::metodo(), self::ruta(), self::cuerpo(), $params);
    }

    public static function autorizacion(): string
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (function_exists('apache_request_headers')) {
            $encabezados = apache_request_headers();
            foreach ($encabezados as $clave => $valor) {
                if (strcasecmp((string) $clave, 'Authorization') === 0) {
                    return (string) $valor;
                }
            }
        }
        return '';
    }

    private static function cuerpo(): array
    {
        $crudo = file_get_contents('php://input');
        if ($crudo === false || trim($crudo) === '') {
            return [];
        }
        $decodificado = json_decode($crudo, true);
        if (!is_array($decodificado)) {
            Respuesta::json(422, ['error' => 'El cuerpo debe ser JSON']);
        }
        return $decodificado;
    }
}
