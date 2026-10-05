<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Ficha
{
    public static function clientesProfesional(int $sede, int $colaboradorId, string $texto): array
    {
        $sql = 'SELECT cl.id, cl.nombre, cl.telefono, MAX(c.inicio) AS ultima
                FROM cliente cl
                INNER JOIN cita c ON c.cliente_id = cl.id AND c.establecimiento_id = :sede
                WHERE c.colaborador_id = :colaborador';
        $params = ['sede' => $sede, 'colaborador' => $colaboradorId];
        if ($texto !== '') {
            $sql .= ' AND (cl.nombre LIKE :texto OR cl.telefono LIKE :telefono)';
            $params['texto'] = '%' . $texto . '%';
            $params['telefono'] = '%' . $texto . '%';
        }
        $sql .= ' GROUP BY cl.id, cl.nombre, cl.telefono
                  ORDER BY ultima DESC, cl.nombre
                  LIMIT 30';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($params);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
                'telefono' => $fila['telefono'],
            ];
        }
        return $filas;
    }

    public static function tieneCita(int $sede, int $clienteId, int $colaboradorId): bool
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id
             FROM cita
             WHERE establecimiento_id = :sede
               AND cliente_id = :cliente
               AND colaborador_id = :colaborador
             LIMIT 1'
        );
        $consulta->execute([
            'sede' => $sede,
            'cliente' => $clienteId,
            'colaborador' => $colaboradorId,
        ]);
        return $consulta->fetch() !== false;
    }

    public static function leer(int $sede, int $clienteId): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT f.preferencias, f.cortes_habituales, f.formulas
             FROM ficha_cliente f
             INNER JOIN cliente c ON c.id = f.cliente_id
             WHERE f.cliente_id = :cliente AND c.establecimiento_id = :sede
             LIMIT 1'
        );
        $consulta->execute(['cliente' => $clienteId, 'sede' => $sede]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'preferencias' => $fila['preferencias'],
            'cortes_habituales' => $fila['cortes_habituales'],
            'formulas' => $fila['formulas'],
        ];
    }

    public static function guardar(PDO $pdo, int $clienteId, array $datos): void
    {
        $guardar = $pdo->prepare(
            'INSERT INTO ficha_cliente (cliente_id, preferencias, cortes_habituales, formulas)
             VALUES (:cliente, :preferencias, :cortes, :formulas)
             ON DUPLICATE KEY UPDATE
               preferencias = :preferencias_nueva,
               cortes_habituales = :cortes_nuevos,
               formulas = :formulas_nuevas'
        );
        $guardar->execute([
            'cliente' => $clienteId,
            'preferencias' => $datos['preferencias'],
            'cortes' => $datos['cortes_habituales'],
            'formulas' => $datos['formulas'],
            'preferencias_nueva' => $datos['preferencias'],
            'cortes_nuevos' => $datos['cortes_habituales'],
            'formulas_nuevas' => $datos['formulas'],
        ]);
    }

    public static function notas(int $sede, int $clienteId): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT n.id, n.observacion, n.creado_en, n.cita_id, c.inicio, co.nombre AS profesional
             FROM nota_visita n
             INNER JOIN cliente cl ON cl.id = n.cliente_id
             INNER JOIN colaborador co ON co.id = n.colaborador_id
             LEFT JOIN cita c ON c.id = n.cita_id
             WHERE n.cliente_id = :cliente AND cl.establecimiento_id = :sede
             ORDER BY n.creado_en DESC, n.id DESC'
        );
        $consulta->execute(['cliente' => $clienteId, 'sede' => $sede]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'observacion' => $fila['observacion'],
                'creado_en' => $fila['creado_en'],
                'cita_id' => $fila['cita_id'] !== null ? (int) $fila['cita_id'] : null,
                'inicio' => $fila['inicio'],
                'profesional' => $fila['profesional'],
            ];
        }
        return $filas;
    }

    public static function visitasSinNota(int $sede, int $clienteId, int $colaboradorId): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT c.id, c.inicio, c.estado
             FROM cita c
             WHERE c.establecimiento_id = :sede
               AND c.cliente_id = :cliente
               AND c.colaborador_id = :colaborador
               AND c.estado <> \'cancelada\'
               AND NOT EXISTS (
                 SELECT 1 FROM nota_visita n WHERE n.cita_id = c.id
               )
             ORDER BY c.inicio DESC, c.id DESC'
        );
        $consulta->execute([
            'sede' => $sede,
            'cliente' => $clienteId,
            'colaborador' => $colaboradorId,
        ]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'inicio' => $fila['inicio'],
                'estado' => $fila['estado'],
            ];
        }
        return $filas;
    }

    public static function bloquearCita(PDO $pdo, int $sede, int $clienteId, int $colaboradorId, int $citaId): ?array
    {
        $consulta = $pdo->prepare(
            'SELECT id, estado
             FROM cita
             WHERE id = :id
               AND establecimiento_id = :sede
               AND cliente_id = :cliente
               AND colaborador_id = :colaborador
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute([
            'id' => $citaId,
            'sede' => $sede,
            'cliente' => $clienteId,
            'colaborador' => $colaboradorId,
        ]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'id' => (int) $fila['id'],
            'estado' => $fila['estado'],
        ];
    }

    public static function tieneNota(PDO $pdo, int $citaId): bool
    {
        $consulta = $pdo->prepare(
            'SELECT id FROM nota_visita WHERE cita_id = :cita LIMIT 1 FOR UPDATE'
        );
        $consulta->execute(['cita' => $citaId]);
        return $consulta->fetch() !== false;
    }

    public static function crearNota(PDO $pdo, int $clienteId, int $citaId, int $colaboradorId, string $observacion): void
    {
        $insertar = $pdo->prepare(
            'INSERT INTO nota_visita (cliente_id, cita_id, colaborador_id, observacion, creado_en)
             VALUES (:cliente, :cita, :colaborador, :observacion, :creado_en)'
        );
        $insertar->execute([
            'cliente' => $clienteId,
            'cita' => $citaId,
            'colaborador' => $colaboradorId,
            'observacion' => $observacion,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
    }
}
