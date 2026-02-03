<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Set MySQL timezone ke Asia/Jakarta
        DB::statement("SET time_zone = '+07:00'");
        
        // Update semua data presensi yang tanggalnya salah
        // (karena timezone mismatch)
        $presensis = DB::table('presensis')
            ->whereDate('tanggal', '>', now()->subDays(30))
            ->get();
        
        foreach ($presensis as $presensi) {
            // Jika waktu > 12:00, kemungkinan salah timezone
            $time = \Carbon\Carbon::parse($presensi->waktu);
            
            if ($time->hour >= 12) {
                $correctedTime = $time->copy()->subHours(7);
                
                DB::table('presensis')
                    ->where('id', $presensi->id)
                    ->update([
                        'waktu' => $correctedTime->format('H:i:s')
                    ]);
            }
        }
    }

    public function down()
    {
        // Tidak perlu rollback
    }
};