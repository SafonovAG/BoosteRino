<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';
define('BASE_PATH', dirname(__DIR__));

use App\Core\Database;

if ($argc < 3) {
    echo "php bin/create_superadmin.php email password\n";
    exit(1);
}

$hash = password_hash($argv[2], PASSWORD_BCRYPT, ['cost' => 12]);
Database::pdo()->prepare(
    'INSERT INTO users (email,password_hash,role,email_verified_at) VALUES (:e,:h,\'superadmin\',NOW())
     ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),role=\'superadmin\',email_verified_at=NOW()'
)->execute(['e' => mb_strtolower($argv[1]), 'h' => $hash]);

(new App\Services\SettingsService())->ensureSecret();
echo "OK: {$argv[1]}\n";
