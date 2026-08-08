<?php

declare(strict_types=1);

namespace App\Domain\Auth;

enum Role: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Visualizador',
        };
    }
}
