<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\PricingService;
use App\Services\ServiceCatalog;

final class ServiceApi
{
    public static function index(Request $r): void
    {
        $p = new PricingService();
        Response::ok(['services' => array_map([$p, 'format'], ServiceCatalog::active())]);
    }

    public static function show(Request $r, array $par): void
    {
        $s = ServiceCatalog::find((int) $par['id']);
        if (!$s || !$s['is_active']) {
            Response::fail('not_found', 'Не найдено.', 404);
        }
        $qty = max(1, (int) $r->query('quantity', (string) $s['min_qty']));
        $p = new PricingService();
        $f = $p->format($s);
        $f['quantity'] = $qty;
        $f['price_rub'] = $p->forService($s, $qty);
        Response::ok(['service' => $f]);
    }
}
