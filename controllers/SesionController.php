<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Models\Establecimiento;
use TresBigotes\Models\Permiso;
use TresBigotes\Models\Sesion;
use TresBigotes\Models\Usuario;

final class SesionController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('POST', '/api/sesion', [self::class, 'entrar'], true);
        $router->registrar('GET', '/api/sesion', [self::class, 'actual']);
        $router->registrar('PUT', '/api/sesion/sede', [self::class, 'elegirSede']);
        $router->registrar('DELETE', '/api/sesion', [self::class, 'salir']);
    }

    public static function entrar(Peticion $peticion): void
    {
        $correo = strtolower(trim((string) ($peticion->cuerpo['correo'] ?? '')));
        $clave = (string) ($peticion->cuerpo['clave'] ?? '');
        if ($correo === '' || $clave === '') {
            Respuesta::json(422, ['error' => 'Correo y clave son obligatorios']);
        }

        $fila = Usuario::buscarPorCorreo($correo);
        $claveValida = is_array($fila) && password_verify($clave, (string) $fila['password_hash']);
        if (!is_array($fila) || (int) $fila['activo'] !== 1 || !$claveValida) {
            Respuesta::json(401, ['error' => 'Correo o clave incorrectos']);
        }
        $esSuper = ($fila['rol'] ?? '') === 'superadministrador';
        if (!$esSuper) {
            if ($fila['establecimiento_id'] === null || (int) $fila['sede_activa'] !== 1) {
                Respuesta::json(401, ['error' => 'Esta sede está de baja']);
            }
        }
        $fila['permisos'] = Permiso::codigosDeRol((int) $fila['rol_id']);
        $sedeActiva = $esSuper ? null : (int) $fila['establecimiento_id'];
        $usuario = Usuario::presentar($fila);
        $usuario['establecimiento_activo_id'] = $sedeActiva;

        Respuesta::json(200, [
            'token' => Sesion::abrir((int) $fila['id'], $sedeActiva),
            'usuario' => $usuario,
        ]);
    }

    public static function actual(Peticion $peticion): void
    {
        Respuesta::json(200, ['usuario' => $peticion->usuario]);
    }

    public static function elegirSede(Peticion $peticion): void
    {
        if (!Acceso::esSuper($peticion)) {
            Respuesta::json(403, ['error' => 'Solo el super administrador elige sede de trabajo']);
        }
        $raw = $peticion->cuerpo['establecimiento_id'] ?? null;
        if ($raw === null || $raw === '') {
            Sesion::elegirSede((string) $peticion->tokenHash, null);
            $usuario = $peticion->usuario;
            $usuario['establecimiento_activo_id'] = null;
            Respuesta::json(200, ['usuario' => $usuario]);
        }
        if (!is_numeric($raw) || (int) $raw < 1) {
            Respuesta::json(422, ['error' => 'Indica una sede válida']);
        }
        $sedeId = (int) $raw;
        $sede = Establecimiento::buscar($sedeId);
        if ($sede === null || !$sede['activo']) {
            Respuesta::json(422, ['error' => 'Esa sede no está disponible']);
        }
        Sesion::elegirSede((string) $peticion->tokenHash, $sedeId);
        $usuario = $peticion->usuario;
        $usuario['establecimiento_activo_id'] = $sedeId;
        Respuesta::json(200, ['usuario' => $usuario]);
    }

    public static function salir(Peticion $peticion): void
    {
        Sesion::cerrar((string) $peticion->tokenHash);
        Respuesta::json(200, ['ok' => true]);
    }
}
