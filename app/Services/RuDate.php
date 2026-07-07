<?php

declare(strict_types=1);

namespace App\Services;

final class RuDate
{
    /** @var list<string> */
    private const MONTHS = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];

    public static function format(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        $month = (int) date('n', $ts);
        return sprintf(
            '%02d %s %d г.',
            (int) date('d', $ts),
            self::MONTHS[$month] ?? '',
            (int) date('Y', $ts)
        );
    }
}
