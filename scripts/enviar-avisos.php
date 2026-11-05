<?php

declare(strict_types=1);

use TresBigotes\Config\Correo;
use TresBigotes\Models\Aviso;

date_default_timezone_set('America/Bogota');

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

if (!Correo::listo()) {
    fwrite(STDERR, "Correo no configurado (MAIL_* en config/.env)\n");
    exit(1);
}

$resultado = Aviso::procesarPendientes(50);
echo json_encode($resultado, JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($resultado['fallidos'] > 0 && $resultado['enviados'] === 0 ? 1 : 0);
