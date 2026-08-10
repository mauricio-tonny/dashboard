<?php

declare(strict_types=1);

namespace App\Domain\System;

final class ScheduledTaskResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $metadata = []
    ) {
    }

    public static function success(string $message, array $metadata = []): self
    {
        return new self(true, $message, $metadata);
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(false, $message, $metadata);
    }
}
