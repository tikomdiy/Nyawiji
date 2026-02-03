<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. Tentukan Tanggal (Filter atau Hari Ini) ---
        $selectedDate = $request->has('tanggal') && !empty($request->tanggal)
            ? Carbon::parse($request->tanggal)->setTimezone('Asia/Jakarta')->startOfDay()
            : Carbon::now('Asia/Jakarta')->startOfDay();
        
        $today = $selectedDate->toDateString();
        $dayName = $selectedDate->locale('id')->isoFormat('dddd');
        
        // --- 2. Tentukan Apakah Hari Apel ---
        $isApelDay = in_array($selectedDate->dayOfWeek, [1, 3, 5]); // Senin=1, Rabu=3, Jumat=5
        
        // --- 3. STATISTIK BARU: Pisahkan Magang dan Pegawai ---
        
        // A. Hitung total berdasarkan jenis
        $totalMagang = Pegawai::where('jenis_pegawai', 'magang')->count();
        $totalPegawaiTetap = Pegawai::where('jenis_pegawai', '!=', 'magang')->count();
        $totalSemuaPegawai = Pegawai::count();
        
        // B. STATISTIK MAGANG
        // Peserta magang yang sudah presensi APEL pagi
        $apelMagangTepatWaktu = Presensi::whereDate('tanggal', $today)
            ->where('jenis_presensi', 'apel_pagi')
            ->whereHas('pegawai', function($q) {
                $q->where('jenis_pegawai', 'magang');
            })
            ->count();
        
        // Peserta magang yang presensi masuk HARIAN tepat waktu (masuk <= 07:35)
        $tepatWaktuMagang = Presensi::whereDate('tanggal', $today)
            ->where('jenis_presensi', 'harian_masuk')
            ->where('status', 'tepat_waktu')
            ->whereHas('pegawai', function($q) {
                $q->where('jenis_pegawai', 'magang');
            })
            ->count();
        
        // Peserta magang yang TERLAMBAT masuk harian (masuk > 07:35)
        $terlambatMagang = Presensi::whereDate('tanggal', $today)
            ->where('jenis_presensi', 'harian_masuk')
            ->where('status', 'terlambat')
            ->whereHas('pegawai', function($q) {
                $q->where('jenis_pegawai', 'magang');
            })
            ->count();
        
        // TOTAL Hadir Masuk Harian (TEPAT WAKTU + TERLAMBAT)
        $hadirMasukMagang = $tepatWaktuMagang + $terlambatMagang;
        
        // Peserta magang yang BELUM HADIR sama sekali (tidak apel dan tidak masuk harian)
        $belumHadirMagang = $totalMagang - max($apelMagangTepatWaktu, $hadirMasukMagang);
        if ($belumHadirMagang < 0) $belumHadirMagang = 0;
        
        // C. STATISTIK PEGAWAI TETAP - Hanya Apel Pagi
        // Pegawai yang hadir apel
        $hadirApelPegawai = Presensi::whereDate('tanggal', $today)
            ->where('jenis_presensi', 'apel_pagi')
            ->whereHas('pegawai', function($q) {
                $q->where('jenis_pegawai', '!=', 'magang');
            })
            ->count();
            
        // Pegawai yang TIDAK hadir apel
        $tidakHadirApelPegawai = $totalPegawaiTetap - $hadirApelPegawai;
        if ($tidakHadirApelPegawai < 0) $tidakHadirApelPegawai = 0;
        
        // D. STATISTIK UMUM (untuk kompatibilitas view lama)
        $hadir = Presensi::whereDate('tanggal', $today)->distinct('pegawai_id')->count('pegawai_id');
        $tepatWaktu = Presensi::whereDate('tanggal', $today)
            ->where('status', 'tepat_waktu')
            ->whereIn('jenis_presensi', ['apel_pagi', 'harian_masuk'])
            ->count();
        $terlambat = Presensi::whereDate('tanggal', $today)
            ->where('status', 'terlambat')
            ->whereIn('jenis_presensi', ['apel_pagi', 'harian_masuk'])
            ->count();
        $belumHadir = $totalSemuaPegawai - $hadir;

        // --- 4. DATA UNTUK TABEL DETAIL SETIAP FILTER ---
        
        // Inisialisasi semua variabel
        $belumHadirList = [];
        $totalMagangList = [];
        $totalPegawaiList = [];
        $apelMagangList = [];
        $hadirMasukMagangList = [];
        $tepatWaktuMagangList = [];
        $terlambatMagangList = [];
        $belumHadirMagangList = [];
        $hadirApelPegawaiList = [];
        $tidakHadirApelPegawaiList = [];
        $filteredLogs = [];
        $allPegawais = collect();
        
        // Tentukan filter aktif
        $activeFilter = $request->filter;
        
        // === FILTER UNTUK MAGANG ===
        
        // 1. TOTAL MAGANG
        if ($activeFilter == 'total_magang') {
            $totalMagangList = Pegawai::where('jenis_pegawai', 'magang')
                ->orderBy('nama')
                ->get();
        }
        
        // 2. APEL PAGI MAGANG
        elseif ($activeFilter == 'apel_magang') {
            $apelMagangList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'apel_pagi')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 3. HADIR MASUK MAGANG (GABUNGAN: Tepat Waktu + Terlambat)
        elseif ($activeFilter == 'hadir_masuk_magang') {
            // Ambil data tepat waktu
            $tepatWaktuList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->where('status', 'tepat_waktu')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
            
            // Ambil data terlambat
            $terlambatList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->where('status', 'terlambat')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
            
            // Gabungkan kedua collection
            $hadirMasukMagangList = $tepatWaktuList->concat($terlambatList);
        }
        
        // 4. TEPAT WAKTU MAGANG (hanya untuk referensi)
        elseif ($activeFilter == 'tepat_waktu_magang') {
            $tepatWaktuMagangList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->where('status', 'tepat_waktu')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 5. TERLAMBAT MAGANG (hanya untuk referensi)
        elseif ($activeFilter == 'terlambat_magang') {
            $terlambatMagangList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->where('status', 'terlambat')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 6. BELUM HADIR MAGANG
        elseif ($activeFilter == 'belum_hadir_magang') {
            // Dapatkan semua peserta magang
            $allMagang = Pegawai::where('jenis_pegawai', 'magang')->get();
            
            // Dapatkan yang sudah hadir (apel atau masuk harian)
            $sudahHadirIds = [];
            
            // Yang sudah apel
            $apelIds = Presensi::whereDate('tanggal', $today)
                ->where('jenis_presensi', 'apel_pagi')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->pluck('pegawai_id')
                ->toArray();
            $sudahHadirIds = array_merge($sudahHadirIds, $apelIds);
            
            // Yang sudah masuk harian
            $masukIds = Presensi::whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', 'magang');
                })
                ->pluck('pegawai_id')
                ->toArray();
            $sudahHadirIds = array_merge($sudahHadirIds, $masukIds);
            
            // Filter yang belum hadir
            $belumHadirMagangList = $allMagang->filter(function($pegawai) use ($sudahHadirIds) {
                return !in_array($pegawai->id, $sudahHadirIds);
            });
        }
        
        // === FILTER UNTUK PEGAWAI TETAP ===
        
        // 7. TOTAL PEGAWAI TETAP
        elseif ($activeFilter == 'total_pegawai') {
            $totalPegawaiList = Pegawai::where('jenis_pegawai', '!=', 'magang')
                ->orderBy('nama')
                ->get();
        }
        
        // 8. KEHADIRAN APEL PEGAWAI
        elseif ($activeFilter == 'kehadiran_apel_pegawai') {
            $hadirApelPegawaiList = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('jenis_presensi', 'apel_pagi')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', '!=', 'magang');
                })
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 9. TIDAK HADIR APEL PEGAWAI
        elseif ($activeFilter == 'tidak_hadir_apel_pegawai') {
            // Dapatkan semua pegawai tetap
            $allPegawaiTetap = Pegawai::where('jenis_pegawai', '!=', 'magang')->get();
            
            // Dapatkan yang sudah hadir apel
            $sudahApelIds = Presensi::whereDate('tanggal', $today)
                ->where('jenis_presensi', 'apel_pagi')
                ->whereHas('pegawai', function($q) {
                    $q->where('jenis_pegawai', '!=', 'magang');
                })
                ->pluck('pegawai_id')
                ->toArray();
            
            // Filter yang belum hadir apel
            $tidakHadirApelPegawaiList = $allPegawaiTetap->filter(function($pegawai) use ($sudahApelIds) {
                return !in_array($pegawai->id, $sudahApelIds);
            });
        }
        
        // === FILTER LAMA (untuk kompatibilitas) ===
        
        // 10. BELUM HADIR (SEMUA)
        elseif ($activeFilter == 'belum_hadir') {
            // Dapatkan semua pegawai (magang + tetap)
            $allPegawai = Pegawai::all();
            
            // Dapatkan yang sudah hadir (apel atau masuk harian)
            $sudahHadirAllIds = [];
            
            // Yang sudah apel
            $apelAllIds = Presensi::whereDate('tanggal', $today)
                ->where('jenis_presensi', 'apel_pagi')
                ->pluck('pegawai_id')
                ->toArray();
            $sudahHadirAllIds = array_merge($sudahHadirAllIds, $apelAllIds);
            
            // Yang sudah masuk harian
            $masukAllIds = Presensi::whereDate('tanggal', $today)
                ->where('jenis_presensi', 'harian_masuk')
                ->pluck('pegawai_id')
                ->toArray();
            $sudahHadirAllIds = array_merge($sudahHadirAllIds, $masukAllIds);
            
            // Filter yang belum hadir
            $belumHadirList = $allPegawai->filter(function($pegawai) use ($sudahHadirAllIds) {
                return !in_array($pegawai->id, $sudahHadirAllIds);
            });
        }
        
        // 11. TEPAT WAKTU (SEMUA) - untuk kompatibilitas
        elseif ($activeFilter == 'tepat_waktu') {
            $filteredLogs = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('status', 'tepat_waktu')
                ->whereIn('jenis_presensi', ['apel_pagi', 'harian_masuk'])
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 12. TERLAMBAT (SEMUA) - untuk kompatibilitas
        elseif ($activeFilter == 'terlambat') {
            $filteredLogs = Presensi::with('pegawai')
                ->whereDate('tanggal', $today)
                ->where('status', 'terlambat')
                ->whereIn('jenis_presensi', ['apel_pagi', 'harian_masuk'])
                ->orderBy('waktu', 'asc')
                ->get();
        }
        
        // 13. TOTAL SEMUA PEGAWAI (lama)
        elseif ($activeFilter == 'total_semua_pegawai') {
            $allPegawais = Pegawai::orderBy('nama')->get();
        }

        // --- 5. Data Riwayat untuk Tabel Utama ---
        
        // Data Apel (semua pegawai + magang)
        $riwayatApel = Presensi::with('pegawai')
            ->whereDate('tanggal', $today)
            ->where('jenis_presensi', 'apel_pagi')
            ->orderBy('waktu', 'asc')
            ->get()
            ->map(function($presensi) {
                return [
                    'pegawai' => $presensi->pegawai,
                    'waktu' => Carbon::parse($presensi->waktu)->format('H:i'),
                    'status' => $presensi->status,
                    'keterangan' => $presensi->keterangan
                ];
            })
            ->toArray();
        
        // Data Magang Harian (hanya magang)
        $rawMagang = Presensi::with('pegawai')
            ->whereDate('tanggal', $today)
            ->whereIn('jenis_presensi', ['harian_masuk', 'harian_pulang'])
            ->whereHas('pegawai', function($q) {
                $q->where('jenis_pegawai', 'magang');
            })
            ->orderBy('waktu', 'asc')
            ->get();
        
        $riwayatMagang = [];
        foreach ($rawMagang as $log) {
            $pegawaiId = $log->pegawai_id;
            
            if (!isset($riwayatMagang[$pegawaiId])) {
                $riwayatMagang[$pegawaiId] = [
                    'pegawai' => $log->pegawai,
                    'masuk' => '-',
                    'pulang' => '-',
                    'status' => 'Belum Masuk'
                ];
            }
            
            $jam = Carbon::parse($log->waktu)->setTimezone('Asia/Jakarta')->format('H:i');
            
            if ($log->jenis_presensi == 'harian_masuk') {
                $riwayatMagang[$pegawaiId]['masuk'] = $jam;
                $riwayatMagang[$pegawaiId]['status'] = $log->status;
            } 
            elseif ($log->jenis_presensi == 'harian_pulang') {
                $riwayatMagang[$pegawaiId]['pulang'] = $jam;
            }
        }
        
        $riwayatMagang = array_values($riwayatMagang);

        // --- 6. Hitung Persentase ---
        // $persentaseMagang = $totalMagang > 0 
        //     ? round((max($apelMagangTepatWaktu, $hadirMasukMagang) / $totalMagang) * 100, 1)
        //     : 0;
            
        // $persentasePegawai = $totalPegawaiTetap > 0
        //     ? round(($hadirApelPegawai / $totalPegawaiTetap) * 100, 1)
        //     : 0;

        // --- 7. Kirim Data ke View ---
        return view('dashboard', [
            // Data dasar
            'selectedDate' => $selectedDate->toDateString(),
            'dayName' => $dayName,
            'isApelDay' => $isApelDay,
            'tanggalDisplay' => $selectedDate->locale('id')->isoFormat('dddd, D MMMM Y'),
            
            // Statistik LAMA (untuk kompatibilitas view lama)
            'totalPegawai' => $totalSemuaPegawai,
            'hadir' => $hadir,
            'tepatWaktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'belumHadir' => $belumHadir,
            
            // Statistik BARU - MAGANG
            'totalMagang' => $totalMagang,
            'apelMagangTepatWaktu' => $apelMagangTepatWaktu,
            'tepatWaktuMagang' => $tepatWaktuMagang,
            'terlambatMagang' => $terlambatMagang,
            'hadirMasukMagang' => $hadirMasukMagang, // TOTAL GABUNGAN
            'belumHadirMagang' => $belumHadirMagang,
            
            // Statistik BARU - PEGAWAI TETAP
            'totalPegawaiTetap' => $totalPegawaiTetap,
            'hadirApelPegawai' => $hadirApelPegawai,
            'tidakHadirApelPegawai' => $tidakHadirApelPegawai,
            
            // Persentase
            // 'persentaseMagang' => $persentaseMagang,
            // 'persentasePegawai' => $persentasePegawai,
            
            // Data untuk tabel utama
            'riwayatApel' => $riwayatApel,
            'riwayatMagang' => $riwayatMagang,
            
            // Data untuk FILTER DETAIL
            'activeFilter' => $activeFilter,
            
            // Filter Magang
            'totalMagangList' => $totalMagangList,
            'apelMagangList' => $apelMagangList,
            'hadirMasukMagangList' => $hadirMasukMagangList,
            'tepatWaktuMagangList' => $tepatWaktuMagangList,
            'terlambatMagangList' => $terlambatMagangList,
            'belumHadirMagangList' => $belumHadirMagangList,
            
            // Filter Pegawai Tetap
            'totalPegawaiList' => $totalPegawaiList,
            'hadirApelPegawaiList' => $hadirApelPegawaiList,
            'tidakHadirApelPegawaiList' => $tidakHadirApelPegawaiList,
            
            // Filter Lama
            'belumHadirList' => $belumHadirList,
            'allPegawais' => $allPegawais,
            'filteredLogs' => $filteredLogs,
        ]);
    }
    
    /**
     * Export data presensi ke Excel
     */
    public function export(Request $request)
    {
        // ... kode export jika diperlukan ...
    }
    
    /**
     * Get real-time clock data (untuk AJAX)
     */
    public function getClock()
    {
        return response()->json([
            'time' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'date' => Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y'),
            'timezone' => 'WIB (UTC+7)'
        ]);
    }
}