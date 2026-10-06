<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Servicio
{
    public static function listar(int $sede, bool $soloActivos): array
    {
        $sql = 'SELECT id, nombre, categoria, duracion_minutos, precio, activo
                FROM servicio
                WHERE establecimiento_id = :sede';
        if ($soloActivos) {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY nombre, id';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function bloquear(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, categoria, duracion_minutos, precio, activo
             FROM servicio
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO servicio (establecimiento_id, nombre, categoria, duracion_minutos, precio, activo)
             VALUES (:sede, :nombre, :categoria, :duracion, :precio, 1)'
        );
        $insertar->execute([
            'sede' => $sede,
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria'],
            'duracion' => $datos['duracion_minutos'],
            'precio' => $datos['precio'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $sede, int $id, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE servicio
             SET nombre = :nombre, categoria = :categoria, duracion_minutos = :duracion, precio = :precio, activo = :activo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria'],
            'duracion' => $datos['duracion_minutos'],
            'precio' => $datos['precio'],
            'activo' => $datos['activo'] ? 1 : 0,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function insumosAgrupados(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT si.servicio_id, si.producto_id, si.cantidad, p.nombre
             FROM servicio_insumo si
             INNER JOIN servicio s ON s.id = si.servicio_id
             INNER JOIN producto p ON p.id = si.producto_id
             WHERE s.establecimiento_id = :sede AND p.establecimiento_id = :sede_producto
             ORDER BY p.nombre, p.id'
        );
        $consulta->execute(['sede' => $sede, 'sede_producto' => $sede]);
        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $servicioId = (int) $fila['servicio_id'];
            $mapa[$servicioId][] = [
                'producto_id' => (int) $fila['producto_id'],
                'nombre' => $fila['nombre'],
                'cantidad' => (int) $fila['cantidad'],
            ];
        }
        return $mapa;
    }

    public static function reemplazarInsumos(PDO $pdo, int $servicioId, array $lineas): void
    {
        $borrar = $pdo->prepare('DELETE FROM servicio_insumo WHERE servicio_id = :servicio');
        $borrar->execute(['servicio' => $servicioId]);
        $insertar = $pdo->prepare(
            'INSERT INTO servicio_insumo (servicio_id, producto_id, cantidad)
             VALUES (:servicio, :producto, :cantidad)'
        );
        foreach ($lineas as $linea) {
            $insertar->execute([
                'servicio' => $servicioId,
                'producto' => $linea['producto_id'],
                'cantidad' => $linea['cantidad'],
            ]);
        }
    }

    public static function consumirInsumos(PDO $pdo, int $sede, int $citaId, int $usuarioId): ?string
    {
        $consulta = $pdo->prepare(
            'SELECT p.id, p.nombre, SUM(si.cantidad) AS cantidad
             FROM cita_servicio cs
             INNER JOIN servicio_insumo si ON si.servicio_id = cs.servicio_id
             INNER JOIN servicio s ON s.id = cs.servicio_id
             INNER JOIN producto p ON p.id = si.producto_id
             WHERE cs.cita_id = :cita
               AND s.establecimiento_id = :sede
               AND p.establecimiento_id = :sede_producto
               AND p.tipo = \'insumo\'
             GROUP BY p.id, p.nombre
             ORDER BY p.id'
        );
        $consulta->execute(['cita' => $citaId, 'sede' => $sede, 'sede_producto' => $sede]);
        foreach ($consulta->fetchAll() as $fila) {
            $cantidad = (int) $fila['cantidad'];
            if ($cantidad < 1) {
                continue;
            }
            if (!Producto::aplicarMovimiento($pdo, $sede, (int) $fila['id'], -$cantidad)) {
                return $fila['nombre'];
            }
            Producto::registrarMovimiento(
                $pdo,
                (int) $fila['id'],
                $usuarioId,
                'salida',
                $cantidad,
                'Consumo de la cita ' . $citaId
            );
        }
        return null;
    }

    public static function presentar(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'categoria' => $fila['categoria'],
            'duracion_minutos' => (int) $fila['duracion_minutos'],
            'precio' => $fila['precio'],
            'activo' => (int) $fila['activo'] === 1,
        ];
    }
}
