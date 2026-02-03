<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| 1. RUTE PUBLIK (BISA DIAKSES TANPA LOGIN)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Rute Captcha (SVG)
Route::get('/captcha-image', function () {
    $code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
    session(['captcha_code' => $code]); 
    $svg = '<?xml version="1.0" standalone="no"?>
            <svg width="120" height="40" version="1.1" xmlns="http://www.w3.org/2000/svg">
              <rect width="100%" height="100%" fill="#07213D"/>
              <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#EEBF63" font-family="monospace" font-size="20" font-weight="bold" letter-spacing="4">'.$code.'</text>
              <line x1="0" y1="0" x2="120" y2="40" stroke="#EEBF63" stroke-width="1" opacity="0.3"/>
              <line x1="120" y1="0" x2="0" y2="40" stroke="#EEBF63" stroke-width="1" opacity="0.3"/>
            </svg>';
    return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
});

// ========== RUTE UNTUK SCANNER EKSTERNAL (PUBLIC) ==========
// Rute ini harus bisa diakses tanpa login untuk scanner

// Endpoint utama untuk scanner Postronik
Route::post('/postronik-scan', [PresensiController::class, 'scanPostronik'])
    ->name('postronik.scan');

// Endpoint alternatif untuk scanner eksternal
Route::post('/external-scan', [PresensiController::class, 'scanExternal'])
    ->name('external.scan');

// Endpoint simple untuk scanner umum
Route::post('/scan', [PresensiController::class, 'scanExternal'])
    ->name('scan.simple');

// Route test untuk scanner
Route::post('/presensi/test', [PresensiController::class, 'testStore'])
    ->name('presensi.test');

// Route untuk testing koneksi
Route::get('/test-presensi', function() {
    return response()->json([
        'status' => 'ok',
        'message' => 'Server berjalan',
        'time' => now('Asia/Jakarta')->format('H:i:s'),
        'csrf_token' => csrf_token(),
        'endpoints' => [
            'postronik_scan' => url('/postronik-scan'),
            'external_scan' => url('/external-scan'),
            'simple_scan' => url('/scan'),
            'presensi_store' => url('/presensi')
        ]
    ]);
})->name('test.presensi');

// Test endpoint untuk scanner (lebih detail)
Route::any('/scanner-test', function(Request $request) {
    Log::info('Scanner Test Hit', [
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'data' => $request->all(),
        'ip' => $request->ip(),
        'url' => $request->fullUrl()
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Scanner endpoint test successful',
        'received_data' => $request->all(),
        'server_time' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        'endpoint_info' => [
            'postronik_scan' => 'POST /postronik-scan',
            'external_scan' => 'POST /external-scan',
            'simple_scan' => 'POST /scan',
            'test_endpoint' => 'POST /presensi/test'
        ],
        'instructions' => 'Scanner harus mengirim POST request ke salah satu endpoint di atas dengan field "data" berisi NIP'
    ]);
});

// Endpoint untuk test data scanner (menerima berbagai format)
Route::any('/test-scanner-data', function(Request $request) {
    Log::info('Test Scanner Data Received', [
        'method' => $request->method(),
        'content_type' => $request->header('Content-Type'),
        'raw_content' => $request->getContent(),
        'parsed_data' => $request->all(),
        'ip' => $request->ip()
    ]);
    
    $data = $request->getContent();
    
    // Coba berbagai cara parsing
    $parsedNip = null;
    
    // Coba sebagai plain text
    if (preg_match('/^\d+$/', trim($data))) {
        $parsedNip = trim($data);
    }
    
    // Coba sebagai JSON
    if (!$parsedNip && $request->header('Content-Type') === 'application/json') {
        try {
            $json = json_decode($data, true);
            if ($json) {
                if (isset($json['nip'])) {
                    $parsedNip = $json['nip'];
                } elseif (isset($json['data'])) {
                    $parsedNip = $json['data'];
                }
            }
        } catch (\Exception $e) {
            // Bukan JSON valid
        }
    }
    
    // Coba dari form data
    if (!$parsedNip && $request->has('nip')) {
        $parsedNip = $request->input('nip');
    }
    
    if (!$parsedNip && $request->has('data')) {
        $parsedNip = $request->input('data');
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Data diterima untuk testing',
        'raw_content' => $data,
        'parsed_nip' => $parsedNip,
        'content_type' => $request->header('Content-Type'),
        'parsing_methods_tried' => [
            'plain_text' => 'Mencocokkan pola angka',
            'json_parsing' => $request->header('Content-Type') === 'application/json' ? 'Ya' : 'Tidak',
            'form_data' => $request->has('nip') || $request->has('data') ? 'Ya' : 'Tidak'
        ],
        'recommendation' => 'Gunakan format: {"nip": "123456"} atau kirim plain text NIP saja'
    ]);
});

