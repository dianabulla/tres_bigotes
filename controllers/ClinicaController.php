<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Cita;
use TresBigotes\Models\Cliente;
use TresBigotes\Models\Ficha;

final class ClinicaController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/clinica/clientes', [self::class, 'buscar']);
        $router->registrar('GET', '/api/clinica/clientes/{id}', [self::class, 'ver']);
        $router->registrar('PUT', '/api/clinica/clientes/{id}', [self::class, 'guardar']);
        $router->registrar('POST', '/api/clinica/clientes/{id}/notas', [self::class, 'anotar']);
    }

    public static function buscar(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $texto = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($texto) > 80) {
            Respuesta::json(422, ['error' => 'La búsqueda es demasiado larga']);
        }
        $texto = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
        $profesional = self::profesionalPropio($peticion, $sede);
        if ($profesional !== null) {
            Respuesta::json(200, ['clientes' => Ficha::clientesProfesional($sede, $profesional, $texto)]);
        }
        if ($texto === '') {
            Respuesta::json(422, ['error' => 'Escribe un nombre o teléfono para buscar']);
        }
        Respuesta::json(200, ['clientes' => Cliente::buscarTexto($sede, $texto)]);
    }

    public static function ver(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $id = self::id($peticion);
        $profesional = self::profesionalPropio($peticion, $sede);
        $cliente = self::clienteVisible($sede, $id, $profesional);
        Respuesta::json(200, self::presentar($sede, $cliente, $profesional));
    }

    public static function guardar(Peticion $peticion): void
    {
        $sede = self::exigirEdicion($peticion);
        $id = self::id($peticion);
        $profesional = self::profesionalPropio($peticion, $sede);
        self::clienteVisible($sede, $id, $profesional);
        $datos = self::leerFicha($peticion->cuerpo);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            Ficha::guardar($pdo, $id, $datos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $cliente = Cliente::buscar($sede, $id);
        Respuesta::json(200, self::presentar($sede, $cliente, $profesional));
    }

    public static function anotar(Peticion $peticion): void
    {
        $sede = self::exigirConsulta($peticion);
        $id = self::id($peticion);
        $profesional = self::profesionalPropio($peticion, $sede);
        if ($profesional === null && Acceso::tiene($peticion, 'clinica.propia')) {
            $vinculo = Cita::profesionalDeUsuario($sede, (int) $peticion->usuario['id']);
            if (is_array($vinculo) && $vinculo['activo']) {
                $profesional = $vinculo['id'];
            }
        }
        if ($profesional === null) {
            Respuesta::json(403, ['error' => 'La nota la escribe el profesional de la cita']);
        }
        $observacion = trim((string) ($peticion->cuerpo['observacion'] ?? ''));
        if ($observacion === '' || mb_strlen($observacion) > 2000) {
            Respuesta::json(422, ['error' => 'La nota es obligatoria y no puede pasar de 2000 caracteres']);
        }
        $citaId = $peticion->cuerpo['cita_id'] ?? null;
        if (!is_numeric($citaId) || (int) $citaId < 1 || (int) $citaId != $citaId) {
            Respuesta::json(422, ['error' => 'Elige la visita de esa nota']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $cita = Ficha::bloquearCita($pdo, $sede, $id, $profesional, (int) $citaId);
            if ($cita === null) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Cita no encontrada']);
            }
            if ($cita['estado'] === 'cancelada') {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Una cita cancelada no lleva nota']);
            }
            if (Ficha::tieneNota($pdo, (int) $citaId)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Esa visita ya tiene nota']);
            }
            Ficha::crearNota($pdo, $id, (int) $citaId, $profesional, $observacion);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $cliente = Cliente::buscar($sede, $id);
        Respuesta::json(200, self::presentar($sede, $cliente, $profesional));
    }

    private static function presentar(int $sede, array $cliente, ?int $profesional): array
    {
        return [
            'cliente' => [
                'id' => $cliente['id'],
                'nombre' => $cliente['nombre'],
                'telefono' => $cliente['telefono'],
            ],
            'ficha' => Ficha::leer($sede, $cliente['id']),
            'notas' => Ficha::notas($sede, $cliente['id']),
            'visitas' => $profesional === null ? [] : Ficha::visitasSinNota($sede, $cliente['id'], $profesional),
        ];
    }

    private static function clienteVisible(int $sede, int $id, ?int $profesional): array
    {
        $cliente = Cliente::buscar($sede, $id);
        if ($cliente === null) {
            Respuesta::json(404, ['error' => 'Cliente no encontrado']);
        }
        if ($profesional !== null && !Ficha::tieneCita($sede, $id, $profesional)) {
            Respuesta::json(404, ['error' => 'Cliente no encontrado']);
        }
        return $cliente;
    }

    private static function leerFicha(array $cuerpo): array
    {
        return [
            'preferencias' => self::texto($cuerpo['preferencias'] ?? '', 'Las preferencias'),
            'cortes_habituales' => self::texto($cuerpo['cortes_habituales'] ?? '', 'Los cortes habituales'),
            'formulas' => self::texto($cuerpo['formulas'] ?? '', 'Las fórmulas'),
        ];
    }

    private static function texto($valor, string $etiqueta): ?string
    {
        $texto = trim((string) $valor);
        if (mb_strlen($texto) > 2000) {
            Respuesta::json(422, ['error' => $etiqueta . ' no pueden pasar de 2000 caracteres']);
        }
        return $texto === '' ? null : $texto;
    }

    private static function exigirConsulta(Peticion $peticion): int
    {
        return Acceso::exigirAlguno(
            $peticion,
            ['clinica', 'clinica.consulta', 'clinica.propia'],
            'No tienes permiso para ver la ficha'
        );
    }

    private static function exigirEdicion(Peticion $peticion): int
    {
        if (!Acceso::tiene($peticion, 'clinica') && !Acceso::tiene($peticion, 'clinica.propia')) {
            Respuesta::json(403, ['error' => 'No tienes permiso para modificar la ficha']);
        }
        return self::exigirConsulta($peticion);
    }

    private static function profesionalPropio(Peticion $peticion, int $sede): ?int
    {
        $amplia = Acceso::tiene($peticion, 'clinica') || Acceso::tiene($peticion, 'clinica.consulta');
        if ($amplia || !Acceso::tiene($peticion, 'clinica.propia')) {
            return null;
        }
        $profesional = Cita::profesionalDeUsuario($sede, (int) $peticion->usuario['id']);
        if ($profesional === null || !$profesional['activo']) {
            Respuesta::json(403, ['error' => 'No tienes permiso para ver la ficha']);
        }
        return $profesional['id'];
    }

    private static function id(Peticion $peticion): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => 'Cliente no encontrado']);
        }
        return (int) $id;
    }
}
