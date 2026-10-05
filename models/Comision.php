<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Comision
{
    public static function reglas(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT r.id, r.servicio_id, r.porcentaje, r.umbral_cantidad, r.periodo, s.nombre AS servicio
             FROM regla_comision r
             LEFT JOIN servicio s ON s.id = r.servicio_id
             WHERE r.establecimiento_id = :sede
             ORDER BY s.nombre, r.umbral_cantidad, r.id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentarRegla($fila);
        }
        return $filas;
    }

    public static function servicios(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre FROM servicio WHERE establecimiento_id = :sede ORDER BY nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = ['id' => (int) $fila['id'], 'nombre' => $fila['nombre']];
        }
        return $filas;
    }

    public static function profesionales(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre FROM colaborador WHERE establecimiento_id = :sede ORDER BY nombre, id'
        );
        $consulta->execute(['sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = ['id' => (int) $fila['id'], 'nombre' => $fila['nombre']];
        }
        return $filas;
    }

    public static function servicioDeSede(int $sede, int $id): bool
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id FROM servicio WHERE id = :id AND establecimiento_id = :sede LIMIT 1'
        );
        $consulta->execute(['id' => $id, 'sede' => $sede]);
        return $consulta->fetch() !== false;
    }

    public static function baseRepetida(int $sede, ?int $servicioId, ?int $ignorar): bool
    {
        $sql = 'SELECT id FROM regla_comision
                WHERE establecimiento_id = :sede AND umbral_cantidad IS NULL';
        $params = ['sede' => $sede];
        if ($servicioId === null) {
            $sql .= ' AND servicio_id IS NULL';
        } else {
            $sql .= ' AND servicio_id = :servicio';
            $params['servicio'] = $servicioId;
        }
        if ($ignorar !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignorar;
        }
        $sql .= ' LIMIT 1';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($params);
        return $consulta->fetch() !== false;
    }

    public static function crearRegla(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO regla_comision (establecimiento_id, servicio_id, porcentaje, umbral_cantidad, periodo)
             VALUES (:sede, :servicio, :porcentaje, :umbral, :periodo)'
        );
        $insertar->execute([
            'sede' => $sede,
            'servicio' => $datos['servicio_id'],
            'porcentaje' => $datos['porcentaje'],
            'umbral' => $datos['umbral_cantidad'],
            'periodo' => $datos['periodo'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarRegla(PDO $pdo, int $sede, int $id, array $datos): void
    {
        $guardar = $pdo->prepare(
            'UPDATE regla_comision
             SET servicio_id = :servicio, porcentaje = :porcentaje, umbral_cantidad = :umbral, periodo = :periodo
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'servicio' => $datos['servicio_id'],
            'porcentaje' => $datos['porcentaje'],
            'umbral' => $datos['umbral_cantidad'],
            'periodo' => $datos['periodo'],
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function eliminarRegla(PDO $pdo, int $sede, int $id): bool
    {
        $borrar = $pdo->prepare(
            'DELETE FROM regla_comision WHERE id = :id AND establecimiento_id = :sede'
        );
        $borrar->execute(['id' => $id, 'sede' => $sede]);
        return $borrar->rowCount() === 1;
    }

    public static function liquidaciones(int $sede, ?int $colaboradorId): array
    {
        $sql = 'SELECT l.id, l.colaborador_id, l.desde, l.hasta, l.total, l.creado_en, c.nombre AS colaborador
                FROM liquidacion l
                INNER JOIN colaborador c ON c.id = l.colaborador_id
                WHERE l.establecimiento_id = :sede';
        $params = ['sede' => $sede];
        if ($colaboradorId !== null) {
            $sql .= ' AND l.colaborador_id = :colaborador';
            $params['colaborador'] = $colaboradorId;
        }
        $sql .= ' ORDER BY l.creado_en DESC, l.id DESC LIMIT 40';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($params);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = self::presentarLiquidacion($fila, []);
        }
        return $filas;
    }

    public static function detalle(int $sede, int $id, ?int $colaboradorId): ?array
    {
        $sql = 'SELECT l.id, l.colaborador_id, l.desde, l.hasta, l.total, l.creado_en, c.nombre AS colaborador
                FROM liquidacion l
                INNER JOIN colaborador c ON c.id = l.colaborador_id
                WHERE l.id = :id AND l.establecimiento_id = :sede';
        $params = ['id' => $id, 'sede' => $sede];
        if ($colaboradorId !== null) {
            $sql .= ' AND l.colaborador_id = :colaborador';
            $params['colaborador'] = $colaboradorId;
        }
        $sql .= ' LIMIT 1';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($params);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return self::presentarLiquidacion($fila, self::lineas((int) $fila['id']));
    }

    public static function liquidar(PDO $pdo, int $sede, int $colaboradorId, string $desde, string $hasta): array
    {
        $existente = self::buscarExacta($pdo, $sede, $colaboradorId, $desde, $hasta);
        if ($existente !== null) {
            $detalle = self::detalle($sede, $existente, null);
            return ['liquidacion' => $detalle, 'nueva' => false];
        }
        $candidatas = self::candidatas($pdo, $sede, $colaboradorId, $desde, $hasta);
        $reglas = self::reglasEn($pdo, $sede);
        $lineas = [];
        $totalCentavos = 0;
        foreach ($candidatas as $candidata) {
            $porcentaje = self::porcentaje($pdo, $sede, $colaboradorId, $candidata, $reglas);
            if ($porcentaje === null) {
                return ['error' => 'Falta una regla de comisión para ' . $candidata['servicio']];
            }
            $monto = self::monto($candidata['precio_aplicado'], $porcentaje);
            $totalCentavos += self::centavos($monto);
            $lineas[] = [
                'cita_servicio_id' => $candidata['id'],
                'base' => $candidata['precio_aplicado'],
                'porcentaje' => $porcentaje,
                'monto' => $monto,
            ];
        }
        $total = self::desdeCentavos($totalCentavos);
        $insertar = $pdo->prepare(
            'INSERT INTO liquidacion (establecimiento_id, colaborador_id, desde, hasta, total, creado_en)
             VALUES (:sede, :colaborador, :desde, :hasta, :total, :creado_en)'
        );
        $insertar->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => $total,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $pdo->lastInsertId();
        $linea = $pdo->prepare(
            'INSERT INTO liquidacion_linea (liquidacion_id, cita_servicio_id, base, porcentaje, monto)
             VALUES (:liquidacion, :cita_servicio, :base, :porcentaje, :monto)'
        );
        foreach ($lineas as $item) {
            $linea->execute([
                'liquidacion' => $id,
                'cita_servicio' => $item['cita_servicio_id'],
                'base' => $item['base'],
                'porcentaje' => $item['porcentaje'],
                'monto' => $item['monto'],
            ]);
        }
        return ['liquidacion' => self::detalle($sede, $id, null), 'nueva' => true];
    }

    public static function anular(PDO $pdo, int $sede, int $id): bool
    {
        $existe = $pdo->prepare(
            'SELECT id FROM liquidacion WHERE id = :id AND establecimiento_id = :sede LIMIT 1 FOR UPDATE'
        );
        $existe->execute(['id' => $id, 'sede' => $sede]);
        if ($existe->fetch() === false) {
            return false;
        }
        $lineas = $pdo->prepare('DELETE FROM liquidacion_linea WHERE liquidacion_id = :id');
        $lineas->execute(['id' => $id]);
        $borrar = $pdo->prepare('DELETE FROM liquidacion WHERE id = :id AND establecimiento_id = :sede');
        $borrar->execute(['id' => $id, 'sede' => $sede]);
        return $borrar->rowCount() === 1;
    }

    private static function buscarExacta(PDO $pdo, int $sede, int $colaboradorId, string $desde, string $hasta): ?int
    {
        $consulta = $pdo->prepare(
            'SELECT id FROM liquidacion
             WHERE establecimiento_id = :sede AND colaborador_id = :colaborador
               AND desde = :desde AND hasta = :hasta
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
        $fila = $consulta->fetch();
        return is_array($fila) ? (int) $fila['id'] : null;
    }

    private static function candidatas(PDO $pdo, int $sede, int $colaboradorId, string $desde, string $hasta): array
    {
        $consulta = $pdo->prepare(
            'SELECT cs.id, cs.servicio_id, cs.precio_aplicado, s.nombre AS servicio, c.inicio
             FROM cita_servicio cs
             INNER JOIN cita c ON c.id = cs.cita_id
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE c.establecimiento_id = :sede
               AND c.colaborador_id = :colaborador
               AND c.estado = \'completada\'
               AND c.inicio >= :desde
               AND c.inicio <= :hasta
               AND NOT EXISTS (
                 SELECT 1 FROM liquidacion_linea ll
                 INNER JOIN liquidacion l ON l.id = ll.liquidacion_id
                 WHERE ll.cita_servicio_id = cs.id AND l.establecimiento_id = :sede_linea
               )
             ORDER BY c.inicio, cs.id
             FOR UPDATE'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
            'sede_linea' => $sede,
        ]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'servicio_id' => (int) $fila['servicio_id'],
                'precio_aplicado' => $fila['precio_aplicado'],
                'servicio' => $fila['servicio'],
                'inicio' => $fila['inicio'],
            ];
        }
        return $filas;
    }

    private static function reglasEn(PDO $pdo, int $sede): array
    {
        $consulta = $pdo->prepare(
            'SELECT servicio_id, porcentaje, umbral_cantidad, periodo
             FROM regla_comision
             WHERE establecimiento_id = :sede'
        );
        $consulta->execute(['sede' => $sede]);
        $bases = [];
        $tramos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $clave = $fila['servicio_id'] === null ? 0 : (int) $fila['servicio_id'];
            if ($fila['umbral_cantidad'] === null) {
                $bases[$clave] = $fila['porcentaje'];
                continue;
            }
            $tramos[$clave][] = [
                'umbral' => (int) $fila['umbral_cantidad'],
                'periodo' => $fila['periodo'],
                'porcentaje' => $fila['porcentaje'],
            ];
        }
        return ['bases' => $bases, 'tramos' => $tramos];
    }

    private static function porcentaje(PDO $pdo, int $sede, int $colaboradorId, array $linea, array $reglas): ?string
    {
        $servicioId = $linea['servicio_id'];
        $propia = isset($reglas['bases'][$servicioId]);
        if ($propia) {
            $base = $reglas['bases'][$servicioId];
            $tramos = $reglas['tramos'][$servicioId] ?? [];
        } elseif (isset($reglas['bases'][0])) {
            $base = $reglas['bases'][0];
            $tramos = $reglas['tramos'][0] ?? [];
        } else {
            return null;
        }
        $elegido = null;
        foreach ($tramos as $tramo) {
            $cantidad = self::contar($pdo, $sede, $colaboradorId, $linea['inicio'], $tramo['periodo']);
            if ($cantidad > $tramo['umbral'] && ($elegido === null || $tramo['umbral'] > $elegido['umbral'])) {
                $elegido = $tramo;
            }
        }
        return $elegido === null ? $base : $elegido['porcentaje'];
    }

    private static function contar(PDO $pdo, int $sede, int $colaboradorId, string $inicio, string $periodo): int
    {
        $dia = substr($inicio, 0, 10);
        if ($periodo === 'semana') {
            $fecha = new \DateTime($dia);
            $fecha->modify('-' . ((int) $fecha->format('N') - 1) . ' days');
            $desde = $fecha->format('Y-m-d') . ' 00:00:00';
            $fecha->modify('+7 days');
            $hasta = $fecha->format('Y-m-d') . ' 00:00:00';
        } else {
            $desde = $dia . ' 00:00:00';
            $hasta = date('Y-m-d', strtotime($dia . ' +1 day')) . ' 00:00:00';
        }
        $consulta = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM cita_servicio cs
             INNER JOIN cita c ON c.id = cs.cita_id
             WHERE c.establecimiento_id = :sede
               AND c.colaborador_id = :colaborador
               AND c.estado = \'completada\'
               AND c.inicio >= :desde
               AND c.inicio < :hasta'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
        $fila = $consulta->fetch();
        return (int) $fila['total'];
    }

    private static function lineas(int $liquidacionId): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT ll.id, ll.base, ll.porcentaje, ll.monto, s.nombre AS servicio, c.inicio
             FROM liquidacion_linea ll
             INNER JOIN cita_servicio cs ON cs.id = ll.cita_servicio_id
             INNER JOIN servicio s ON s.id = cs.servicio_id
             INNER JOIN cita c ON c.id = cs.cita_id
             WHERE ll.liquidacion_id = :id
             ORDER BY c.inicio, ll.id'
        );
        $consulta->execute(['id' => $liquidacionId]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'servicio' => $fila['servicio'],
                'inicio' => $fila['inicio'],
                'base' => $fila['base'],
                'porcentaje' => $fila['porcentaje'],
                'monto' => $fila['monto'],
            ];
        }
        return $filas;
    }

    private static function presentarRegla(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'servicio_id' => $fila['servicio_id'] !== null ? (int) $fila['servicio_id'] : null,
            'servicio' => $fila['servicio'] ?? 'General',
            'porcentaje' => $fila['porcentaje'],
            'umbral_cantidad' => $fila['umbral_cantidad'] !== null ? (int) $fila['umbral_cantidad'] : null,
            'periodo' => $fila['periodo'],
        ];
    }

    private static function presentarLiquidacion(array $fila, array $lineas): array
    {
        return [
            'id' => (int) $fila['id'],
            'colaborador_id' => (int) $fila['colaborador_id'],
            'colaborador' => $fila['colaborador'],
            'desde' => $fila['desde'],
            'hasta' => $fila['hasta'],
            'total' => $fila['total'],
            'creado_en' => $fila['creado_en'],
            'lineas' => $lineas,
        ];
    }

    private static function monto(string $base, string $porcentaje): string
    {
        $centavos = (int) round(self::centavos($base) * self::centavos($porcentaje) / 10000);
        $pesos = (int) round($centavos / 100);
        return $pesos . '.00';
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
}
