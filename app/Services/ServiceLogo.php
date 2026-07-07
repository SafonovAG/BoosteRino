<?php

declare(strict_types=1);

namespace App\Services;

final class ServiceLogo
{
    private const LOGOS = [
        'telegram' => '/assets/images/logo/telegram.svg',
        'tiktok' => '/assets/images/logo/tiktok.svg',
        'youtube' => '/assets/images/logo/youtube.svg',
        'vk' => '/assets/images/logo/vk.svg',
        'facebook' => '/assets/images/logo/facebook.svg',
        'twitch' => '/assets/images/logo/twitch.svg',
    ];

    private const DEFAULT = '/assets/images/logo/default.svg';

    /** @var array<string, list<string>> */
    private const KEYWORDS = [
        'telegram' => ['telegram', 'телеграм', 'tg '],
        'tiktok' => ['tiktok', 'тик ток', 'тикток'],
        'youtube' => ['youtube', 'ютуб'],
        'vk' => ['вконтакте', 'vkontakte', ' vk', 'vk '],
        'facebook' => ['facebook', 'фейсбук', ' fb'],
        'twitch' => ['twitch', 'твич'],
    ];

    public static function forService(array $service): string
    {
        $hay = mb_strtolower(implode(' ', [
            $service['category'] ?? '',
            $service['name'] ?? '',
            $service['type'] ?? '',
        ]));

        foreach (self::KEYWORDS as $platform => $words) {
            foreach ($words as $w) {
                if (str_contains($hay, $w)) {
                    return self::LOGOS[$platform];
                }
            }
        }

        if (preg_match('/\bvk\b/u', $hay)) {
            return self::LOGOS['vk'];
        }

        return self::DEFAULT;
    }

    /** @return list<array{slug: string, name: string, logo: string}> */
    public static function platforms(): array
    {
        return [
            ['slug' => 'all', 'name' => 'Все платформы', 'logo' => self::DEFAULT],
            ['slug' => 'telegram', 'name' => 'Telegram', 'logo' => self::LOGOS['telegram']],
            ['slug' => 'vk', 'name' => 'VK', 'logo' => self::LOGOS['vk']],
            ['slug' => 'youtube', 'name' => 'YouTube', 'logo' => self::LOGOS['youtube']],
            ['slug' => 'tiktok', 'name' => 'TikTok', 'logo' => self::LOGOS['tiktok']],
            ['slug' => 'facebook', 'name' => 'Facebook', 'logo' => self::LOGOS['facebook']],
            ['slug' => 'twitch', 'name' => 'Twitch', 'logo' => self::LOGOS['twitch']],
        ];
    }

    public static function matchesPlatform(array $service, string $slug): bool
    {
        if ($slug === 'all' || $slug === '') {
            return true;
        }
        $logo = self::forService($service);
        return isset(self::LOGOS[$slug]) && self::LOGOS[$slug] === $logo;
    }
}
