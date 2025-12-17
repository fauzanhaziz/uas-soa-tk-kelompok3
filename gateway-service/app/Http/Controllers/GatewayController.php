<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    // URL service lain
    private $userServiceUrl = 'http://127.0.0.1:8001/api';
    private $tabunganServiceUrl = 'http://127.0.0.1:8002/api';

    /**
     * Endpoint 1: Menampilkan data tabungan user yang login beserta info user
     * GET /api/gateway/my-tabungan
     */
    public function getMyTabungan(Request $request)
    {
        try {
            // Ambil Correlation ID dan Authorization token
            $correlationId = $request->attributes->get('correlation_id');
            $authToken = $request->bearerToken();
            
            Log::info('Gateway: Fetching user tabungan data', [
                'endpoint' => 'getMyTabungan'
            ]);

            // 1. Call ke User Service untuk mendapatkan data user yang login
            $userResponse = $this->callUserService($correlationId, $authToken, '/me');
            
            if (!$userResponse['success']) {
                return $this->errorResponse(
                    'User Service Unavailable', 
                    503, 
                    $userResponse['error']
                );
            }

            $user = $userResponse['data'];
            Log::info('Gateway: User data retrieved', ['user_id' => $user['id']]);

            // 2. Call ke Tabungan Service untuk mendapatkan saldo siswa
            $saldoResponse = $this->callTabunganService(
                $correlationId, 
                $authToken, 
                "/saldo/{$user['id']}"
            );

            if (!$saldoResponse['success']) {
                return $this->errorResponse(
                    'Tabungan Service Unavailable', 
                    503, 
                    $saldoResponse['error']
                );
            }

            $saldo = $saldoResponse['data'];
            Log::info('Gateway: Saldo data retrieved', ['saldo' => $saldo['saldo']]);

            // 3. Call ke Tabungan Service untuk mendapatkan history transaksi
            $tabunganResponse = $this->callTabunganService(
                $correlationId, 
                $authToken, 
                "/tabungan"
            );

            if (!$tabunganResponse['success']) {
                return $this->errorResponse(
                    'Tabungan Service Unavailable', 
                    503, 
                    $tabunganResponse['error']
                );
            }

            // Filter transaksi untuk user yang login saja
            $allTabungan = $tabunganResponse['data'];
            $userTabungan = array_filter($allTabungan, function($item) use ($user) {
                return $item['id_siswa'] == $user['id'];
            });

            // 4. Gabungkan data
            $result = [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'saldo' => $saldo['saldo'],
                'riwayat_transaksi' => array_values($userTabungan),
                'total_transaksi' => count($userTabungan)
            ];

            Log::info('Gateway: Request completed successfully');

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Gateway: Unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Internal Server Error', 
                500, 
                $e->getMessage()
            );
        }
    }

    /**
     * Endpoint 2: Menambah transaksi tabungan (setor/tarik)
     * POST /api/gateway/tabungan
     */
    public function storeTabungan(Request $request)
    {
        try {
            $correlationId = $request->attributes->get('correlation_id');
            $authToken = $request->bearerToken();
            
            Log::info('Gateway: Creating new tabungan transaction', [
                'endpoint' => 'storeTabungan',
                'jenis_transaksi' => $request->input('jenis_transaksi')
            ]);

            // 1. Validasi user terlebih dahulu
            $userResponse = $this->callUserService($correlationId, $authToken, '/me');
            
            if (!$userResponse['success']) {
                return $this->errorResponse(
                    'User Service Unavailable', 
                    503, 
                    $userResponse['error']
                );
            }

            $user = $userResponse['data'];
            Log::info('Gateway: User validated', ['user_id' => $user['id']]);

            // 2. Siapkan data transaksi
            $tabunganData = [
                'id_siswa' => $user['id'],
                'nominal' => $request->input('nominal'),
                'jenis_transaksi' => $request->input('jenis_transaksi'),
                'tanggal' => $request->input('tanggal', date('Y-m-d')),
                'keterangan' => $request->input('keterangan')
            ];

            // 3. Kirim ke Tabungan Service
            $tabunganResponse = $this->callTabunganService(
                $correlationId, 
                $authToken, 
                '/tabungan',
                'POST',
                $tabunganData
            );

            if (!$tabunganResponse['success']) {
                return $this->errorResponse(
                    'Tabungan Service Unavailable', 
                    503, 
                    $tabunganResponse['error']
                );
            }

            Log::info('Gateway: Tabungan transaction created successfully', [
                'transaction_id' => $tabunganResponse['data']['data']['id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil',
                'data' => $tabunganResponse['data']
            ], 201);

        } catch (\Exception $e) {
            Log::error('Gateway: Error creating tabungan transaction', [
                'message' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Internal Server Error', 
                500, 
                $e->getMessage()
            );
        }
    }

    /**
     * Endpoint 3: Cek saldo user tertentu (admin function)
     * GET /api/gateway/saldo/{user_id}
     */
    public function checkUserSaldo(Request $request, $userId)
    {
        try {
            $correlationId = $request->attributes->get('correlation_id');
            $authToken = $request->bearerToken();
            
            Log::info('Gateway: Checking user saldo', [
                'endpoint' => 'checkUserSaldo',
                'user_id' => $userId
            ]);

            // 1. Ambil data user dari User Service
            $userResponse = $this->callUserService($correlationId, $authToken, "/users/{$userId}");
            
            if (!$userResponse['success']) {
                return $this->errorResponse(
                    'User Service Unavailable', 
                    503, 
                    $userResponse['error']
                );
            }

            $user = $userResponse['data'];

            // 2. Ambil saldo dari Tabungan Service
            $saldoResponse = $this->callTabunganService(
                $correlationId, 
                $authToken, 
                "/saldo/{$userId}"
            );

            if (!$saldoResponse['success']) {
                return $this->errorResponse(
                    'Tabungan Service Unavailable', 
                    503, 
                    $saldoResponse['error']
                );
            }

            // 3. Gabungkan data
            $result = [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'saldo' => $saldoResponse['data']['saldo']
            ];

            Log::info('Gateway: User saldo retrieved successfully');

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Gateway: Error checking user saldo', [
                'message' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Internal Server Error', 
                500, 
                $e->getMessage()
            );
        }
    }

    /**
     * Helper: Call ke User Service
     */
    private function callUserService($correlationId, $authToken, $endpoint, $method = 'GET', $data = [])
    {
        try {
            Log::info('Gateway: Calling User Service', [
                'endpoint' => $endpoint,
                'method' => $method
            ]);

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json'
            ];

            // Tambahkan Authorization header jika token ada
            if ($authToken) {
                $headers['Authorization'] = 'Bearer ' . $authToken;
            }

            $response = Http::withHeaders($headers)->timeout(10);

            if ($method === 'GET') {
                $response = $response->get($this->userServiceUrl . $endpoint);
            } elseif ($method === 'POST') {
                $response = $response->post($this->userServiceUrl . $endpoint, $data);
            } elseif ($method === 'PUT') {
                $response = $response->put($this->userServiceUrl . $endpoint, $data);
            }

            if ($response->failed()) {
                Log::warning('Gateway: User Service call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'User Service returned error: ' . $response->status()
                ];
            }

            return [
                'success' => true,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Gateway: Exception calling User Service', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Helper: Call ke Tabungan Service
     */
    private function callTabunganService($correlationId, $authToken, $endpoint, $method = 'GET', $data = [])
    {
        try {
            Log::info('Gateway: Calling Tabungan Service', [
                'endpoint' => $endpoint,
                'method' => $method
            ]);

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json'
            ];

            // Tabungan Service mungkin tidak butuh auth, tapi kita tetap kirim untuk consistency
            if ($authToken) {
                $headers['Authorization'] = 'Bearer ' . $authToken;
            }

            $response = Http::withHeaders($headers)->timeout(10);

            if ($method === 'GET') {
                $response = $response->get($this->tabunganServiceUrl . $endpoint);
            } elseif ($method === 'POST') {
                $response = $response->post($this->tabunganServiceUrl . $endpoint, $data);
            } elseif ($method === 'PUT') {
                $response = $response->put($this->tabunganServiceUrl . $endpoint, $data);
            } elseif ($method === 'DELETE') {
                $response = $response->delete($this->tabunganServiceUrl . $endpoint);
            }

            if ($response->failed()) {
                Log::warning('Gateway: Tabungan Service call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Tabungan Service returned error: ' . $response->status()
                ];
            }

            return [
                'success' => true,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Gateway: Exception calling Tabungan Service', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Helper: Format error response konsisten
     */
    private function errorResponse($message, $statusCode, $details = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($details) {
            $response['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }
}