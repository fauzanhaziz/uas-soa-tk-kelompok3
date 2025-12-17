<?php


namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CorrelationIdMiddleware
{
public function handle(Request $request, Closure $next)
{
$correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();


$request->headers->set('X-Correlation-ID', $correlationId);


$response = $next($request);
$response->headers->set('X-Correlation-ID', $correlationId);


return $response;
}
}