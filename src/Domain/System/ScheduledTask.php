<?php

declare(strict_types=1);

namespace App\Domain\System;

interface ScheduledTask
{
    public function code(): string;

    public function name(): string;

    public function intervalMinutes(): int;

    public function run(): ScheduledTaskResult;
}
