<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\SettingsService;

if ($argc < 3) {
    echo "Usage: php bin/create_superadmin.php email password\n";
    exit(1);
}

$email = mb_strtolower(trim($argv[1]));
$password = $argv[2];

if (strlen($password) < 8) {
    echo "Password must be at least 8 characters.\n";
    exit(1);
}

$pdo = Database::connection();
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare(
    'INSERT INTO users (email, password_hash, role, email_verified_at)
     VALUES (:email, :hash, :role, NOW())
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = :role2, email_verified_at = NOW()'
);
$stmt->execute(['email' => $email, 'hash' => $hash, 'role' => 'superadmin', 'role2' => 'superadmin']);

(new SettingsService())->ensureAppSecret();

echo "Superadmin created: {$email}\n";
