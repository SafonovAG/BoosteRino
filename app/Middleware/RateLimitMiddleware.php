<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

final class RateLimitMiddleware extends Middleware
{
    public function __construct(
        private readonly string $action,
        private readonly int $maxAttempts = 5,
        private readonly int $windowMinutes = 15,
    ) {
    }

    public function handle(Request $request, array $params, callable $next): mixed
    {
        $ip = $request->ip();
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, attempts, window_start FROM rate_limits WHERE ip = :ip AND action = :action LIMIT 1'
        );
        $stmt->execute(['ip' => $ip, 'action' => $this->action]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new \DateTimeImmutable();

        if ($row) {
            $windowStart = new \DateTimeImmutable($row['window_start']);
            $diff = $now->getTimestamp() - $windowStart->getTimestamp();

            if ($diff > $this->windowMinutes * 60) {
                $update = $pdo->prepare(
                    'UPDATE rate_limits SET attempts = 1, window_start = NOW() WHERE id = :id'
                );
                $update->execute(['id' => $row['id']]);
            } elseif ((int) $row['attempts'] >= $this->maxAttempts) {
                Response::error('rate_limit', 'Слишком много попыток. Попробуйте позже.', 429);
                return null;
            } else {
                $update = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE id = :id');
                $update->execute(['id' => $row['id']]);
            }
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO rate_limits (ip, action, attempts, window_start) VALUES (:ip, :action, 1, NOW())'
            );
            $insert->execute(['ip' => $ip, 'action' => $this->action]);
        }

        return $next($request, $params);
    }
}
