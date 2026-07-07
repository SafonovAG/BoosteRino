<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;

final class PaymentNotifyController
{
    public static function yoomoney(Request $request): void
    {
        $data = $request->all();
        if ($data === [] && !empty($_POST)) {
            $data = $_POST;
        }

        $ok = (new PaymentService())->handleNotification($data);
        if (!$ok) {
            Response::error('invalid_notification', 'Invalid notification.', 400);
            return;
        }
        Response::success(['ok' => true]);
    }
}
