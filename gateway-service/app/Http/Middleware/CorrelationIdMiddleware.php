<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil Correlation ID dari header atau buat baru
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        
        // Simpan di request attributes
        $request->attributes->set('correlation_id', $correlationId);
        
        // Set context untuk logging
        Log::withContext(['correlation_id' => $correlationId]);
        
        // Proses request
        $response = $next($request);
        
        // Tambahkan Correlation ID ke response header
        $response->headers->set('X-Correlation-ID', $correlationId);
        
        return $response;
    }
}