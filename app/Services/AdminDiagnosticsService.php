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
        $results[] = $this->check('Интеграции', 'URL уведомлений ЮMoney', fn () => $this->checkYoomoneyNotifyUrl());
        $results[] = $this->check('Каталог', 'Активные услуги', fn () => $this->checkActiveServices());

        return $results;
    }

    /** Проверка API поставщика по документации api/info (Twiboost API v2). */
    /** @return list<array{group: string, name: string, status: string, message: string, ms: int}> */
    public function runSupplier(): array
    {
        $results = [];
        $results[] = $this->check('Подключение', 'API v2 доступен', fn () => $this->checkSupplierReachable());
        $results[] = $this->check('API: balance', 'action=balance', fn () => $this->checkSupplierActionBalance());
        $results[] = $this->check('API: services', 'action=services', fn () => $this->checkSupplierActionServices());
        $results[] = $this->check('API: status', 'action=status (один заказ)', fn () => $this->checkSupplierActionStatusOne());
        $results[] = $this->check('API: status', 'action=status (несколько)', fn () => $this->checkSupplierActionStatusBatch());
        $results[] = $this->check('API: refill', 'action=refill (проба ответа)', fn () => $this->checkSupplierActionRefillProbe());
        $results[] = $this->check('API: cancel', 'action=cancel (проба ответа)', fn () => $this->checkSupplierActionCancelProbe());
        $results[] = $this->check('Клиент', 'TwiboostClient методы', fn () => $this->checkTwiboostClientCoverage());
        $results[] = $this->check('Каталог', 'Сверка external_id', fn () => $this->checkSupplierCatalogMatch());
        $results[] = $this->check('Каталог', 'Свежесть синхронизации', fn () => $this->checkCatalogFreshness());
        $results[] = $this->check('Заказы', 'Заказы с ID поставщика', fn () => $this->checkSupplierLinkedOrders());

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
    private function checkYoomoneyNotifyUrl(): array
    {
        $expected = (new PaymentService())->notifyUrl();
        $appUrl = (new SettingsService())->get('app_url');
        if ($appUrl === '') {
            return ['ok' => false, 'message' => 'app_url не задан'];
        }
        return ['ok' => true, 'message' => $expected];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierReachable(): array
    {
        $client = new TwiboostClient();
        $client->balance();
        return ['ok' => true, 'message' => 'https://twiboost.com/api/v2 отвечает'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionBalance(): array
    {
        $b = (new TwiboostClient())->balance();
        if (!isset($b['balance'])) {
            return ['ok' => false, 'message' => 'Нет поля balance в ответе'];
        }
        $cur = $b['currency'] ?? '';
        return ['ok' => true, 'message' => $b['balance'] . ($cur ? ' ' . $cur : '')];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionServices(): array
    {
        $list = (new TwiboostClient())->services();
        if (!is_array($list) || $list === []) {
            return ['ok' => false, 'message' => 'Пустой список услуг'];
        }
        $first = $list[0];
        $required = ['service', 'name', 'type', 'category', 'rate', 'min', 'max', 'refill', 'cancel'];
        $missing = array_filter($required, static fn ($k) => !array_key_exists($k, $first));
        if ($missing !== []) {
            return ['ok' => false, 'message' => 'Нет полей: ' . implode(', ', $missing)];
        }
        return ['ok' => true, 'message' => count($list) . ' услуг, структура OK'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionStatusOne(): array
    {
        $id = $this->latestSupplierOrderId();
        if ($id === null) {
            return ['ok' => true, 'message' => 'Нет заказов для проверки (пропуск)'];
        }
        $st = (new TwiboostClient())->status($id);
        if (!isset($st['status'])) {
            return ['ok' => false, 'message' => 'Нет поля status: ' . json_encode($st, JSON_UNESCAPED_UNICODE)];
        }
        return ['ok' => true, 'message' => 'Заказ #' . $id . ': ' . $st['status']];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionStatusBatch(): array
    {
        $ids = $this->latestSupplierOrderIds(3);
        if ($ids === []) {
            return ['ok' => true, 'message' => 'Нет заказов для проверки (пропуск)'];
        }
        $st = (new TwiboostClient())->statuses($ids);
        if (!is_array($st)) {
            return ['ok' => false, 'message' => 'Некорректный ответ'];
        }
        $ok = 0;
        foreach ($ids as $id) {
            if (isset($st[(string) $id]) && is_array($st[(string) $id])) {
                $ok++;
            }
        }
        return ['ok' => $ok > 0, 'message' => $ok . '/' . count($ids) . ' заказов получены'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionRefillProbe(): array
    {
        $r = (new TwiboostClient())->call(['action' => 'refill', 'order' => 0]);
        if (isset($r['refill'])) {
            return ['ok' => true, 'message' => 'Эндпоинт отвечает'];
        }
        $msg = $r['error'] ?? json_encode($r, JSON_UNESCAPED_UNICODE);
        return ['ok' => true, 'message' => 'Ответ API: ' . (is_string($msg) ? $msg : 'получен')];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierActionCancelProbe(): array
    {
        $r = (new TwiboostClient())->call(['action' => 'cancel', 'order' => 0]);
        if (isset($r['ok'])) {
            return ['ok' => true, 'message' => 'Эндпоинт отвечает'];
        }
        $msg = $r['error'] ?? json_encode($r, JSON_UNESCAPED_UNICODE);
        return ['ok' => true, 'message' => 'Ответ API: ' . (is_string($msg) ? $msg : 'получен')];
    }

    /** @return array{ok: bool, message: string} */
    private function checkTwiboostClientCoverage(): array
    {
        $methods = ['services', 'balance', 'add', 'status', 'statuses', 'refill', 'cancel'];
        $missing = [];
        foreach ($methods as $m) {
            if (!method_exists(TwiboostClient::class, $m)) {
                $missing[] = $m;
            }
        }
        if ($missing !== []) {
            return ['ok' => false, 'message' => 'Нет методов: ' . implode(', ', $missing)];
        }
        return ['ok' => true, 'message' => 'Все методы из api/info реализованы'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierCatalogMatch(): array
    {
        $supplier = (new TwiboostClient())->services();
        if (!is_array($supplier)) {
            return ['ok' => false, 'message' => 'Не удалось получить каталог'];
        }
        $ids = [];
        foreach ($supplier as $item) {
            if (isset($item['service'])) {
                $ids[(int) $item['service']] = true;
            }
        }
        $active = Database::pdo()->query('SELECT external_id FROM services WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
        if ($active === []) {
            return ['ok' => false, 'message' => 'Нет активных услуг в БД'];
        }
        $missing = 0;
        foreach ($active as $ext) {
            if (!isset($ids[(int) $ext])) {
                $missing++;
            }
        }
        return $missing === 0
            ? ['ok' => true, 'message' => count($active) . ' активных услуг найдены у поставщика']
            : ['ok' => false, 'message' => $missing . ' услуг не найдены в каталоге поставщика'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkCatalogFreshness(): array
    {
        $st = Database::pdo()->query(
            'SELECT MAX(synced_at) FROM services'
        )->fetchColumn();
        if (!$st) {
            return ['ok' => false, 'message' => 'Каталог ни разу не синхронизировался'];
        }
        $age = time() - strtotime((string) $st);
        $hours = (int) round($age / 3600);
        return $hours <= 24
            ? ['ok' => true, 'message' => 'Обновлён ' . $hours . ' ч. назад']
            : ['ok' => false, 'message' => 'Устарел: ' . $hours . ' ч. назад (запустите sync)'];
    }

    /** @return array{ok: bool, message: string} */
    private function checkSupplierLinkedOrders(): array
    {
        $total = (int) Database::pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $linked = (int) Database::pdo()->query('SELECT COUNT(*) FROM orders WHERE twiboost_order_id IS NOT NULL')->fetchColumn();
        return ['ok' => true, 'message' => $linked . ' / ' . $total . ' с ID поставщика'];
    }

    private function latestSupplierOrderId(): ?int
    {
        $ids = $this->latestSupplierOrderIds(1);
        return $ids[0] ?? null;
    }

    /** @return list<int> */
    private function latestSupplierOrderIds(int $limit): array
    {
        $st = Database::pdo()->prepare(
            'SELECT twiboost_order_id FROM orders WHERE twiboost_order_id IS NOT NULL ORDER BY id DESC LIMIT :l'
        );
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->execute();
        return array_map('intval', array_filter($st->fetchAll(PDO::FETCH_COLUMN)));
    }

    /** @return array{ok: bool, message: string} */
    private function checkActiveServices(): array
    {
        $n = (int) Database::pdo()->query('SELECT COUNT(*) FROM services WHERE is_active=1')->fetchColumn();
        return ['ok' => $n > 0, 'message' => $n . ' активных'];
    }
}
