<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function siswaDetail(Request $request, $id)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token required'
            ], 401);
        }

        try {
            // USER SERVICE
            $user = Http::withHeaders([
                'Authorization' => $token
            ])->get(env('USER_SERVICE_URL') . "/api/siswa/$id");

            // TABUNGAN SERVICE
            $tabungan = Http::withHeaders([
                'Authorization' => $token
            ])->get(env('TABUNGAN_SERVICE_URL') . "/api/tabungan/siswa/$id");

            if (!$user->successful() || !$tabungan->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service error'
                ], 502);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'siswa' => $user->json(),
                    'tabungan' => $tabungan->json()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
