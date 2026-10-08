<?php

declare(strict_types=1);

use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Controllers\CajaController;
use TresBigotes\Controllers\CitasController;
use TresBigotes\Controllers\ClinicaController;
use TresBigotes\Controllers\ColaboradoresController;
use TresBigotes\Controllers\ComisionesController;
use TresBigotes\Controllers\InventarioController;
use TresBigotes\Controllers\RolesController;
use TresBigotes\Controllers\SedesController;
use TresBigotes\Controllers\SesionController;
use TresBigotes\Controllers\UsuariosController;

function registrarRutas(Router $router): void
{
    $router->registrar('GET', '/api/salud', static function (Peticion $peticion): void {
        $base = false;
        try {
            Conexion::obtener()->query('SELECT 1');
            $base = true;
        } catch (Throwable) {
            $base = false;
        }
        Respuesta::json(200, [
            'ok' => true,
            'servicio' => 'tres-bigotes',
            'base' => $base,
        ]);
    }, true);

    SesionController::registrar($router);
    CitasController::registrar($router);
    ClinicaController::registrar($router);
    ColaboradoresController::registrar($router);
    ComisionesController::registrar($router);
    InventarioController::registrar($router);
    CajaController::registrar($router);
    SedesController::registrar($router);
    UsuariosController::registrar($router);
    RolesController::registrar($router);
}
