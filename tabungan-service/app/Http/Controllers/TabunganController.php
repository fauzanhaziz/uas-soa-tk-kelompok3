<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tabungan;

class TabunganController extends Controller
{
    // 1. Melihat semua data tabungan
    public function index()
    {
        $data = Tabungan::all();
        return response()->json($data, 200);
    }

    // 2. Menambah Transaksi Baru (Setor/Tarik)
    public function store(Request $request)
    {
        // Validasi Input
        $request->validate([
            'id_siswa' => 'required|integer',
            'nominal' => 'required|numeric|min:1000',
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'tanggal' => 'required|date',
        ]);

        // Logika Khusus: Jika Tarik Tunai, cek saldo dulu!
        if ($request->jenis_transaksi == 'keluar') {
            $totalMasuk = Tabungan::where('id_siswa', $request->id_siswa)
                            ->where('jenis_transaksi', 'masuk')
                            ->sum('nominal');
                            
            $totalKeluar = Tabungan::where('id_siswa', $request->id_siswa)
                             ->where('jenis_transaksi', 'keluar')
                             ->sum('nominal');
                             
            $saldoSaatIni = $totalMasuk - $totalKeluar;

            if ($saldoSaatIni < $request->nominal) {
                return response()->json(['message' => 'Saldo tidak cukup!'], 400);
            }
        }

        // Simpan ke Database
        $tabungan = Tabungan::create($request->all());

        return response()->json([
            'message' => 'Transaksi berhasil disimpan',
            'data' => $tabungan
        ], 201);
    }
    
    // 3. Cek Saldo Siswa Tertentu
    public function cekSaldo($id_siswa)
    {
        $masuk = Tabungan::where('id_siswa', $id_siswa)
                   ->where('jenis_transaksi', 'masuk')
                   ->sum('nominal');
                   
        $keluar = Tabungan::where('id_siswa', $id_siswa)
                    ->where('jenis_transaksi', 'keluar')
                    ->sum('nominal');
        
        return response()->json([
            'id_siswa' => $id_siswa,
            'saldo' => $masuk - $keluar
        ], 200);
    }
}