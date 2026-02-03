<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index untuk tabel presensis
        Schema::table('presensis', function (Blueprint $table) {
            // Single column indexes
            $table->index('pegawai_id');
            $table->index('tanggal');
            $table->index('jenis_presensi');
            $table->index('status');
            
            // Composite indexes untuk query yang sering
            $table->index(['pegawai_id', 'tanggal']);
            $table->index(['pegawai_id', 'tanggal', 'jenis_presensi']);
            $table->index(['tanggal', 'jenis_presensi']);
        });
        
        // Index untuk tabel pegawai
        Schema::table('pegawai', function (Blueprint $table) {
            $table->index('nip');
            $table->index('jenis_pegawai');
            $table->index(['jenis_pegawai', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropIndex(['pegawai_id']);
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['jenis_presensi']);
            $table->dropIndex(['status']);
            $table->dropIndex(['pegawai_id', 'tanggal']);
            $table->dropIndex(['pegawai_id', 'tanggal', 'jenis_presensi']);
            $table->dropIndex(['tanggal', 'jenis_presensi']);
        });
        
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropIndex(['nip']);
            $table->dropIndex(['jenis_pegawai']);
            $table->dropIndex(['jenis_pegawai', 'status']);
        });
    }
};