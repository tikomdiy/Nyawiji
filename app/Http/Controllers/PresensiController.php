<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Presensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    /**
     * Tampilkan halaman scanner
     */
    public function index() 
    { 
        return view('presensi.scan'); 
    }

    /**
     * Get label untuk jenis presensi
     */
    private function getJenisPresensiLabel($jenisPresensi)
    {
        $labels = [
            'apel_pagi' => 'Apel Pagi',
            'harian_masuk' => 'Masuk Harian',
            'harian_pulang' => 'Pulang Harian'
        ];
        
        return $labels[$jenisPresensi] ?? $jenisPresensi;
    }

    /**
     * Validasi untuk presensi masuk magang (batas 07:35)
     */
    private function validateMasukMagang($jamSekarang)
    {
        $batasWaktu = Carbon::createFromTime(7, 35, 0, 'Asia/Jakarta');
        
        if ($jamSekarang->greaterThan($batasWaktu)) {
            $menitTerlambat = $jamSekarang->diffInMinutes($batasWaktu);
            return [
                'success' => false,
                'message' => 'Anda terlambat ' . $menitTerlambat . ' menit. Batas waktu masuk: 07:35 WIB.',
                'type' => 'warning',
                'menit_terlambat' => $menitTerlambat
            ];
        }
        
        return ['success' => true];
    }

    /**
     * Validasi untuk presensi pulang magang (minimal 15:55)
     */
    private function validatePulangMagang($jamSekarang)
    {
        $waktuMinimalPulang = Carbon::createFromTime(15, 55, 0, 'Asia/Jakarta');
        
        if ($jamSekarang->lessThan($waktuMinimalPulang)) {
            $selisihMenit = $waktuMinimalPulang->diffInMinutes($jamSekarang);
            return [
                'success' => false,
                'message' => 'Presensi pulang dimulai pukul 15:55 WIB. Silakan kembali ' . $selisihMenit . ' menit lagi.',
                'type' => 'warning'
            ];
        }
        
        return ['success' => true];
    }

    /**
     * MAIN METHOD: Simpan presensi dari web interface (button atas)
     */
    public function store(Request $request)
    {
        Log::info('========== PRESENSI WEB REQUEST ==========');
        Log::info('IP: ' . $request->ip());
        Log::info('User-Agent: ' . $request->header('User-Agent'));
        Log::info('Data: ', $request->all());
        
        date_default_timezone_set('Asia/Jakarta');
        
        try {
            // Validasi input
            $validated = $request->validate([
                'nip' => 'required|string',
                'jenis_presensi' => 'required|in:apel_pagi,harian_masuk,harian_pulang',
            ]);
            
            $jamSekarang = Carbon::now('Asia/Jakarta');
            
            Log::info('Waktu server: ' . $jamSekarang->format('Y-m-d H:i:s'));
            Log::info('Timezone: ' . date_default_timezone_get());
            
            // Cari pegawai berdasarkan NIP
            $pegawai = Pegawai::where('nip', $validated['nip'])->first();
            
            if (!$pegawai) {
                Log::warning('Pegawai tidak ditemukan: ' . $validated['nip']);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Data pegawai tidak ditemukan. Periksa NIP Anda.',
                    'type' => 'error'
                ], 404);
            }
            
            $waktuDisplay = $jamSekarang->format('H:i');
            $waktuFull = $jamSekarang->format('H:i:s');
            $tanggalDisplay = $jamSekarang->format('d-m-Y');
            $tanggalDatabase = $jamSekarang->format('Y-m-d');
            $hariIni = $jamSekarang->isoFormat('dddd');
            
            Log::info('========== INFO PEGAWAI ==========');
            Log::info('Pegawai ID: ' . $pegawai->id);
            Log::info('Nama: ' . $pegawai->nama);
            Log::info('Jenis Pegawai: ' . $pegawai->jenis_pegawai);
            Log::info('Jenis Presensi: ' . $validated['jenis_presensi']);
            Log::info('Tanggal: ' . $tanggalDatabase);
            Log::info('Hari: ' . $hariIni);
            
            // ========== VALIDASI BERDASARKAN JENIS PRESENSI ==========
            
            $status = 'tepat_waktu';
            $menitTerlambat = 0;
            $keterangan = '';
            
            // --- APEL PAGI (BISA SEMUA HARI) ---
            if ($validated['jenis_presensi'] == 'apel_pagi') {
                // TIDAK ADA VALIDASI HARI - BISA DILAKUKAN KAPAN SAJA
                $status = 'tepat_waktu';
                $menitTerlambat = 0;
                $keterangan = 'Apel pagi bisa dilakukan di semua hari kerja';
                
            } 
            // --- MASUK HARIAN (HANYA MAGANG) ---
            elseif ($validated['jenis_presensi'] == 'harian_masuk') {
                // Hanya untuk PESERTA MAGANG
                if ($pegawai->jenis_pegawai != 'magang') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Presensi masuk harian hanya untuk peserta magang.',
                        'type' => 'warning'
                    ], 400);
                }
                
                // Validasi batas waktu masuk magang (07:35 WIB)
                $validasiMasuk = $this->validateMasukMagang($jamSekarang);
                if (!$validasiMasuk['success']) {
                    // Simpan sebagai terlambat tapi tetap bisa presensi
                    $status = 'terlambat';
                    $menitTerlambat = $validasiMasuk['menit_terlambat'] ?? 0;
                    $keterangan = $validasiMasuk['message'];
                    
                    Log::info('Presensi masuk terlambat: ' . $menitTerlambat . ' menit');
                } else {
                    $keterangan = 'Tepat waktu (batas: 07:35 WIB)';
                }
                
            } 
            // --- PULANG HARIAN (HANYA MAGANG) ---
            elseif ($validated['jenis_presensi'] == 'harian_pulang') {
                // Hanya untuk PESERTA MAGANG
                if ($pegawai->jenis_pegawai != 'magang') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Presensi pulang harian hanya untuk peserta magang.',
                        'type' => 'warning'
                    ], 400);
                }
                
                // Validasi waktu minimal pulang (15:55 WIB)
                $validasiPulang = $this->validatePulangMagang($jamSekarang);
                if (!$validasiPulang['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $validasiPulang['message'],
                        'type' => $validasiPulang['type']
                    ], 400);
                }
                
                $keterangan = 'Waktu pulang (mulai: 15:55 WIB)';
            }
            
            // ========== CEK DUPLIKASI PRESENSI ==========
            Log::info('========== CEK DUPLIKASI ==========');
            
            $presensiHariIni = Presensi::where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal', $tanggalDatabase)
                ->where('jenis_presensi', $validated['jenis_presensi'])
                ->first();
            
            if ($presensiHariIni) {
                $jenisLabel = $this->getJenisPresensiLabel($validated['jenis_presensi']);
                
                Log::info('Duplikasi ditemukan: ' . $jenisLabel);
                Log::info('Presensi sebelumnya: ID=' . $presensiHariIni->id . ', Waktu=' . $presensiHariIni->waktu);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan presensi ' . $jenisLabel . ' hari ini.',
                    'type' => 'warning',
                    'data' => [
                        'nama' => $pegawai->nama,
                        'nip' => $pegawai->nip,
                        'jenis_presensi' => $jenisLabel,
                        'waktu_sebelumnya' => $presensiHariIni->waktu,
                        'status_sebelumnya' => $presensiHariIni->status,
                    ]
                ], 400);
            }
            
            // ========== SIMPAN KE DATABASE ==========
            $presensi = Presensi::create([
                'pegawai_id' => $pegawai->id,
                'tanggal' => $tanggalDatabase,
                'waktu' => $jamSekarang->format('H:i:s'),
                'jenis_presensi' => $validated['jenis_presensi'],
                'status' => $status,
                'menit_terlambat' => $menitTerlambat,
                'keterangan' => $keterangan,
            ]);
            
            Log::info('========== PRESENSI BERHASIL ==========');
            Log::info('ID: ' . $presensi->id);
            Log::info('Status: ' . $status);
            Log::info('Menit Terlambat: ' . $menitTerlambat);
            Log::info('=======================================');
            
            // ========== RESPONSE ==========
            $jenisLabel = $this->getJenisPresensiLabel($validated['jenis_presensi']);
            
            // Format status display
            if ($status == 'terlambat') {
                $statusDisplay = 'Terlambat (' . $menitTerlambat . ' menit)';
            } else {
                $statusDisplay = 'Tepat Waktu';
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Presensi ' . $jenisLabel . ' berhasil direkam',
                'type' => 'success',
                'mode_presensi' => $jenisLabel,
                'data' => [
                    'nama' => $pegawai->nama,
                    'nip' => $pegawai->nip,
                    'jenis_pegawai' => $pegawai->jenis_pegawai,
                    'jabatan' => $pegawai->jabatan,
                    'jenis_presensi' => $jenisLabel,
                    'waktu' => $waktuFull,
                    'waktu_display' => $waktuDisplay,
                    'tanggal' => $tanggalDisplay,
                    'hari' => $hariIni,
                    'status' => $statusDisplay,
                    'menit_terlambat' => $menitTerlambat,
                    'keterangan' => $keterangan
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid: ' . implode(', ', array_map(function($errors) {
                    return implode(', ', $errors);
                }, $e->errors())),
                'type' => 'error'
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Presensi error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
                'type' => 'error',
                'debug_error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Endpoint untuk scanner eksternal (Wireless Postronik 2D SCANPRO)
     * NOTE: Scanner TIDAK mengirim jenis_presensi, jadi kita auto-detect
     */
    public function scanPostronik(Request $request)
    {
        Log::info('========== SCANNER POSTRONIK REQUEST ==========');
        Log::info('IP: ' . $request->ip());
        Log::info('Method: ' . $request->method());
        Log::info('Content-Type: ' . $request->header('Content-Type'));
        Log::info('User-Agent: ' . $request->header('User-Agent'));
        Log::info('All Headers: ', $request->headers->all());
        Log::info('Request Data: ', $request->all());
        
        date_default_timezone_set('Asia/Jakarta');
        
        try {
            // ========== AMBIL DATA DARI SCANNER ==========
            $rawData = null;
            
            // Coba berbagai cara
            if ($request->has('data')) {
                $rawData = $request->input('data');
            } elseif ($request->has('nip')) {
                $rawData = $request->input('nip');
            } elseif ($request->getContent()) {
                $rawData = $request->getContent();
                
                // Coba parse jika content adalah JSON
                if ($request->header('Content-Type') === 'application/json') {
                    try {
                        $jsonData = json_decode($rawData, true);
                        if ($jsonData) {
                            if (isset($jsonData['data'])) {
                                $rawData = $jsonData['data'];
                            } elseif (isset($jsonData['nip'])) {
                                $rawData = $jsonData['nip'];
                            } elseif (isset($jsonData['NIP'])) {
                                $rawData = $jsonData['NIP'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::info('Bukan JSON valid: ' . $e->getMessage());
                    }
                }
            }
            
            if (!$rawData) {
                Log::warning('Tidak ada data dari scanner');
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang diterima dari scanner',
                    'type' => 'error'
                ], 400);
            }
            
            Log::info('Raw data from scanner: ' . $rawData);
            
            // ========== PARSE NIP ==========
            $nip = $this->extractNipFromScannerData($rawData);
            
            if (!$nip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format data tidak valid. Tidak dapat membaca NIP.',
                    'type' => 'error'
                ], 400);
            }
            
            Log::info('Parsed NIP: ' . $nip);
            
            // ========== AUTO-DETECT JENIS PRESENSI ==========
            // Karena scanner tidak mengirim jenis_presensi, kita detect otomatis
            $jenisPresensi = $this->autoDetectJenisPresensi($nip);
            
            Log::info('Auto-detected jenis: ' . $jenisPresensi . ' untuk NIP: ' . $nip);
            
            // ========== PANGGIL METHOD STORE ==========
            $storeRequest = new Request([
                'nip' => $nip,
                'jenis_presensi' => $jenisPresensi
            ]);
            
            return $this->store($storeRequest);
            
        } catch (\Exception $e) {
            Log::error('Scanner Postronik error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'type' => 'error',
                'debug_error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Extract NIP dari data scanner (berbagai format)
     */
    private function extractNipFromScannerData($data)
    {
        $data = trim($data);
        
        // 1. Coba sebagai JSON
        if (strpos($data, '{') === 0 || strpos($data, '[') === 0) {
            try {
                $json = json_decode($data, true);
                if ($json) {
                    if (isset($json['nip'])) {
                        return trim($json['nip']);
                    }
                    if (isset($json['data'])) {
                        return trim($json['data']);
                    }
                    if (isset($json['NIP'])) {
                        return trim($json['NIP']);
                    }
                    // Jika JSON array, ambil elemen pertama
                    if (is_array($json) && count($json) > 0 && is_string($json[0])) {
                        return trim($json[0]);
                    }
                }
            } catch (\Exception $e) {
                Log::info('Bukan JSON valid: ' . $e->getMessage());
            }
        }
        
        // 2. Coba pola: NIP:123456 atau nip=123456
        if (preg_match('/(?:NIP|nip)[:=]?\s*(\d+)/i', $data, $matches)) {
            return $matches[1];
        }
        
        // 3. Cari semua angka dalam string
        if (preg_match_all('/\d+/', $data, $matches)) {
            // Ambil rangkaian angka terpanjang (biasanya NIP)
            $longest = '';
            foreach ($matches[0] as $match) {
                if (strlen($match) > strlen($longest)) {
                    $longest = $match;
                }
            }
            if ($longest) {
                return $longest;
            }
        }
        
        // 4. Jika hanya angka, anggap sebagai NIP
        if (preg_match('/^\d+$/', $data)) {
            return $data;
        }
        
        // 5. Default: return as is (mungkin NIP dengan format khusus)
        Log::info('NIP extracted (raw): ' . $data);
        return $data;
    }

    /**
     * Auto-detect jenis presensi berdasarkan waktu dan jenis pegawai
     * RULES:
     * - Pagi (00:00 - 11:59): Apel Pagi (semua)
     * - Siang (12:00 - 15:54): Apel Pagi (default)
     * - Sore (15:55 - 23:59): Pulang (hanya magang), Apel (lainnya)
     */
    private function autoDetectJenisPresensi($nip)
    {
        $now = Carbon::now('Asia/Jakarta');
        $jam = $now->hour;
        $menit = $now->minute;
        $currentTime = $jam * 100 + $menit; // Format: HHMM
        
        Log::info('Auto-detect - Waktu: ' . $now->format('H:i') . ' (' . $currentTime . ')');
        
        // Cari pegawai
        $pegawai = Pegawai::where('nip', $nip)->first();
        
        if (!$pegawai) {
            Log::warning('Pegawai tidak ditemukan untuk auto-detect: ' . $nip);
            return 'apel_pagi'; // Default ke apel pagi
        }
        
        Log::info('Pegawai ditemukan: ' . $pegawai->nama . ' (' . $pegawai->jenis_pegawai . ')');
        
        // ========== LOGIKA AUTO-DETECT ==========
        
        // PAGI (00:00 - 11:59) -> APEL PAGI (semua orang)
        if ($currentTime >= 0 && $currentTime < 1200) {
            Log::info('Auto-detect: Pagi -> Apel Pagi');
            return 'apel_pagi';
        }
        // SIANG (12:00 - 15:54) -> APEL PAGI (default)
        elseif ($currentTime >= 1200 && $currentTime < 1555) {
            Log::info('Auto-detect: Siang -> Apel Pagi (default)');
            return 'apel_pagi';
        }
        // SORE (15:55 - 23:59) -> PULANG hanya untuk MAGANG
        elseif ($currentTime >= 1555 && $currentTime < 2400) {
            if ($pegawai->jenis_pegawai === 'magang') {
                Log::info('Auto-detect: Sore -> Pulang Harian (magang)');
                return 'harian_pulang';
            } else {
                Log::info('Auto-detect: Sore -> Apel Pagi (non-magang)');
                return 'apel_pagi';
            }
        }
        // DEFAULT -> APEL PAGI
        else {
            Log::info('Auto-detect: Default -> Apel Pagi');
            return 'apel_pagi';
        }
    }

    /**
     * Endpoint alternatif untuk scanner eksternal
     */
    public function scanExternal(Request $request)
    {
        // Alias untuk scanPostronik
        return $this->scanPostronik($request);
    }

    /**
     * Get info hari ini (untuk tampilan di web)
     */
    public function getInfoPresensiHariIni()
    {
        $now = Carbon::now('Asia/Jakarta');
        $hariIni = $now->isoFormat('dddd');
        $dayOfWeek = $now->dayOfWeekIso;
        
        $info = [
            'hari' => $hariIni,
            'tanggal' => $now->format('d-m-Y'),
            'waktu' => $now->format('H:i:s'),
            'batas_masuk_magang' => '07:35 WIB',
            'mulai_pulang_magang' => '15:55 WIB',
            'catatan' => 'Apel pagi bisa dilakukan di hari apa saja',
            'jam_sekarang' => $now->format('H:i'),
            'is_weekend' => ($dayOfWeek == 6 || $dayOfWeek == 7) ? true : false
        ];
        
        return response()->json($info);
    }

    /**
     * Test endpoint untuk debugging
     */
    public function testStore(Request $request)
    {
        try {
            Log::info('Test endpoint called', $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Test endpoint berhasil',
                'type' => 'success',
                'data' => [
                    'nip' => $request->nip ?? 'TEST123',
                    'jenis_presensi' => $request->jenis_presensi ?? 'apel_pagi',
                    'time' => now('Asia/Jakarta')->format('H:i:s'),
                    'server' => gethostname(),
                    'csrf_token' => csrf_token(),
                    'endpoints' => [
                        'web_presensi' => 'POST /presensi',
                        'scanner_postronik' => 'POST /postronik-scan',
                        'scanner_external' => 'POST /external-scan'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Test error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Test error: ' . $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }
    /**
     * Halaman rekap presensi (admin only)
     */
    public function halamanRekap()
{
    if (Auth::user()->role !== 'admin') {
        abort(403);
    }

    $hariIni = Carbon::now('Asia/Jakarta')->toDateString();
    $pegawai = Pegawai::orderBy('nama')->get();
    $rekapHarian = [];
    
    foreach ($pegawai as $p) {
        $presensi = Presensi::where('pegawai_id', $p->id)
            ->whereDate('tanggal', $hariIni)
            ->get();
        
        $apel = '-';
        $masuk = '-';
        $pulang = '-';
        $status = 'Tanpa Keterangan';
        
        if ($presensi->isNotEmpty()) {
            // Catat semua presensi
            foreach ($presensi as $log) {
                $jam = Carbon::parse($log->waktu)->setTimezone('Asia/Jakarta')->format('H:i');
                
                switch ($log->jenis_presensi) {
                    case 'apel_pagi':
                        $apel = $jam;
                        break;
                    case 'harian_masuk':
                        $masuk = $jam;
                        break;
                    case 'harian_pulang':
                        $pulang = $jam;
                        break;
                }
            }
            
            // Tentukan status berdasarkan aturan
            
            // ATURAN 1: Pegawai cukup APEL = Hadir
            if ($p->jenis_pegawai != 'magang' && $apel != '-') {
                $status = 'Hadir';
            }
            
            // ATURAN 2: Peserta magang harus MASUK = Hadir
            if ($p->jenis_pegawai == 'magang' && $masuk != '-') {
                // Cek apakah terlambat
                $presensiMasuk = $presensi->where('jenis_presensi', 'harian_masuk')->first();
                if ($presensiMasuk && $presensiMasuk->status == 'terlambat') {
                    $menitTerlambat = $presensiMasuk->menit_terlambat ?? 0;
                    $status = 'Terlambat (' . $menitTerlambat . ' menit)';
                } else {
                    $status = 'Hadir';
                }
            }
            
            // ATURAN 3: Pegawai yang masuk (selain apel) juga Hadir
            if ($p->jenis_pegawai != 'magang' && $masuk != '-') {
                $status = 'Hadir';
            }
            
            // CATATAN: Peserta magang yang hanya apel (tanpa masuk) tetap "Tanpa Keterangan"
        }
        
        $rekapHarian[] = [
            'pegawai' => $p,
            'jenis_pegawai' => $p->jenis_pegawai,
            'apel' => $apel,
            'masuk' => $masuk,
            'pulang' => $pulang,
            'status' => $status,
        ];
    }
    
    return view('presensi.rekap', compact('rekapHarian'));
}
    /**
     * Export data presensi ke Excel/CSV
     */
    public function exportExcel(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $tglMulai = $request->query('tgl_mulai');
        $tglSelesai = $request->query('tgl_selesai');
        
        if (!$tglMulai || !$tglSelesai) {
            return redirect()->back()->with('error', 'Tanggal harus diisi!');
        }

        $jenisPegawai = $request->query('jenis_pegawai');

        $qPegawai = Pegawai::orderBy('nama');
        if ($jenisPegawai) {
            $qPegawai->where('jenis_pegawai', $jenisPegawai);
        }
        $pegawais = $qPegawai->get();
        
        $presensis = Presensi::whereBetween('tanggal', [$tglMulai, $tglSelesai])->get();
        
        $filename = 'Rekap-Presensi-' . $tglMulai . '-sd-' . $tglSelesai . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($tglMulai, $tglSelesai, $pegawais, $presensis) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, ['No', 'Hari', 'Tanggal', 'NIP', 'Nama Pegawai', 'Jabatan', 'Status Peg.', 'Jam Apel', 'Jam Masuk', 'Jam Pulang', 'Status', 'Keterangan']);
            
            $no = 1;
            $period = CarbonPeriod::create($tglMulai, $tglSelesai);
            
            foreach ($period as $date) {
                $currentDate = $date->format('Y-m-d');
                $namaHari = $date->locale('id')->isoFormat('dddd');
                
                foreach ($pegawais as $pegawai) {
                    $logs = $presensis->where('tanggal', $currentDate)
                                      ->where('pegawai_id', $pegawai->id);
                    
                    $apel = '-'; $masuk = '-'; $pulang = '-';
                    $statusAkhir = 'Tanpa Keterangan';
                    $keterangan = '';
                    
                    if ($logs->isNotEmpty()) {
                        $statusAkhir = 'Hadir';
                        foreach ($logs as $log) {
                            $jam = Carbon::parse($log->waktu)->format('H:i');
                            if ($log->jenis_presensi == 'apel_pagi') $apel = $jam;
                            if ($log->jenis_presensi == 'harian_masuk') {
                                $masuk = $jam;
                                if ($log->status == 'terlambat') {
                                    $statusAkhir = 'Terlambat (' . ($log->menit_terlambat ?? 0) . ' menit)';
                                    $keterangan = 'Terlambat masuk';
                                }
                            }
                            if ($log->jenis_presensi == 'harian_pulang') $pulang = $jam;
                        }
                    }
                    
                    fputcsv($file, [
                        $no++,
                        $namaHari,
                        $date->format('d-m-Y'),
                        '="' .$pegawai->nip. '"',
                        $pegawai->nama,
                        $pegawai->jabatan,
                        $pegawai->jenis_pegawai == 'magang' ? 'Magang' : 'Pegawai',
                        $apel,
                        $masuk,
                        $pulang,
                        $statusAkhir,
                        $keterangan
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export HTML/Excel
     */
    public function exportExcelHtml(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $tglMulai = $request->query('tgl_mulai');
        $tglSelesai = $request->query('tgl_selesai');
        
        if (!$tglMulai || !$tglSelesai) {
            return redirect()->back()->with('error', 'Tanggal harus diisi!');
        }

        $jenisPegawai = $request->query('jenis_pegawai');

        $qPegawai = Pegawai::orderBy('nama');
        if ($jenisPegawai) {
            $qPegawai->where('jenis_pegawai', $jenisPegawai);
        }
        $pegawais = $qPegawai->get();
        
        $presensis = Presensi::whereBetween('tanggal', [$tglMulai, $tglSelesai])->get();
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rekap Presensi ' . $tglMulai . ' s/d ' . $tglSelesai . '</title>
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                th { background-color: #2D3748; color: white; padding: 10px; text-align: center; }
                td { border: 1px solid #ddd; padding: 8px; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .terlambat { color: red; font-weight: bold; }
            </style>
        </head>
        <body>
            <h2>Rekap Presensi ' . $tglMulai . ' s/d ' . $tglSelesai . '</h2>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Hari</th>
                        <th>Tanggal</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Status Peg.</th>
                        <th>Jam Apel</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $no = 1;
        $period = CarbonPeriod::create($tglMulai, $tglSelesai);
        
        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');
            $namaHari = $date->locale('id')->isoFormat('dddd');
            
            foreach ($pegawais as $pegawai) {
                $logs = $presensis->where('tanggal', $currentDate)
                                  ->where('pegawai_id', $pegawai->id);
                
                $apel = '-'; $masuk = '-'; $pulang = '-';
                $statusAkhir = 'Tanpa Keterangan';
                $keterangan = '';
                
                if ($logs->isNotEmpty()) {
                    $statusAkhir = 'Hadir';
                    foreach ($logs as $log) {
                        $jam = Carbon::parse($log->waktu)->format('H:i');
                        if ($log->jenis_presensi == 'apel_pagi') $apel = $jam;
                        if ($log->jenis_presensi == 'harian_masuk') {
                            $masuk = $jam;
                            if ($log->status == 'terlambat') {
                                $statusAkhir = 'Terlambat (' . ($log->menit_terlambat ?? 0) . ' menit)';
                                $keterangan = 'Terlambat masuk';
                            }
                        }
                        if ($log->jenis_presensi == 'harian_pulang') $pulang = $jam;
                    }
                }
                
                $statusClass = strpos($statusAkhir, 'Terlambat') !== false ? 'terlambat' : '';
                
                $html .= '<tr>
                    <td>' . $no++ . '</td>
                    <td>' . $namaHari . '</td>
                    <td>' . $date->format('d-m-Y') . '</td>
                    <td>' . htmlspecialchars('="' .$pegawai->nip. '"') . '</td>
                    <td>' . htmlspecialchars($pegawai->nama) . '</td>
                    <td>' . htmlspecialchars($pegawai->jabatan) . '</td>
                    <td>' . ($pegawai->jenis_pegawai == 'magang' ? 'Magang' : 'Pegawai') . '</td>
                    <td>' . $apel . '</td>
                    <td>' . $masuk . '</td>
                    <td>' . $pulang . '</td>
                    <td class="' . $statusClass . '">' . $statusAkhir . '</td>
                    <td>' . $keterangan . '</td>
                </tr>';
            }
        }
        
        $html .= '</tbody></table>
        </body></html>';
        
        $filename = 'Rekap-Presensi-' . $tglMulai . '-sd-' . $tglSelesai . '.xls';
        
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Route alternatif untuk export
     */
    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        
        if ($format === 'html') {
            return $this->exportExcelHtml($request);
        }
        
        return $this->exportExcel($request);
    }

    /**
     * Generate QR Code untuk pegawai
     */
    public function generateQRCode($nip)
    {
        $pegawai = Pegawai::where('nip', $nip)->first();
        
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
                'type' => 'error'
            ], 404);
        }
        
        $qrData = [
            'nip' => '="' .$pegawai->nip. '"',
            'nama' => $pegawai->nama,
            'jenis_pegawai' => $pegawai->jenis_pegawai,
            'timestamp' => now()->timestamp
        ];
        
        $qrContent = json_encode($qrData);
        
        return response()->json([
            'success' => true,
            'type' => 'success',
            'data' => [
                'qr_content' => $qrContent,
                'pegawai' => $pegawai
            ]
        ]);
    }

    /**
     * Debug endpoint untuk development
     */
    public function debugPresensi(Request $request)
    {
        if (!env('APP_DEBUG')) {
            return response()->json(['error' => 'Hanya untuk development'], 403);
        }
        
        $nip = $request->nip;
        $pegawai = Pegawai::where('nip', $nip)->first();
        
        if (!$pegawai) {
            return response()->json(['error' => 'Pegawai tidak ditemukan'], 404);
        }
        
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        $presensis = Presensi::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->get();
        
        return response()->json([
            'pegawai' => [
                'id' => $pegawai->id,
                'nip' => $pegawai->nip,
                'nama' => $pegawai->nama,
                'jenis_pegawai' => $pegawai->jenis_pegawai
            ],
            'today' => $today,
            'total_presensi_hari_ini' => $presensis->count(),
            'presensi_hari_ini' => $presensis,
            'timezone_info' => [
                'php' => date_default_timezone_get(),
                'carbon' => Carbon::now()->timezoneName,
                'mysql' => DB::select('SELECT @@time_zone as tz')[0]->tz ?? 'unknown'
            ]
        ]);
    }

    /**
     * Reset data test untuk development
     */
    public function resetTestData($nip)
    {
        if (!env('APP_DEBUG')) {
            return response()->json(['error' => 'Hanya untuk development'], 403);
        }
        
        $pegawai = Pegawai::where('nip', $nip)->first();
        
        if (!$pegawai) {
            return response()->json(['error' => 'Pegawai tidak ditemukan'], 404);
        }
        
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $deleted = Presensi::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->delete();
        
        Log::info('Reset data untuk NIP: ' . $nip . ', dihapus: ' . $deleted . ' records');
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus ' . $deleted . ' presensi hari ini',
            'pegawai' => [
                'id' => $pegawai->id,
                'nip' => $pegawai->nip,
                'nama' => $pegawai->nama
            ],
            'tanggal' => $today
        ]);
    }

    /**
     * Perbaiki data presensi lama (jika ada masalah timezone)
     */
    public function perbaikiDataPresensi()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }
        
        $presensis = Presensi::all();
        $diperbaiki = 0;
        
        foreach ($presensis as $presensi) {
            if ($presensi->waktu) {
                $jamDiDatabase = Carbon::parse($presensi->waktu);
                
                // Perbaiki jika jam lebih dari 12 (mungkin masalah timezone)
                if ($jamDiDatabase->hour >= 12) {
                    $jamYangBenar = $jamDiDatabase->copy()->subHours(7);
                    $presensi->waktu = $jamYangBenar->format('H:i:s');
                    $presensi->save();
                    $diperbaiki++;
                }
            }
        }
        
        return redirect()->back()->with('success', 
            'Berhasil memperbaiki ' . $diperbaiki . ' data presensi.');
    }

    /**
 * Simpan keterangan manual/izin
 */
public function simpanKeteranganManual(Request $request)
{
    if (Auth::user()->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }
    
    try {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer|exists:pegawai,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:500',
            'jenis' => 'required|in:izin_manual,catatan_khusus'
        ]);
        
        // Cari atau buat record presensi untuk tanggal ini
        $presensi = Presensi::firstOrCreate([
            'pegawai_id' => $validated['pegawai_id'],
            'tanggal' => $validated['tanggal'],
            'jenis_presensi' => 'keterangan_manual' // Buat jenis khusus
        ], [
            'waktu' => now()->format('H:i:s'),
            'status' => 'izin',
            'keterangan' => $validated['keterangan'],
            'menit_terlambat' => 0
        ]);
        
        // Update jika sudah ada
        if (!$presensi->wasRecentlyCreated) {
            $presensi->update([
                'keterangan' => $validated['keterangan'],
                'updated_at' => now()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Keterangan berhasil disimpan',
            'data' => $presensi
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
}