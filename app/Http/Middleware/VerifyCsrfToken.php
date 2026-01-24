<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'storage/*',          // 🔥 INI WAJIB
        'api/*',              // API (Flutter)
        'telegram/webhook',   // legacy (boleh buang kalau nak)
        'api/telegram/webhook',
    ];
}
