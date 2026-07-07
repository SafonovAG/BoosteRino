<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use PDO;

final class AuthService
{
    public function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }
        $st = Database::pdo()->prepare('SELECT id,email,role,balance_rub,email_verified_at,is_active FROM users WHERE id=:id');
        $st->execute(['id' => $id]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        return ($u && $u['is_active']) ? $u : null;
    }

    public function register(string $email, string $pass): void
    {
        $email = mb_strtolower(trim($email));
        if (!Validator::email($email) || !Validator::minLen($pass, 8)) {
            throw new \InvalidArgumentException('Некорректные данные.');
        }
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT id FROM users WHERE email=:e');
        $chk->execute(['e' => $email]);
        if ($chk->fetch()) {
            throw new \InvalidArgumentException('Email уже занят.');
        }
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('INSERT INTO users (email,password_hash) VALUES (:e,:h)')->execute(['e' => $email, 'h' => $hash]);
        $uid = (int) $pdo->lastInsertId();
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO email_verifications (user_id,token,expires_at) VALUES (:u,:t,DATE_ADD(NOW(),INTERVAL 24 HOUR))')
            ->execute(['u' => $uid, 't' => $token]);
        (new MailService())->verifyLink($email, $token);
    }

    public function login(string $email, string $pass): array
    {
        $email = mb_strtolower(trim($email));
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email=:e');
        $st->execute(['e' => $email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if (!$u || !$u['is_active'] || !password_verify($pass, $u['password_hash'])) {
            throw new \InvalidArgumentException('Неверный email или пароль.');
        }
        Session::regen();
        Session::set('user_id', (int) $u['id']);
        unset($u['password_hash']);
        return $u;
    }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::regen();
    }

    public function verifyEmail(string $token): bool
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT user_id FROM email_verifications WHERE token=:t AND expires_at>NOW()');
        $st->execute(['t' => $token]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return false;
        }
        $pdo->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=:id')->execute(['id' => $r['user_id']]);
        $pdo->prepare('DELETE FROM email_verifications WHERE token=:t')->execute(['t' => $token]);
        return true;
    }

    public function forgot(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $st = Database::pdo()->prepare('SELECT id FROM users WHERE email=:e');
        $st->execute(['e' => $email]);
        if (!$st->fetch()) {
            return;
        }
        $token = bin2hex(random_bytes(32));
        Database::pdo()->prepare('INSERT INTO password_resets (email,token,expires_at) VALUES (:e,:t,DATE_ADD(NOW(),INTERVAL 1 HOUR))')
            ->execute(['e' => $email, 't' => $token]);
        (new MailService())->resetLink($email, $token);
    }

    public function reset(string $token, string $pass): bool
    {
        if (!Validator::minLen($pass, 8)) {
            throw new \InvalidArgumentException('Пароль минимум 8 символов.');
        }
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT email FROM password_resets WHERE token=:t AND expires_at>NOW()');
        $st->execute(['t' => $token]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return false;
        }
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE users SET password_hash=:h WHERE email=:e')->execute(['h' => $hash, 'e' => $r['email']]);
        $pdo->prepare('DELETE FROM password_resets WHERE email=:e')->execute(['e' => $r['email']]);
        return true;
    }
}
