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
        // Index untuk tabel presensis (hanya tambahkan jika belum ada)
        Schema::table('presensis', function (Blueprint $table) {
            // Gunakan nama index yang spesifik untuk menghindari konflik
            if (!Schema::hasIndex('presensis', 'presensis_pegawai_id_index')) {
                $table->index('pegawai_id', 'presensis_pegawai_id_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_tanggal_index')) {
                $table->index('tanggal', 'presensis_tanggal_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_jenis_presensi_index')) {
                $table->index('jenis_presensi', 'presensis_jenis_presensi_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_status_index')) {
                $table->index('status', 'presensis_status_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_pegawai_id_tanggal_index')) {
                $table->index(['pegawai_id', 'tanggal'], 'presensis_pegawai_id_tanggal_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_pegawai_id_tanggal_jenis_presensi_index')) {
                $table->index(['pegawai_id', 'tanggal', 'jenis_presensi'], 'presensis_pegawai_id_tanggal_jenis_presensi_index');
            }
            if (!Schema::hasIndex('presensis', 'presensis_tanggal_jenis_presensi_index')) {
                $table->index(['tanggal', 'jenis_presensi'], 'presensis_tanggal_jenis_presensi_index');
            }
        });
        
        // Index untuk tabel pegawai
        Schema::table('pegawai', function (Blueprint $table) {
            if (!Schema::hasIndex('pegawai', 'pegawai_nip_index')) {
                $table->index('nip', 'pegawai_nip_index');
            }
            if (!Schema::hasIndex('pegawai', 'pegawai_jenis_pegawai_index')) {
                $table->index('jenis_pegawai', 'pegawai_jenis_pegawai_index');
            }
            if (!Schema::hasIndex('pegawai', 'pegawai_jenis_pegawai_status_index')) {
                $table->index(['jenis_pegawai', 'status'], 'pegawai_jenis_pegawai_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            // Hapus hanya index yang kita buat (dengan nama yang spesifik)
            $indexes = [
                'presensis_pegawai_id_index',
                'presensis_tanggal_index',
                'presensis_jenis_presensi_index',
                'presensis_status_index',
                'presensis_pegawai_id_tanggal_index',
                'presensis_pegawai_id_tanggal_jenis_presensi_index',
                'presensis_tanggal_jenis_presensi_index',
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasIndex('presensis', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
        
        Schema::table('pegawai', function (Blueprint $table) {
            $indexes = [
                'pegawai_nip_index',
                'pegawai_jenis_pegawai_index',
                'pegawai_jenis_pegawai_status_index',
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasIndex('pegawai', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }
};