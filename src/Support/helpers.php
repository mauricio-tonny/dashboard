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

function financial_values_are_masked(mixed $user = null): bool
{
    return is_object($user)
        && method_exists($user, 'masksFinancialValues')
        && $user->masksFinancialValues();
}

function format_money_for_user(mixed $value, mixed $user = null, bool $nbsp = false): string
{
    if ($value === null) {
        return '-';
    }

    $space = $nbsp ? '&nbsp;' : ' ';
    $amount = financial_values_are_masked($user) ? 0.0 : (float) $value;

    return 'R$' . $space . number_format($amount, 2, ',', '.');
}
