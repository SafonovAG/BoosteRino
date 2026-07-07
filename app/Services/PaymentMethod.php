<?php

declare(strict_types=1);

namespace App\Services;

final class PaymentMethod
{
    public const EXTERNAL_SHORT = 'Карта, SberPay, МИР или ЮMoney';

    public const EXTERNAL_LONG = 'банковской картой, SberPay, МИР или кошельком ЮMoney';

    public const EXTERNAL_NOUN = 'Банковской картой, SberPay, МИР или кошелёк ЮMoney';

    public static function label(string $method): string
    {
        return match ($method) {
            'balance' => 'С баланса',
            'yoomoney' => self::EXTERNAL_SHORT,
            default => $method,
        };
    }
}
