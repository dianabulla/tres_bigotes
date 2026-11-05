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

    public static function ocupados(int $sede, int $colaboradorId, string $desde, string $hasta, ?int $exceptoId = null): array
    {
        $sql = 'SELECT inicio, fin
                FROM cita
                WHERE establecimiento_id = :sede
                  AND colaborador_id = :colaborador
                  AND estado IN (\'pendiente\', \'en_proceso\')
                  AND inicio < :hasta
                  AND fin > :desde';
        $params = [
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :excepto';
            $params['excepto'] = $exceptoId;
        }
        $sql .= ' ORDER BY inicio';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($params);
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

    public static function haySolape(PDO $pdo, int $sede, int $colaboradorId, string $inicio, string $fin, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT id
                FROM cita
                WHERE establecimiento_id = :sede
                  AND colaborador_id = :colaborador
                  AND estado IN (\'pendiente\', \'en_proceso\')
                  AND inicio < :fin
                  AND fin > :inicio';
        $params = [
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'fin' => $fin,
            'inicio' => $inicio,
        ];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :excepto';
            $params['excepto'] = $exceptoId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $consulta = $pdo->prepare($sql);
        $consulta->execute($params);
        return $consulta->fetch() !== false;
    }

    public static function listarPublicasCliente(int $sede, string $telefono, string $correo): array
    {
        $pdo = Conexion::obtener();
        $consulta = $pdo->prepare(
            'SELECT c.id, c.inicio, c.fin, c.estado,
                    co.id AS colaborador_id, co.nombre AS colaborador_nombre,
                    cl.nombre AS cliente_nombre
             FROM cita c
             INNER JOIN cliente cl ON cl.id = c.cliente_id
             INNER JOIN colaborador co ON co.id = c.colaborador_id
             WHERE c.establecimiento_id = :sede
               AND cl.telefono = :telefono
               AND LOWER(cl.correo) = :correo
               AND c.estado = \'pendiente\'
               AND c.inicio >= :ahora
             ORDER BY c.inicio, c.id'
        );
        $consulta->execute([
            'sede' => $sede,
            'telefono' => $telefono,
            'correo' => strtolower($correo),
            'ahora' => date('Y-m-d H:i:s'),
        ]);
        $citas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            $citas[$id] = [
                'id' => $id,
                'inicio' => $fila['inicio'],
                'fin' => $fila['fin'],
                'estado' => $fila['estado'],
                'cliente' => $fila['cliente_nombre'],
                'profesional' => $fila['colaborador_nombre'],
                'colaborador_id' => (int) $fila['colaborador_id'],
                'servicios' => [],
                'servicio_ids' => [],
            ];
        }
        if ($citas === []) {
            return [];
        }
        $ids = implode(',', array_map('intval', array_keys($citas)));
        $servicios = $pdo->query(
            'SELECT cs.cita_id, s.id, s.nombre, s.duracion_minutos
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE cs.cita_id IN (' . $ids . ')
             ORDER BY cs.id'
        );
        foreach ($servicios->fetchAll() as $fila) {
            $citaId = (int) $fila['cita_id'];
            if (!isset($citas[$citaId])) {
                continue;
            }
            $citas[$citaId]['servicios'][] = $fila['nombre'];
            $citas[$citaId]['servicio_ids'][] = (int) $fila['id'];
        }
        return array_values($citas);
    }

    public static function bloquearPublica(PDO $pdo, int $sede, int $id): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT c.id, c.cliente_id, c.colaborador_id, c.estado, c.inicio, c.fin,
                    cl.telefono, cl.correo, cl.nombre AS cliente_nombre,
                    co.nombre AS colaborador_nombre
             FROM cita c
             INNER JOIN cliente cl ON cl.id = c.cliente_id
             INNER JOIN colaborador co ON co.id = c.colaborador_id
             WHERE c.id = :id AND c.establecimiento_id = :sede
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
            'cliente_id' => (int) $fila['cliente_id'],
            'colaborador_id' => (int) $fila['colaborador_id'],
            'estado' => $fila['estado'],
            'inicio' => $fila['inicio'],
            'fin' => $fila['fin'],
            'telefono' => $fila['telefono'],
            'correo' => $fila['correo'],
            'cliente_nombre' => $fila['cliente_nombre'],
            'colaborador_nombre' => $fila['colaborador_nombre'],
        ];
    }

    public static function serviciosDeCita(PDO $pdo, int $citaId): array
    {
        $consulta = $pdo->prepare(
            'SELECT s.id, s.nombre, s.duracion_minutos
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE cs.cita_id = :cita
             ORDER BY cs.id'
        );
        $consulta->execute(['cita' => $citaId]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
                'duracion_minutos' => (int) $fila['duracion_minutos'],
            ];
        }
        return $filas;
    }

    public static function reagendar(PDO $pdo, int $sede, int $id, int $colaboradorId, string $inicio, string $fin): void
    {
        $guardar = $pdo->prepare(
            'UPDATE cita
             SET colaborador_id = :colaborador, inicio = :inicio, fin = :fin
             WHERE id = :id AND establecimiento_id = :sede'
        );
        $guardar->execute([
            'colaborador' => $colaboradorId,
            'inicio' => $inicio,
            'fin' => $fin,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function actualizarPendiente(
        PDO $pdo,
        int $sede,
        int $id,
        int $colaboradorId,
        string $inicio,
        string $fin,
        ?string $notas
    ): void {
        $guardar = $pdo->prepare(
            'UPDATE cita
             SET colaborador_id = :colaborador, inicio = :inicio, fin = :fin, notas = :notas
             WHERE id = :id AND establecimiento_id = :sede AND estado = \'pendiente\''
        );
        $guardar->execute([
            'colaborador' => $colaboradorId,
            'inicio' => $inicio,
            'fin' => $fin,
            'notas' => $notas,
            'id' => $id,
            'sede' => $sede,
        ]);
    }

    public static function borrarServicios(PDO $pdo, int $citaId): void
    {
        $borrar = $pdo->prepare('DELETE FROM cita_servicio WHERE cita_id = :cita');
        $borrar->execute(['cita' => $citaId]);
    }

    public static function reprogramarAvisos(PDO $pdo, int $citaId, string $programado): void
    {
        $borrar = $pdo->prepare(
            'DELETE FROM aviso_cita
             WHERE cita_id = :cita AND estado = \'pendiente\''
        );
        $borrar->execute(['cita' => $citaId]);
        self::crearAvisos($pdo, $citaId, $programado);
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
