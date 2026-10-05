<?php

declare(strict_types=1);

namespace TresBigotes\Config;

final class Config
{
    private static ?array $archivo = null;

    public static function obtener(string $clave, ?string $defecto = null): ?string
    {
        $entorno = getenv($clave);
        if ($entorno !== false && $entorno !== '') {
            return $entorno;
        }
        self::cargar();
        return self::$archivo[$clave] ?? $defecto;
    }

    private static function cargar(): void
    {
        if (self::$archivo !== null) {
            return;
        }
        self::$archivo = [];
        $ruta = __DIR__ . '/.env';
        if (!is_file($ruta)) {
            return;
        }
        $lineas = file($ruta, FILE_IGNORE_NEW_LINES);
        if ($lineas === false) {
            return;
        }
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }
            $partes = explode('=', $linea, 2);
            if (count($partes) !== 2) {
                continue;
            }
            self::$archivo[trim($partes[0])] = trim($partes[1]);
        }
    }
}
