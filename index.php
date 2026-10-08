<?php

declare(strict_types=1);

$archivo = __DIR__ . '/dist/index.html';
if (!is_file($archivo)) {
    http_response_code(404);
    echo 'Falta la carpeta dist.';
    return;
}
header('Content-Type: text/html; charset=UTF-8');
readfile($archivo);
