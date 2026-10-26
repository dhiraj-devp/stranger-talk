<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $user?->staffRole();

        if (! $user || ($role !== 'admin' && ! in_array($role, $roles, true))) {
            abort(403, 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}
