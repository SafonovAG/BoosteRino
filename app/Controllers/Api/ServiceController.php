<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\PricingService;
use App\Services\ServiceCatalog;

final class ServiceController
{
    public static function index(Request $request): void
    {
        $pricing = new PricingService();
        $services = array_map(
            static fn (array $s) => $pricing->formatService($s),
            ServiceCatalog::activeList()
        );
        Response::success(['services' => $services]);
    }

    public static function show(Request $request, array $params): void
    {
        $service = ServiceCatalog::find((int) $params['id']);
        if (!$service || !(int) $service['is_active']) {
            Response::error('not_found', 'Услуга не найдена.', 404);
            return;
        }

        $quantity = max(1, (int) $request->query('quantity', (string) $service['min_qty']));
        $pricing = new PricingService();
        $formatted = $pricing->formatService($service);
        $formatted['price_rub'] = $pricing->forService($service, $quantity);
        $formatted['quantity'] = $quantity;

        Response::success(['service' => $formatted]);
    }
}
