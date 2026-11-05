<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Caja
{
    public const MEDIOS = [
        ['codigo' => 'efectivo', 'nombre' => 'Efectivo'],
        ['codigo' => 'digital', 'nombre' => 'Digital'],
    ];

    public static function nombresMedios(): string
    {
        $nombres = [];
        foreach (self::MEDIOS as $medio) {
            $nombres[] = $medio['nombre'];
        }
        if (count($nombres) === 1) {
            return $nombres[0];
        }
        $ultimo = array_pop($nombres);
        return implode(', ', $nombres) . ' y ' . $ultimo;
    }

    public static function turnoAbierto(PDO $pdo, int $sede, bool $bloquear): ?array
    {
        $sql = 'SELECT t.id, t.saldo_inicial, t.abierto_en, t.estado, u.nombre AS usuario
                FROM turno_caja t
                INNER JOIN usuario u ON u.id = t.usuario_id
                WHERE t.establecimiento_id = :sede AND t.estado = \'abierto\'
                LIMIT 1';
        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }
        $consulta = $pdo->prepare($sql);
        $consulta->execute(['sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function abrir(PDO $pdo, int $sede, int $usuarioId, string $saldoInicial): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO turno_caja (establecimiento_id, usuario_id, saldo_inicial, abierto_en, cerrado_en, estado)
             VALUES (:sede, :usuario_id, :saldo_inicial, :abierto_en, NULL, \'abierto\')'
        );
        $insertar->execute([
            'sede' => $sede,
            'usuario_id' => $usuarioId,
            'saldo_inicial' => $saldoInicial,
            'abierto_en' => date('Y-m-d H:i:s'),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function productos(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, precio_venta, stock
             FROM producto
             WHERE establecimiento_id = :sede AND tipo = \'venta\' AND activo = 1
             ORDER BY nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
                'precio_venta' => $fila['precio_venta'],
                'stock' => (int) $fila['stock'],
            ];
        }
        return $filas;
    }

    public static function serviciosActivos(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, categoria, precio
             FROM servicio
             WHERE establecimiento_id = :sede AND activo = 1
             ORDER BY categoria, nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
                'categoria' => $fila['categoria'],
                'precio' => $fila['precio'],
            ];
        }
        return $filas;
    }

    public static function colaboradoresActivos(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre
             FROM colaborador
             WHERE establecimiento_id = :sede AND activo = 1
             ORDER BY nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
            ];
        }
        return $filas;
    }

    public static function bloquearServicio(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, precio, activo
             FROM servicio
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'precio' => $fila['precio'],
            'activo' => (int) $fila['activo'] === 1,
        ];
    }

    public static function bloquearColaborador(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, activo
             FROM colaborador
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'activo' => (int) $fila['activo'] === 1,
        ];
    }

    public static function bloquearProducto(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, nombre, tipo, precio_venta, stock, activo
             FROM producto
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        return is_array($fila) ? $fila : null;
    }

    public static function citasPorCobrar(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT c.id, c.inicio, c.estado, cl.nombre AS cliente, co.nombre AS profesional,
                    s.id AS servicio_id, s.nombre AS servicio, cs.precio_aplicado
             FROM cita c
             INNER JOIN cliente cl ON cl.id = c.cliente_id
             INNER JOIN colaborador co ON co.id = c.colaborador_id
             INNER JOIN cita_servicio cs ON cs.cita_id = c.id
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE c.establecimiento_id = :sede
               AND c.estado IN (\'pendiente\', \'en_proceso\', \'completada\')
               AND c.inicio >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
               AND c.inicio < DATE_ADD(CURDATE(), INTERVAL 2 DAY)
               AND NOT EXISTS (SELECT 1 FROM venta v WHERE v.cita_id = c.id)
             ORDER BY c.inicio ASC, c.id ASC, cs.id
             LIMIT 120'
        );
        $consulta->execute(['sede' => $sede]);
        $citas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            if (!isset($citas[$id])) {
                $citas[$id] = [
                    'id' => $id,
                    'inicio' => $fila['inicio'],
                    'estado' => $fila['estado'],
                    'cliente' => $fila['cliente'],
                    'profesional' => $fila['profesional'],
                    'servicios' => [],
                    'total' => '0.00',
                ];
            }
            $citas[$id]['servicios'][] = [
                'servicio_id' => (int) $fila['servicio_id'],
                'nombre' => $fila['servicio'],
                'precio_aplicado' => $fila['precio_aplicado'],
            ];
        }
        $lista = [];
        foreach ($citas as $cita) {
            $centavos = 0;
            foreach ($cita['servicios'] as $servicio) {
                $centavos += self::centavos($servicio['precio_aplicado']);
            }
            $cita['total'] = self::desdeCentavos($centavos);
            $lista[] = $cita;
            if (count($lista) === 20) {
                break;
            }
        }
        return $lista;
    }

    public static function bloquearCita(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, estado, cliente_id, colaborador_id
             FROM cita
             WHERE id = :id AND establecimiento_id = :sede
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        $servicios = $pdo->prepare(
            'SELECT cs.servicio_id, cs.precio_aplicado, s.nombre
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE cs.cita_id = :cita
             ORDER BY cs.id
             FOR UPDATE'
        );
        $servicios->execute(['cita' => $id]);
        $lineas = [];
        foreach ($servicios->fetchAll() as $servicio) {
            $lineas[] = [
                'servicio_id' => (int) $servicio['servicio_id'],
                'precio_aplicado' => $servicio['precio_aplicado'],
                'nombre' => $servicio['nombre'],
            ];
        }
        return [
            'id' => (int) $fila['id'],
            'estado' => $fila['estado'],
            'cliente_id' => (int) $fila['cliente_id'],
            'colaborador_id' => (int) $fila['colaborador_id'],
            'servicios' => $lineas,
        ];
    }

    public static function citaCobrada(PDO $pdo, int $citaId): bool
    {
        $consulta = $pdo->prepare(
            'SELECT id FROM venta WHERE cita_id = :cita LIMIT 1 FOR UPDATE'
        );
        $consulta->execute(['cita' => $citaId]);
        return $consulta->fetch() !== false;
    }

    public static function crearVenta(PDO $pdo, int $sede, int $turnoId, string $total, ?int $clienteId = null, ?int $citaId = null): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO venta (establecimiento_id, turno_caja_id, cliente_id, cita_id, total, creado_en)
             VALUES (:sede, :turno, :cliente, :cita, :total, :creado_en)'
        );
        $insertar->execute([
            'sede' => $sede,
            'turno' => $turnoId,
            'cliente' => $clienteId,
            'cita' => $citaId,
            'total' => $total,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function crearItem(PDO $pdo, int $ventaId, int $productoId, int $cantidad, string $precio, string $subtotal): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO venta_item (venta_id, tipo, servicio_id, producto_id, colaborador_id, cantidad, precio_unitario, subtotal)
             VALUES (:venta_id, \'producto\', NULL, :producto_id, NULL, :cantidad, :precio, :subtotal)'
        );
        $insertar->execute([
            'venta_id' => $ventaId,
            'producto_id' => $productoId,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function crearItemServicio(PDO $pdo, int $ventaId, int $servicioId, int $colaboradorId, string $precio): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO venta_item (venta_id, tipo, servicio_id, producto_id, colaborador_id, cantidad, precio_unitario, subtotal)
             VALUES (:venta_id, \'servicio\', :servicio_id, NULL, :colaborador_id, 1, :precio, :subtotal)'
        );
        $insertar->execute([
            'venta_id' => $ventaId,
            'servicio_id' => $servicioId,
            'colaborador_id' => $colaboradorId,
            'precio' => $precio,
            'subtotal' => $precio,
        ]);
    }

    public static function crearPago(PDO $pdo, int $ventaId, string $medio, string $monto): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO pago (venta_id, medio, monto) VALUES (:venta_id, :medio, :monto)'
        );
        $insertar->execute([
            'venta_id' => $ventaId,
            'medio' => $medio,
            'monto' => $monto,
        ]);
    }

    public static function totales(PDO $pdo, int $turnoId, string $saldoInicial = '0.00'): array
    {
        $centavos = [];
        foreach (self::MEDIOS as $medio) {
            $centavos[$medio['codigo']] = 0;
        }
        $centavos['efectivo'] = self::centavos($saldoInicial);

        $consulta = $pdo->prepare(
            'SELECT p.medio, SUM(p.monto) AS monto
             FROM pago p
             INNER JOIN venta v ON v.id = p.venta_id
             WHERE v.turno_caja_id = :turno
             GROUP BY p.medio'
        );
        $consulta->execute(['turno' => $turnoId]);
        foreach ($consulta->fetchAll() as $fila) {
            $codigo = $fila['medio'];
            if (!isset($centavos[$codigo])) {
                continue;
            }
            $centavos[$codigo] += self::centavos(number_format((float) $fila['monto'], 2, '.', ''));
        }

        $ajustes = $pdo->prepare(
            'SELECT medio, tipo, SUM(monto) AS monto
             FROM movimiento_caja
             WHERE turno_caja_id = :turno
             GROUP BY medio, tipo'
        );
        $ajustes->execute(['turno' => $turnoId]);
        foreach ($ajustes->fetchAll() as $fila) {
            $codigo = $fila['medio'];
            if (!isset($centavos[$codigo])) {
                continue;
            }
            $monto = self::centavos(number_format((float) $fila['monto'], 2, '.', ''));
            if ($fila['tipo'] === 'ingreso') {
                $centavos[$codigo] += $monto;
            } else {
                $centavos[$codigo] -= $monto;
            }
        }

        $montos = [];
        foreach ($centavos as $codigo => $valor) {
            $montos[$codigo] = self::desdeCentavos($valor);
        }
        return $montos;
    }

    public static function crearMovimiento(
        PDO $pdo,
        int $sede,
        int $turnoId,
        int $usuarioId,
        string $tipo,
        string $medio,
        string $monto,
        string $motivo
    ): int {
        $insertar = $pdo->prepare(
            'INSERT INTO movimiento_caja
             (establecimiento_id, turno_caja_id, usuario_id, tipo, medio, monto, motivo, creado_en)
             VALUES (:sede, :turno, :usuario_id, :tipo, :medio, :monto, :motivo, :creado_en)'
        );
        $insertar->execute([
            'sede' => $sede,
            'turno' => $turnoId,
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'medio' => $medio,
            'monto' => $monto,
            'motivo' => $motivo,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function movimientos(PDO $pdo, int $turnoId): array
    {
        $consulta = $pdo->prepare(
            'SELECT m.id, m.tipo, m.medio, m.monto, m.motivo, m.creado_en, u.nombre AS usuario
             FROM movimiento_caja m
             INNER JOIN usuario u ON u.id = m.usuario_id
             WHERE m.turno_caja_id = :turno
             ORDER BY m.id DESC
             LIMIT 40'
        );
        $consulta->execute(['turno' => $turnoId]);
        $lista = [];
        foreach ($consulta->fetchAll() as $fila) {
            $lista[] = [
                'id' => (int) $fila['id'],
                'tipo' => $fila['tipo'],
                'medio' => $fila['medio'],
                'nombre_medio' => self::nombreMedio($fila['medio']),
                'monto' => $fila['monto'],
                'motivo' => $fila['motivo'],
                'creado_en' => $fila['creado_en'],
                'usuario' => $fila['usuario'],
            ];
        }
        return $lista;
    }

    public static function ventas(PDO $pdo, int $turnoId): array
    {
        $consulta = $pdo->prepare(
            'SELECT id, total, creado_en
             FROM venta
             WHERE turno_caja_id = :turno
             ORDER BY id DESC
             LIMIT 40'
        );
        $consulta->execute(['turno' => $turnoId]);
        $ventas = [];
        foreach ($consulta->fetchAll() as $venta) {
            $id = (int) $venta['id'];
            $ventas[] = [
                'id' => $id,
                'total' => $venta['total'],
                'creado_en' => $venta['creado_en'],
                'items' => self::items($pdo, $id),
                'pagos' => self::pagos($pdo, $id),
            ];
        }
        return $ventas;
    }

    public static function guardarArqueo(PDO $pdo, int $turnoId, string $medio, string $sistema, string $reportado): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO arqueo_linea (turno_caja_id, medio, monto_sistema, monto_reportado, diferencia)
             VALUES (:turno, :medio, :sistema, :reportado, :reportado_diferencia - :sistema_diferencia)'
        );
        $insertar->execute([
            'turno' => $turnoId,
            'medio' => $medio,
            'sistema' => $sistema,
            'reportado' => $reportado,
            'reportado_diferencia' => $reportado,
            'sistema_diferencia' => $sistema,
        ]);
    }

    public static function cerrar(PDO $pdo, int $sede, int $turnoId): bool
    {
        $guardar = $pdo->prepare(
            'UPDATE turno_caja
             SET estado = \'cerrado\', cerrado_en = :cerrado_en
             WHERE id = :id AND establecimiento_id = :sede AND estado = \'abierto\''
        );
        $guardar->execute([
            'cerrado_en' => date('Y-m-d H:i:s'),
            'id' => $turnoId,
            'sede' => $sede,
        ]);
        return $guardar->rowCount() === 1;
    }

    public static function cierres(int $sede, ?string $desde = null, ?string $hasta = null): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT t.id, t.saldo_inicial, t.abierto_en, t.cerrado_en, u.nombre AS usuario
             FROM turno_caja t
             INNER JOIN usuario u ON u.id = t.usuario_id
             WHERE t.establecimiento_id = :sede AND t.estado = \'cerrado\'';
        $params = ['sede' => $sede];
        if ($desde !== null && $hasta !== null) {
            $sql .= ' AND t.cerrado_en >= :desde AND t.cerrado_en < :hasta';
            $params['desde'] = $desde;
            $params['hasta'] = $hasta;
        }
        $sql .= ' ORDER BY t.cerrado_en DESC, t.id DESC LIMIT 100';
        $consulta = $pdo->prepare($sql);
        $consulta->execute($params);
        $cierres = [];
        foreach ($consulta->fetchAll() as $fila) {
            $cierres[] = self::presentarCierre($pdo, $fila);
        }
        return $cierres;
    }

    public static function presentarTurno(PDO $pdo, array $fila): array
    {
        $id = (int) $fila['id'];
        $base = number_format((float) $fila['saldo_inicial'], 2, '.', '');
        $totales = self::totales($pdo, $id, $base);
        $medios = [];
        foreach (self::MEDIOS as $medio) {
            $codigo = $medio['codigo'];
            $total = $totales[$codigo];
            $linea = [
                'medio' => $codigo,
                'nombre' => $medio['nombre'],
                'monto_sistema' => $total,
            ];
            if ($codigo === 'efectivo') {
                $linea['base_apertura'] = $base;
                $linea['sin_base'] = self::desdeCentavos(
                    self::centavos($total) - self::centavos($base)
                );
            }
            $medios[] = $linea;
        }
        return [
            'id' => $id,
            'saldo_inicial' => $base,
            'abierto_en' => $fila['abierto_en'],
            'usuario' => $fila['usuario'],
            'medios' => $medios,
            'ventas' => self::ventas($pdo, $id),
            'ajustes' => self::movimientos($pdo, $id),
        ];
    }

    private static function presentarCierre(PDO $pdo, array $fila): array
    {
        $id = (int) $fila['id'];
        $consulta = $pdo->prepare(
            'SELECT medio, monto_sistema, monto_reportado, diferencia
             FROM arqueo_linea
             WHERE turno_caja_id = :turno
             ORDER BY id'
        );
        $consulta->execute(['turno' => $id]);
        $lineas = [];
        foreach ($consulta->fetchAll() as $linea) {
            $lineas[] = [
                'medio' => $linea['medio'],
                'nombre' => self::nombreMedio($linea['medio']),
                'monto_sistema' => $linea['monto_sistema'],
                'monto_reportado' => $linea['monto_reportado'],
                'diferencia' => $linea['diferencia'],
            ];
        }
        return [
            'id' => $id,
            'saldo_inicial' => $fila['saldo_inicial'],
            'abierto_en' => $fila['abierto_en'],
            'cerrado_en' => $fila['cerrado_en'],
            'usuario' => $fila['usuario'],
            'lineas' => $lineas,
        ];
    }

    private static function items(PDO $pdo, int $ventaId): array
    {
        $consulta = $pdo->prepare(
            'SELECT vi.cantidad, vi.precio_unitario, vi.subtotal, COALESCE(p.nombre, s.nombre) AS nombre
             FROM venta_item vi
             LEFT JOIN producto p ON p.id = vi.producto_id
             LEFT JOIN servicio s ON s.id = vi.servicio_id
             WHERE vi.venta_id = :venta
             ORDER BY vi.id'
        );
        $consulta->execute(['venta' => $ventaId]);
        return $consulta->fetchAll();
    }

    private static function pagos(PDO $pdo, int $ventaId): array
    {
        $consulta = $pdo->prepare(
            'SELECT medio, monto FROM pago WHERE venta_id = :venta ORDER BY id'
        );
        $consulta->execute(['venta' => $ventaId]);
        $pagos = [];
        foreach ($consulta->fetchAll() as $pago) {
            $pagos[] = [
                'medio' => $pago['medio'],
                'nombre' => self::nombreMedio($pago['medio']),
                'monto' => $pago['monto'],
            ];
        }
        return $pagos;
    }

    private static function nombreMedio(string $codigo): string
    {
        foreach (self::MEDIOS as $medio) {
            if ($medio['codigo'] === $codigo) {
                return $medio['nombre'];
            }
        }
        return $codigo;
    }

    private static function centavos(string $monto): int
    {
        $partes = explode('.', (string) $monto, 2);
        $decimales = str_pad($partes[1] ?? '00', 2, '0');
        return ((int) $partes[0]) * 100 + (int) substr($decimales, 0, 2);
    }

    private static function desdeCentavos(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
