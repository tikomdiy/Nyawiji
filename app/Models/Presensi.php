<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use HasFactory;

    // TAMBAHKAN INI UNTUK KEJELASAN
    protected $table = 'presensis';

    // PERBAIKI: Sesuaikan dengan Controller
    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'waktu', // Controller pakai 'waktu', bukan 'jam_masuk'
        'jenis_presensi', // apel_pagi, harian_masuk, harian_pulang
        'status', // tepat_waktu, terlambat
    ];

    // RELASI KE PEGAWAI
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
}