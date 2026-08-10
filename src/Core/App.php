<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class App
{
    public function __construct(private array $container)
    {
    }

    public function make(string $abstract): mixed
    {
        if (!array_key_exists($abstract, $this->container)) {
            throw new RuntimeException("Dependencia {$abstract} não encontrada.");
        }

        return $this->container[$abstract];
    }
}