/*
|--------------------------------------------------------------------------
| 2. DASHBOARD (HARUS LOGIN)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| 3. RUTE KHUSUS MEMBER (Harus Login Dulu)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    
    // --- MANAJEMEN PEGAWAI ---
    Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::get('/pegawai/magang', [PegawaiController::class, 'indexMagang'])->name('pegawai.magang');
    Route::get('/pegawai/tambah', [PegawaiController::class, 'create'])->name('pegawai.create');
    Route::post('/pegawai/simpan', [PegawaiController::class, 'store'])->name('pegawai.store');
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy');
    
    // CETAK
    Route::get('/pegawai/{id}/cetak', [PegawaiController::class, 'cetakKartu'])->name('pegawai.cetak');
    Route::get('/pegawai/{id}/cetak-qr', [PegawaiController::class, 'cetakQr'])->name('pegawai.cetak-qr');

    // --- PRESENSI (WEB INTERFACE) ---
    Route::get('/presensi/scan', [PresensiController::class, 'index'])->name('presensi.scan');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::post('/presensi/store', [PresensiController::class, 'store'])->name('presensi.store.alt');

    // ========== RUTE UNTUK SCANNER EKSTERNAL (LOGGED IN) ==========
    // Alternatif jika perlu login
    Route::post('/presensi/scan-external', [PresensiController::class, 'scanExternal'])
        ->name('presensi.scan-external');
    
    Route::post('/presensi/scan-postronik', [PresensiController::class, 'scanPostronik'])
        ->name('presensi.scan-postronik');

    // ========== RUTE INFO & DEBUG ==========
    Route::get('/presensi/info-hari-ini', [PresensiController::class, 'getInfoPresensiHariIni'])
        ->name('presensi.info-hari-ini');
    
    Route::get('/presensi/debug', [PresensiController::class, 'debugPresensi'])
        ->name('presensi.debug');
    
    Route::get('/presensi/reset-test/{nip}', [PresensiController::class, 'resetTestData'])
        ->name('presensi.reset-test');

    // Route untuk perbaiki data presensi lama
    Route::get('/presensi/perbaiki-data', [PresensiController::class, 'perbaikiDataPresensi'])
        ->name('presensi.perbaiki-data');
    
    // Route untuk QR Code
    Route::get('/qrcode/{nip}', [PresensiController::class, 'generateQRCode'])->name('presensi.qrcode');

    // --- REKAP PRESENSI (SEMUA USER BISA LIHAT, TAPI HANYA ADMIN BISA EKSPOR) ---
    Route::get('/presensi/rekap', [PresensiController::class, 'halamanRekap'])
        ->name('presensi.rekap');

    Route::post('/presensi/simpan-keterangan', [PresensiController::class, 'simpanKeteranganManual'])->name('presensi.simpan-keterangan');
    // ========== RUTE EXPORT PRESENSI ==========
    // Versi 1: Dengan parameter format (default ke CSV)
    Route::get('/presensi/export', [PresensiController::class, 'export'])
        ->name('presensi.export');

    // Versi 2: Langsung ke HTML/Excel (alternatif)
    Route::get('/presensi/export-html', [PresensiController::class, 'exportExcelHtml'])
        ->name('presensi.export.html');

    // Versi 3: Langsung ke CSV (alternatif)
    Route::get('/presensi/export-csv', [PresensiController::class, 'exportExcel'])
        ->name('presensi.export.csv');

    // ========== RUTE TESTING & DEBUGGING ==========
    Route::get('/test-timezone', function() {
        echo "<h3>Timezone Test</h3>";
        echo "PHP time(): " . date('Y-m-d H:i:s') . "<br>";
        echo "date_default_timezone_get(): " . date_default_timezone_get() . "<br>";
        echo "config('app.timezone'): " . config('app.timezone') . "<br>";
        echo "Carbon UTC: " . \Carbon\Carbon::now('UTC')->format('Y-m-d H:i:s') . "<br>";
        echo "Carbon Jakarta: " . \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s') . "<br>";
        
        // Test database time
        try {
            $dbTime = \Illuminate\Support\Facades\DB::select('SELECT NOW() as now, @@global.time_zone as global_tz, @@session.time_zone as session_tz')[0];
            echo "MySQL NOW(): " . $dbTime->now . "<br>";
            echo "MySQL Global Timezone: " . $dbTime->global_tz . "<br>";
            echo "MySQL Session Timezone: " . $dbTime->session_tz . "<br>";
        } catch (\Exception $e) {
            echo "Database error: " . $e->getMessage() . "<br>";
        }
        
        // Test presensi data
        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $presensiCount = \App\Models\Presensi::whereDate('tanggal', $today)->count();
        echo "Presensi hari ini ($today): " . $presensiCount . " records<br>";
        
        return "<hr>Time test completed";
    })->name('test.timezone');

    // Test endpoint untuk debugging CSRF
    Route::get('/test-csrf', function() {
        return response()->json([
            'csrf_token' => csrf_token(),
            'session_id' => session()->getId(),
            'cookies' => $_COOKIE,
            'app_key' => config('app.key') ? 'Set' : 'Not Set',
            'app_debug' => config('app.debug'),
            'app_env' => config('app.env')
        ]);
    })->name('test.csrf');

    // Test presensi endpoint
    Route::get('/test-presensi-endpoint', function() {
        return response()->json([
            'endpoints' => [
                'POST /presensi' => 'Store presensi (web interface)',
                'POST /postronik-scan' => 'Scanner Postronik (public)',
                'POST /external-scan' => 'Scanner external (public)',
                'POST /scan' => 'Simple scanner (public)',
                'POST /presensi/test' => 'Test endpoint (public)',
                'GET /presensi/info-hari-ini' => 'Get today info (auth)',
                'GET /presensi/debug' => 'Debug presensi (auth)'
            ],
            'scanner_instructions' => 'Scanner harus mengirim POST request ke /postronik-scan dengan field "data" berisi NIP'
        ]);
    })->name('test.presensi-endpoint');

    // --- PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

// ========== RUTE FALLBACK UNTUK SCANNER ==========
// Catch-all route untuk scanner yang mungkin mengirim ke URL berbeda
Route::any('/scanner/{any}', function($any, Request $request) {
    Log::warning('Scanner hit unknown route: /scanner/' . $any, [
        'method' => $request->method(),
        'data' => $request->all(),
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent')
    ]);
    
    // Coba proses data untuk melihat format
    $rawData = $request->getContent();
    $contentType = $request->header('Content-Type');
    
    // Log raw data untuk debugging
    Log::info('Raw data from unknown scanner endpoint:', [
        'raw' => $rawData,
        'content_type' => $contentType,
        'length' => strlen($rawData)
    ]);
    
    return response()->json([
        'error' => 'Endpoint tidak ditemukan: /scanner/' . $any,
        'received_data' => [
            'raw' => $rawData,
            'content_type' => $contentType,
            'parsed' => $request->all()
        ],
        'suggestion' => 'Gunakan endpoint yang benar: /postronik-scan',
        'correct_endpoint' => url('/postronik-scan'),
        'method' => 'POST',
        'example_data' => '{"data": "123456"} atau form data dengan field "data"'
    ], 404);
})->where('any', '.*');

// ========== RUTE API SIMPLE (UNTUK SCANNER) ==========
// API routes yang lebih sederhana tanpa banyak middleware
Route::prefix('api')->group(function () {
    // Simple API untuk scanner
    Route::post('/presensi', [PresensiController::class, 'store'])
        ->name('api.presensi.store');
    
    Route::post('/scan', [PresensiController::class, 'scanExternal'])
        ->name('api.scan');
    
    Route::post('/scanner', [PresensiController::class, 'scanPostronik'])
        ->name('api.scanner');
    
    // Test API endpoint
    Route::get('/test', function() {
        return response()->json([
            'status' => 'API OK',
            'time' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'endpoints' => [
                'POST /api/presensi' => 'Store presensi',
                'POST /api/scan' => 'Scanner endpoint',
                'POST /api/scanner' => 'Scanner endpoint (alternatif)'
            ]
        ]);
    });
});

// ========== RUTE UTILITY UNTUK SCANNER ==========
// Endpoint untuk mendapatkan info scanner
Route::get('/scanner-info', function() {
    return response()->json([
        'scanner_configuration' => [
            'endpoints' => [
                'primary' => 'POST /postronik-scan',
                'alternative' => 'POST /external-scan',
                'simple' => 'POST /scan',
                'api' => 'POST /api/scan'
            ],
            'data_format' => [
                'json' => '{"data": "NIP_VALUE"}',
                'form_data' => 'data=NIP_VALUE',
                'plain_text' => 'NIP_VALUE (raw)'
            ],
            'content_types' => [
                'application/json',
                'application/x-www-form-urlencoded',
                'text/plain'
            ]
        ],
        'server_info' => [
            'time' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta',
            'url' => url('/')
        ]
    ]);
});

// Memanggil rute otentikasi (login, register, dll)
require __DIR__.'/auth.php';