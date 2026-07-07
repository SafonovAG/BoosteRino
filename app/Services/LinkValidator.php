<?php

declare(strict_types=1);

namespace App\Services;

final class LinkValidator
{
    /** @var array<string, list<string>> */
    private const HOSTS = [
        'telegram' => ['t.me', 'telegram.me', 'telegram.dog'],
        'telegram-premium' => ['t.me', 'telegram.me', 'telegram.dog'],
        'instagram' => ['instagram.com', 'www.instagram.com', 'instagr.am'],
        'vk' => ['vk.com', 'www.vk.com', 'm.vk.com', 'vk.ru', 'www.vk.ru'],
        'youtube' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
        'tiktok' => ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
        'facebook' => ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.com', 'fb.watch'],
        'twitter' => ['twitter.com', 'www.twitter.com', 'mobile.twitter.com', 'x.com', 'www.x.com'],
        'twitch' => ['twitch.tv', 'www.twitch.tv', 'm.twitch.tv'],
        'discord' => ['discord.com', 'www.discord.com', 'discord.gg'],
        'dzen' => ['dzen.ru', 'www.dzen.ru', 'zen.yandex.ru'],
        'rutube' => ['rutube.ru', 'www.rutube.ru'],
        'odnoklassniki' => ['ok.ru', 'www.ok.ru', 'm.ok.ru'],
        'pinterest' => ['pinterest.com', 'www.pinterest.com', 'pin.it'],
        'linkedin' => ['linkedin.com', 'www.linkedin.com'],
        'spotify' => ['open.spotify.com', 'spotify.com'],
        'steam' => ['steamcommunity.com', 'store.steampowered.com'],
        'kick' => ['kick.com', 'www.kick.com'],
        'trovo' => ['trovo.live', 'www.trovo.live'],
        'likee' => ['likee.video', 'l.likee.video'],
        'threads' => ['threads.net', 'www.threads.net'],
        'avito' => ['avito.ru', 'www.avito.ru'],
        'vcru' => ['vc.ru', 'www.vc.ru'],
        'dtf' => ['dtf.ru', 'www.dtf.ru'],
        'yandexmusic' => ['music.yandex.ru', 'music.yandex.com'],
    ];

    /** @var list<string> */
    private const PROFILE_TYPES = [
        'subscribe', 'follow', 'friend', 'favorite',
    ];

