<?php

declare(strict_types=1);

namespace App\Services;

final class TwiboostClient
{
    private const BASE_URL = 'https://twiboost.com/api/v2';

    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    public function services(): array
    {
        return $this->request(['action' => 'services']);
    }

    public function balance(): array
    {
        return $this->request(['action' => 'balance']);
    }

    public function addOrder(int $serviceId, string $link, int $quantity): array
    {
        return $this->request([
            'action' => 'add',
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
        ]);
    }

    public function orderStatus(int $orderId): array
    {
        return $this->request(['action' => 'status', 'order' => $orderId]);
    }

    public function ordersStatus(array $orderIds): array
    {
        return $this->request([
            'action' => 'status',
            'orders' => implode(',', array_map('strval', $orderIds)),
        ]);
    }

    public function refill(int $orderId): array
    {
        return $this->request(['action' => 'refill', 'order' => $orderId]);
    }

    public function cancel(int $orderId): array
    {
        return $this->request(['action' => 'cancel', 'order' => $orderId]);
    }

    private function request(array $params): array
    {
        $key = $this->settings->get('twiboost_api_key');
        if ($key === '') {
            throw new \RuntimeException('Twiboost API key не настроен.');
        }

        $params['key'] = $key;
        $url = self::BASE_URL . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "Accept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Ошибка запроса к Twiboost API.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Некорректный ответ Twiboost API.');
        }

        return $data;
    }
}
