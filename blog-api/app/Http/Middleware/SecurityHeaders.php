<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('X-Content-Type-Options', 'nosniff')
                    ->header('X-Frame-Options', 'DENY')
                    ->header('X-XSS-Protection', '1; mode=block')
                    ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
                    
            if ($request->secure()) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }
        
        return $response;
    }
}