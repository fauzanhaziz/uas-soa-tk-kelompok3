<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TabunganTest extends TestCase
{
    use RefreshDatabase;
    // Tes 1: Pastikan bisa mengambil data (Status 200)
    public function test_bisa_melihat_data_tabungan()
    {
        $response = $this->getJson('/api/tabungan');
        $response->assertStatus(200);
    }

    // Tes 2: Pastikan endpoint saldo bisa diakses
    public function test_cek_halaman_tidak_error()
    {
        $response = $this->get('/api/tabungan');
        $response->assertOk();
    }
}