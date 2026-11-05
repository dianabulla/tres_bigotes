<?php

declare(strict_types=1);

namespace TresBigotes\Config;

final class Correo
{
    public static function listo(): bool
    {
        $host = trim((string) Config::obtener('MAIL_HOST', ''));
        $usuario = trim((string) Config::obtener('MAIL_USUARIO', ''));
        $clave = (string) Config::obtener('MAIL_CLAVE', '');
        $desde = trim((string) Config::obtener('MAIL_DESDE', $usuario));
        return $host !== '' && $usuario !== '' && $clave !== '' && $desde !== '';
    }

    public static function enviar(string $para, string $asunto, string $texto, ?string $html = null): bool
    {
        $para = trim($para);
        if ($para === '' || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!self::listo()) {
            error_log('Correo: faltan MAIL_HOST, MAIL_USUARIO, MAIL_CLAVE o MAIL_DESDE');
            return false;
        }

        $host = trim((string) Config::obtener('MAIL_HOST'));
        $puerto = (int) Config::obtener('MAIL_PORT', '465');
        $usuario = trim((string) Config::obtener('MAIL_USUARIO'));
        $clave = (string) Config::obtener('MAIL_CLAVE');
        $desde = trim((string) Config::obtener('MAIL_DESDE', $usuario));
        $nombre = trim((string) Config::obtener('MAIL_DESDE_NOMBRE', '3 Bigotes'));
        $cifrado = strtolower(trim((string) Config::obtener('MAIL_CIFRADO', 'ssl')));

        $usarSsl = $cifrado === 'ssl' || $puerto === 465;
        $remoto = $usarSsl
            ? 'ssl://' . $host . ':' . $puerto
            : $host . ':' . $puerto;

        $socket = @stream_socket_client(
            $remoto,
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ])
        );
        if ($socket === false) {
            error_log('Correo SMTP: no conecta ' . $remoto . ' — ' . $errstr);
            return false;
        }
        stream_set_timeout($socket, 20);

        try {
            if (!self::esperar($socket, 220)) {
                return false;
            }
            self::escribir($socket, 'EHLO 3bigotesbarberie.com');
            if (!self::esperar($socket, 250)) {
                return false;
            }
            if ($cifrado === 'tls' && $puerto === 587) {
                self::escribir($socket, 'STARTTLS');
                if (!self::esperar($socket, 220)) {
                    return false;
                }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log('Correo SMTP: falla STARTTLS');
                    return false;
                }
                self::escribir($socket, 'EHLO 3bigotesbarberie.com');
                if (!self::esperar($socket, 250)) {
                    return false;
                }
            }
            self::escribir($socket, 'AUTH LOGIN');
            if (!self::esperar($socket, 334)) {
                return false;
            }
            self::escribir($socket, base64_encode($usuario));
            if (!self::esperar($socket, 334)) {
                return false;
            }
            self::escribir($socket, base64_encode($clave));
            if (!self::esperar($socket, 235)) {
                error_log('Correo SMTP: autenticación rechazada');
                return false;
            }
            self::escribir($socket, 'MAIL FROM:<' . $desde . '>');
            if (!self::esperar($socket, 250)) {
                return false;
            }
            self::escribir($socket, 'RCPT TO:<' . $para . '>');
            if (!self::esperar($socket, 250)) {
                return false;
            }
            self::escribir($socket, 'DATA');
            if (!self::esperar($socket, 354)) {
                return false;
            }
            $mensaje = self::armarMensaje($desde, $nombre, $para, $asunto, $texto, $html);
            self::escribir($socket, $mensaje . "\r\n.");
            if (!self::esperar($socket, 250)) {
                return false;
            }
            self::escribir($socket, 'QUIT');
            return true;
        } finally {
            fclose($socket);
        }
    }

    /** @param array{cliente:string,correo_cliente:string,profesional:string,correo_colaborador:?string,inicio:string,fin:string,servicios:list<string>,sede:string} $datos */
    public static function confirmacionCliente(array $datos): bool
    {
        $cuando = self::formatearCuando($datos['inicio']);
        $servicios = self::listaServicios($datos['servicios']);
        $asunto = 'Confirmación de cita — 3 Bigotes';
        $texto = "Hola {$datos['cliente']},\n\n"
            . "Tu cita en {$datos['sede']} quedó confirmada.\n\n"
            . "Cuándo: {$cuando}\n"
            . "Barbero: {$datos['profesional']}\n"
            . "Servicios: {$servicios}\n\n"
            . "Si necesitas cambiar o cancelar, usa Consultar cita en 3bigotesbarberie.com con el teléfono y correo de la reserva.\n\n"
            . "Te esperamos.\n3 Bigotes\n";
        $html = self::plantilla(
            'Cita confirmada',
            '<p>Hola <strong>' . self::e($datos['cliente']) . '</strong>,</p>'
            . '<p>Tu cita en <strong>' . self::e($datos['sede']) . '</strong> quedó confirmada.</p>'
            . self::bloqueDetalle($cuando, $datos['profesional'], $servicios)
            . '<p>Si necesitas cambiar o cancelar, entra a <a href="https://3bigotesbarberie.com/">3bigotesbarberie.com</a> y usa <em>Consultar cita</em> con el teléfono y correo de la reserva.</p>'
            . '<p>Te esperamos.<br>3 Bigotes</p>'
        );
        return self::enviar($datos['correo_cliente'], $asunto, $texto, $html);
    }

    /** @param array{cliente:string,correo_cliente:string,profesional:string,correo_colaborador:?string,inicio:string,fin:string,servicios:list<string>,sede:string} $datos */
    public static function confirmacionColaborador(array $datos): bool
    {
        $correo = trim((string) ($datos['correo_colaborador'] ?? ''));
        if ($correo === '') {
            return false;
        }
        $cuando = self::formatearCuando($datos['inicio']);
        $servicios = self::listaServicios($datos['servicios']);
        $asunto = 'Nueva cita — ' . $datos['cliente'];
        $texto = "Hola {$datos['profesional']},\n\n"
            . "Tienes una nueva cita en {$datos['sede']}.\n\n"
            . "Cliente: {$datos['cliente']}\n"
            . "Cuándo: {$cuando}\n"
            . "Servicios: {$servicios}\n\n"
            . "3 Bigotes\n";
        $html = self::plantilla(
            'Nueva cita',
            '<p>Hola <strong>' . self::e($datos['profesional']) . '</strong>,</p>'
            . '<p>Tienes una nueva cita en <strong>' . self::e($datos['sede']) . '</strong>.</p>'
            . '<p><strong>Cliente:</strong> ' . self::e($datos['cliente']) . '</p>'
            . self::bloqueDetalle($cuando, $datos['profesional'], $servicios)
            . '<p>3 Bigotes</p>'
        );
        return self::enviar($correo, $asunto, $texto, $html);
    }

    /** @param array{cliente:string,correo_cliente:string,profesional:string,correo_colaborador:?string,inicio:string,fin:string,servicios:list<string>,sede:string} $datos */
    public static function recordatorioCliente(array $datos): bool
    {
        $cuando = self::formatearCuando($datos['inicio']);
        $servicios = self::listaServicios($datos['servicios']);
        $asunto = 'Recordatorio de cita — 3 Bigotes';
        $texto = "Hola {$datos['cliente']},\n\n"
            . "Te recordamos tu cita en {$datos['sede']} en aproximadamente dos horas.\n\n"
            . "Cuándo: {$cuando}\n"
            . "Barbero: {$datos['profesional']}\n"
            . "Servicios: {$servicios}\n\n"
            . "Te esperamos.\n3 Bigotes\n";
        $html = self::plantilla(
            'Recordatorio de cita',
            '<p>Hola <strong>' . self::e($datos['cliente']) . '</strong>,</p>'
            . '<p>Te recordamos tu cita en <strong>' . self::e($datos['sede']) . '</strong> en aproximadamente dos horas.</p>'
            . self::bloqueDetalle($cuando, $datos['profesional'], $servicios)
            . '<p>Te esperamos.<br>3 Bigotes</p>'
        );
        return self::enviar($datos['correo_cliente'], $asunto, $texto, $html);
    }

    /** @param array{cliente:string,correo_cliente:string,profesional:string,correo_colaborador:?string,inicio:string,fin:string,servicios:list<string>,sede:string} $datos */
    public static function recordatorioColaborador(array $datos): bool
    {
        $correo = trim((string) ($datos['correo_colaborador'] ?? ''));
        if ($correo === '') {
            return false;
        }
        $cuando = self::formatearCuando($datos['inicio']);
        $servicios = self::listaServicios($datos['servicios']);
        $asunto = 'Recordatorio — cita con ' . $datos['cliente'];
        $texto = "Hola {$datos['profesional']},\n\n"
            . "En aproximadamente dos horas tienes cita con {$datos['cliente']} en {$datos['sede']}.\n\n"
            . "Cuándo: {$cuando}\n"
            . "Servicios: {$servicios}\n\n"
            . "3 Bigotes\n";
        $html = self::plantilla(
            'Recordatorio de cita',
            '<p>Hola <strong>' . self::e($datos['profesional']) . '</strong>,</p>'
            . '<p>En aproximadamente dos horas tienes cita con <strong>' . self::e($datos['cliente']) . '</strong> en <strong>' . self::e($datos['sede']) . '</strong>.</p>'
            . self::bloqueDetalle($cuando, $datos['profesional'], $servicios)
            . '<p>3 Bigotes</p>'
        );
        return self::enviar($correo, $asunto, $texto, $html);
    }

    private static function formatearCuando(string $inicio): string
    {
        $ts = strtotime($inicio);
        if ($ts === false) {
            return $inicio;
        }
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $dia = $dias[(int) date('w', $ts)];
        $mes = $meses[(int) date('n', $ts)];
        return ucfirst($dia) . ' ' . date('j', $ts) . ' de ' . $mes . ' de ' . date('Y', $ts) . ' a las ' . date('H:i', $ts);
    }

    /** @param list<string> $servicios */
    private static function listaServicios(array $servicios): string
    {
        $limpios = array_values(array_filter(array_map(
            static fn ($s): string => trim((string) $s),
            $servicios
        )));
        return $limpios === [] ? 'Servicio' : implode(', ', $limpios);
    }

    private static function bloqueDetalle(string $cuando, string $profesional, string $servicios): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0;border-collapse:collapse;">'
            . '<tr><td style="padding:4px 12px 4px 0;color:#888;">Cuándo</td><td style="padding:4px 0;">' . self::e($cuando) . '</td></tr>'
            . '<tr><td style="padding:4px 12px 4px 0;color:#888;">Barbero</td><td style="padding:4px 0;">' . self::e($profesional) . '</td></tr>'
            . '<tr><td style="padding:4px 12px 4px 0;color:#888;">Servicios</td><td style="padding:4px 0;">' . self::e($servicios) . '</td></tr>'
            . '</table>';
    }

    private static function plantilla(string $titulo, string $cuerpo): string
    {
        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>' . self::e($titulo) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#0b0b0b;color:#ece9e2;font-family:Arial,Helvetica,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;padding:32px 20px;">'
            . '<p style="margin:0 0 8px;letter-spacing:0.2em;color:#d4af37;font-size:12px;">3 BIGOTES</p>'
            . '<h1 style="margin:0 0 24px;font-size:22px;color:#ece9e2;">' . self::e($titulo) . '</h1>'
            . '<div style="font-size:15px;line-height:1.5;">' . $cuerpo . '</div>'
            . '<p style="margin-top:32px;font-size:12px;color:#888;">Este mensaje lo envía 3 Bigotes · atencion@3bigotesbarberie.com</p>'
            . '</div></body></html>';
    }

    private static function e(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function armarMensaje(
        string $desde,
        string $nombre,
        string $para,
        string $asunto,
        string $texto,
        ?string $html
    ): string {
        $boundary = 'tb_' . bin2hex(random_bytes(8));
        $desdeCabecera = self::codificarNombre($nombre) . ' <' . $desde . '>';
        $lineas = [
            'From: ' . $desdeCabecera,
            'To: <' . $para . '>',
            'Subject: ' . self::codificarAsunto($asunto),
            'MIME-Version: 1.0',
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@3bigotesbarberie.com>',
        ];
        if ($html === null || $html === '') {
            $lineas[] = 'Content-Type: text/plain; charset=UTF-8';
            $lineas[] = 'Content-Transfer-Encoding: base64';
            $lineas[] = '';
            $lineas[] = chunk_split(base64_encode($texto));
            return implode("\r\n", $lineas);
        }
        $lineas[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $lineas[] = '';
        $lineas[] = '--' . $boundary;
        $lineas[] = 'Content-Type: text/plain; charset=UTF-8';
        $lineas[] = 'Content-Transfer-Encoding: base64';
        $lineas[] = '';
        $lineas[] = chunk_split(base64_encode($texto));
        $lineas[] = '--' . $boundary;
        $lineas[] = 'Content-Type: text/html; charset=UTF-8';
        $lineas[] = 'Content-Transfer-Encoding: base64';
        $lineas[] = '';
        $lineas[] = chunk_split(base64_encode($html));
        $lineas[] = '--' . $boundary . '--';
        return implode("\r\n", $lineas);
    }

    private static function codificarAsunto(string $asunto): string
    {
        return '=?UTF-8?B?' . base64_encode($asunto) . '?=';
    }

    private static function codificarNombre(string $nombre): string
    {
        if ($nombre === '' || preg_match('/^[\x20-\x7E]+$/', $nombre) === 1) {
            return $nombre === '' ? '3 Bigotes' : $nombre;
        }
        return '=?UTF-8?B?' . base64_encode($nombre) . '?=';
    }

    /** @param resource $socket */
    private static function escribir($socket, string $linea): void
    {
        fwrite($socket, $linea . "\r\n");
    }

    /** @param resource $socket */
    private static function esperar($socket, int $codigo): bool
    {
        $respuesta = '';
        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        $ok = str_starts_with(trim($respuesta), (string) $codigo);
        if (!$ok) {
            error_log('Correo SMTP inesperado: ' . trim($respuesta));
        }
        return $ok;
    }
}
