<?php

declare(strict_types=1);

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Config;

date_default_timezone_set('America/Bogota');
ini_set('display_errors', '0');

spl_autoload_register(static function (string $clase): void {
    $prefijo = 'TresBigotes\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }
    $relativa = substr($clase, strlen($prefijo));
    $partes = explode('\\', $relativa);
    $carpeta = array_shift($partes);
    $mapa = [
        'Config' => dirname(__DIR__) . '/config/',
        'Api' => dirname(__DIR__) . '/api/',
        'Controllers' => dirname(__DIR__) . '/controllers/',
        'Models' => dirname(__DIR__) . '/models/',
    ];
    if (!isset($mapa[$carpeta])) {
        return;
    }
    $ruta = $mapa[$carpeta] . implode('/', $partes) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

$origenPedido = $_SERVER['HTTP_ORIGIN'] ?? '';
$permitidos = array_values(array_filter(array_map(
    static fn (string $valor): string => trim($valor),
    explode(',', (string) Config::obtener('APP_ORIGEN', 'http://localhost:5173'))
)));
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$hostActual = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
if ($origenPedido !== '' && (in_array($origenPedido, $permitidos, true) || $origenPedido === $hostActual)) {
    header('Access-Control-Allow-Origin: ' . $origenPedido);
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
}

require dirname(__DIR__) . '/api/rutas.php';

$router = new Router();
registrarRutas($router);

try {
    $router->despachar(Peticion::metodo(), Peticion::ruta());
} catch (Throwable) {
    Respuesta::json(500, ['error' => 'Error interno']);
}
