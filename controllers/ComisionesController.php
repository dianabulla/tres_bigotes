<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use DateTime;
use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Cita;
use TresBigotes\Models\Comision;

final class ComisionesController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/comisiones', [self::class, 'ver']);
        $router->registrar('POST', '/api/comisiones/reglas', [self::class, 'crearRegla']);
        $router->registrar('PUT', '/api/comisiones/reglas/{id}', [self::class, 'actualizarRegla']);
        $router->registrar('DELETE', '/api/comisiones/reglas/{id}', [self::class, 'eliminarRegla']);
        $router->registrar('POST', '/api/comisiones/liquidaciones', [self::class, 'liquidar']);
        $router->registrar('GET', '/api/comisiones/liquidaciones/{id}', [self::class, 'detalle']);
        $router->registrar('DELETE', '/api/comisiones/liquidaciones/{id}', [self::class, 'anular']);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $propio = self::profesionalPropio($peticion, $sede);
        if ($propio !== null) {
            Respuesta::json(200, [
                'reglas' => [],
                'servicios' => [],
                'profesionales' => [],
                'liquidaciones' => Comision::liquidaciones($sede, $propio),
            ]);
        }
        Respuesta::json(200, [
            'reglas' => Comision::reglas($sede),
            'servicios' => Comision::servicios($sede),
            'profesionales' => Comision::profesionales($sede),
            'liquidaciones' => Comision::liquidaciones($sede, null),
        ]);
    }

    public static function crearRegla(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $datos = self::leerRegla($sede, $peticion->cuerpo, null);
        $pdo = Conexion::obtener();
        Comision::crearRegla($pdo, $sede, $datos);
        self::ver($peticion);
    }

    public static function actualizarRegla(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion, 'Regla no encontrada');
        $encontrada = false;
        foreach (Comision::reglas($sede) as $regla) {
            if ($regla['id'] === $id) {
                $encontrada = true;
            }
        }
        if (!$encontrada) {
            Respuesta::json(404, ['error' => 'Regla no encontrada']);
        }
        $datos = self::leerRegla($sede, $peticion->cuerpo, $id);
        $pdo = Conexion::obtener();
        Comision::actualizarRegla($pdo, $sede, $id, $datos);
        self::ver($peticion);
    }

    public static function eliminarRegla(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion, 'Regla no encontrada');
        if (!Comision::eliminarRegla(Conexion::obtener(), $sede, $id)) {
            Respuesta::json(404, ['error' => 'Regla no encontrada']);
        }
        self::ver($peticion);
    }

    public static function liquidar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $colaboradorId = $peticion->cuerpo['colaborador_id'] ?? null;
        if (!is_numeric($colaboradorId) || (int) $colaboradorId < 1 || (int) $colaboradorId != $colaboradorId) {
            Respuesta::json(422, ['error' => 'Elige un profesional']);
        }
        $desde = self::dia($peticion->cuerpo['desde'] ?? null, '00:00:00', 'Indica la fecha inicial');
        $hasta = self::dia($peticion->cuerpo['hasta'] ?? null, '23:59:59', 'Indica la fecha final');
        if ($hasta < $desde) {
            Respuesta::json(422, ['error' => 'El rango de la liquidación no es válido']);
        }
        $inicio = new DateTime(substr($desde, 0, 10));
        $fin = new DateTime(substr($hasta, 0, 10));
        if ($inicio->diff($fin)->days > 366) {
            Respuesta::json(422, ['error' => 'La liquidación no puede pasar de un año']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $profesional = Cita::bloquearProfesional($pdo, $sede, (int) $colaboradorId);
            if ($profesional === null) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Profesional no encontrado']);
            }
            $resultado = Comision::liquidar($pdo, $sede, (int) $colaboradorId, $desde, $hasta);
            if (isset($resultado['error'])) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => $resultado['error']]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, ['liquidacion' => $resultado['liquidacion']]);
    }

    public static function detalle(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $id = self::id($peticion, 'Liquidación no encontrada');
        $detalle = Comision::detalle($sede, $id, self::profesionalPropio($peticion, $sede));
        if ($detalle === null) {
            Respuesta::json(404, ['error' => 'Liquidación no encontrada']);
        }
        Respuesta::json(200, ['liquidacion' => $detalle]);
    }

    public static function anular(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion, 'Liquidación no encontrada');
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            if (!Comision::anular($pdo, $sede, $id)) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Liquidación no encontrada']);
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

    private static function leerRegla(int $sede, array $cuerpo, ?int $ignorar): array
    {
        $servicioId = $cuerpo['servicio_id'] ?? null;
        if ($servicioId === '' || $servicioId === null) {
            $servicioId = null;
        } elseif (!is_numeric($servicioId) || (int) $servicioId < 1 || !Comision::servicioDeSede($sede, (int) $servicioId)) {
            Respuesta::json(404, ['error' => 'Servicio no encontrado']);
        } else {
            $servicioId = (int) $servicioId;
        }
        $porcentaje = self::porcentaje($cuerpo['porcentaje'] ?? null);
        if ($porcentaje === null) {
            Respuesta::json(422, ['error' => 'El porcentaje debe estar entre 0 y 100']);
        }
        $umbral = $cuerpo['umbral_cantidad'] ?? null;
        $periodo = $cuerpo['periodo'] ?? null;
        if ($umbral === '' || $umbral === null) {
            $umbral = null;
            $periodo = null;
        } else {
            if (!is_numeric($umbral) || (int) $umbral < 1 || (int) $umbral != $umbral) {
                Respuesta::json(422, ['error' => 'El tramo necesita una cantidad mayor que cero']);
            }
            $umbral = (int) $umbral;
            if ($periodo !== 'dia' && $periodo !== 'semana') {
                Respuesta::json(422, ['error' => 'El tramo debe ser por día o por semana']);
            }
        }
        if ($umbral === null && Comision::baseRepetida($sede, $servicioId, $ignorar)) {
            Respuesta::json(422, ['error' => 'Ya hay un porcentaje base para ese servicio']);
        }
        return [
            'servicio_id' => $servicioId,
            'porcentaje' => $porcentaje,
            'umbral_cantidad' => $umbral,
            'periodo' => $periodo,
        ];
    }

    private static function exigirConsulta(Peticion $peticion): int
    {
        return Acceso::exigirAlguno($peticion, ['comisiones', 'comisiones.propias'], 'No tienes permiso para ver las comisiones');
    }

    private static function exigirAdministrador(Peticion $peticion): int
    {
        return Acceso::exigir($peticion, 'comisiones', 'No tienes permiso para liquidar comisiones');
    }

    private static function profesionalPropio(Peticion $peticion, int $sede): ?int
    {
        if (Acceso::tiene($peticion, 'comisiones') || !Acceso::tiene($peticion, 'comisiones.propias')) {
            return null;
        }
        $profesional = Cita::profesionalDeUsuario($sede, (int) $peticion->usuario['id']);
        if ($profesional === null || !$profesional['activo']) {
            Respuesta::json(403, ['error' => 'No tienes permiso para ver las comisiones']);
        }
        return $profesional['id'];
    }

    private static function id(Peticion $peticion, string $mensaje): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => $mensaje]);
        }
        return (int) $id;
    }

    private static function dia($valor, string $hora, string $mensaje): string
    {
        if (!is_string($valor)) {
            Respuesta::json(422, ['error' => $mensaje]);
        }
        $valor = substr(trim($valor), 0, 10);
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            Respuesta::json(422, ['error' => $mensaje]);
        }
        return $valor . ' ' . $hora;
    }

    private static function porcentaje($valor): ?string
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
        if ((float) $normal > 100) {
            return null;
        }
        return $normal;
    }
}
