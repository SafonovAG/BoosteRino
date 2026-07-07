<?php

declare(strict_types=1);

namespace App\Services;

final class PaymentType
{
    /** @var array<string, string> */
    private const LABELS = [
        'topup' => 'Пополнение',
        'order' => 'Оплата заказа',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? $type;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Ожидает',
            'success' => 'Успешно',
            'failed' => 'Ошибка',
            default => $status,
        };
    }
}
