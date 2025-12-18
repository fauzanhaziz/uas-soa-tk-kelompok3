<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    public function siswaDetail(Request $request, $id)
    {
        // Get correlation ID from request
        $correlationId = $request->header('X-Correlation-ID') ?? $request->attributes->get('correlation_id');
        
        Log::info('Gateway: Processing siswa detail request', [
            'siswa_id' => $id,
            'correlation_id' => $correlationId
        ]);
        
        $token = $request->header('Authorization');

        if (!$token) {
            Log::warning('Gateway: Authorization token missing', [
                'correlation_id' => $correlationId
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token required'
            ], 401);
        }

        try {
            // Prepare headers with correlation ID and authorization
            $headers = [
                'Authorization' => $token,
                'X-Correlation-ID' => $correlationId
            ];
            
            Log::info('Gateway: Calling User Service', [
                'url' => env('USER_SERVICE_URL') . "/api/siswa/$id",
                'correlation_id' => $correlationId
            ]);
            
            // USER SERVICE
            $user = Http::withHeaders($headers)
                ->get(env('USER_SERVICE_URL') . "/api/siswa/$id");

            Log::info('Gateway: Calling Tabungan Service', [
                'url' => env('TABUNGAN_SERVICE_URL') . "/api/tabungan/siswa/$id",
                'correlation_id' => $correlationId
            ]);
            
            // TABUNGAN SERVICE
            $tabungan = Http::withHeaders($headers)
                ->get(env('TABUNGAN_SERVICE_URL') . "/api/tabungan/siswa/$id");

            if (!$user->successful() || !$tabungan->successful()) {
                Log::error('Gateway: Service error', [
                    'user_service_status' => $user->status(),
                    'tabungan_service_status' => $tabungan->status(),
                    'correlation_id' => $correlationId
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service error'
                ], 502);
            }

            Log::info('Gateway: Successfully aggregated data', [
                'siswa_id' => $id,
                'correlation_id' => $correlationId
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'siswa' => $user->json(),
                    'tabungan' => $tabungan->json()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Gateway: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
