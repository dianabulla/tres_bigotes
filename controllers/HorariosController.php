<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Cita;
use TresBigotes\Models\Horario;

final class HorariosController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/horarios', [self::class, 'ver']);
        $router->registrar('PUT', '/api/horarios/trabajo', [self::class, 'guardarTrabajo']);
        $router->registrar('POST', '/api/horarios/bloqueos', [self::class, 'crearBloqueo']);
        $router->registrar('DELETE', '/api/horarios/bloqueos/{id}', [self::class, 'eliminarBloqueo']);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = Acceso::exigir($peticion, 'horarios', 'No tienes permiso para gestionar horarios');
        Respuesta::json(200, [
            'horarios' => Horario::listarTrabajo($sede),
            'bloqueos' => Horario::listarBloqueos($sede),
            'profesionales' => Cita::profesionales($sede),
        ]);
    }

    public static function guardarTrabajo(Peticion $peticion): void
    {
        $sede = Acceso::exigir($peticion, 'horarios', 'No tienes permiso para gestionar horarios');
        $tramos = self::leerTramos($peticion->cuerpo['tramos'] ?? null);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Horario::reemplazarTrabajoSede($pdo, $sede, $tramos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, [
            'horarios' => Horario::listarTrabajo($sede),
            'bloqueos' => Horario::listarBloqueos($sede),
            'profesionales' => Cita::profesionales($sede),
        ]);
    }

    public static function crearBloqueo(Peticion $peticion): void
    {
        $sede = Acceso::exigir($peticion, 'horarios', 'No tienes permiso para gestionar horarios');
        $datos = self::leerBloqueo($peticion->cuerpo, $sede);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Horario::crearBloqueo($pdo, $sede, $datos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, [
            'horarios' => Horario::listarTrabajo($sede),
            'bloqueos' => Horario::listarBloqueos($sede),
            'profesionales' => Cita::profesionales($sede),
        ]);
    }

    public static function eliminarBloqueo(Peticion $peticion): void
    {
        $sede = Acceso::exigir($peticion, 'horarios', 'No tienes permiso para gestionar horarios');
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => 'Bloqueo no encontrado']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            if (!Horario::eliminarBloqueo($pdo, $sede, (int) $id)) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Bloqueo no encontrado']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, [
            'horarios' => Horario::listarTrabajo($sede),
            'bloqueos' => Horario::listarBloqueos($sede),
            'profesionales' => Cita::profesionales($sede),
        ]);
    }

    private static function leerTramos(mixed $crudo): array
    {
        if (!is_array($crudo)) {
            Respuesta::json(422, ['error' => 'Envía los tramos del horario semanal']);
        }
        $tramos = [];
        foreach ($crudo as $item) {
            if (!is_array($item)) {
                Respuesta::json(422, ['error' => 'Cada tramo debe indicar día e intervalo']);
            }
            $dia = $item['dia_semana'] ?? null;
            if (!is_numeric($dia) || (int) $dia < 1 || (int) $dia > 7 || (int) $dia != $dia) {
                Respuesta::json(422, ['error' => 'El día de la semana debe ser de 1 (lunes) a 7 (domingo)']);
            }
            $inicio = self::hora($item['hora_inicio'] ?? null, 'hora de inicio');
            $fin = self::hora($item['hora_fin'] ?? null, 'hora de fin');
            if ($fin <= $inicio) {
                Respuesta::json(422, ['error' => 'La hora de fin debe ser posterior al inicio']);
            }
            $tramos[] = [
                'dia_semana' => (int) $dia,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
            ];
        }
        usort($tramos, static function (array $a, array $b): int {
            return [$a['dia_semana'], $a['hora_inicio']] <=> [$b['dia_semana'], $b['hora_inicio']];
        });
        for ($i = 1, $n = count($tramos); $i < $n; $i++) {
            $prev = $tramos[$i - 1];
            $actual = $tramos[$i];
            if ($prev['dia_semana'] === $actual['dia_semana'] && $actual['hora_inicio'] < $prev['hora_fin']) {
                Respuesta::json(422, ['error' => 'Hay tramos solapados el mismo día']);
            }
        }
        return $tramos;
    }

    private static function leerBloqueo(array $cuerpo, int $sede): array
    {
        $titulo = trim((string) ($cuerpo['titulo'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 120) {
            Respuesta::json(422, ['error' => 'El bloqueo necesita un título de hasta 120 caracteres']);
        }
        $colaboradorId = $cuerpo['colaborador_id'] ?? null;
        if ($colaboradorId === '' || $colaboradorId === null) {
            $colaboradorId = null;
        } elseif (!is_numeric($colaboradorId) || (int) $colaboradorId < 1 || (int) $colaboradorId != $colaboradorId) {
            Respuesta::json(422, ['error' => 'Profesional no válido']);
        } else {
            $colaboradorId = (int) $colaboradorId;
            $existe = false;
            foreach (Cita::profesionales($sede) as $profesional) {
                if ($profesional['id'] === $colaboradorId) {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                Respuesta::json(404, ['error' => 'Profesional no encontrado']);
            }
        }

        $tipo = (string) ($cuerpo['tipo'] ?? '');
        $todoElDia = !empty($cuerpo['todo_el_dia']);
        $horaInicio = null;
        $horaFin = null;
        if (!$todoElDia) {
            $horaInicio = self::hora($cuerpo['hora_inicio'] ?? null, 'hora de inicio');
            $horaFin = self::hora($cuerpo['hora_fin'] ?? null, 'hora de fin');
            if ($horaFin <= $horaInicio) {
                Respuesta::json(422, ['error' => 'La hora de fin debe ser posterior al inicio']);
            }
        }

        if ($tipo === 'recurrente') {
            $dia = $cuerpo['dia_semana'] ?? null;
            if (!is_numeric($dia) || (int) $dia < 1 || (int) $dia > 7 || (int) $dia != $dia) {
                Respuesta::json(422, ['error' => 'Elige el día de la semana del bloqueo']);
            }
            if ($todoElDia) {
                Respuesta::json(422, ['error' => 'Un bloqueo semanal necesita hora de inicio y fin (por ejemplo almuerzo). Para cerrar un día quita ese día del horario semanal']);
            }
            return [
                'colaborador_id' => $colaboradorId,
                'titulo' => $titulo,
                'dia_semana' => (int) $dia,
                'fecha' => null,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
            ];
        }

        if ($tipo === 'fecha') {
            $fecha = $cuerpo['fecha'] ?? null;
            if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                Respuesta::json(422, ['error' => 'Elige la fecha del bloqueo']);
            }
            return [
                'colaborador_id' => $colaboradorId,
                'titulo' => $titulo,
                'dia_semana' => null,
                'fecha' => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
            ];
        }

        Respuesta::json(422, ['error' => 'El bloqueo debe ser recurrente o de una fecha']);
    }

    private static function hora(mixed $valor, string $etiqueta): string
    {
        if (!is_string($valor) || !preg_match('/^\d{2}:\d{2}$/', $valor)) {
            Respuesta::json(422, ['error' => 'Indica la ' . $etiqueta . ' (HH:MM)']);
        }
        [$h, $m] = array_map('intval', explode(':', $valor));
        if ($h > 23 || $m > 59) {
            Respuesta::json(422, ['error' => 'Indica la ' . $etiqueta . ' (HH:MM)']);
        }
        return sprintf('%02d:%02d:00', $h, $m);
    }
}
