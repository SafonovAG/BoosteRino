<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function email(string $v): bool
    {
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function minLen(string $v, int $n): bool
    {
        return mb_strlen($v) >= $n;
    }

    public static function in(mixed $v, array $list): bool
    {
        return in_array($v, $list, true);
    }
}
