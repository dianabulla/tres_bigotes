<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Permiso;
use TresBigotes\Models\Rol;

final class RolesController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/roles', [self::class, 'listar']);
        $router->registrar('POST', '/api/roles', [self::class, 'crear']);
        $router->registrar('PUT', '/api/roles/{id}', [self::class, 'actualizar']);
        $router->registrar('DELETE', '/api/roles/{id}', [self::class, 'eliminar']);
    }

    public static function listar(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'roles', 'No tienes permiso para administrar los roles');
        Respuesta::json(200, [
            'roles' => Rol::listar(),
            'permisos' => Permiso::catalogo(),
        ]);
    }

    public static function crear(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'roles', 'No tienes permiso para administrar los roles');
        $nombre = self::nombre($peticion->cuerpo);
        $permisos = self::permisos($peticion->cuerpo, []);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $id = Rol::crear($pdo, self::codigo($nombre), $nombre);
            Permiso::reemplazar($pdo, $id, $permisos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        Respuesta::json(200, ['rol' => Rol::buscar($id)]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'roles', 'No tienes permiso para administrar los roles');
        $id = self::id($peticion);
        $actual = Rol::buscar($id);
        if ($actual === null) {
            Respuesta::json(404, ['error' => 'Rol no encontrado']);
        }
        $nombre = self::nombre($peticion->cuerpo);
        $permisos = self::permisos($peticion->cuerpo, $actual['bloqueados']);
        if ($actual['codigo'] === ($peticion->usuario['rol'] ?? '') && !in_array('roles', $permisos, true)) {
            Respuesta::json(422, ['error' => 'No puedes quitarte el permiso de administrar roles']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Rol::actualizar($pdo, $id, $nombre);
            Permiso::reemplazar($pdo, $id, $permisos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, ['rol' => Rol::buscar($id)]);
    }

    public static function eliminar(Peticion $peticion): void
    {
        Acceso::exigir($peticion, 'roles', 'No tienes permiso para administrar los roles');
        $id = self::id($peticion);
        $actual = Rol::buscar($id);
        if ($actual === null) {
            Respuesta::json(404, ['error' => 'Rol no encontrado']);
        }
        if ($actual['sistema']) {
            Respuesta::json(422, ['error' => 'Este rol es del sistema']);
        }
        if ($actual['codigo'] === ($peticion->usuario['rol'] ?? '')) {
            Respuesta::json(422, ['error' => 'No puedes borrar el rol con el que entraste']);
        }
        if (Rol::contarUsuarios($id) > 0) {
            Respuesta::json(422, ['error' => 'Hay usuarios con este rol']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Rol::eliminar($pdo, $id);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, ['ok' => true]);
    }

    private static function nombre(array $cuerpo): string
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            Respuesta::json(422, ['error' => 'El nombre del rol es obligatorio']);
        }
        return $nombre;
    }

    private static function permisos(array $cuerpo, array $bloqueados): array
    {
        $pedidos = $cuerpo['permisos'] ?? null;
        if (!is_array($pedidos)) {
            Respuesta::json(422, ['error' => 'Elige los permisos del rol']);
        }
        $validos = Permiso::codigos();
        $elegidos = [];
        foreach ($pedidos as $codigo) {
            if (!is_string($codigo) || !in_array($codigo, $validos, true)) {
                Respuesta::json(422, ['error' => 'Hay un permiso que no existe']);
            }
            $elegidos[$codigo] = $codigo;
        }
        foreach ($bloqueados as $codigo) {
            $elegidos[$codigo] = $codigo;
        }
        if ($elegidos === []) {
            Respuesta::json(422, ['error' => 'Elige al menos un permiso']);
        }
        return array_values($elegidos);
    }

    private static function codigo(string $nombre): string
    {
        $texto = mb_strtolower($nombre, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
        $texto = trim((string) $texto, '_');
        if ($texto === '' || strlen($texto) > 40) {
            Respuesta::json(422, ['error' => 'El nombre del rol no se puede usar']);
        }
        return $texto;
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id)) {
            Respuesta::json(404, ['error' => 'Rol no encontrado']);
        }
        return (int) $id;
    }

    private static function traducirDuplicado(PDOException $e): void
    {
        if (($e->errorInfo[1] ?? null) === 1062) {
            Respuesta::json(422, ['error' => 'Ya hay un rol con ese nombre']);
        }
        throw $e;
    }
}
