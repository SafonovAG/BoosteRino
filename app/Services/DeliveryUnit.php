<?php

declare(strict_types=1);

namespace App\Services;

final class DeliveryUnit
{
    /** @var list<array{0: string, 1: string}> */
    private const RULES = [
        ['/подписчик|followers?|subscriber/ui', 'подписчиков'],
        ['/лайк|like/ui', 'лайков'],
        ['/просмотр|view/ui', 'просмотров'],
        ['/коммент/ui', 'комментариев'],
        ['/репост|repost|share/ui', 'репостов'],
        ['/сохранен|save/ui', 'сохранений'],
        ['/охват|reach/ui', 'охвата'],
        ['/показ/ui', 'показов'],
        ['/голос|vote/ui', 'голосов'],
        ['/участник|member/ui', 'участников'],
        ['/друг|friend/ui', 'друзей'],
        ['/прослуш|play/ui', 'прослушиваний'],
        ['/реакц/ui', 'реакций'],
        ['/отзыв|review/ui', 'отзывов'],
    ];

    public static function fromName(string $name): string
    {
        foreach (self::RULES as [$pattern, $unit]) {
            if (preg_match($pattern, $name)) {
                return $unit;
            }
        }

        return 'единиц';
    }

    public static function priceLabel(string $name, int $basis = 1000): string
    {
        return 'за ' . number_format($basis, 0, '', ' ') . ' ' . self::fromName($name);
    }
}
