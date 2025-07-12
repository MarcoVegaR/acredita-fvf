<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->active) {
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.inactive')], 403);
            }
            
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.inactive')]);
        }

        return $next($request);
    }
}
