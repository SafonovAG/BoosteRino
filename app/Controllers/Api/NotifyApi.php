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
        $d = $r->all() ?: $_POST;
        if (!(new PaymentService())->notify($d)) {
            Response::fail('notify', 'Invalid.', 400);
        }
        Response::ok();
    }
}
