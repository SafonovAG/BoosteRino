<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\OrderService;

try {
    $count = (new OrderService())->syncActiveStatuses();
    echo date('c') . " Updated {$count} orders.\n";
} catch (Throwable $e) {
    echo date('c') . ' Error: ' . $e->getMessage() . "\n";
    exit(1);
}
