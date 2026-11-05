<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;

final class Horario
{
    public static function listarTrabajo(int $sede, ?int $colaboradorId = null): array
    {
        $pdo = Conexion::obtener();
        if ($colaboradorId === null) {
            $consulta = $pdo->prepare(
                'SELECT id, colaborador_id, dia_semana, hora_inicio, hora_fin
                 FROM horario_trabajo
                 WHERE establecimiento_id = :sede AND colaborador_id IS NULL
                 ORDER BY dia_semana, hora_inicio, id'
            );
            $consulta->execute(['sede' => $sede]);
        } else {
            $consulta = $pdo->prepare(
                'SELECT id, colaborador_id, dia_semana, hora_inicio, hora_fin
                 FROM horario_trabajo
                 WHERE establecimiento_id = :sede AND colaborador_id = :colaborador
                 ORDER BY dia_semana, hora_inicio, id'
            );
            $consulta->execute(['sede' => $sede, 'colaborador' => $colaboradorId]);
        }
        return array_map([self::class, 'presentarTramo'], $consulta->fetchAll());
    }

    public static function reemplazarTrabajoSede(PDO $pdo, int $sede, array $tramos): void
    {
        $borrar = $pdo->prepare(
            'DELETE FROM horario_trabajo
             WHERE establecimiento_id = :sede AND colaborador_id IS NULL'
        );
        $borrar->execute(['sede' => $sede]);
        $insertar = $pdo->prepare(
            'INSERT INTO horario_trabajo (establecimiento_id, colaborador_id, dia_semana, hora_inicio, hora_fin)
             VALUES (:sede, NULL, :dia, :inicio, :fin)'
        );
        foreach ($tramos as $tramo) {
            $insertar->execute([
                'sede' => $sede,
                'dia' => $tramo['dia_semana'],
                'inicio' => $tramo['hora_inicio'],
                'fin' => $tramo['hora_fin'],
            ]);
        }
    }

    public static function sembrarSede(PDO $pdo, int $sede): void
    {
        $tramos = [];
        for ($dia = 1; $dia <= 6; $dia++) {
            $tramos[] = [
                'dia_semana' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => '20:00:00',
            ];
        }
        self::reemplazarTrabajoSede($pdo, $sede, $tramos);
    }

    public static function listarBloqueos(int $sede): array
    {
        $pdo = Conexion::obtener();
        $consulta = $pdo->prepare(
            'SELECT b.id, b.colaborador_id, b.titulo, b.dia_semana, b.fecha, b.hora_inicio, b.hora_fin,
                    c.nombre AS colaborador_nombre
             FROM bloqueo_agenda b
             LEFT JOIN colaborador c ON c.id = b.colaborador_id
             WHERE b.establecimiento_id = :sede
             ORDER BY b.fecha IS NULL, b.fecha DESC, b.dia_semana, b.hora_inicio, b.id DESC'
        );
        $consulta->execute(['sede' => $sede]);
        return array_map([self::class, 'presentarBloqueo'], $consulta->fetchAll());
    }

