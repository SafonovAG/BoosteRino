<?php

declare(strict_types=1);

namespace App\Services;

final class OrderStatus
{
    /** @var array<string, string> */
    private const LABELS = [
        'pending' => 'Ожидает обработки',
        'pending_payment' => 'Ожидает оплаты',
        'Awaiting' => 'Ожидает запуска',
        'In progress' => 'Выполняется',
        'Partial' => 'Частично выполнен',
        'Completed' => 'Выполнен',
        'Canceled' => 'Отменён',
        'Cancelled' => 'Отменён',
        'Fail' => 'Ошибка',
        'Failed' => 'Ошибка',
        'Error' => 'Ошибка',
    ];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function isActive(string $status): bool
    {
        $s = strtolower($status);
        return str_contains($s, 'progress')
            || str_contains($s, 'await')
            || str_contains($s, 'partial')
            || $status === 'pending'
            || $status === 'pending_payment';
    }
}
