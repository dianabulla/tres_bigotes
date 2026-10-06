<?php

declare(strict_types=1);

namespace TresBigotes\Controllers;

use DateTime;
use PDO;
use PDOException;
use TresBigotes\Api\Peticion;
use TresBigotes\Api\Respuesta;
use TresBigotes\Api\Router;
use TresBigotes\Config\Conexion;
use TresBigotes\Models\Cita;
use TresBigotes\Models\Cliente;
use TresBigotes\Models\Producto;
use TresBigotes\Models\Resena;
use TresBigotes\Models\Servicio;

final class CitasController
{
    public static function registrar(Router $router): void
    {
        $router->registrar('GET', '/api/servicios', [self::class, 'listarServicios']);
        $router->registrar('POST', '/api/servicios', [self::class, 'crearServicio']);
        $router->registrar('PUT', '/api/servicios/{id}', [self::class, 'actualizarServicio']);
        $router->registrar('GET', '/api/clientes', [self::class, 'buscarClientes']);
        $router->registrar('GET', '/api/citas', [self::class, 'listar']);
        $router->registrar('POST', '/api/citas', [self::class, 'crear']);
        $router->registrar('PUT', '/api/citas/{id}/estado', [self::class, 'estado']);
        $router->registrar('GET', '/api/reserva', [self::class, 'catalogoPublico'], true);
        $router->registrar('GET', '/api/reserva/cliente', [self::class, 'clientePublico'], true);
        $router->registrar('GET', '/api/reserva/horarios', [self::class, 'horariosPublicos'], true);
        $router->registrar('POST', '/api/reserva', [self::class, 'reservarPublico'], true);
    }

    public static function listarServicios(Peticion $peticion): void
    {
        $sede = self::exigirAgenda($peticion);
        $rol = $peticion->usuario['rol'] ?? '';
        if ($rol === 'colaborador') {
            Respuesta::json(403, ['error' => 'No tienes permiso para ver el catálogo de servicios']);
        }
        $servicios = Servicio::listar($sede, $rol !== 'administrador');
        if ($rol === 'administrador') {
            $mapa = Servicio::insumosAgrupados($sede);
            foreach ($servicios as $indice => $servicio) {
                $servicios[$indice]['insumos'] = $mapa[$servicio['id']] ?? [];
            }
        }
        Respuesta::json(200, ['servicios' => $servicios]);
    }

    public static function crearServicio(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $datos = self::leerServicio($peticion->cuerpo, true);
        $insumos = self::leerInsumos($peticion->cuerpo) ?? [];
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            self::validarInsumos($pdo, $sede, $insumos);
            $id = Servicio::crear($pdo, $sede, $datos);
            Servicio::reemplazarInsumos($pdo, $id, $insumos);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $fila = Servicio::bloquear($pdo, $sede, $id);
        $servicio = Servicio::presentar($fila ?? []);
        $servicio['insumos'] = Servicio::insumosAgrupados($sede)[$id] ?? [];
        Respuesta::json(200, ['servicio' => $servicio]);
    }

