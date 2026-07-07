<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AdminDiagnosticsService
{
    /** @return list<array{group: string, name: string, status: string, message: string, ms: int}> */
    public function runAll(): array
    {
        $results = [];
        $results[] = $this->check('Система', 'База данных', fn () => $this->checkDatabase());
        $results[] = $this->check('Система', 'Таблица settings', fn () => $this->checkTable('settings'));
        $results[] = $this->check('Система', 'Таблица services', fn () => $this->checkTable('services'));
        $results[] = $this->check('Система', 'Таблица orders', fn () => $this->checkTable('orders'));
        $results[] = $this->check('Система', 'Таблица users', fn () => $this->checkTable('users'));
        $results[] = $this->check('Настройки', 'URL сайта', fn () => $this->checkSetting('app_url'));
        $results[] = $this->check('Настройки', 'Наценка', fn () => $this->checkSetting('global_markup_percent'));
        $results[] = $this->check('Настройки', 'Ключ поставщика', fn () => $this->checkSecret('twiboost_api_key'));
        $results[] = $this->check('Настройки', 'Кошелёк ЮMoney', fn () => $this->checkSetting('yoomoney_wallet'));
        $results[] = $this->check('Настройки', 'Секрет ЮMoney', fn () => $this->checkSecret('yoomoney_secret'));
        $results[] = $this->check('Настройки', 'SMTP host', fn () => $this->checkSetting('mail_host'));
        $results[] = $this->check('Поставщик', 'Баланс', fn () => $this->checkSupplierBalance());
        $results[] = $this->check('Поставщик', 'Каталог услуг', fn () => $this->checkSupplierServices());
        $results[] = $this->check('Каталог', 'Активные услуги', fn () => $this->checkActiveServices());
        $results[] = $this->check('Заказы', 'Синхронизация статусов', fn () => $this->checkOrderSync());

        return $results;
    }

    /** @param callable(): array{ok: bool, message: string} $fn */
    private function check(string $group, string $name, callable $fn): array
    {
        $start = hrtime(true);
        try {
            $r = $fn();
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            return [
                'group' => $group,
                'name' => $name,
                'status' => $r['ok'] ? 'ok' : 'warn',
                'message' => $r['message'],
                'ms' => $ms,
            ];
        } catch (\Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            return [
                'group' => $group,
                'name' => $name,
                'status' => 'error',
                'message' => $e->getMessage(),
                'ms' => $ms,
            ];
        }
    }

    /** @return array{ok: bool, message: string} */
    private function checkDatabase(): array
    {
        Database::pdo()->query('SELECT 1');
        return ['ok' => true, 'message' => 'Подключение успешно'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkTable(string $table): array
    {
        $n = (int) Database::pdo()->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        return ['ok' => $n >= 0, 'message' => $n . ' записей'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSetting(string $key): array
    {
        $v = (new SettingsService())->get($key);
        if ($v === '') {
            return ['ok' => false, 'message' => 'Не задано'];
        }
        return ['ok' => true, 'message' => mb_strlen($v) > 40 ? mb_substr($v, 0, 37) . '...' : $v];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSecret(string $key): array
    {
        $v = (new SettingsService())->get($key);
        return $v !== ''
            ? ['ok' => true, 'message' => 'Задан (' . strlen($v) . ' симв.)']
            : ['ok' => false, 'message' => 'Не задан'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierBalance(): array
    {
        $b = (new TwiboostClient())->balance();
        $bal = $b['balance'] ?? $b['balance_rub'] ?? null;
        $cur = $b['currency'] ?? '';
        return ['ok' => $bal !== null, 'message' => ($bal ?? '?') . ($cur ? ' ' . $cur : '')];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierServices(): array
    {
        $list = (new TwiboostClient())->services();
        $count = is_array($list) ? count($list) : 0;
        return ['ok' => $count > 0, 'message' => $count . ' услуг получено'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkActiveServices(): array
    {
        $n = (int) Database::pdo()->query('SELECT COUNT(*) FROM services WHERE is_active=1')->fetchColumn();
        return ['ok' => $n > 0, 'message' => $n . ' активных'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkOrderSync(): array
    {
        $n = (new OrderService())->sync();
        return ['ok' => true, 'message' => 'Обновлено: ' . $n];
    }
}
