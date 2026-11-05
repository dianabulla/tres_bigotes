<?php

declare(strict_types=1);

namespace TresBigotes\Models;

use PDO;
use TresBigotes\Config\Conexion;
use TresBigotes\Config\Correo;

final class Aviso
{
    public static function enviarConfirmacion(int $citaId): void
    {
        $datos = self::datosCita($citaId);
        if ($datos === null || $datos['estado'] === 'cancelada') {
            return;
        }
        try {
            Correo::confirmacionCliente($datos);
            if (trim((string) ($datos['correo_colaborador'] ?? '')) !== '') {
                Correo::confirmacionColaborador($datos);
            }
        } catch (\Throwable $e) {
            error_log('Aviso confirmación cita ' . $citaId . ': ' . $e->getMessage());
        }
    }

    /** @return array{procesados:int,enviados:int,fallidos:int,omitidos:int} */
    public static function procesarPendientes(int $limite = 50): array
    {
        $resultado = ['procesados' => 0, 'enviados' => 0, 'fallidos' => 0, 'omitidos' => 0];
        $pdo = Conexion::obtener();
        $avisos = self::listarPendientes($pdo, $limite);
        foreach ($avisos as $aviso) {
            $resultado['procesados']++;
            if ($aviso['estado_cita'] !== 'pendiente') {
                self::marcar($pdo, $aviso['id'], 'fallido');
                $resultado['omitidos']++;
                continue;
            }
            $datos = [
                'cliente' => $aviso['cliente_nombre'],
                'correo_cliente' => (string) ($aviso['cliente_correo'] ?? ''),
                'profesional' => $aviso['colaborador_nombre'],
                'correo_colaborador' => $aviso['colaborador_correo'],
                'inicio' => $aviso['inicio'],
                'fin' => $aviso['fin'],
                'servicios' => self::nombresServicio($pdo, $aviso['cita_id']),
                'sede' => $aviso['sede_nombre'],
            ];
            $ok = false;
            try {
                if ($aviso['destinatario'] === 'cliente') {
                    $ok = Correo::recordatorioCliente($datos);
                } else {
                    $ok = Correo::recordatorioColaborador($datos);
                }
            } catch (\Throwable $e) {
                error_log('Aviso recordatorio ' . $aviso['id'] . ': ' . $e->getMessage());
                $ok = false;
            }
            if ($ok) {
                self::marcar($pdo, $aviso['id'], 'enviado');
                $resultado['enviados']++;
            } else {
                self::marcar($pdo, $aviso['id'], 'fallido');
                $resultado['fallidos']++;
            }
        }
        return $resultado;
    }

    public static function cancelarPendientes(PDO $pdo, int $citaId): void
    {
        $borrar = $pdo->prepare(
            'DELETE FROM aviso_cita
             WHERE cita_id = :cita AND estado = \'pendiente\''
        );
        $borrar->execute(['cita' => $citaId]);
    }

    /** @return list<array<string,mixed>> */
    private static function listarPendientes(PDO $pdo, int $limite): array
    {
        $limite = max(1, min(200, $limite));
        $consulta = $pdo->prepare(
            'SELECT a.id, a.cita_id, a.destinatario,
                    c.inicio, c.fin, c.estado AS estado_cita,
                    cl.nombre AS cliente_nombre, cl.correo AS cliente_correo,
                    co.nombre AS colaborador_nombre, u.correo AS colaborador_correo,
                    e.nombre AS sede_nombre
             FROM aviso_cita a
             INNER JOIN cita c ON c.id = a.cita_id
             INNER JOIN cliente cl ON cl.id = c.cliente_id
             INNER JOIN colaborador co ON co.id = c.colaborador_id
             LEFT JOIN usuario u ON u.id = co.usuario_id
             INNER JOIN establecimiento e ON e.id = c.establecimiento_id
             WHERE a.estado = \'pendiente\'
               AND a.programado_para <= :ahora
             ORDER BY a.programado_para, a.id
             LIMIT ' . $limite
        );
        $consulta->execute(['ahora' => date('Y-m-d H:i:s')]);
        $filas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $filas[] = [
                'id' => (int) $fila['id'],
                'cita_id' => (int) $fila['cita_id'],
                'destinatario' => $fila['destinatario'],
                'inicio' => $fila['inicio'],
                'fin' => $fila['fin'],
                'estado_cita' => $fila['estado_cita'],
                'cliente_nombre' => $fila['cliente_nombre'],
                'cliente_correo' => $fila['cliente_correo'],
                'colaborador_nombre' => $fila['colaborador_nombre'],
                'colaborador_correo' => $fila['colaborador_correo'],
                'sede_nombre' => $fila['sede_nombre'],
            ];
        }
        return $filas;
    }

    private static function marcar(PDO $pdo, int $id, string $estado): void
    {
        $guardar = $pdo->prepare(
            'UPDATE aviso_cita SET estado = :estado WHERE id = :id AND estado = \'pendiente\''
        );
        $guardar->execute(['estado' => $estado, 'id' => $id]);
    }

    /** @return array{cliente:string,correo_cliente:string,profesional:string,correo_colaborador:?string,inicio:string,fin:string,servicios:list<string>,sede:string,estado:string}|null */
    private static function datosCita(int $citaId): ?array
    {
        $pdo = Conexion::obtener();
        $consulta = $pdo->prepare(
            'SELECT c.inicio, c.fin, c.estado,
                    cl.nombre AS cliente_nombre, cl.correo AS cliente_correo,
                    co.nombre AS colaborador_nombre, u.correo AS colaborador_correo,
                    e.nombre AS sede_nombre
             FROM cita c
             INNER JOIN cliente cl ON cl.id = c.cliente_id
             INNER JOIN colaborador co ON co.id = c.colaborador_id
             LEFT JOIN usuario u ON u.id = co.usuario_id
             INNER JOIN establecimiento e ON e.id = c.establecimiento_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $consulta->execute(['id' => $citaId]);
        $fila = $consulta->fetch();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'cliente' => $fila['cliente_nombre'],
            'correo_cliente' => (string) ($fila['cliente_correo'] ?? ''),
            'profesional' => $fila['colaborador_nombre'],
            'correo_colaborador' => $fila['colaborador_correo'],
            'inicio' => $fila['inicio'],
            'fin' => $fila['fin'],
            'servicios' => self::nombresServicio($pdo, $citaId),
            'sede' => $fila['sede_nombre'],
            'estado' => $fila['estado'],
        ];
    }

    /** @return list<string> */
    private static function nombresServicio(PDO $pdo, int $citaId): array
    {
        $consulta = $pdo->prepare(
            'SELECT s.nombre
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id = cs.servicio_id
             WHERE cs.cita_id = :cita
             ORDER BY cs.id'
        );
        $consulta->execute(['cita' => $citaId]);
        $nombres = [];
        foreach ($consulta->fetchAll() as $fila) {
            $nombres[] = $fila['nombre'];
        }
        return $nombres;
    }
}
