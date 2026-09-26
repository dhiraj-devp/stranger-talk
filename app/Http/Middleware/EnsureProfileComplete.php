<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->profileComplete()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Finish your profile before starting a chat.'], 409);
            }

            return redirect()->route('profile.setup');
        }

        return $next($request);
    }
}
