<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Caja;
use TresBigotes\Models\Producto;

final class CajaController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/caja', [self::class, 'ver']);
        $router->registrar('POST', '/api/caja/turno', [self::class, 'abrir']);
        $router->registrar('POST', '/api/caja/ventas', [self::class, 'vender']);
        $router->registrar('POST', '/api/caja/cierre', [self::class, 'cerrar']);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = self::exigirCaja($peticion);
        $pdo = Conexion::obtener();
        $turno = Caja::turnoAbierto($pdo, $sede, false);
        Respuesta::json(200, [
            'medios' => Caja::MEDIOS,
            'turno' => $turno === null ? null : Caja::presentarTurno($pdo, $turno),
            'productos' => Caja::productos($sede),
            'citas' => Caja::citasPorCobrar($sede),
            'cierres' => Caja::cierres($sede),
        ]);
    }

    public static function abrir(Peticion $peticion): void
    {
        $sede = self::exigirCaja($peticion);
        $saldo = self::monto($peticion->cuerpo['saldo_inicial'] ?? null, true);
        if ($saldo === null) {
            Respuesta::json(422, ['error' => 'La base de apertura no puede ser negativa']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            if (Caja::turnoAbierto($pdo, $sede, true) !== null) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Ya hay un turno abierto']);
            }
            Caja::abrir($pdo, $sede, (int) $peticion->usuario['id'], $saldo);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $e->getCode() === '23000') {
                Respuesta::json(422, ['error' => 'Ya hay un turno abierto']);
            }
            throw $e;
        }
        self::ver($peticion);
    }

    public static function vender(Peticion $peticion): void
    {
        $sede = self::exigirCaja($peticion);
        $cantidades = self::leerLineas($peticion->cuerpo);
        $citaId = self::citaPedida($peticion->cuerpo);
        if ($cantidades === [] && $citaId === null) {
            Respuesta::json(422, ['error' => 'La venta necesita un producto o una cita']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $turno = Caja::turnoAbierto($pdo, $sede, true);
            if ($turno === null) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'No hay un turno abierto']);
            }
            $items = [];
            $servicios = [];
            $clienteId = null;
            $centavos = 0;
            if ($citaId !== null) {
                $cita = Caja::bloquearCita($pdo, $sede, $citaId);
                if ($cita === null) {
                    $pdo->rollBack();
                    Respuesta::json(404, ['error' => 'Cita no encontrada']);
                }
                if ($cita['estado'] !== 'completada') {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Solo se cobra una cita completada']);
                }
                if (Caja::citaCobrada($pdo, $citaId)) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Esa cita ya fue cobrada']);
                }
                if ($cita['servicios'] === []) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Esa cita no tiene servicios']);
                }
                $clienteId = $cita['cliente_id'];
                foreach ($cita['servicios'] as $servicio) {
                    $precio = number_format((float) $servicio['precio_aplicado'], 2, '.', '');
                    $centavos += self::centavos($precio);
                    $servicios[] = [
                        'servicio_id' => $servicio['servicio_id'],
                        'colaborador_id' => $cita['colaborador_id'],
                        'precio' => $precio,
                    ];
                }
            }
            foreach ($cantidades as $productoId => $cantidad) {
                $producto = Caja::bloquearProducto($pdo, $sede, $productoId);
                if ($producto === null) {
                    $pdo->rollBack();
                    Respuesta::json(404, ['error' => 'Producto no encontrado']);
                }
                if ($producto['tipo'] !== 'venta' || (int) $producto['activo'] !== 1 || $producto['precio_venta'] === null) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Ese producto no se vende en caja']);
                }
                if ((int) $producto['stock'] < $cantidad) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'No hay stock suficiente de ' . $producto['nombre']]);
                }
                $precio = number_format((float) $producto['precio_venta'], 2, '.', '');
                $subtotal = self::porCantidad($precio, $cantidad);
                $centavos += self::centavos($subtotal);
                $items[] = [
                    'producto_id' => $productoId,
                    'nombre' => $producto['nombre'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                ];
            }
            $total = self::desdeCentavos($centavos);
            $pagos = self::leerPagos($peticion->cuerpo, $total);
            if (isset($pagos['error'])) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => $pagos['error']]);
            }
            $ventaId = Caja::crearVenta($pdo, $sede, (int) $turno['id'], $total, $clienteId, $citaId);
            foreach ($servicios as $servicio) {
                Caja::crearItemServicio($pdo, $ventaId, $servicio['servicio_id'], $servicio['colaborador_id'], $servicio['precio']);
            }
            foreach ($items as $item) {
                $itemId = Caja::crearItem($pdo, $ventaId, $item['producto_id'], $item['cantidad'], $item['precio'], $item['subtotal']);
                Producto::registrarMovimiento(
                    $pdo,
                    $item['producto_id'],
                    (int) $peticion->usuario['id'],
                    'venta',
                    $item['cantidad'],
                    'Venta en caja',
                    $itemId
                );
                if (!Producto::aplicarMovimiento($pdo, $sede, $item['producto_id'], -$item['cantidad'])) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'No hay stock suficiente de ' . $item['nombre']]);
                }
            }
            foreach ($pagos['pagos'] as $pago) {
                Caja::crearPago($pdo, $ventaId, $pago['medio'], $pago['monto']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        self::ver($peticion);
    }

    public static function cerrar(Peticion $peticion): void
    {
        $sede = self::exigirCaja($peticion);
        $reportados = self::leerReporte($peticion->cuerpo);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $turno = Caja::turnoAbierto($pdo, $sede, true);
            if ($turno === null) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'No hay un turno abierto']);
            }
            $turnoId = (int) $turno['id'];
            $sistema = Caja::totales($pdo, $turnoId);
            foreach (Caja::MEDIOS as $medio) {
                $codigo = $medio['codigo'];
                Caja::guardarArqueo($pdo, $turnoId, $codigo, $sistema[$codigo], $reportados[$codigo]);
            }
            if (!Caja::cerrar($pdo, $sede, $turnoId)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'No hay un turno abierto']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        self::ver($peticion);
    }

    private static function exigirCaja(Peticion $peticion): int
    {
        return Acceso::exigir($peticion, 'caja', 'No tienes permiso para usar la caja');
    }

    private static function leerLineas(array $cuerpo): array
    {
        $lineas = $cuerpo['lineas'] ?? [];
        if (!is_array($lineas) || !self::esLista($lineas)) {
            Respuesta::json(422, ['error' => 'La venta necesita un producto o una cita']);
        }
        if ($lineas === []) {
            return [];
        }
        $cantidades = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) {
                Respuesta::json(422, ['error' => 'La venta necesita un producto o una cita']);
            }
            $productoId = $linea['producto_id'] ?? null;
            $cantidad = $linea['cantidad'] ?? null;
            if (!is_numeric($productoId) || (int) $productoId < 1 || (int) $productoId != $productoId) {
                Respuesta::json(422, ['error' => 'Producto no encontrado']);
            }
            if (!is_numeric($cantidad) || (int) $cantidad < 1 || (int) $cantidad != $cantidad) {
                Respuesta::json(422, ['error' => 'La cantidad debe ser un entero mayor que cero']);
            }
            $id = (int) $productoId;
            $cantidades[$id] = ($cantidades[$id] ?? 0) + (int) $cantidad;
        }
        ksort($cantidades);
        return $cantidades;
    }

    private static function citaPedida(array $cuerpo): ?int
    {
        $citaId = $cuerpo['cita_id'] ?? null;
        if ($citaId === null || $citaId === '') {
            return null;
        }
        if (!is_numeric($citaId) || (int) $citaId < 1 || (int) $citaId != $citaId) {
            Respuesta::json(404, ['error' => 'Cita no encontrada']);
        }
        return (int) $citaId;
    }

    private static function leerPagos(array $cuerpo, string $total): array
    {
        $pagos = $cuerpo['pagos'] ?? null;
        if (!is_array($pagos) || !self::esLista($pagos) || $pagos === []) {
            return ['error' => 'La venta necesita al menos un pago'];
        }
        $validos = [];
        $vistos = [];
        $centavos = 0;
        foreach ($pagos as $pago) {
            if (!is_array($pago)) {
                return ['error' => 'El medio debe ser Nequi, Daviplata o QR'];
            }
            $medio = (string) ($pago['medio'] ?? '');
            if (!self::medioValido($medio)) {
                return ['error' => 'El medio debe ser Nequi, Daviplata o QR'];
            }
            if (isset($vistos[$medio])) {
                return ['error' => 'Cada medio va una sola vez en la venta'];
            }
            $vistos[$medio] = true;
            $monto = self::monto($pago['monto'] ?? null, false);
            if ($monto === null) {
                return ['error' => 'Cada pago debe ser mayor que cero'];
            }
            $centavos += self::centavos($monto);
            $validos[] = ['medio' => $medio, 'monto' => $monto];
        }
        if ($centavos !== self::centavos($total)) {
            return ['error' => 'Los pagos deben sumar ' . $total];
        }
        return ['pagos' => $validos];
    }

    private static function leerReporte(array $cuerpo): array
    {
        $lineas = $cuerpo['lineas'] ?? null;
        if (!is_array($lineas) || !self::esLista($lineas)) {
            Respuesta::json(422, ['error' => 'El cierre debe reportar Nequi, Daviplata y QR']);
        }
        $reportados = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) {
                Respuesta::json(422, ['error' => 'El cierre debe reportar Nequi, Daviplata y QR']);
            }
            $medio = (string) ($linea['medio'] ?? '');
            if (!self::medioValido($medio) || isset($reportados[$medio])) {
                Respuesta::json(422, ['error' => 'El cierre debe reportar Nequi, Daviplata y QR']);
            }
            $monto = self::monto($linea['monto_reportado'] ?? null, true);
            if ($monto === null) {
                Respuesta::json(422, ['error' => 'El monto reportado no puede ser negativo']);
            }
            $reportados[$medio] = $monto;
        }
        if (count($reportados) !== count(Caja::MEDIOS)) {
            Respuesta::json(422, ['error' => 'El cierre debe reportar Nequi, Daviplata y QR']);
        }
        return $reportados;
    }

    private static function medioValido(string $medio): bool
    {
        foreach (Caja::MEDIOS as $definicion) {
            if ($definicion['codigo'] === $medio) {
                return true;
            }
        }
        return false;
    }

    private static function monto($valor, bool $permiteCero): ?string
    {
        if (is_int($valor)) {
            $valor = (string) $valor;
        } elseif (is_float($valor)) {
            $valor = number_format($valor, 2, '.', '');
        }
        if (!is_string($valor) || !preg_match('/^\d+(\.\d{1,2})?$/', trim($valor))) {
            return null;
        }
        $normal = number_format((float) trim($valor), 2, '.', '');
        if (!$permiteCero && $normal === '0.00') {
            return null;
        }
        return $normal;
    }

    private static function porCantidad(string $precio, int $cantidad): string
    {
        return self::desdeCentavos(self::centavos($precio) * $cantidad);
    }

    private static function centavos(string $monto): int
    {
        $partes = explode('.', $monto, 2);
        $decimales = str_pad($partes[1] ?? '00', 2, '0');
        return ((int) $partes[0]) * 100 + (int) substr($decimales, 0, 2);
    }

    private static function desdeCentavos(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    private static function esLista(array $valor): bool
    {
        if ($valor === []) {
            return true;
        }
        return array_keys($valor) === range(0, count($valor) - 1);
    }
}
