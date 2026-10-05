<?php

declare(strict_types=1);

namespace TresBigotes\Config;

use PDO;

final class Conexion
{
    private static ?PDO $pdo = null;

    public static function obtener(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $host = Config::obtener('DB_HOST', '127.0.0.1');
        $puerto = Config::obtener('DB_PORT', '3306');
        $nombre = Config::obtener('DB_NAME', 'tres_bigotes');
        $usuario = Config::obtener('DB_USER', 'root');
        $clave = Config::obtener('DB_PASSWORD', '') ?? '';
        $dsn = "mysql:host={$host};port={$puerto};dbname={$nombre};charset=utf8mb4";
        self::$pdo = new PDO($dsn, $usuario, $clave, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$pdo;
    }
}
