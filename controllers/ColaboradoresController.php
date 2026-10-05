<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use DateTime;
use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Colaborador;
use TresBigotes\Models\Usuario;

final class ColaboradoresController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/colaboradores', [self::class, 'listar']);
        $router->registrar('GET', '/api/colaboradores/{id}', [self::class, 'ver']);
        $router->registrar('POST', '/api/colaboradores', [self::class, 'crear']);
        $router->registrar('PUT', '/api/colaboradores/{id}', [self::class, 'actualizar']);
    }

    public static function listar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        Respuesta::json(200, ['colaboradores' => Colaborador::listar($sede)]);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $fila = Colaborador::buscar($sede, self::id($peticion));
        if ($fila === null) {
            Respuesta::json(404, ['error' => 'Colaborador no encontrado']);
        }
        Respuesta::json(200, ['colaborador' => Colaborador::presentar($fila)]);
    }

    public static function crear(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $datos = self::leerDatos($peticion->cuerpo, true);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $usuarioId = null;
            if ($datos['correo'] !== '') {
                $usuarioId = Usuario::crearAcceso($pdo, $sede, $datos);
                if ($usuarioId === 0) {
                    $pdo->rollBack();
                    Respuesta::json(500, ['error' => 'Error interno']);
                }
            }
            $id = Colaborador::crear($pdo, $sede, $datos, $usuarioId);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        $fila = Colaborador::buscar($sede, $id);
        Respuesta::json(200, ['colaborador' => Colaborador::presentar($fila)]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion);
        $actual = Colaborador::buscar($sede, $id);
        if ($actual === null) {
            Respuesta::json(404, ['error' => 'Colaborador no encontrado']);
        }
        $datos = self::leerDatos($peticion->cuerpo, false);
        if ($actual['usuario_id'] !== null && $datos['correo'] === '') {
            Respuesta::json(422, ['error' => 'El colaborador ya tiene acceso; el correo es obligatorio']);
        }
        if ($actual['usuario_id'] === null && $datos['correo'] !== '' && $datos['clave'] === '') {
            Respuesta::json(422, ['error' => 'La clave es obligatoria para crear el acceso']);
        }

        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $usuarioId = $actual['usuario_id'] !== null ? (int) $actual['usuario_id'] : null;
            if ($usuarioId === null && $datos['correo'] !== '') {
                $usuarioId = Usuario::crearAcceso($pdo, $sede, $datos);
                if ($usuarioId === 0) {
                    $pdo->rollBack();
                    Respuesta::json(500, ['error' => 'Error interno']);
                }
            } elseif ($usuarioId !== null) {
                Usuario::actualizarAcceso($pdo, $sede, $usuarioId, $datos);
            }
            Colaborador::actualizar($pdo, $sede, $id, $datos, $usuarioId);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        $fila = Colaborador::buscar($sede, $id);
        Respuesta::json(200, ['colaborador' => Colaborador::presentar($fila)]);
    }

    private static function exigirAdministrador(Peticion $peticion): int
    {
        if (($peticion->usuario['rol'] ?? '') !== 'administrador') {
            Respuesta::json(403, ['error' => 'No tienes permiso para gestionar colaboradores']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => 'Colaborador no encontrado']);
        }
        return (int) $id;
    }

    private static function leerDatos(array $cuerpo, bool $esAlta): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre es obligatorio y no puede pasar de 120 caracteres']);
        }

        $telefono = trim((string) ($cuerpo['telefono'] ?? ''));
        if ($telefono === '') {
            $telefono = null;
        } elseif (mb_strlen($telefono) > 20) {
            Respuesta::json(422, ['error' => 'El teléfono no puede pasar de 20 caracteres']);
        }

        $fecha = trim((string) ($cuerpo['fecha_ingreso'] ?? ''));
        if ($fecha === '') {
            $fecha = null;
        } else {
            $interpretada = DateTime::createFromFormat('Y-m-d', $fecha);
            $errores = DateTime::getLastErrors();
            $mal = $errores !== false && (($errores['warning_count'] ?? 0) > 0 || ($errores['error_count'] ?? 0) > 0);
            if (!$interpretada || $mal || $interpretada->format('Y-m-d') !== $fecha) {
                Respuesta::json(422, ['error' => 'La fecha de ingreso debe tener el formato AAAA-MM-DD']);
            }
        }

        $correo = strtolower(trim((string) ($cuerpo['correo'] ?? '')));
        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 160)) {
            Respuesta::json(422, ['error' => 'El correo no es válido']);
        }

        $clave = (string) ($cuerpo['clave'] ?? '');
        if ($esAlta && $correo !== '' && strlen($clave) < 8) {
            Respuesta::json(422, ['error' => 'La clave debe tener al menos 8 caracteres']);
        }
        if (!$esAlta && $clave !== '' && strlen($clave) < 8) {
            Respuesta::json(422, ['error' => 'La clave debe tener al menos 8 caracteres']);
        }

        $activo = true;
        if (!$esAlta) {
            $activo = self::booleano($cuerpo['activo'] ?? null);
            if ($activo === null) {
                Respuesta::json(422, ['error' => 'Indica si el colaborador está activo']);
            }
        }

        return [
            'nombre' => $nombre,
            'telefono' => $telefono,
            'fecha_ingreso' => $fecha,
            'correo' => $correo,
            'clave' => $clave,
            'activo' => $activo,
        ];
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

    private static function traducirDuplicado(PDOException $e): void
    {
        if (($e->errorInfo[1] ?? null) === 1062) {
            Respuesta::json(422, ['error' => 'Ese correo ya está registrado']);
        }
        throw $e;
    }
}
