<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only active admin users may reach the dashboard. */
class EnsureActiveAdmin
{
    private const ADMIN_ROLES = ['super_admin', 'content_manager', 'product_manager', 'analyst'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! in_array($user->role, self::ADMIN_ROLES)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'This account is not authorized for the admin panel.']);
        }

        return $next($request);
    }
}