    public static function actualizarServicio(Peticion $peticion): void
    {
        $sede = self::exigirAdministrador($peticion);
        $id = self::id($peticion, 'Servicio no encontrado');
        $pdo = Conexion::obtener();
        if (Servicio::bloquear($pdo, $sede, $id) === null) {
            Respuesta::json(404, ['error' => 'Servicio no encontrado']);
        }
        $datos = self::leerServicio($peticion->cuerpo, false);
        $insumos = self::leerInsumos($peticion->cuerpo);
        $pdo->beginTransaction();
        try {
            if ($insumos !== null) {
                self::validarInsumos($pdo, $sede, $insumos);
            }
            Servicio::actualizar($pdo, $sede, $id, $datos);
            if ($insumos !== null) {
                Servicio::reemplazarInsumos($pdo, $id, $insumos);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $fila = Servicio::bloquear($pdo, $sede, $id);
        $servicio = Servicio::presentar($fila ?? []);
        $servicio['insumos'] = Servicio::insumosAgrupados($sede)[$id] ?? [];
        Respuesta::json(200, ['servicio' => $servicio]);
    }

    public static function buscarClientes(Peticion $peticion): void
    {
        $sede = self::exigirReserva($peticion);
        $texto = trim((string) ($_GET['q'] ?? ''));
        if ($texto === '' || mb_strlen($texto) > 80) {
            Respuesta::json(422, ['error' => 'Escribe un nombre o teléfono para buscar']);
        }
        $texto = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
        Respuesta::json(200, ['clientes' => Cliente::buscarTexto($sede, $texto)]);
    }

    public static function listar(Peticion $peticion): void
    {
        $sede = self::exigirAgenda($peticion);
        $desde = self::fecha($_GET['desde'] ?? null, 'Indica desde cuándo consultar la agenda');
        $hasta = self::fecha($_GET['hasta'] ?? null, 'Indica hasta cuándo consultar la agenda');
        if ($hasta <= $desde) {
            Respuesta::json(422, ['error' => 'El rango de la agenda no es válido']);
        }
        $inicio = new DateTime($desde);
        $fin = new DateTime($hasta);
        if ($inicio->diff($fin)->days > 31) {
            Respuesta::json(422, ['error' => 'La agenda se consulta de a un mes']);
        }
        $propio = self::profesionalPropio($peticion, $sede);
        $filtro = $propio;
        if ($propio === null) {
            $pedido = $_GET['colaborador_id'] ?? '';
            if ($pedido !== '' && $pedido !== null) {
                if (!is_string($pedido) && !is_int($pedido)) {
                    Respuesta::json(404, ['error' => 'Profesional no encontrado']);
                }
                $pedido = (string) $pedido;
                if (!ctype_digit($pedido)) {
                    Respuesta::json(404, ['error' => 'Profesional no encontrado']);
                }
                $existe = false;
                foreach (Cita::profesionales($sede) as $profesional) {
                    if ($profesional['id'] === (int) $pedido) {
                        $existe = true;
                    }
                }
                if (!$existe) {
                    Respuesta::json(404, ['error' => 'Profesional no encontrado']);
                }
                $filtro = (int) $pedido;
            }
        }
        Respuesta::json(200, [
            'citas' => Cita::listar($sede, $desde, $hasta, $filtro),
            'profesionales' => $propio === null ? Cita::profesionales($sede) : [],
        ]);
    }

    public static function crear(Peticion $peticion): void
    {
        $sede = self::exigirReserva($peticion);
        $inicio = self::fecha($peticion->cuerpo['inicio'] ?? null, 'La cita necesita fecha y hora');
        $notas = trim((string) ($peticion->cuerpo['notas'] ?? ''));
        if (mb_strlen($notas) > 500) {
            Respuesta::json(422, ['error' => 'Las notas no pueden pasar de 500 caracteres']);
        }
        $colaboradorId = $peticion->cuerpo['colaborador_id'] ?? null;
        if (!is_numeric($colaboradorId) || (int) $colaboradorId < 1 || (int) $colaboradorId != $colaboradorId) {
            Respuesta::json(422, ['error' => 'Elige un profesional']);
        }
        $servicios = self::idsServicio($peticion->cuerpo['servicios'] ?? null);
        $clienteNuevo = null;
        $clienteIdPedido = $peticion->cuerpo['cliente_id'] ?? null;
        if ($clienteIdPedido === null || $clienteIdPedido === '') {
            if (!is_array($peticion->cuerpo['cliente'] ?? null)) {
                Respuesta::json(422, ['error' => 'La cita necesita un cliente']);
            }
            $clienteNuevo = self::leerCliente($peticion->cuerpo['cliente']);
            $clienteIdPedido = null;
        } elseif (!is_numeric($clienteIdPedido) || (int) $clienteIdPedido < 1) {
            Respuesta::json(404, ['error' => 'Cliente no encontrado']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $profesional = Cita::bloquearProfesional($pdo, $sede, (int) $colaboradorId);
            if ($profesional === null) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Profesional no encontrado']);
            }
            if (!$profesional['activo']) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'El profesional no está activo']);
            }
            $clienteId = self::resolverCliente($pdo, $sede, $clienteIdPedido, $clienteNuevo);
            $elegidos = [];
            $minutos = 0;
            foreach ($servicios as $servicioId) {
                $servicio = Servicio::bloquear($pdo, $sede, $servicioId);
                if ($servicio === null) {
                    $pdo->rollBack();
                    Respuesta::json(404, ['error' => 'Servicio no encontrado']);
                }
                if ((int) $servicio['activo'] !== 1) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Ese servicio no está activo']);
                }
                $minutos += (int) $servicio['duracion_minutos'];
                $elegidos[] = $servicio;
            }
            $fin = date('Y-m-d H:i:s', strtotime($inicio) + ($minutos * 60));
            if (Cita::haySolape($pdo, $sede, (int) $colaboradorId, $inicio, $fin)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Ese profesional ya tiene una cita en ese horario']);
            }
            $citaId = Cita::crear($pdo, $sede, $clienteId, (int) $colaboradorId, $inicio, $fin, $notas === '' ? null : $notas);
            foreach ($elegidos as $servicio) {
                Cita::agregarServicio($pdo, $citaId, (int) $servicio['id'], number_format((float) $servicio['precio'], 2, '.', ''));
            }
            $aviso = strtotime($inicio) - 3600;
            if ($aviso < time()) {
                $aviso = time();
            }
            Cita::crearAvisos($pdo, $citaId, date('Y-m-d H:i:s', $aviso));
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $e->getCode() === '23000') {
                Respuesta::json(422, ['error' => 'Ya hay un cliente con ese teléfono']);
            }
            throw $e;
        }
        $dia = substr($inicio, 0, 10);
        Respuesta::json(200, [
            'citas' => Cita::listar($sede, $dia . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($dia . ' +1 day')), null),
        ]);
    }

    public static function estado(Peticion $peticion): void
    {
        $sede = self::exigirAgenda($peticion);
        $id = self::id($peticion, 'Cita no encontrada');
        $estado = (string) ($peticion->cuerpo['estado'] ?? '');
        $propio = self::profesionalPropio($peticion, $sede);
        if ($propio !== null && $estado === 'cancelada') {
            Respuesta::json(403, ['error' => 'No tienes permiso para cancelar la cita']);
        }
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $cita = Cita::bloquear($pdo, $sede, $id);
            if ($cita === null || ($propio !== null && $cita['colaborador_id'] !== $propio)) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Cita no encontrada']);
            }
            $permitido = [
                'pendiente' => ['en_proceso', 'cancelada'],
                'en_proceso' => ['completada', 'cancelada'],
            ];
            $siguientes = $permitido[$cita['estado']] ?? [];
            if (!in_array($estado, $siguientes, true)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Esa cita no puede pasar a ese estado']);
            }
            Cita::cambiarEstado($pdo, $sede, $id, $estado);
            if ($estado === 'completada') {
                $faltante = Servicio::consumirInsumos($pdo, $sede, $id, (int) $peticion->usuario['id']);
                if ($faltante !== null) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'No hay stock de ' . $faltante . ' para completar la cita']);
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Respuesta::json(200, ['ok' => true, 'estado' => $estado]);
    }

    public static function catalogoPublico(Peticion $peticion): void
    {
        $sede = self::sedePublica();
        Respuesta::json(200, [
            'sede' => $sede['nombre'],
            'servicios' => Servicio::listar($sede['id'], true),
            'profesionales' => Cita::profesionales($sede['id']),
            'resenas' => Resena::listar($sede['id']),
        ]);
    }

    public static function clientePublico(Peticion $peticion): void
    {
        $sede = self::sedePublica();
        $telefono = trim((string) ($_GET['telefono'] ?? ''));
        if ($telefono === '' || mb_strlen($telefono) < 7 || mb_strlen($telefono) > 20) {
            Respuesta::json(422, ['error' => 'Escribe un teléfono válido']);
        }
        $cliente = Cliente::buscarTelefono(Conexion::obtener(), $sede['id'], $telefono);
        if ($cliente === null) {
            Respuesta::json(200, ['cliente' => null]);
        }
        Respuesta::json(200, [
            'cliente' => [
                'nombre' => $cliente['nombre'],
                'telefono' => $cliente['telefono'],
                'correo' => $cliente['correo'],
            ],
        ]);
    }

    public static function horariosPublicos(Peticion $peticion): void
    {
        $sede = self::sedePublica();
        $fecha = self::dia($_GET['fecha'] ?? null);
        $colaboradorId = $_GET['colaborador_id'] ?? '';
        if (!is_string($colaboradorId) || !ctype_digit($colaboradorId)) {
            Respuesta::json(404, ['error' => 'Profesional no encontrado']);
        }
        $lista = self::listaServicios($_GET['servicios'] ?? '');
        $servicios = $lista === [] ? [] : self::idsServicio($lista);
        $profesionalValido = false;
        foreach (Cita::profesionales($sede['id']) as $profesional) {
            if ($profesional['id'] === (int) $colaboradorId) {
                $profesionalValido = true;
            }
        }
        if (!$profesionalValido) {
            Respuesta::json(404, ['error' => 'Profesional no encontrado']);
        }
        $minutos = $servicios === [] ? 30 : self::minutosActivos($sede['id'], $servicios);
        if (!self::diaReservable($fecha)) {
            Respuesta::json(200, ['horarios' => []]);
        }
        $ocupados = Cita::ocupados($sede['id'], (int) $colaboradorId, $fecha . ' 00:00:00', $fecha . ' 23:59:59');
        $horarios = [];
        $apertura = strtotime($fecha . ' 08:00:00');
        $cierre = strtotime($fecha . ' 20:00:00');
        for ($inicio = $apertura; $inicio + ($minutos * 60) <= $cierre; $inicio += 1800) {
            if ($inicio < time()) {
                continue;
            }
            $fin = $inicio + ($minutos * 60);
            $libre = true;
            foreach ($ocupados as $cita) {
                $ocupadoInicio = strtotime($cita['inicio']);
                $ocupadoFin = strtotime($cita['fin']);
                if ($inicio < $ocupadoFin && $fin > $ocupadoInicio) {
                    $libre = false;
                    break;
                }
            }
            if ($libre) {
                $horarios[] = date('H:i', $inicio);
            }
        }
        Respuesta::json(200, ['horarios' => $horarios]);
    }

    public static function reservarPublico(Peticion $peticion): void
    {
        $sede = self::sedePublica();
        $inicio = self::fecha($peticion->cuerpo['inicio'] ?? null, 'Elige un horario');
        $cliente = self::leerClientePublico($peticion->cuerpo);
        $colaboradorId = $peticion->cuerpo['colaborador_id'] ?? null;
        if (!is_numeric($colaboradorId) || (int) $colaboradorId < 1 || (int) $colaboradorId != $colaboradorId) {
            Respuesta::json(422, ['error' => 'Elige un profesional']);
        }
        $servicios = self::idsServicio($peticion->cuerpo['servicios'] ?? null);
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $profesional = Cita::bloquearProfesional($pdo, $sede['id'], (int) $colaboradorId);
            if ($profesional === null || !$profesional['activo']) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Profesional no encontrado']);
            }
            $existente = Cliente::buscarTelefono($pdo, $sede['id'], $cliente['telefono']);
            if ($existente === null) {
                $clienteId = Cliente::crear($pdo, $sede['id'], $cliente);
            } else {
                $clienteId = $existente['id'];
                Cliente::actualizarContacto($pdo, $sede['id'], $clienteId, $cliente['nombre'], $cliente['correo']);
            }
            $elegidos = [];
            $minutos = 0;
            foreach ($servicios as $servicioId) {
                $servicio = Servicio::bloquear($pdo, $sede['id'], $servicioId);
                if ($servicio === null || (int) $servicio['activo'] !== 1) {
                    $pdo->rollBack();
                    Respuesta::json(422, ['error' => 'Ese servicio no está activo']);
                }
                $minutos += (int) $servicio['duracion_minutos'];
                $elegidos[] = $servicio;
            }
            $finTs = strtotime($inicio) + ($minutos * 60);
            if (!self::horarioPermitido($inicio, $minutos)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Ese horario no está disponible']);
            }
            $fin = date('Y-m-d H:i:s', $finTs);
            if (Cita::haySolape($pdo, $sede['id'], (int) $colaboradorId, $inicio, $fin)) {
                $pdo->rollBack();
                Respuesta::json(422, ['error' => 'Ese profesional ya tiene una cita en ese horario']);
            }
            $citaId = Cita::crear($pdo, $sede['id'], $clienteId, (int) $colaboradorId, $inicio, $fin, null);
            $nombres = [];
            foreach ($elegidos as $servicio) {
                Cita::agregarServicio($pdo, $citaId, (int) $servicio['id'], number_format((float) $servicio['precio'], 2, '.', ''));
                $nombres[] = $servicio['nombre'];
            }
            $aviso = strtotime($inicio) - 3600;
            if ($aviso < time()) {
                $aviso = time();
            }
            Cita::crearAvisos($pdo, $citaId, date('Y-m-d H:i:s', $aviso));
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $e->getCode() === '23000') {
                Respuesta::json(422, ['error' => 'Ya hay un cliente con ese teléfono']);
            }
            throw $e;
        }
        Respuesta::json(200, [
            'reserva' => [
                'inicio' => $inicio,
                'fin' => $fin,
                'cliente' => $cliente['nombre'],
                'profesional' => $profesional['nombre'],
                'servicios' => $nombres,
            ],
        ]);
    }

    private static function sedePublica(): array
    {
        $sede = Cita::sedePublica();
        if ($sede === null) {
            Respuesta::json(404, ['error' => 'No hay una sede disponible']);
        }
        return $sede;
    }

    private static function leerClientePublico(array $cuerpo): array
    {
        $datos = self::leerCliente([
            'nombre' => $cuerpo['nombre'] ?? '',
            'telefono' => $cuerpo['telefono'] ?? '',
            'correo' => $cuerpo['correo'] ?? '',
        ]);
        if ($datos['telefono'] === null || mb_strlen($datos['telefono']) < 7) {
            Respuesta::json(422, ['error' => 'El teléfono es obligatorio']);
        }
        return $datos;
    }

    private static function listaServicios($valor): array
    {
        if (!is_string($valor) || trim($valor) === '') {
            return [];
        }
        return explode(',', $valor);
    }

    private static function minutosActivos(int $sede, array $servicios): int
    {
        $minutos = 0;
        foreach ($servicios as $servicioId) {
            $servicio = Servicio::bloquear(Conexion::obtener(), $sede, $servicioId);
            if ($servicio === null || (int) $servicio['activo'] !== 1) {
                Respuesta::json(422, ['error' => 'Ese servicio no está activo']);
            }
            $minutos += (int) $servicio['duracion_minutos'];
        }
        return $minutos;
    }

    private static function dia(mixed $valor): string
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            Respuesta::json(422, ['error' => 'Elige un día']);
        }
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            Respuesta::json(422, ['error' => 'Elige un día']);
        }
        return $valor;
    }

    private static function diaReservable(string $fecha): bool
    {
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime('+60 days'));
        return $fecha >= $hoy && $fecha <= $limite;
    }

    private static function horarioPermitido(string $inicio, int $minutos): bool
    {
        $marca = strtotime($inicio);
        if ($marca === false || $marca < time()) {
            return false;
        }
        $dia = date('Y-m-d', $marca);
        if (!self::diaReservable($dia)) {
            return false;
        }
        $apertura = strtotime($dia . ' 08:00:00');
        $cierre = strtotime($dia . ' 20:00:00');
        $fin = $marca + ($minutos * 60);
        if ($marca < $apertura || $fin > $cierre) {
            return false;
        }
        return ($marca - $apertura) % 1800 === 0;
    }

    private static function exigirAgenda(Peticion $peticion): int
    {
        $rol = $peticion->usuario['rol'] ?? '';
        if ($rol !== 'administrador' && $rol !== 'recepcion' && $rol !== 'colaborador') {
            Respuesta::json(403, ['error' => 'No tienes permiso para ver la agenda']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function exigirReserva(Peticion $peticion): int
    {
        $rol = $peticion->usuario['rol'] ?? '';
        if ($rol !== 'administrador' && $rol !== 'recepcion') {
            Respuesta::json(403, ['error' => 'No tienes permiso para reservar']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function exigirAdministrador(Peticion $peticion): int
    {
        if (($peticion->usuario['rol'] ?? '') !== 'administrador') {
            Respuesta::json(403, ['error' => 'No tienes permiso para configurar servicios']);
        }
        return (int) $peticion->usuario['establecimiento_id'];
    }

    private static function profesionalPropio(Peticion $peticion, int $sede): ?int
    {
        if (($peticion->usuario['rol'] ?? '') !== 'colaborador') {
            return null;
        }
        $profesional = Cita::profesionalDeUsuario($sede, (int) $peticion->usuario['id']);
        if ($profesional === null || !$profesional['activo']) {
            Respuesta::json(403, ['error' => 'No tienes permiso para ver la agenda']);
        }
        return $profesional['id'];
    }

    private static function resolverCliente(PDO $pdo, int $sede, $clienteId, ?array $nuevo): int
    {
        if ($clienteId !== null) {
            $cliente = Cliente::buscar($sede, (int) $clienteId);
            if ($cliente === null) {
                $pdo->rollBack();
                Respuesta::json(404, ['error' => 'Cliente no encontrado']);
            }
            return (int) $clienteId;
        }
        if ($nuevo['telefono'] !== null && Cliente::buscarTelefono($pdo, $sede, $nuevo['telefono']) !== null) {
            $pdo->rollBack();
            Respuesta::json(422, ['error' => 'Ya hay un cliente con ese teléfono']);
        }
        return Cliente::crear($pdo, $sede, $nuevo);
    }

    private static function leerCliente(array $cuerpo): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre del cliente es obligatorio']);
        }
        $telefono = trim((string) ($cuerpo['telefono'] ?? ''));
        if (mb_strlen($telefono) > 20) {
            Respuesta::json(422, ['error' => 'El teléfono no puede pasar de 20 caracteres']);
        }
        $correo = trim((string) ($cuerpo['correo'] ?? ''));
        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 160)) {
            Respuesta::json(422, ['error' => 'El correo del cliente no es válido']);
        }
        return [
            'nombre' => $nombre,
            'telefono' => $telefono === '' ? null : $telefono,
            'correo' => $correo === '' ? null : $correo,
        ];
    }

    private static function leerServicio(array $cuerpo, bool $esAlta): array
    {
        $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            Respuesta::json(422, ['error' => 'El nombre del servicio es obligatorio']);
        }
        $duracion = $cuerpo['duracion_minutos'] ?? null;
        if (!is_numeric($duracion) || (int) $duracion < 1 || (int) $duracion != $duracion || (int) $duracion > 720) {
            Respuesta::json(422, ['error' => 'La duración debe ser entre 1 y 720 minutos']);
        }
        $precio = self::monto($cuerpo['precio'] ?? null);
        if ($precio === null) {
            Respuesta::json(422, ['error' => 'El precio del servicio no puede ser negativo']);
        }
        $categoria = trim((string) ($cuerpo['categoria'] ?? ''));
        if ($categoria === '' || mb_strlen($categoria) > 60) {
            Respuesta::json(422, ['error' => 'Indica la categoría del servicio']);
        }
        $activo = true;
        if (!$esAlta) {
            $activo = self::booleano($cuerpo['activo'] ?? null);
            if ($activo === null) {
                Respuesta::json(422, ['error' => 'Indica si el servicio está activo']);
            }
        }
        return [
            'nombre' => $nombre,
            'categoria' => $categoria,
            'duracion_minutos' => (int) $duracion,
            'precio' => $precio,
            'activo' => $activo,
        ];
    }

    private static function leerInsumos(array $cuerpo): ?array
    {
        if (!array_key_exists('insumos', $cuerpo)) {
            return null;
        }
        $valor = $cuerpo['insumos'];
        if (!is_array($valor)) {
            Respuesta::json(422, ['error' => 'Los insumos del servicio no son válidos']);
        }
        $lineas = [];
        foreach ($valor as $linea) {
            if (!is_array($linea)) {
                Respuesta::json(422, ['error' => 'Los insumos del servicio no son válidos']);
            }
            $productoId = $linea['producto_id'] ?? null;
            $cantidad = $linea['cantidad'] ?? null;
            if (!is_numeric($productoId) || (int) $productoId < 1 || (int) $productoId != $productoId) {
                Respuesta::json(422, ['error' => 'Elige un insumo activo de esta sede']);
            }
            if (!is_numeric($cantidad) || (int) $cantidad < 1 || (int) $cantidad != $cantidad || (int) $cantidad > 9999) {
                Respuesta::json(422, ['error' => 'La cantidad del insumo debe ser entre 1 y 9999']);
            }
            $lineas[] = [
                'producto_id' => (int) $productoId,
                'cantidad' => (int) $cantidad,
            ];
        }
        return $lineas;
    }

    private static function validarInsumos(PDO $pdo, int $sede, array $lineas): void
    {
        $vistos = [];
        foreach ($lineas as $linea) {
            $producto = Producto::buscar($sede, $linea['producto_id']);
            if ($producto === null || $producto['tipo'] !== 'insumo' || (int) $producto['activo'] !== 1) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                Respuesta::json(422, ['error' => 'Elige un insumo activo de esta sede']);
            }
            if (isset($vistos[$linea['producto_id']])) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                Respuesta::json(422, ['error' => 'Un servicio no puede repetir el mismo insumo']);
            }
            $vistos[$linea['producto_id']] = true;
        }
    }

    private static function idsServicio($valor): array
    {
        if (!is_array($valor) || $valor === [] || !self::esLista($valor)) {
            Respuesta::json(422, ['error' => 'La cita necesita al menos un servicio']);
        }
        $ids = [];
        foreach ($valor as $id) {
            if (!is_numeric($id) || (int) $id < 1 || (int) $id != $id) {
                Respuesta::json(422, ['error' => 'Servicio no encontrado']);
            }
            $ids[(int) $id] = (int) $id;
        }
        $ids = array_values($ids);
        sort($ids);
        return $ids;
    }

    private static function id(Peticion $peticion, string $mensaje): int
    {
        $id = $peticion->params['id'] ?? '';
        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            Respuesta::json(404, ['error' => $mensaje]);
        }
        return (int) $id;
    }

    private static function fecha($valor, string $mensaje): string
    {
        if (!is_string($valor)) {
            Respuesta::json(422, ['error' => $mensaje]);
        }
        $valor = str_replace('T', ' ', trim($valor));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $valor)) {
            $valor .= ':00';
        }
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $valor);
        if (!$fecha || $fecha->format('Y-m-d H:i:s') !== $valor) {
            Respuesta::json(422, ['error' => $mensaje]);
        }
        return $valor;
    }

    private static function monto($valor): ?string
    {
        if (is_int($valor)) {
            $valor = (string) $valor;
        } elseif (is_float($valor)) {
            $valor = number_format($valor, 2, '.', '');
        }
        if (!is_string($valor) || !preg_match('/^\d+(\.\d{1,2})?$/', trim($valor))) {
            return null;
        }
        return number_format((float) trim($valor), 2, '.', '');
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

    private static function esLista(array $valor): bool
    {
        if ($valor === []) {
            return true;
        }
        return array_keys($valor) === range(0, count($valor) - 1);
    }
}
