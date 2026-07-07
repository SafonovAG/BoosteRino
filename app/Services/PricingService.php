<?php

declare(strict_types=1);

namespace App\Services;

final class PricingService
{
    private const PRICE_BASIS = 1000;

    public function price(float $rate, int $qty, ?float $override = null): float
    {
        $markup = $override ?? (new SettingsService())->getFloat('global_markup_percent', 30);
        $base = ($rate / self::PRICE_BASIS) * $qty;
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
        $linkHint = (new LinkValidator())->hint($s);
        $deliveryUnit = DeliveryUnit::fromName((string) $s['name']);
        return [
            'id' => (int) $s['id'],
            'external_id' => (int) $s['external_id'],
            'name' => $s['name'],
            'type' => $s['type'],
            'category' => $s['category'],
            'price_basis' => self::PRICE_BASIS,
            'delivery_unit' => $deliveryUnit,
            'price_unit_label' => DeliveryUnit::priceLabel((string) $s['name'], self::PRICE_BASIS),
            'price_per_thousand_rub' => $this->price((float) $s['rate'], self::PRICE_BASIS, $ov),
            'min' => (int) $s['min_qty'],
            'max' => (int) $s['max_qty'],
            'refill' => (bool) $s['refill'],
            'cancel' => (bool) $s['cancel'],
            'logo' => ServiceLogo::forService($s),
            'platform' => ServiceLogo::platformSlug($s),
            'platform_name' => ServiceLogo::platformName($s),
            'category_label' => ServiceLogo::categoryLabel($s),
            'link_label' => $linkHint['label'],
            'link_placeholder' => $linkHint['placeholder'],
            'link_example' => $linkHint['example'],
            'link_kind' => $linkHint['kind'],
        ];
    }

    private static function platformSlug(array $s): string
    {
        return ServiceLogo::platformSlug($s);
    }
}
