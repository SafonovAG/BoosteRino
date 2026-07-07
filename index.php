<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/bootstrap/autoload.php';

use App\Core\App;

App::run();
