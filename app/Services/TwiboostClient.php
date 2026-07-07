<?php

declare(strict_types=1);

namespace App\Services;

final class TwiboostClient
{
    private const URL = 'https://twiboost.com/api/v2';

    public function call(array $params): array
    {
        $key = (new SettingsService())->get('twiboost_api_key');
        if ($key === '') {
            throw new \RuntimeException('API-ключ Twiboost не настроен.');
        }
        $params['key'] = $key;
        $url = self::URL . '?' . http_build_query($params);
        $ctx = stream_context_create(['http' => ['timeout' => 30, 'header' => "Accept: application/json\r\n"]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException('Ошибка запроса к Twiboost.');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Некорректный ответ Twiboost.');
        }
        return $data;
    }

    public function services(): array { return $this->call(['action' => 'services']); }
    public function balance(): array { return $this->call(['action' => 'balance']); }
    public function add(int $sid, string $link, int $qty): array
    {
        return $this->call(['action' => 'add', 'service' => $sid, 'link' => $link, 'quantity' => $qty]);
    }
    public function status(int $id): array { return $this->call(['action' => 'status', 'order' => $id]); }
    public function statuses(array $ids): array
    {
        return $this->call(['action' => 'status', 'orders' => implode(',', $ids)]);
    }
    public function refill(int $id): array { return $this->call(['action' => 'refill', 'order' => $id]); }
    public function cancel(int $id): array { return $this->call(['action' => 'cancel', 'order' => $id]); }
}
