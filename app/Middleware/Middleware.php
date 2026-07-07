<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

abstract class Middleware
{
    abstract public function handle(Request $req, array $params, callable $next): mixed;
}
