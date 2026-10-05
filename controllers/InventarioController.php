<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Producto;

final class InventarioController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/productos', [self::class, 'listar']);
        $router->registrar('GET', '/api/productos/{id}', [self::class, 'ver']);
        $router->registrar('POST', '/api/productos', [self::class, 'crear']);
        $router->registrar('PUT', '/api/productos/{id}', [self::class, 'actualizar']);
        $router->registrar('GET', '/api/productos/{id}/movimientos', [self::class, 'movimientos']);
        $router->registrar('POST', '/api/productos/{id}/movimientos', [self::class, 'mover']);
    }

    public static function listar(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        Respuesta::json(200, ['productos' => Producto::listar($sede)]);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $fila = Producto::buscar($sede, self::id($peticion));
        if ($fila === null) {
            Respuesta::json(404, ['error' => 'Producto no encontrado']);
        }
        Respuesta::json(200, ['producto' => Producto::presentar($fila)]);
    }

    public static function crear(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $datos = self::leerProducto($peticion->cuerpo, true);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $id = Producto::crear($pdo, $sede, $datos);
            if ($datos['stock_inicial'] > 0) {
                Producto::registrarMovimiento($pdo, $id, (int) $peticion->usuario['id'], 'entrada', $datos['stock_inicial'], 'Stock inicial');
                if (!Producto::aplicarMovimiento($pdo, $sede, $id, $datos['stock_inicial'])) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'No se pudo registrar el stock inicial']);
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $fila = Producto::buscar($sede, $id);
        Respuesta::json(200, ['producto' => Producto::presentar($fila)]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion);
        if (Producto::buscar($sede, $id) === null) {
            Respuesta::json(404, ['error' => 'Producto no encontrado']);
        }
        $datos = self::leerProducto($peticion->cuerpo, false);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Producto::actualizar($pdo, $sede, $id, $datos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $fila = Producto::buscar($sede, $id);
        Respuesta::json(200, ['producto' => Producto::presentar($fila)]);
    }

    public static function movimientos(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $id = self::id($peticion);
        if (Producto::buscar($sede, $id) === null) {
            Respuesta::json(404, ['error' => 'Producto no encontrado']);
        }
        Respuesta::json(200, ['movimientos' => Producto::movimientos($sede, $id)]);
    }

    public static function mover(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion);
        if (Producto::buscar($sede, $id) === null) {
            Respuesta::json(404, ['error' => 'Producto no encontrado']);
        }
        $movimiento = self::leerMovimiento($peticion->cuerpo);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Producto::registrarMovimiento(
                $pdo,
                $id,
                (int) $peticion->usuario['id'],
                $movimiento['tipo'],
                $movimiento['cantidad'],
                $movimiento['motivo']
            );
            if (!Producto::aplicarMovimiento($pdo, $sede, $id, $movimiento['delta'])) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'No hay stock suficiente para esta salida']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $fila = Producto::buscar($sede, $id);
        Respuesta::json(200, ['producto' => Producto::presentar($fila)]);
    }

    private static function exigirConsulta(Peticion $peticion): int
    {
        $rol = $peticion->usuario['rol'] ?? '';
        if ($rol !== 'administrador' && $rol !== 'recepcion') {
            Respuesta::json(403, ['error' => 'No tienes permiso para consultar el inventario']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function exigirAdministrador(Peticion $peticion): int
    {
        if (($peticion->usuario['rol'] ?? '') !== 'administrador') {
            Respuesta::json(403, ['error' => 'No tienes permiso para modificar el inventario']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => 'Producto no encontrado']);
        }
        return (int) $id;
    }

    private static function leerProducto(array $cuerpo, bool $esAlta): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre es obligatorio y no puede pasar de 120 caracteres']);
        }
        $tipo = (string) ($cuerpo['tipo'] ?? '');
        if ($tipo !== 'insumo' && $tipo !== 'venta') {
            Respuesta::json(422, ['error' => 'El tipo debe ser insumo o venta']);
        }
        $compra = self::dinero($cuerpo, 'precio_compra', 'El precio de compra es obligatorio y no puede ser negativo');
        $precio = null;
        if ($tipo === 'venta') {
            $precio = self::dinero($cuerpo, 'precio_venta', 'El producto de venta necesita un precio de venta válido');
        }
        $minimo = $cuerpo['stock_minimo'] ?? 0;
        if (!is_numeric($minimo) || (int) $minimo < 0 || (string) (int) $minimo !== (string) $minimo && !ctype_digit((string) $minimo)) {
            Respuesta::json(422, ['error' => 'El stock mínimo debe ser un entero de cero en adelante']);
        }
        $stockInicial = 0;
        if ($esAlta) {
            $stockInicial = $cuerpo['stock_inicial'] ?? 0;
            if ($stockInicial === '' || $stockInicial === null) {
                $stockInicial = 0;
            }
            if (!is_numeric($stockInicial) || (int) $stockInicial < 0 || (int) $stockInicial != $stockInicial) {
                Respuesta::json(422, ['error' => 'El stock inicial debe ser un entero de cero en adelante']);
            }
            $stockInicial = (int) $stockInicial;
        }
        $activo = true;
        if (!$esAlta) {
            $activo = self::booleano($cuerpo['activo'] ?? null);
            if ($activo === null) {
                Respuesta::json(422, ['error' => 'Indica si el producto está activo']);
            }
        }
        return [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'precio_compra' => $compra,
            'precio_venta' => $precio,
            'stock_minimo' => (int) $minimo,
            'stock_inicial' => $stockInicial,
            'activo' => $activo,
        ];
    }

    private static function leerMovimiento(array $cuerpo): array
    {
        $tipo = (string) ($cuerpo['tipo'] ?? '');
        if ($tipo === 'venta') {
            Respuesta::json(422, ['error' => 'La venta de producto se registra en caja']);
        }
        if ($tipo !== 'entrada' && $tipo !== 'salida' && $tipo !== 'ajuste') {
            Respuesta::json(422, ['error' => 'El movimiento debe ser entrada, salida o ajuste']);
        }
        $cantidad = $cuerpo['cantidad'] ?? null;
        if (!is_numeric($cantidad) || (int) $cantidad <= 0 || (int) $cantidad != $cantidad) {
            Respuesta::json(422, ['error' => 'La cantidad debe ser un entero mayor que cero']);
        }
        $motivo = trim((string) ($cuerpo['motivo'] ?? ''));
        if (mb_strlen($motivo) > 180) {
            Respuesta::json(422, ['error' => 'El motivo no puede pasar de 180 caracteres']);
        }
        $delta = (int) $cantidad;
        if ($tipo === 'salida') {
            $delta = -$delta;
        }
        if ($tipo === 'ajuste') {
            $efecto = (string) ($cuerpo['efecto'] ?? '');
            if ($efecto !== 'aumenta' && $efecto !== 'disminuye') {
                Respuesta::json(422, ['error' => 'El ajuste debe indicar si aumenta o disminuye']);
            }
            if ($efecto === 'disminuye') {
                $delta = -$delta;
            }
        }
        return [
            'tipo' => $tipo,
            'cantidad' => (int) $cantidad,
            'motivo' => $motivo === '' ? null : $motivo,
            'delta' => $delta,
        ];
    }

    private static function dinero(array $cuerpo, string $campo, string $mensaje): string
    {
        if (!isset($cuerpo[$campo]) || $cuerpo[$campo] === '' || !is_numeric($cuerpo[$campo]) || (float) $cuerpo[$campo] < 0) {
            Respuesta::json(422, ['error' => $mensaje]);
        }
        return number_format((float) $cuerpo[$campo], 2, '.', '');
    }

    private static function booleano($valor): ?bool
    {
        if (is_bool($valor)) {
            return $valor;
        }
        if ($valor === 1 || $valor === '1') {
            return true;
        }
        if ($valor === 0 || $valor === '0') {
            return false;
        }
        return null;
    }
}
