<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Producto
{
    public static function listar(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, tipo, precio_compra, precio_venta, stock, stock_minimo, activo
             FROM producto
             WHERE establecimiento_id = :sede
             ORDER BY nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentar($fila);
        }
        return $filas;
    }

    public static function buscar(int $sede, int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, tipo, precio_compra, precio_venta, stock, stock_minimo, activo
             FROM producto
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function crear(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO producto (establecimiento_id, nombre, tipo, precio_compra, precio_venta, stock, stock_minimo, activo)
             VALUES (:sede, :nombre, :tipo, :precio_compra, :precio_venta, 0, :stock_minimo, 1)'
        );
        $insertar->execute([
            'sede' => $sede,
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'precio_compra' => $datos['precio_compra'],
            'precio_venta' => $datos['precio_venta'],
            'stock_minimo' => $datos['stock_minimo'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(PDO $pdo, int $sede, int $id, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE producto
             SET nombre = :nombre, tipo = :tipo, precio_compra = :precio_compra, precio_venta = :precio_venta,
                 stock_minimo = :stock_minimo, activo = :activo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'precio_compra' => $datos['precio_compra'],
            'precio_venta' => $datos['precio_venta'],
            'stock_minimo' => $datos['stock_minimo'],
            'activo' => $datos['activo'] ? 1 : 0,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function aplicarMovimiento(PDO $pdo, int $sede, int $id, int $delta): bool
    {
        $guardar = $pdo->prepare(
            'UPDATE producto
             SET stock = stock + :delta
             WHERE id = :id AND establecimiento_id = :sede AND stock + :delta_control >= 0'
        );
        $guardar->execute([
            'delta' => $delta,
            'delta_control' => $delta,
            'id' => $id,
            'sede' => $sede,
        ]);
        return $guardar->rowCount() === 1;
    }

    public static function registrarMovimiento(PDO $pdo, int $productoId, int $usuarioId, string $tipo, int $cantidad, ?string $motivo, ?int $ventaItemId = null): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO movimiento_inventario (producto_id, tipo, cantidad, venta_item_id, usuario_id, motivo, creado_en)
             VALUES (:producto_id, :tipo, :cantidad, :venta_item_id, :usuario_id, :motivo, :creado_en)'
        );
        $insertar->execute([
            'producto_id' => $productoId,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'venta_item_id' => $ventaItemId,
            'usuario_id' => $usuarioId,
            'motivo' => $motivo,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function movimientos(int $sede, int $productoId): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT m.id, m.tipo, m.cantidad, m.motivo, m.creado_en, u.nombre AS usuario
             FROM movimiento_inventario m
             INNER JOIN producto p ON p.id = m.producto_id
             INNER JOIN usuario u ON u.id = m.usuario_id
             WHERE m.producto_id = :producto_id AND p.establecimiento_id = :sede
             ORDER BY m.creado_en DESC, m.id DESC
             LIMIT 30'
        );
        $consulta->execute([
            'producto_id' => $productoId,
            'sede' => $sede,
        ]);
        return $consulta->fetchAll();
    }

    public static function presentar(array $fila): array
    {
        $stock = (int) $fila['stock'];
        $minimo = (int) $fila['stock_minimo'];
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'tipo' => $fila['tipo'],
            'precio_compra' => $fila['precio_compra'],
            'precio_venta' => $fila['precio_venta'],
            'stock' => $stock,
            'stock_minimo' => $minimo,
            'activo' => (int) $fila['activo'] === 1,
            'alerta' => $stock <= $minimo,
        ];
    }
}