    public static function crearBloqueo(PDO $pdo, int $sede, array $datos): int
    {
        $insertar = $pdo->prepare(
            'INSERT INTO bloqueo_agenda
             (establecimiento_id, colaborador_id, titulo, dia_semana, fecha, hora_inicio, hora_fin)
             VALUES (:sede, :colaborador, :titulo, :dia, :fecha, :inicio, :fin)'
        );
        $insertar->execute([
            'sede' => $sede,
            'colaborador' => $datos['colaborador_id'],
            'titulo' => $datos['titulo'],
            'dia' => $datos['dia_semana'],
            'fecha' => $datos['fecha'],
            'inicio' => $datos['hora_inicio'],
            'fin' => $datos['hora_fin'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function eliminarBloqueo(PDO $pdo, int $sede, int $id): bool
    {
        $borrar = $pdo->prepare(
            'DELETE FROM bloqueo_agenda WHERE establecimiento_id = :sede AND id = :id'
        );
        $borrar->execute(['sede' => $sede, 'id' => $id]);
        return $borrar->rowCount() === 1;
    }

    public static function huecosLibres(int $sede, int $colaboradorId, string $fecha, int $minutos, ?int $exceptoId = null): array
    {
        $tramos = self::tramosDelDia($sede, $colaboradorId, $fecha);
        if ($tramos === []) {
            return [];
        }
        $bloqueos = self::bloqueosDelDia($sede, $colaboradorId, $fecha);
        $ocupados = Cita::ocupados($sede, $colaboradorId, $fecha . ' 00:00:00', $fecha . ' 23:59:59', $exceptoId);
        $horarios = [];
        $ahora = time();
        foreach ($tramos as $tramo) {
            $apertura = strtotime($fecha . ' ' . $tramo['hora_inicio']);
            $cierre = strtotime($fecha . ' ' . $tramo['hora_fin']);
            if ($apertura === false || $cierre === false) {
                continue;
            }
            for ($inicio = $apertura; $inicio + ($minutos * 60) <= $cierre; $inicio += 1800) {
                if ($inicio < $ahora) {
                    continue;
                }
                $fin = $inicio + ($minutos * 60);
                if (self::cruzaBloqueo($inicio, $fin, $bloqueos, $fecha)) {
                    continue;
                }
                $libre = true;
                foreach ($ocupados as $cita) {
                    $ocupadoInicio = strtotime($cita['inicio']);
                    $ocupadoFin = strtotime($cita['fin']);
                    if ($inicio < $ocupadoFin && $fin > $ocupadoInicio) {
                        $libre = false;
                        break;
                    }
                }
                if ($libre) {
                    $horarios[] = date('H:i', $inicio);
                }
            }
        }
        return array_values(array_unique($horarios));
    }

    public static function permitido(int $sede, int $colaboradorId, string $inicio, int $minutos): bool
    {
        $marca = strtotime($inicio);
        if ($marca === false || $marca < time()) {
            return false;
        }
        $fecha = date('Y-m-d', $marca);
        $fin = $marca + ($minutos * 60);
        $tramos = self::tramosDelDia($sede, $colaboradorId, $fecha);
        if ($tramos === []) {
            return false;
        }
        $cabe = false;
        foreach ($tramos as $tramo) {
            $apertura = strtotime($fecha . ' ' . $tramo['hora_inicio']);
            $cierre = strtotime($fecha . ' ' . $tramo['hora_fin']);
            if ($apertura === false || $cierre === false) {
                continue;
            }
            if ($marca >= $apertura && $fin <= $cierre && ($marca - $apertura) % 1800 === 0) {
                $cabe = true;
                break;
            }
        }
        if (!$cabe) {
            return false;
        }
        return !self::cruzaBloqueo($marca, $fin, self::bloqueosDelDia($sede, $colaboradorId, $fecha), $fecha);
    }

    private static function tramosDelDia(int $sede, int $colaboradorId, string $fecha): array
    {
        $pdo = Conexion::obtener();
        $dia = (int) date('N', strtotime($fecha . ' 12:00:00'));
        $consulta = $pdo->prepare(
            'SELECT hora_inicio, hora_fin
             FROM horario_trabajo
             WHERE establecimiento_id = :sede AND colaborador_id = :colaborador AND dia_semana = :dia
             ORDER BY hora_inicio'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'dia' => $dia,
        ]);
        $propios = $consulta->fetchAll();
        if ($propios !== []) {
            return array_map([self::class, 'normalizarTramo'], $propios);
        }
        $sedeConsulta = $pdo->prepare(
            'SELECT hora_inicio, hora_fin
             FROM horario_trabajo
             WHERE establecimiento_id = :sede AND colaborador_id IS NULL AND dia_semana = :dia
             ORDER BY hora_inicio'
        );
        $sedeConsulta->execute(['sede' => $sede, 'dia' => $dia]);
        return array_map([self::class, 'normalizarTramo'], $sedeConsulta->fetchAll());
    }

    private static function bloqueosDelDia(int $sede, int $colaboradorId, string $fecha): array
    {
        $pdo = Conexion::obtener();
        $dia = (int) date('N', strtotime($fecha . ' 12:00:00'));
        $consulta = $pdo->prepare(
            'SELECT hora_inicio, hora_fin
             FROM bloqueo_agenda
             WHERE establecimiento_id = :sede
               AND (colaborador_id IS NULL OR colaborador_id = :colaborador)
               AND ((fecha = :fecha) OR (dia_semana = :dia))'
        );
        $consulta->execute([
            'sede' => $sede,
            'colaborador' => $colaboradorId,
            'fecha' => $fecha,
            'dia' => $dia,
        ]);
        return array_map([self::class, 'normalizarBloqueo'], $consulta->fetchAll());
    }

    private static function cruzaBloqueo(int $inicio, int $fin, array $bloqueos, string $fecha): bool
    {
        foreach ($bloqueos as $bloqueo) {
            if ($bloqueo['todo_el_dia']) {
                return true;
            }
            $bloqueoInicio = strtotime($fecha . ' ' . $bloqueo['hora_inicio']);
            $bloqueoFin = strtotime($fecha . ' ' . $bloqueo['hora_fin']);
            if ($bloqueoInicio === false || $bloqueoFin === false) {
                continue;
            }
            if ($inicio < $bloqueoFin && $fin > $bloqueoInicio) {
                return true;
            }
        }
        return false;
    }

    private static function normalizarTramo(array $fila): array
    {
        return [
            'hora_inicio' => self::hora($fila['hora_inicio']),
            'hora_fin' => self::hora($fila['hora_fin']),
        ];
    }

    private static function normalizarBloqueo(array $fila): array
    {
        $todo = $fila['hora_inicio'] === null || $fila['hora_fin'] === null;
        return [
            'todo_el_dia' => $todo,
            'hora_inicio' => $todo ? null : self::hora($fila['hora_inicio']),
            'hora_fin' => $todo ? null : self::hora($fila['hora_fin']),
        ];
    }

    private static function presentarTramo(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'colaborador_id' => $fila['colaborador_id'] === null ? null : (int) $fila['colaborador_id'],
            'dia_semana' => (int) $fila['dia_semana'],
            'hora_inicio' => substr(self::hora($fila['hora_inicio']), 0, 5),
            'hora_fin' => substr(self::hora($fila['hora_fin']), 0, 5),
        ];
    }

    private static function presentarBloqueo(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'colaborador_id' => $fila['colaborador_id'] === null ? null : (int) $fila['colaborador_id'],
            'colaborador' => $fila['colaborador_nombre'],
            'titulo' => $fila['titulo'],
            'dia_semana' => $fila['dia_semana'] === null ? null : (int) $fila['dia_semana'],
            'fecha' => $fila['fecha'],
            'hora_inicio' => $fila['hora_inicio'] === null ? null : substr(self::hora($fila['hora_inicio']), 0, 5),
            'hora_fin' => $fila['hora_fin'] === null ? null : substr(self::hora($fila['hora_fin']), 0, 5),
            'todo_el_dia' => $fila['hora_inicio'] === null || $fila['hora_fin'] === null,
        ];
    }

    private static function hora(mixed $valor): string
    {
        $texto = (string) $valor;
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $texto) === 1) {
            return $texto;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $texto) === 1) {
            return $texto . ':00';
        }
        return date('H:i:s', strtotime($texto) ?: 0);
    }
}
