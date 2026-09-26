<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is suspended.'], 403);
            }

            return redirect()->route('banned');
        }

        return $next($request);
    }
}
