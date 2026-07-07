<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;

final class NotifyApi
{
    public static function yoomoney(Request $r): void
    {
        $data = $_POST !== [] ? $_POST : $r->all();
        if (!(new PaymentService())->notify($data)) {
            http_response_code(400);
            exit;
        }
        Response::okEmpty();
    }
}
