<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);

    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function view(string $template, array $data = []): string
{
    $file = base_path('views/' . $template . '.php');

    if (!is_file($file)) {
        throw new RuntimeException("View {$template} não encontrada.");
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;
    return (string) ob_get_clean();
}

