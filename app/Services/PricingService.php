<?php

declare(strict_types=1);

namespace App\Services;

final class PricingService
{
    public function price(float $rate, int $qty, ?float $override = null): float
    {
        $markup = $override ?? (new SettingsService())->getFloat('global_markup_percent', 30);
        $base = ($rate / 1000) * $qty;
        return ceil($base * (1 + $markup / 100) * 100) / 100;
    }

    public function forService(array $s, int $qty): float
    {
        $ov = $s['markup_override'] !== null ? (float) $s['markup_override'] : null;
        return $this->price((float) $s['rate'], $qty, $ov);
    }

    public function format(array $s): array
    {
        $ov = $s['markup_override'] !== null ? (float) $s['markup_override'] : null;
        return [
            'id' => (int) $s['id'],
            'external_id' => (int) $s['external_id'],
            'name' => $s['name'],
            'type' => $s['type'],
            'category' => $s['category'],
            'price_per_thousand_rub' => $this->price((float) $s['rate'], 1000, $ov),
            'min' => (int) $s['min_qty'],
            'max' => (int) $s['max_qty'],
            'refill' => (bool) $s['refill'],
            'cancel' => (bool) $s['cancel'],
        ];
    }
}
