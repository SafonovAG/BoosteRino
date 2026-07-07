<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\ServiceCatalog;

try {
    $count = ServiceCatalog::syncFromTwiboost();
    echo date('c') . " Synced {$count} services.\n";
} catch (Throwable $e) {
    echo date('c') . ' Error: ' . $e->getMessage() . "\n";
    exit(1);
}
