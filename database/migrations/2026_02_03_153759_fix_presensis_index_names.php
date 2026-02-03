<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            // Hapus index dengan nama typo jika ada
            $indexes = [
                'pressensis_pegawai_id_index',
                'pressensis_tanggal_index', 
                'pressensis_jenis_presensi_index',
                'pressensis_status_index',
                'pressensis_pegawai_id_tanggal_index',
                'pressensis_pegawai_id_tanggal_jenis_presensi_index',
                'pressensis_pega wai_id_tanggal_jenis_presensi_index', // Perhatikan spasi di nama
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasIndex('presensis', $index)) {
                    $table->dropIndex($index);
                }
            }
            
            // Tambahkan kembali dengan nama yang benar
            $table->index('pegawai_id', 'presensis_pegawai_id_index');
            $table->index('tanggal', 'presensis_tanggal_index');
            $table->index('jenis_presensi', 'presensis_jenis_presensi_index');
            $table->index('status', 'presensis_status_index');
            $table->index(['pegawai_id', 'tanggal'], 'presensis_pegawai_id_tanggal_index');
            $table->index(['pegawai_id', 'tanggal', 'jenis_presensi'], 'presensis_pegawai_id_tanggal_jenis_presensi_index');
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            // Hapus index dengan nama yang benar
            $indexes = [
                'presensis_pegawai_id_index',
                'presensis_tanggal_index',
                'presensis_jenis_presensi_index',
                'presensis_status_index',
                'presensis_pegawai_id_tanggal_index',
                'presensis_pegawai_id_tanggal_jenis_presensi_index',
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasIndex('presensis', $index)) {
                    $table->dropIndex($index);
                }
            }
            
            // Kembalikan index dengan nama lama (jika perlu)
            $table->index('pegawai_id', 'pressensis_pegawai_id_index');
            $table->index('tanggal', 'pressensis_tanggal_index');
            $table->index('jenis_presensi', 'pressensis_jenis_presensi_index');
            $table->index('status', 'pressensis_status_index');
            $table->index(['pegawai_id', 'tanggal'], 'pressensis_pegawai_id_tanggal_index');
            $table->index(['pegawai_id', 'tanggal', 'jenis_presensi'], 'pressensis_pegawai_id_tanggal_jenis_presensi_index');
        });
    }
};