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
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, email, role, balance_rub, email_verified_at, is_active, created_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(int) $user['is_active']) {
            return null;
        }

        return $user;
    }

    public function register(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));

        if (!Validator::email($email)) {
            throw new \InvalidArgumentException('Некорректный email.');
        }
        if (!Validator::minLength($password, 8)) {
            throw new \InvalidArgumentException('Пароль должен быть не менее 8 символов.');
        }

        $pdo = Database::connection();

        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            throw new \InvalidArgumentException('Email уже зарегистрирован.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, :role)'
        );
        $stmt->execute(['email' => $email, 'hash' => $hash, 'role' => 'user']);
        $userId = (int) $pdo->lastInsertId();

        $token = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO email_verifications (user_id, token, expires_at)
             VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        )->execute(['user_id' => $userId, 'token' => $token]);

        (new MailService())->sendVerification($email, $token);

        return ['id' => $userId, 'email' => $email];
    }

    public function login(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $stmt = Database::connection()->prepare(
            'SELECT id, email, password_hash, role, balance_rub, email_verified_at, is_active
             FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            throw new \InvalidArgumentException('Неверный email или пароль.');
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        unset($user['password_hash']);

        return $user;
    }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::forget('current_user');
        Session::regenerate();
    }

    public function verifyEmail(string $token): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT ev.user_id FROM email_verifications ev
             WHERE ev.token = :token AND ev.expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        $pdo->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :id')
            ->execute(['id' => $row['user_id']]);
        $pdo->prepare('DELETE FROM email_verifications WHERE token = :token')
            ->execute(['token' => $token]);

        return true;
    }

    public function forgotPassword(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if (!$stmt->fetch()) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        Database::connection()->prepare(
            'INSERT INTO password_resets (email, token, expires_at)
             VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute(['email' => $email, 'token' => $token]);

        (new MailService())->sendPasswordReset($email, $token);
    }

    public function resetPassword(string $token, string $password): bool
    {
        if (!Validator::minLength($password, 8)) {
            throw new \InvalidArgumentException('Пароль должен быть не менее 8 символов.');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE users SET password_hash = :hash WHERE email = :email')
            ->execute(['hash' => $hash, 'email' => $row['email']]);
        $pdo->prepare('DELETE FROM password_resets WHERE email = :email')
            ->execute(['email' => $row['email']]);

        return true;
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current, $row['password_hash'])) {
            throw new \InvalidArgumentException('Текущий пароль неверен.');
        }
        if (!Validator::minLength($new, 8)) {
            throw new \InvalidArgumentException('Новый пароль должен быть не менее 8 символов.');
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::connection()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute(['hash' => $hash, 'id' => $userId]);
    }
}
