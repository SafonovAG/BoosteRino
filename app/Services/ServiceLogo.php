<?php

declare(strict_types=1);

namespace App\Services;

final class ServiceLogo
{
    private const DEFAULT = '/assets/images/logo/default.svg';

    /** @var array<string, string> */
    private const LOGOS = [
        'telegram-premium' => '/assets/images/logo/telegram-premium.png',
        'telegram' => '/assets/images/logo/telegram.svg',
        'tiktok' => '/assets/images/logo/tiktok.svg',
        'youtube' => '/assets/images/logo/youtube.svg',
        'vk' => '/assets/images/logo/vk.svg',
        'facebook' => '/assets/images/logo/facebook.svg',
        'twitch' => '/assets/images/logo/twitch.svg',
        'twitter' => '/assets/images/logo/twitter.png',
        'instagram' => '/assets/images/logo/instagram.svg',
        'discord' => '/assets/images/logo/discord.png',
        'dzen' => '/assets/images/logo/dzen.png',
        'rutube' => '/assets/images/logo/rutube.png',
        'odnoklassniki' => '/assets/images/logo/odnoklassniki.png',
        'pinterest' => '/assets/images/logo/pinterest.png',
        'linkedin' => '/assets/images/logo/linkedin.png',
        'spotify' => '/assets/images/logo/spotify.png',
        'steam' => '/assets/images/logo/steam.png',
        'kick' => '/assets/images/logo/kick.png',
        'trovo' => '/assets/images/logo/trovo.png',
        'likee' => '/assets/images/logo/likee.png',
        'threads' => '/assets/images/logo/threads.png',
        'avito' => '/assets/images/logo/avito.png',
        'vcru' => '/assets/images/logo/vcru.png',
        'dtf' => '/assets/images/logo/dtf.png',
        'yandexmusic' => '/assets/images/logo/yandexmusic.png',
        'yappi' => '/assets/images/logo/yappi.jpg',
        'wibes' => '/assets/images/logo/wibes.ico',
        'max' => '/assets/images/logo/max.png',
        'trafficnasayt' => '/assets/images/logo/trafficnasayt.png',
    ];

    /** @var array<string, list<string>> */
    private const KEYWORDS = [
        'telegram-premium' => ['telegram premium', 'tg premium', 'премиум подпис', 'premium telegram', 'телеграм премиум'],
        'telegram' => ['telegram', 'телеграм', 'tg ', ' tg'],
        'tiktok' => ['tiktok', 'тик ток', 'тикток'],
        'youtube' => ['youtube', 'ютуб', 'you tube'],
        'vk' => ['вконтакте', 'vkontakte', ' vk', 'vk '],
        'facebook' => ['facebook', 'фейсбук', ' fb', 'fb '],
        'twitch' => ['twitch', 'твич'],
        'twitter' => ['twitter', 'твиттер', ' x.com', 'twitter / x', 'twitter/x'],
        'instagram' => ['instagram', 'инстаграм', 'insta '],
        'discord' => ['discord', 'дискорд'],
        'dzen' => ['дзен', 'dzen', 'zen.yandex', 'яндекс дзен'],
        'rutube' => ['rutube', 'рутуб'],
        'odnoklassniki' => ['одноклассник', 'odnoklassniki', 'ok.ru', ' ok '],
        'pinterest' => ['pinterest', 'пинтерест'],
        'linkedin' => ['linkedin', 'линкедин'],
        'spotify' => ['spotify', 'спотифай'],
        'steam' => ['steam', 'стим'],
        'kick' => ['kick.com', ' kick '],
        'trovo' => ['trovo', 'трово'],
        'likee' => ['likee'],
        'threads' => ['threads', 'тредс'],
        'avito' => ['avito', 'авито'],
        'vcru' => ['vc.ru', 'vcru', 'vc ru'],
        'dtf' => ['dtf.ru', ' dtf'],
        'yandexmusic' => ['yandex music', 'yandexmusic', 'яндекс муз', 'яндекс.музы'],
        'yappi' => ['yappi', 'яппи'],
        'wibes' => ['wibes', 'вайбс'],
        'max' => ['vk max', 'мессенджер max', ' max messenger'],
        'trafficnasayt' => ['трафик на сайт', 'traffic website', 'веб трафик', 'website traffic', 'посещения сайт'],
    ];

    /** @var array<string, string> */
    private const PLATFORM_NAMES = [
        'telegram' => 'Telegram',
        'vk' => 'VK',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'facebook' => 'Facebook',
        'twitch' => 'Twitch',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'discord' => 'Discord',
        'dzen' => 'Дзен',
        'rutube' => 'Rutube',
        'odnoklassniki' => 'Одноклассники',
        'spotify' => 'Spotify',
        'steam' => 'Steam',
        'avito' => 'Avito',
    ];

    /** @var array<string, string> */
    private static array $categoryCache = [];

    public static function forCategory(string $category): string
    {
        $key = mb_strtolower(trim($category));
        if ($key === '') {
            return self::DEFAULT;
        }
        if (!isset(self::$categoryCache[$key])) {
            self::$categoryCache[$key] = self::matchText($key);
        }
        return self::$categoryCache[$key];
    }

    public static function forService(array $service): string
    {
        $category = trim($service['category'] ?? '');
        if ($category !== '') {
            return self::forCategory($category);
        }

        $hay = mb_strtolower(implode(' ', [
            $service['name'] ?? '',
            $service['type'] ?? '',
        ]));

        return self::matchText($hay);
    }

    private static function matchText(string $hay): string
    {
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

        foreach (self::LOGOS as $slug => $path) {
            $needle = str_replace('-', ' ', $slug);
            if (str_contains($hay, $needle) || str_contains($hay, $slug)) {
                return $path;
            }
        }

        return self::DEFAULT;
    }

    /** @return list<array{slug: string, name: string, logo: string}> */
    public static function platforms(): array
    {
        $list = [
            ['slug' => 'all', 'name' => 'Все платформы', 'logo' => self::DEFAULT],
        ];

        foreach (self::PLATFORM_NAMES as $slug => $name) {
            if (isset(self::LOGOS[$slug])) {
                $list[] = ['slug' => $slug, 'name' => $name, 'logo' => self::LOGOS[$slug]];
            }
        }

        return $list;
    }

    public static function matchesPlatform(array $service, string $slug): bool
    {
        if ($slug === 'all' || $slug === '') {
            return true;
        }

        return self::platformSlug($service) === $slug;
    }

    public static function platformSlug(array $service): string
    {
        $logo = self::forService($service);
        foreach (self::LOGOS as $slug => $path) {
            if ($path === $logo) {
                return $slug;
            }
        }
        return 'other';
    }
}
