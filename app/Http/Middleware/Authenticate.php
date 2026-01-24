<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        // 🔥 BYPASS AUTH UNTUK STORAGE FILE
        if ($request->is('storage/*')) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function redirectTo($request): ?string
    {
        if ($request->is('api/*')) {
            abort(response()->json(['error' => 'Unauthorized'], 401));
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        return route('admin.login');
    }
}
