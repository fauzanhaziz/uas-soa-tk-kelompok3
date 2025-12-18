<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Middleware ini menangani Correlation ID untuk distributed tracing.
     * Jika request sudah memiliki X-Correlation-ID, gunakan yang ada.
     * Jika tidak, generate UUID baru.
     * Correlation ID akan ditambahkan ke response header dan context logging.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil correlation ID dari header atau generate baru
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        // Set correlation ID ke request header
        $request->headers->set('X-Correlation-ID', $correlationId);
        
        // Set correlation ID ke request attributes untuk akses di controller
        $request->attributes->set('correlation_id', $correlationId);
        
        // Set correlation ID ke logging context
        Log::withContext(['correlation_id' => $correlationId]);
        
        // Log incoming request
        Log::info('Incoming request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'correlation_id' => $correlationId
        ]);
        
        // Process request
        $response = $next($request);
        
        // Set correlation ID ke response header
        $response->headers->set('X-Correlation-ID', $correlationId);
        
        // Log outgoing response
        Log::info('Outgoing response', [
            'status' => $response->getStatusCode(),
            'correlation_id' => $correlationId
        ]);
        
        return $response;
    }
}