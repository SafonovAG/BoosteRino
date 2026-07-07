<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class SettingsService
{
  private static ?array $cache = null;

  private const SENSITIVE_KEYS = [
    'app_secret',
    'twiboost_api_key',
    'yoomoney_secret',
    'mail_pass',
  ];

  public function all(): array
  {
    $this->load();

    return self::$cache ?? [];
  }

  public function get(string $key, string $default = ''): string
  {
    $this->load();

    return self::$cache[$key] ?? $default;
  }

  public function getFloat(string $key, float $default = 0.0): float
  {
    return (float) $this->get($key, (string) $default);
  }

  public function set(string $key, string $value): void
  {
    $pdo = Database::connection();
    $sensitive = in_array($key, self::SENSITIVE_KEYS, true) ? 1 : 0;

    $stmt = $pdo->prepare(
      'INSERT INTO settings (setting_key, setting_value, is_sensitive)
       VALUES (:key, :value, :sensitive)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_sensitive = VALUES(is_sensitive)'
    );
    $stmt->execute([
      'key' => $key,
      'value' => $value,
      'sensitive' => $sensitive,
    ]);

    self::$cache = null;
    $this->load();
  }

  public function forAdmin(): array
  {
    $this->load();
    $result = [];

    foreach (self::$cache ?? [] as $key => $value) {
      $isSensitive = in_array($key, self::SENSITIVE_KEYS, true);
      $result[$key] = [
        'value' => $isSensitive && $value !== '' ? '****' : $value,
        'is_set' => $value !== '',
        'is_sensitive' => $isSensitive,
      ];
    }

    return $result;
  }

  public function ensureAppSecret(): void
  {
    if ($this->get('app_secret') === '') {
      $this->set('app_secret', bin2hex(random_bytes(32)));
    }
  }

  private function load(): void
  {
    if (self::$cache !== null) {
      return;
    }

    $pdo = Database::connection();
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    self::$cache = [];
    foreach ($rows as $row) {
      self::$cache[$row['setting_key']] = (string) $row['setting_value'];
    }
  }
}