    public function normalize(string $link): string
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }

        if (str_starts_with($link, '@')) {
            $user = ltrim($link, '@');
            if ($user !== '' && preg_match('/^[A-Za-z0-9_]{3,}$/', $user)) {
                return 'https://t.me/' . $user;
            }
        }

        if (!preg_match('#^https?://#i', $link)) {
            $link = 'https://' . ltrim($link, '/');
        }

        return $link;
    }

    /** @param array<string, mixed> $service */
    public function validate(array $service, string $link): string
    {
        $normalized = $this->normalize($link);
        if ($normalized === '') {
            throw new \InvalidArgumentException('Укажите ссылку.');
        }

        $parts = parse_url($normalized);
        if ($parts === false || empty($parts['host'])) {
            throw new \InvalidArgumentException('Некорректный адрес ссылки.');
        }

        $host = mb_strtolower($parts['host']);
        $platform = ServiceLogo::platformSlug($service);
        $type = mb_strtolower((string) ($service['type'] ?? ''));
        $path = $parts['path'] ?? '/';
        $hint = $this->hint($service);
        $isProfile = $this->isProfileType($type, $service);

        if ($platform === 'other') {
            if (!filter_var($normalized, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Укажите корректную ссылку https://...');
            }
            return $normalized;
        }

        $allowed = self::HOSTS[$platform] ?? [];
        if ($allowed && !in_array($host, $allowed, true)) {
            throw new \InvalidArgumentException(
                'Ссылка не соответствует платформе ' . ($hint['platform'] ?? $platform)
                . '. Пример: ' . $hint['example']
            );
        }

        if (!$this->pathMatches($platform, $isProfile, $path, $normalized)) {
            throw new \InvalidArgumentException($hint['error'] ?? 'Неверный формат ссылки для этой услуги.');
        }

        return $normalized;
    }

    /** @param array<string, mixed> $service */
    public function hint(array $service): array
    {
        $platform = ServiceLogo::platformSlug($service);
        $type = mb_strtolower((string) ($service['type'] ?? ''));
        $name = ServiceLogo::platformName($service);
        $isProfile = $this->isProfileType($type, $service);

        $examples = [
            'telegram' => ['profile' => 'https://t.me/channel', 'post' => 'https://t.me/channel/123'],
            'telegram-premium' => ['profile' => 'https://t.me/channel', 'post' => 'https://t.me/channel/123'],
            'instagram' => ['profile' => 'https://instagram.com/username', 'post' => 'https://instagram.com/p/ABC123/'],
            'vk' => ['profile' => 'https://vk.com/public123', 'post' => 'https://vk.com/wall-123_456'],
            'youtube' => ['profile' => 'https://youtube.com/@channel', 'post' => 'https://youtube.com/watch?v=abc'],
            'tiktok' => ['profile' => 'https://tiktok.com/@username', 'post' => 'https://tiktok.com/@user/video/123'],
            'facebook' => ['profile' => 'https://facebook.com/pagename', 'post' => 'https://facebook.com/post/123'],
            'twitter' => ['profile' => 'https://x.com/username', 'post' => 'https://x.com/user/status/123'],
            'twitch' => ['profile' => 'https://twitch.tv/channel', 'post' => 'https://twitch.tv/videos/123'],
            'discord' => ['profile' => 'https://discord.gg/invite', 'post' => 'https://discord.com/channels/1/2'],
            'dzen' => ['profile' => 'https://dzen.ru/channel', 'post' => 'https://dzen.ru/a/abc'],
            'rutube' => ['profile' => 'https://rutube.ru/channel/123', 'post' => 'https://rutube.ru/video/abc/'],
            'odnoklassniki' => ['profile' => 'https://ok.ru/group/123', 'post' => 'https://ok.ru/group/123/topic/456'],
        ];

        $kind = $isProfile ? 'profile' : 'post';
        $example = $examples[$platform][$kind] ?? 'https://example.com/...';
        $target = $isProfile ? 'профиль или канал' : 'публикацию или пост';

        return [
            'platform' => $name,
            'platform_slug' => $platform,
            'label' => 'Ссылка на ' . $target . ' (' . $name . ')',
            'placeholder' => $example,
            'example' => $example,
            'error' => 'Для этой услуги нужна ссылка на ' . $target . ' ' . $name . '. Пример: ' . $example,
            'kind' => $kind,
        ];
    }

    /** @param array<string, mixed> $service */
    private function isProfileType(string $type, array $service): bool
    {
        if (in_array($type, self::PROFILE_TYPES, true)) {
            return true;
        }

        $hay = mb_strtolower(implode(' ', [
            $service['name'] ?? '',
            $service['category'] ?? '',
        ]));

        if (preg_match('/подписчик|subscriber|follower|участник|member|друг|friend/i', $hay)) {
            return true;
        }
        if (preg_match('/лайк|like|просмотр|view|коммент|comment|репост|repost|retweet|голос|vote|реакц/i', $hay)) {
            return false;
        }

        return in_array($type, self::PROFILE_TYPES, true);
    }

    private function pathMatches(string $platform, bool $isProfile, string $path, string $url): bool
    {
        $path = rtrim($path, '/') ?: '/';
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return match ($platform) {
            'instagram' => $isProfile
                ? (bool) preg_match('#^/[A-Za-z0-9._]{1,30}/?$#', $path)
                : (bool) preg_match('#^/(p|reel|tv)/[A-Za-z0-9_-]+#', $path),
            'telegram', 'telegram-premium' => (bool) preg_match('#^/[A-Za-z0-9_]{3,}#', $path),
            'vk' => $isProfile
                ? (bool) preg_match('#^/(id\d+|club\d+|public\d+|[A-Za-z0-9._-]+)/?$#', $path)
                : (bool) preg_match('#^/(wall|video|clip|photo)-?\d#', $path),
            'youtube' => $isProfile
                ? (bool) preg_match('#^/(@[\w.-]+|channel/|c/|user/)#', $path)
                : (bool) (preg_match('#^/watch#', $path) || str_contains($url, 'youtu.be/')),
            'tiktok' => $isProfile
                ? (bool) preg_match('#^/@[\w.-]+/?$#', $path)
                : (bool) preg_match('#^/@[\w.-]+/video/\d+#', $path),
            'twitter' => $isProfile
                ? (bool) preg_match('#^/[A-Za-z0-9_]{1,15}/?$#', $path)
                : (bool) preg_match('#^/[A-Za-z0-9_]+/status/\d+#', $path),
            'facebook' => strlen($path) > 1,
            'twitch' => $isProfile
                ? (bool) preg_match('#^/[a-z0-9_]{2,}/?$#i', $path)
                : (bool) preg_match('#^/(videos/\d+|[a-z0-9_]+/clip/)#i', $path),
            'discord' => str_contains($path, 'channels/') || str_contains($path, 'invite') || $host === 'discord.gg',
            default => strlen($path) > 1 || str_contains($url, '?'),
        };
    }
}
