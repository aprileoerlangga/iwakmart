<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya penjual yang dapat mengakses fitur ini.'
            ], 403);
        }

        return $next($request);
    }
}