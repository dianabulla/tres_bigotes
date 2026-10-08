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
use TresBigotes\Models\Usuario;

final class UsuariosController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/usuarios', [self::class, 'listar']);
        $router->registrar('POST', '/api/usuarios', [self::class, 'crear']);
        $router->registrar('PUT', '/api/usuarios/{id}', [self::class, 'actualizar']);
    }

    public static function listar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        Respuesta::json(200, [
            'usuarios' => Usuario::listarPorSede($sede),
            'roles' => Rol::opciones(),
        ]);
    }

    public static function crear(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $datos = self::leer($peticion->cuerpo, true);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $id = Usuario::crearConRol($pdo, $sede, $datos['rol'], $datos);
            if ($id === 0) {
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
        Respuesta::json(200, ['usuario' => Usuario::buscarEnSede($sede, $id)]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion);
        $actual = Usuario::buscarEnSede($sede, $id);
        if ($actual === null) {
            Respuesta::json(404, ['error' => 'Usuario no encontrado']);
        }
        $datos = self::leer($peticion->cuerpo, false);
        if ($id === (int) $peticion->usuario['id'] && (!$datos['activo'] || $datos['rol'] !== $actual['rol'])) {
            Respuesta::json(422, ['error' => 'No puedes cambiar tu propio rol ni darte de baja']);
        }
        $pdo = Conexion::obtener();
        $teniaUsuarios = Permiso::rolTiene($pdo, $actual['rol'], 'usuarios');
        $conservaUsuarios = $datos['activo'] && Permiso::rolTiene($pdo, $datos['rol'], 'usuarios');
        if ($teniaUsuarios && !$conservaUsuarios && Usuario::otrosConPermiso($pdo, $sede, $id, 'usuarios') < 1) {
            Respuesta::json(422, ['error' => 'La sede debe conservar un usuario que pueda administrar usuarios']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Usuario::actualizarCuenta($pdo, $sede, $id, $datos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        Respuesta::json(200, ['usuario' => Usuario::buscarEnSede($sede, $id)]);
    }

    private static function exigirAdministrador(Peticion $peticion): int
    {
        return Acceso::exigir($peticion, 'usuarios', 'No tienes permiso para administrar los usuarios');
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id)) {
            Respuesta::json(404, ['error' => 'Usuario no encontrado']);
        }
        return (int) $id;
    }

    private static function leer(array $cuerpo, bool $esAlta): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre es obligatorio']);
        }
        $correo = strtolower(trim((string) ($cuerpo['correo'] ?? '')));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 160) {
            Respuesta::json(422, ['error' => 'El correo no es válido']);
        }
        $clave = (string) ($cuerpo['clave'] ?? '');
        if ($esAlta && strlen($clave) < 8) {
            Respuesta::json(422, ['error' => 'La clave debe tener al menos 8 caracteres']);
        }
        if (!$esAlta && $clave !== '' && strlen($clave) < 8) {
            Respuesta::json(422, ['error' => 'La clave debe tener al menos 8 caracteres']);
        }
        $rol = trim((string) ($cuerpo['rol'] ?? ''));
        if (Rol::buscarPorCodigo($rol) === null) {
            Respuesta::json(422, ['error' => 'Elige un rol']);
        }
        $activo = true;
        if (!$esAlta) {
            $activo = self::booleano($cuerpo['activo'] ?? null);
            if ($activo === null) {
                Respuesta::json(422, ['error' => 'Indica si el usuario está activo']);
            }
        }
        return [
            'nombre' => $nombre,
            'correo' => $correo,
            'clave' => $clave,
            'rol' => $rol,
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
