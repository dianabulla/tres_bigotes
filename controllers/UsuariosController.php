<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Establecimiento;
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
        Acceso::exigirPermiso($peticion, 'usuarios', 'No tienes permiso para administrar los usuarios');
        $esSuper = Acceso::esSuper($peticion);
        $usuarios = $esSuper
            ? Usuario::listarTodos()
            : Usuario::listarPorSede((int) $peticion->usuario['establecimiento_id']);
        Respuesta::json(200, [
            'usuarios' => $usuarios,
            'roles' => self::rolesAsignables($esSuper),
            'sedes' => $esSuper ? Establecimiento::listar() : [],
            'alcance' => Acceso::alcance($peticion)['tipo'],
        ]);
    }

    public static function crear(Peticion $peticion): void
    {
        Acceso::exigirPermiso($peticion, 'usuarios', 'No tienes permiso para administrar los usuarios');
        $esSuper = Acceso::esSuper($peticion);
        $datos = self::leer($peticion, true, $esSuper);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $id = Usuario::crearConRol($pdo, $datos['establecimiento_id'], $datos['rol'], $datos);
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
        Respuesta::json(200, ['usuario' => Usuario::buscarPorId($id)]);
    }

    public static function actualizar(Peticion $peticion): void
    {
        Acceso::exigirPermiso($peticion, 'usuarios', 'No tienes permiso para administrar los usuarios');
        $esSuper = Acceso::esSuper($peticion);
        $id = self::id($peticion);
        $actual = $esSuper
            ? Usuario::buscarPorId($id)
            : Usuario::buscarEnSede((int) $peticion->usuario['establecimiento_id'], $id);
        if ($actual === null) {
            Respuesta::json(404, ['error' => 'Usuario no encontrado']);
        }
        $datos = self::leer($peticion, false, $esSuper, $actual);
        if ($id === (int) $peticion->usuario['id'] && (!$datos['activo'] || $datos['rol'] !== $actual['rol'])) {
            Respuesta::json(422, ['error' => 'No puedes cambiar tu propio rol ni darte de baja']);
        }
        if ($id === (int) $peticion->usuario['id'] && Acceso::esSuper($peticion)
            && $datos['establecimiento_id'] !== null) {
            Respuesta::json(422, ['error' => 'El super administrador no lleva sede asignada']);
        }
        $pdo = Conexion::obtener();
        $sedeOrigen = $actual['establecimiento_id'];
        $teniaUsuarios = Permiso::rolTiene($pdo, $actual['rol'], 'usuarios');
        $conservaUsuarios = $datos['activo'] && Permiso::rolTiene($pdo, $datos['rol'], 'usuarios')
            && $datos['establecimiento_id'] === $sedeOrigen;
        if ($teniaUsuarios && !$conservaUsuarios && $sedeOrigen !== null
            && Usuario::otrosConPermiso($pdo, $sedeOrigen, $id, 'usuarios') < 1) {
            Respuesta::json(422, ['error' => 'La sede debe conservar un usuario que pueda administrar usuarios']);
        }
        if ($teniaUsuarios && !$conservaUsuarios && $sedeOrigen === null
            && Usuario::otrosConPermiso($pdo, null, $id, 'usuarios') < 1) {
            Respuesta::json(422, ['error' => 'Debe quedar al menos un super administrador activo']);
        }
        $pdo->beginTransaction();
        try {
            $filtro = $esSuper ? null : (int) $peticion->usuario['establecimiento_id'];
            Usuario::actualizarCuenta($pdo, $id, $datos, $filtro);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::traducirDuplicado($e);
        }
        Respuesta::json(200, ['usuario' => Usuario::buscarPorId($id)]);
    }

    private static function rolesAsignables(bool $esSuper): array
    {
        $opciones = [];
        foreach (Rol::opciones() as $rol) {
            if (!$esSuper && $rol['codigo'] === 'superadministrador') {
                continue;
            }
            $opciones[] = $rol;
        }
        return $opciones;
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id)) {
            Respuesta::json(404, ['error' => 'Usuario no encontrado']);
        }
        return (int) $id;
    }

    private static function leer(Peticion $peticion, bool $esAlta, bool $esSuper, ?array $actual = null): array
    {
        $cuerpo = $peticion->cuerpo;
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
        if ($rol === 'superadministrador' && !$esSuper) {
            Respuesta::json(403, ['error' => 'Solo el super administrador puede asignar ese rol']);
        }
        $sede = self::resolverSede($peticion, $cuerpo, $esSuper, $rol, $actual);
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
            'establecimiento_id' => $sede,
        ];
    }

    private static function resolverSede(
        Peticion $peticion,
        array $cuerpo,
        bool $esSuper,
        string $rol,
        ?array $actual
    ): ?int {
        if ($rol === 'superadministrador') {
            return null;
        }
        if (!$esSuper) {
            $sede = (int) ($peticion->usuario['establecimiento_id'] ?? 0);
            if ($sede < 1) {
                Respuesta::json(403, ['error' => 'Tu cuenta no tiene sede asignada']);
            }
            return $sede;
        }
        $raw = $cuerpo['establecimiento_id'] ?? ($actual['establecimiento_id'] ?? null);
        if ($raw === null || $raw === '') {
            Respuesta::json(422, ['error' => 'Asigna el usuario a una sede']);
        }
        if (!is_numeric($raw) || (int) $raw < 1) {
            Respuesta::json(422, ['error' => 'La sede no es válida']);
        }
        $sedeId = (int) $raw;
        $sede = Establecimiento::buscar($sedeId);
        if ($sede === null || !$sede['activo']) {
            Respuesta::json(422, ['error' => 'Esa sede no está disponible']);
        }
        return $sedeId;
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
