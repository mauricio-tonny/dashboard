<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

abstract class Controller
{
    public function __construct(protected App $app)
    {
    }
}

