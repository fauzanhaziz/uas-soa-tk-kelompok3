<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabungan extends Model
{
    use HasFactory;

    // Menentukan kolom mana saja yang boleh diisi user
    protected $fillable = [
        'id_siswa',
        'nominal',
        'jenis_transaksi',
        'tanggal',
        'keterangan'
    ];
}