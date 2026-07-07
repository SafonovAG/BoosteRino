<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

final class RateLimitMiddleware extends Middleware
{
    public function __construct(private readonly string $action, private readonly int $max = 5) {}

    public function handle(Request $req, array $params, callable $next): mixed
    {
        $pdo = Database::pdo();
        $ip = $req->ip();
        $st = $pdo->prepare('SELECT id, attempts, window_start FROM rate_limits WHERE ip=:ip AND action=:a');
        $st->execute(['ip' => $ip, 'a' => $this->action]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $age = time() - strtotime($row['window_start']);
            if ($age > 900) {
                $pdo->prepare('UPDATE rate_limits SET attempts=1, window_start=NOW() WHERE id=:id')->execute(['id' => $row['id']]);
            } elseif ((int) $row['attempts'] >= $this->max) {
                Response::fail('rate_limit', 'Слишком много попыток.', 429);
            } else {
                $pdo->prepare('UPDATE rate_limits SET attempts=attempts+1 WHERE id=:id')->execute(['id' => $row['id']]);
            }
        } else {
            $pdo->prepare('INSERT INTO rate_limits (ip, action) VALUES (:ip,:a)')->execute(['ip' => $ip, 'a' => $this->action]);
        }
        return $next($req, $params);
    }
}
