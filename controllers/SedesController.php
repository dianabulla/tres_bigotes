<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Establecimiento;
use TresBigotes\Models\Usuario;

final class SedesController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/sedes', [self::class, 'listar']);
        $router->registrar('POST', '/api/sedes', [self::class, 'crear']);
        $router->registrar('PUT', '/api/sedes/{id}', [self::class, 'actualizar']);
    }

    public static function listar(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'sedes', 'No tienes permiso para administrar las sedes');
        $actual = (int) $peticion->usuario['establecimiento_id'];
        $sedes = Establecimiento::listar();
        foreach ($sedes as $indice => $sede) {
            $sedes[$indice]['es_actual'] = $sede['id'] === $actual;
        }
        Respuesta::json(200, ['sedes' => $sedes]);
    }

    public static function crear(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'sedes', 'No tienes permiso para administrar las sedes');
        $datos = self::leerSede($peticion->cuerpo, true);
        $admin = self::leerAdmin($peticion->cuerpo);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $id = Establecimiento::crear($pdo, $datos);
            $usuarioId = Usuario::crearConRol($pdo, $id, 'administrador', $admin);
            if ($usuarioId === 0) {
                $pdo->rollBack();
                Respuesta::json(500, ['error' => 'Error interno']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        $sede = Establecimiento::buscar($id);
        $sede['es_actual'] = false;
        Respuesta::json(200, ['sede' => $sede]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'sedes', 'No tienes permiso para administrar las sedes');
        $id = self::id($peticion);
        if (Establecimiento::buscar($id) === null) {
            Respuesta::json(404, ['error' => 'Sede no encontrada']);
        }
        $datos = self::leerSede($peticion->cuerpo, false);
        if (!$datos['activo'] && $id === (int) $peticion->usuario['establecimiento_id']) {
            Respuesta::json(422, ['error' => 'No puedes dar de baja la sede con la que entraste']);
        }
        $pdo = Conexion::obtener();
        Establecimiento::actualizar($pdo, $id, $datos);
        $sede = Establecimiento::buscar($id);
        $sede['es_actual'] = $id === (int) $peticion->usuario['establecimiento_id'];
        Respuesta::json(200, ['sede' => $sede]);
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id)) {
            Respuesta::json(404, ['error' => 'Sede no encontrada']);
        }
        return (int) $id;
    }

    private static function leerSede(array $cuerpo, bool $esAlta): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre de la sede es obligatorio']);
        }
        $direccion = trim((string) ($cuerpo['direccion'] ?? ''));
        if (mb_strlen($direccion) > 180) {
            Respuesta::json(422, ['error' => 'La dirección no puede pasar de 180 caracteres']);
        }
        $telefono = trim((string) ($cuerpo['telefono'] ?? ''));
        if (mb_strlen($telefono) > 20) {
            Respuesta::json(422, ['error' => 'El teléfono no puede pasar de 20 caracteres']);
        }
        $activo = true;
        if (!$esAlta) {
            $activo = self::booleano($cuerpo['activo'] ?? null);
            if ($activo === null) {
                Respuesta::json(422, ['error' => 'Indica si la sede está activa']);
            }
        }
        return [
            'nombre' => $nombre,
            'direccion' => $direccion === '' ? null : $direccion,
            'telefono' => $telefono === '' ? null : $telefono,
            'activo' => $activo,
        ];
    }

    private static function leerAdmin(array $cuerpo): array
    {
        $admin = $cuerpo['administrador'] ?? null;
        if (!is_array($admin)) {
            Respuesta::json(422, ['error' => 'Indica el administrador de la sede nueva']);
        }
        $nombre = trim((string) ($admin['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre del administrador es obligatorio']);
        }
        $correo = strtolower(trim((string) ($admin['correo'] ?? '')));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 160) {
            Respuesta::json(422, ['error' => 'El correo del administrador no es válido']);
        }
        $clave = (string) ($admin['clave'] ?? '');
        if (strlen($clave) < 8) {
            Respuesta::json(422, ['error' => 'La clave del administrador debe tener al menos 8 caracteres']);
        }
        return [
            'nombre' => $nombre,
            'correo' => $correo,
            'clave' => $clave,
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
