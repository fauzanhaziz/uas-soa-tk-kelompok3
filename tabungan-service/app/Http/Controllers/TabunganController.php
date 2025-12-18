<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tabungan;
use Illuminate\Support\Facades\Log;

class TabunganController extends Controller
{
    // 1. Melihat semua data tabungan
    public function index()
    {
        Log::info('Tabungan Service: Fetching all tabungan data');
        
        $data = Tabungan::all();
        
        Log::info('Tabungan Service: Successfully retrieved tabungan data', [
            'count' => $data->count()
        ]);
        
        return response()->json($data, 200);
    }

    // 2. Menambah Transaksi Baru (Setor/Tarik)
    public function store(Request $request)
    {
        Log::info('Tabungan Service: Creating new transaction', [
            'id_siswa' => $request->id_siswa,
            'jenis_transaksi' => $request->jenis_transaksi,
            'nominal' => $request->nominal
        ]);
        
        // Validasi Input
        $request->validate([
            'id_siswa' => 'required|integer',
            'nominal' => 'required|numeric|min:1000',
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'tanggal' => 'required|date',
        ]);

        // Logika Khusus: Jika Tarik Tunai, cek saldo dulu!
        if ($request->jenis_transaksi == 'keluar') {
            Log::info('Tabungan Service: Checking balance for withdrawal', [
                'id_siswa' => $request->id_siswa,
                'requested_amount' => $request->nominal
            ]);
            
            $totalMasuk = Tabungan::where('id_siswa', $request->id_siswa)
                            ->where('jenis_transaksi', 'masuk')
                            ->sum('nominal');
                            
            $totalKeluar = Tabungan::where('id_siswa', $request->id_siswa)
                             ->where('jenis_transaksi', 'keluar')
                             ->sum('nominal');
                             
            $saldoSaatIni = $totalMasuk - $totalKeluar;

            Log::info('Tabungan Service: Balance check result', [
                'id_siswa' => $request->id_siswa,
                'current_balance' => $saldoSaatIni,
                'requested_amount' => $request->nominal
            ]);

            if ($saldoSaatIni < $request->nominal) {
                Log::warning('Tabungan Service: Insufficient balance', [
                    'id_siswa' => $request->id_siswa,
                    'current_balance' => $saldoSaatIni,
                    'requested_amount' => $request->nominal
                ]);
                
                return response()->json(['message' => 'Saldo tidak cukup!'], 400);
            }
        }

        // Simpan ke Database
        $tabungan = Tabungan::create($request->all());

        Log::info('Tabungan Service: Transaction created successfully', [
            'transaction_id' => $tabungan->id,
            'id_siswa' => $tabungan->id_siswa
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil disimpan',
            'data' => $tabungan
        ], 201);
    }
    
    // 3. Cek Saldo Siswa Tertentu
    public function cekSaldo($id_siswa)
    {
        Log::info('Tabungan Service: Checking balance for student', [
            'id_siswa' => $id_siswa
        ]);
        
        $masuk = Tabungan::where('id_siswa', $id_siswa)
                   ->where('jenis_transaksi', 'masuk')
                   ->sum('nominal');
                   
        $keluar = Tabungan::where('id_siswa', $id_siswa)
                    ->where('jenis_transaksi', 'keluar')
                    ->sum('nominal');
        
        $saldo = $masuk - $keluar;
        
        Log::info('Tabungan Service: Balance retrieved', [
            'id_siswa' => $id_siswa,
            'saldo' => $saldo
        ]);
        
        return response()->json([
            'id_siswa' => $id_siswa,
            'saldo' => $saldo
        ], 200);
    }
    
    // Get all tabungan transactions for a specific student
    public function getBySiswa($id_siswa)
    {
        Log::info('Tabungan Service: Fetching tabungan for student', [
            'id_siswa' => $id_siswa
        ]);
        
        $tabungan = Tabungan::where('id_siswa', $id_siswa)->get();
        
        $masuk = Tabungan::where('id_siswa', $id_siswa)
                   ->where('jenis_transaksi', 'masuk')
                   ->sum('nominal');
                   
        $keluar = Tabungan::where('id_siswa', $id_siswa)
                    ->where('jenis_transaksi', 'keluar')
                    ->sum('nominal');
        
        $saldo = $masuk - $keluar;
        
        Log::info('Tabungan Service: Tabungan data retrieved', [
            'id_siswa' => $id_siswa,
            'transaction_count' => $tabungan->count(),
            'saldo' => $saldo
        ]);
        
        return response()->json([
            'id_siswa' => $id_siswa,
            'transactions' => $tabungan,
            'saldo' => $saldo
        ], 200);
    }

    // 4. Update/Edit Transaksi (UPDATE)
    public function update(Request $request, $id)
    {
        $tabungan = Tabungan::find($id);

        if (!$tabungan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi input edit
        $request->validate([
            'nominal' => 'numeric|min:1000',
            'jenis_transaksi' => 'in:masuk,keluar',
            'keterangan' => 'string'
        ]);

        $tabungan->update($request->all());

        return response()->json([
            'message' => 'Data berhasil diubah',
            'data' => $tabungan
        ], 200);
    }

    // 4. Hapus Transaksi (DELETE)
    public function destroy($id)
    {
        $tabungan = Tabungan::find($id);

        if (!$tabungan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $tabungan->delete();
        
        return response()->json(['message' => 'Data berhasil dihapus'], 200);
    }
}