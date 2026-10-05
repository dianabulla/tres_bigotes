<?php

declare(strict_types=1);

namespace TresBigotes\Api;

final class Router
{
    /** @var list<array{metodo: string, plantilla: string, accion: callable, publica: bool}> */
    private array $rutas = [];

    public function registrar(string $metodo, string $plantilla, callable $accion, bool $publica = false): void
    {
        $this->rutas[] = [
            'metodo' => strtoupper($metodo),
            'plantilla' => $plantilla,
            'accion' => $accion,
            'publica' => $publica,
        ];
    }

    public function despachar(string $metodo, string $ruta): void
    {
        if ($metodo === 'OPTIONS') {
            Respuesta::vacia(204);
        }

        foreach ($this->rutas as $definicion) {
            $params = self::extraer($definicion['plantilla'], $ruta);
            if ($definicion['metodo'] !== $metodo || $params === null) {
                continue;
            }
            $peticion = Peticion::actual($params);
            if (!$definicion['publica']) {
                Guardia::exigir($peticion);
            }
            ($definicion['accion'])($peticion);
            return;
        }

        Respuesta::json(404, ['error' => 'Ruta no encontrada']);
    }

    /** @return array<string, string>|null */
    private static function extraer(string $plantilla, string $ruta): ?array
    {
        $partesPlantilla = explode('/', trim($plantilla, '/'));
        $partesRuta = explode('/', trim($ruta, '/'));
        if (count($partesPlantilla) !== count($partesRuta)) {
            return null;
        }
        $params = [];
        foreach ($partesPlantilla as $indice => $parte) {
            if (str_starts_with($parte, '{') && str_ends_with($parte, '}')) {
                $params[trim($parte, '{}')] = $partesRuta[$indice];
                continue;
            }
            if ($parte !== $partesRuta[$indice]) {
                return null;
            }
        }
        return $params;
    }
}
