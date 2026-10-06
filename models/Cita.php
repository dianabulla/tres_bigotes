<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Cita
{
    public static function sedePublica(): ?array
    {
        $consulta = Conexion::obtener()->query(
            'SELECT id, nombre
             FROM establecimiento
             WHERE activo = 1
             ORDER BY id
             LIMIT 1'
        );
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
        ];
    }

    public static function ocupados(int $sede, int $colaboradorId, string $desde, string $hasta): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT inicio, fin
             FROM cita
             WHERE establecimiento_id = :sede
               AND colaborador_id = :colaborador
               AND estado IN (\'pendiente\', \'en_proceso\')
               AND inicio < :hasta
               AND fin > :desde
             ORDER BY inicio'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
        return $consulta->fetchAll();
    }

    public static function profesionalDeUsuario(int $sede, int $usuarioId): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, activo
             FROM colaborador
             WHERE usuario_id = :usuario AND establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['usuario' => $usuarioId, 'sede' => $sede]);
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

    public static function profesionales(int $sede): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, foto
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
                'foto' => $fila['foto'],
            ];
        }
        return $filas;
    }

    public static function bloquearProfesional(PDO $pdo, int $sede, int $id): ?array
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

    public static function listar(int $sede, string $desde, string $hasta, ?int $colaboradorId): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT c.id, c.inicio, c.fin, c.estado, c.notas,
                       cl.id AS cliente_id, cl.nombre AS cliente_nombre, cl.telefono AS cliente_telefono,
                       co.id AS colaborador_id, co.nombre AS colaborador_nombre
                FROM cita c
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                INNER JOIN colaborador co ON co.id = c.colaborador_id
                WHERE c.establecimiento_id = :sede
                  AND c.inicio >= :desde
                  AND c.inicio < :hasta';
        $params = ['sede' => $sede, 'desde' => $desde, 'hasta' => $hasta];
        if ($colaboradorId !== null) {
            $sql .= ' AND c.colaborador_id = :colaborador';
            $params['colaborador'] = $colaboradorId;
        }
        $sql .= ' ORDER BY c.inicio, c.id';
        $consulta = $pdo->prepare($sql);
        $consulta->execute($params);
        $citas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            $citas[$id] = [
                'id' => $id,
                'inicio' => $fila['inicio'],
                'fin' => $fila['fin'],
                'estado' => $fila['estado'],
                'notas' => $fila['notas'],
                'cliente' => [
                    'id' => (int) $fila['cliente_id'],
                    'nombre' => $fila['cliente_nombre'],
                    'telefono' => $fila['cliente_telefono'],
                ],
                'colaborador' => [
                    'id' => (int) $fila['colaborador_id'],
                    'nombre' => $fila['colaborador_nombre'],
                ],
                'servicios' => [],
            ];
        }
        if ($citas === []) {
            return [];
        }
        $servicios = $pdo->prepare(
            'SELECT cs.cita_id, s.id, s.nombre, s.duracion_minutos, cs.precio_aplicado
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id = cs.servicio_id
             INNER JOIN cita c ON c.id = cs.cita_id
             WHERE c.establecimiento_id = :sede
               AND c.inicio >= :desde
               AND c.inicio < :hasta
             ORDER BY cs.id'
        );
        $servicios->execute(['sede' => $sede, 'desde' => $desde, 'hasta' => $hasta]);
        foreach ($servicios->fetchAll() as $fila) {
            $citaId = (int) $fila['cita_id'];
            if (!isset($citas[$citaId])) {
                continue;
            }
            if ($colaboradorId !== null && $citas[$citaId]['colaborador']['id'] !== $colaboradorId) {
                continue;
            }
            $citas[$citaId]['servicios'][] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
                'duracion_minutos' => (int) $fila['duracion_minutos'],
                'precio_aplicado' => $fila['precio_aplicado'],
            ];
        }
        return array_values($citas);
    }

    public static function bloquear(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, colaborador_id, estado
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
        return [
            'id' => (int) $fila['id'],
            'colaborador_id' => (int) $fila['colaborador_id'],
            'estado' => $fila['estado'],
        ];
    }

    public static function haySolape(PDO $pdo, int $sede, int $colaboradorId, string $inicio, string $fin): bool
    {
        $consulta = $pdo->prepare(
            'SELECT id
             FROM cita
             WHERE establecimiento_id = :sede
               AND colaborador_id = :colaborador
               AND estado IN (\'pendiente\', \'en_proceso\')
               AND inicio < :fin
               AND fin > :inicio
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'fin' => $fin,
            'inicio' => $inicio,
        ]);
        return $consulta->fetch() !== false;
    }

    public static function crear(PDO $pdo, int $sede, int $clienteId, int $colaboradorId, string $inicio, string $fin, ?string $notas): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO cita (establecimiento_id, cliente_id, colaborador_id, inicio, fin, estado, notas)
             VALUES (:sede, :cliente, :colaborador, :inicio, :fin, \'pendiente\', :notas)'
        );
        $insertar->execute([
            'sede' => $sede,
            'cliente' => $clienteId,
            'colaborador' => $colaboradorId,
            'inicio' => $inicio,
            'fin' => $fin,
            'notas' => $notas,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function agregarServicio(PDO $pdo, int $citaId, int $servicioId, string $precio): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO cita_servicio (cita_id, servicio_id, precio_aplicado)
             VALUES (:cita, :servicio, :precio)'
        );
        $insertar->execute([
            'cita' => $citaId,
            'servicio' => $servicioId,
            'precio' => $precio,
        ]);
    }

    public static function crearAvisos(PDO $pdo, int $citaId, string $programado): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO aviso_cita (cita_id, destinatario, programado_para, estado)
             VALUES (:cita, :destinatario, :programado, \'pendiente\')'
        );
        foreach (['cliente', 'colaborador'] as $destinatario) {
            $insertar->execute([
                'cita' => $citaId,
                'destinatario' => $destinatario,
                'programado' => $programado,
            ]);
        }
    }

    public static function cambiarEstado(PDO $pdo, int $sede, int $id, string $estado): void
    {
        $guardar = $pdo->prepare(
            'UPDATE cita
             SET estado = :estado
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'estado' => $estado,
            'id' => $id,
            'sede' => $sede,
        ]);
    }
}
