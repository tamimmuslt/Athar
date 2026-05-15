<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    // التأكد أن المستخدم مسجل دخول وأنه ينتمي لجدول users (الأدمن)
    if (auth()->check() && auth()->user() instanceof \App\Models\User) {
        return $next($request);
    }

    return response()->json(['message' => 'غير مصرح لك بالدخول، هذه المنطقة للأدمن فقط'], 403);
}
}
