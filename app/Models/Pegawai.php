<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

    // TAMBAHKAN INI UNTUK KEJELASAN
    protected $table = 'pegawais';

    protected $fillable = [
        'nip',
        'nama',
        'jenis_pegawai', // magang atau pegawai
        'jabatan',
        'divisi',
        'foto_path',
    ];

    // RELASI KE PRESENSI
    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }
}