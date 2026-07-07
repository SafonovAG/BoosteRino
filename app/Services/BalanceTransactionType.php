<?php

declare(strict_types=1);

namespace App\Services;

final class BalanceTransactionType
{
    /** @var array<string, string> */
    private const LABELS = [
        'order' => 'Списание за заказ',
        'topup' => 'Пополнение',
        'admin_adjust' => 'Корректировка администратором',
        'refund' => 'Возврат',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? $type;
    }
}
