<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Resena
{
    public static function listar(int $sede, bool $soloPublicadas = false): array
    {
        $sql = 'SELECT id, autor, correo, texto, calificacion, origen, publicada, creado_en
                FROM resena
                WHERE establecimiento_id = :sede';
        if ($soloPublicadas) {
            $sql .= ' AND publicada = 1';
        }
        $sql .= ' ORDER BY creado_en DESC, id DESC';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO resena (establecimiento_id, autor, correo, texto, calificacion, origen, publicada, creado_en)
             VALUES (:sede, :autor, :correo, :texto, :nota, :origen, :publicada, :creado)'
        );
        $insertar->execute([
            'sede' => $sede,
            'autor' => $datos['autor'],
            'correo' => $datos['correo'] ?? null,
            'texto' => $datos['texto'],
            'nota' => $datos['calificacion'],
            'origen' => $datos['origen'] ?? 'admin',
            'publicada' => !empty($datos['publicada']) ? 1 : 0,
            'creado' => $datos['creado_en'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function publicar(PDO $pdo, int $sede, int $id): bool
    {
        $guardar = $pdo->prepare(
            'UPDATE resena
             SET publicada = 1
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute(['id' => $id, 'sede' => $sede]);
        return $guardar->rowCount() > 0;
    }

    public static function eliminar(PDO $pdo, int $sede, int $id): bool
    {
        $borrar = $pdo->prepare(
            'DELETE FROM resena WHERE id = :id AND establecimiento_id = :sede'
        );
        $borrar->execute(['id' => $id, 'sede' => $sede]);
        return $borrar->rowCount() > 0;
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'autor' => $fila['autor'],
            'correo' => $fila['correo'] ?? null,
            'texto' => $fila['texto'],
            'calificacion' => (int) $fila['calificacion'],
            'origen' => $fila['origen'] ?? 'admin',
            'publicada' => (int) ($fila['publicada'] ?? 1) === 1,
            'creado_en' => $fila['creado_en'],
        ];
    }
}
