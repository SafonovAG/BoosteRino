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
        'medium' => '/assets/images/logo/medium.svg',
        'yappi' => '/assets/images/logo/yappi.svg',
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
        'medium' => ['medium', 'медиум', 'medium.com'],
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
        'medium' => 'Medium',
        'yappi' => 'Yappi',
        'pinterest' => 'Pinterest',
        'linkedin' => 'LinkedIn',
        'kick' => 'Kick',
        'trovo' => 'Trovo',
        'likee' => 'Likee',
        'threads' => 'Threads',
        'vcru' => 'VC.ru',
        'dtf' => 'DTF',
        'yandexmusic' => 'Яндекс Музыка',
        'wibes' => 'Wibes',
        'max' => 'MAX',
        'trafficnasayt' => 'Трафик на сайт',
        'telegram-premium' => 'Telegram Premium',
    ];

    /** @var list<string> */
    private const GENERIC_CATEGORIES = [
        'api', 'other', 'others', 'misc', 'general',
        'разное', 'прочее', 'другое', 'общее',
    ];

    /** @var array<string, string> */
    private static array $categoryCache = [];

    private static function isGenericCategory(string $category): bool
    {
        $key = mb_strtolower(trim($category));
        return $key === '' || in_array($key, self::GENERIC_CATEGORIES, true);
    }

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
        $hayName = mb_strtolower(implode(' ', [
            $service['name'] ?? '',
            $service['type'] ?? '',
        ]));

        if ($category !== '' && !self::isGenericCategory($category)) {
            $fromCategory = self::forCategory($category);
            if ($fromCategory !== self::DEFAULT) {
                return $fromCategory;
            }
        }

        $fromName = self::matchText($hayName);
        if ($fromName !== self::DEFAULT) {
            return $fromName;
        }

        if ($category !== '') {
            return self::forCategory($category);
        }

        return self::DEFAULT;
    }

    public static function platformName(array $service): string
    {
        $slug = self::platformSlug($service);
        return self::PLATFORM_NAMES[$slug] ?? 'Прочее';
    }

    public static function categoryLabel(array $service): string
    {
        $category = trim($service['category'] ?? '');
        if (self::isGenericCategory($category)) {
            $name = self::platformName($service);
            return $name !== 'Прочее' ? $name : ($category !== '' ? $category : 'Прочее');
        }

        return $category !== '' ? $category : self::platformName($service);
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

        foreach (self::LOGOS as $slug => $path) {
            $name = self::PLATFORM_NAMES[$slug] ?? ucfirst(str_replace('-', ' ', $slug));
            $list[] = ['slug' => $slug, 'name' => $name, 'logo' => $path];
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
