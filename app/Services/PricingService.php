<?php

declare(strict_types=1);

namespace App\Services;

final class PricingService
{
    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    public function calculate(float $rate, int $quantity, ?float $markupOverride = null): float
    {
        $base = ($rate / 1000) * $quantity;
        $markup = $markupOverride ?? $this->settings->getFloat('global_markup_percent', 30.0);

        return ceil($base * (1 + $markup / 100) * 100) / 100;
    }

    public function forService(array $service, int $quantity): float
    {
        $override = $service['markup_override'] !== null
            ? (float) $service['markup_override']
            : null;

        return $this->calculate((float) $service['rate'], $quantity, $override);
    }

    public function formatService(array $service): array
    {
        $pricePerThousand = $this->calculate((float) $service['rate'], 1000, $service['markup_override'] !== null ? (float) $service['markup_override'] : null);

        return [
            'id' => (int) $service['id'],
            'external_id' => (int) $service['external_id'],
            'name' => $service['name'],
            'type' => $service['type'],
            'category' => $service['category'],
            'rate' => (float) $service['rate'],
            'price_per_thousand_rub' => $pricePerThousand,
            'min' => (int) $service['min_qty'],
            'max' => (int) $service['max_qty'],
            'refill' => (bool) $service['refill'],
            'cancel' => (bool) $service['cancel'],
            'is_active' => (bool) $service['is_active'],
        ];
    }
}
