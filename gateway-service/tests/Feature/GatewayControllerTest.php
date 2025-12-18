<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class GatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: Generate JWT token untuk testing
     */
    protected function getJWTToken()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = JWTAuth::fromUser($user);
        
        return [$user, $token];
    }

    /**
     * Test 1: Gateway returns correlation id in response
     */
    public function test_gateway_returns_correlation_id_in_response()
    {
        [$user, $token] = $this->getJWTToken();

        // Mock responses dari service lain
        Http::fake([
            '127.0.0.1:8001/api/me' => Http::response([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ], 200),
            '127.0.0.1:8002/api/saldo/*' => Http::response([
                'id_siswa' => $user->id,
                'saldo' => 500000
            ], 200),
            '127.0.0.1:8002/api/tabungan' => Http::response([
                [
                    'id' => 1,
                    'id_siswa' => $user->id,
                    'nominal' => 100000,
                    'jenis_transaksi' => 'masuk',
                    'tanggal' => '2024-12-01'
                ]
            ], 200),
        ]);

        // Test dengan correlation ID custom
        $response = $this->withHeaders([
            'X-Correlation-ID' => 'test-correlation-id-12345',
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/gateway/my-tabungan');

        // Assert response memiliki correlation ID yang sama
        $response->assertHeader('X-Correlation-ID', 'test-correlation-id-12345');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Gateway handles user service failure
     */
    public function test_gateway_handles_user_service_failure()
    {
        [$user, $token] = $this->getJWTToken();

        // Mock User Service gagal
        Http::fake([
            '127.0.0.1:8001/*' => Http::response([], 503),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->get('/api/gateway/my-tabungan');

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'message' => 'User Service Unavailable'
        ]);
    }

    /**
     * Test 3: Gateway handles tabungan service failure
     */
    public function test_gateway_handles_tabungan_service_failure()
    {
        [$user, $token] = $this->getJWTToken();

        // Mock User Service sukses, tapi Tabungan Service gagal
        Http::fake([
            '127.0.0.1:8001/api/me' => Http::response([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ], 200),
            '127.0.0.1:8002/*' => Http::response([], 503),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/gateway/my-tabungan');

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'message' => 'Tabungan Service Unavailable'
        ]);
    }

    /**
     * Test 4: Gateway can create tabungan transaction
     */
    public function test_gateway_can_create_tabungan_transaction()
    {
        [$user, $token] = $this->getJWTToken();

        Http::fake([
            '127.0.0.1:8001/api/me' => Http::response([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ], 200),
            '127.0.0.1:8002/api/tabungan' => Http::response([
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'id' => 1,
                    'id_siswa' => $user->id,
                    'nominal' => 50000,
                    'jenis_transaksi' => 'masuk',
                    'tanggal' => '2024-12-17'
                ]
            ], 201),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ])->postJson('/api/gateway/tabungan', [
            'nominal' => 50000,
            'jenis_transaksi' => 'masuk',
            'tanggal' => '2024-12-17',
            'keterangan' => 'Setor awal'
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Transaksi berhasil'
        ]);
    }

    /**
     * Test 5: Gateway requires authentication
     */
    public function test_gateway_requires_authentication()
    {
        $response = $this->get('/api/gateway/my-tabungan');

        $response->assertStatus(401);
    }
}