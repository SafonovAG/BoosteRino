<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function required(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null && $value !== '';
    }

    public static function minLength(string $value, int $min): bool
    {
        return mb_strlen($value) >= $min;
    }

    public static function maxLength(string $value, int $max): bool
    {
        return mb_strlen($value) <= $max;
    }

    public static function integer(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public static function url(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            || filter_var('https://' . ltrim($value, '/'), FILTER_VALIDATE_URL) !== false;
    }

    public static function in(mixed $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }
}
