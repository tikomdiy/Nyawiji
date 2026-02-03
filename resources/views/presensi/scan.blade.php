<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner Presensi</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Palet Warna */
        :root {
            --midnight-blue: #07213D;
            --midnight-light: #0A2A4D;
            --gold-dignity: #EEBF63;
            --gold-light: #F8E8C8;
            --platinum: #E0E2E3;
        }
        
        body { 
            background: linear-gradient(135deg, var(--midnight-blue) 0%, var(--midnight-light) 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 20px;
            overflow-x: hidden;
        }
        
        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgba(238, 191, 99, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        /* Active Button */
        .active-presensi {
            background: linear-gradient(135deg, rgba(238, 191, 99, 0.9), rgba(248, 232, 200, 0.9));
            color: var(--midnight-blue) !important;
            border: 2px solid rgba(238, 191, 99, 0.7);
            box-shadow: 0 0 20px rgba(238, 191, 99, 0.4);
            font-weight: 700;
        }
        
        /* Scanner Container */
        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            height: 450px;
            border-radius: 20px;
            overflow: hidden;
            border: 3px solid rgba(238, 191, 99, 0.6);
            box-shadow: 0 0 40px rgba(238, 191, 99, 0.3);
            background: #000;
        }
        
        /* Scanner Viewport Area */
        #reader {
            width: 100% !important;
            height: 100% !important;
            position: relative;
        }
        
        /* Overlay untuk area scanner */
        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }
        
        /* Area scanning (viewport) */
        .scan-area {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            height: 320px;
            border: 3px solid rgba(238, 191, 99, 0.9);
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.1);
            box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5);
            pointer-events: none;
            z-index: 2;
        }
        
        /* Corner Markers */
        .corner {
            position: absolute;
            width: 40px;
            height: 40px;
            border-color: rgba(238, 191, 99, 0.95);
            border-width: 6px;
            z-index: 10;
        }
        
        .corner-tl {
            top: 0;
            left: 0;
            border-top-left-radius: 15px;
            border-right: none;
            border-bottom: none;
        }
        
        .corner-tr {
            top: 0;
            right: 0;
            border-top-right-radius: 15px;
            border-left: none;
            border-bottom: none;
        }
        
        .corner-bl {
            bottom: 0;
            left: 0;
            border-bottom-left-radius: 15px;
            border-right: none;
            border-top: none;
        }
        
        .corner-br {
            bottom: 0;
            right: 0;
            border-bottom-right-radius: 15px;
            border-left: none;
            border-top: none;
        }
        
        /* Mode Indicator */
        .mode-indicator {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 10px 25px;
            z-index: 10;
            border: 2px solid rgba(238, 191, 99, 0.4);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            min-width: 200px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Mode colors */
        .mode-apel {
            border-color: rgba(245, 158, 11, 0.7) !important;
            background: rgba(245, 158, 11, 0.2) !important;
        }
        
        .mode-masuk {
            border-color: rgba(59, 130, 246, 0.7) !important;
            background: rgba(59, 130, 246, 0.2) !important;
        }
        
        .mode-pulang {
            border-color: rgba(168, 85, 247, 0.7) !important;
            background: rgba(168, 85, 247, 0.2) !important;
        }
        
        /* Announcement Modal */
        .announcement-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            color: white;
            z-index: 9999;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
            min-width: 400px;
            max-width: 500px;
            text-align: center;
            animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 2px solid rgba(238, 191, 99, 0.3);
        }
        
        .announcement-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.95), rgba(21, 128, 61, 0.95)) !important;
            box-shadow: 0 40px 80px rgba(34, 197, 94, 0.4) !important;
        }
        
        .announcement-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.95), rgba(185, 28, 28, 0.95)) !important;
            box-shadow: 0 40px 80px rgba(239, 68, 68, 0.4) !important;
        }
        
        .announcement-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(217, 119, 6, 0.95)) !important;
            box-shadow: 0 40px 80px rgba(245, 158, 11, 0.4) !important;
        }
        
        .announcement-duplicate {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(217, 119, 6, 0.95)) !important;
            box-shadow: 0 40px 80px rgba(245, 158, 11, 0.4) !important;
            border-color: rgba(245, 158, 11, 0.5) !important;
        }
        
        @keyframes modalIn {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.3); }
            70% { transform: translate(-50%, -50%) scale(1.05); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        @keyframes modalOut {
            0% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(0.7); }
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(7, 33, 61, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px 20px;
            color: white;
            z-index: 9998;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(238, 191, 99, 0.3);
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Status Indicator */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-active {
            background-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }
        
        /* Mode color effects on scanner */
        .scanner-box-apel {
            border-color: rgba(245, 158, 11, 0.8) !important;
            box-shadow: 0 0 40px rgba(245, 158, 11, 0.4) !important;
        }
        
        .scanner-box-masuk {
            border-color: rgba(59, 130, 246, 0.8) !important;
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.4) !important;
        }
        
        .scanner-box-pulang {
            border-color: rgba(168, 85, 247, 0.8) !important;
            box-shadow: 0 0 40px rgba(168, 85, 247, 0.4) !important;
        }
        
        /* Scan area mode colors */
        .scan-area-apel {
            border-color: rgba(245, 158, 11, 0.95) !important;
        }
        
        .scan-area-masuk {
            border-color: rgba(59, 130, 246, 0.95) !important;
        }
        
        .scan-area-pulang {
            border-color: rgba(168, 85, 247, 0.95) !important;
        }
        
        /* Status Badge */
        .status-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 8px 15px;
            z-index: 10;
            border: 1px solid rgba(238, 191, 99, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        /* Guide Text */
        .guide-text {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 10px 25px;
            z-index: 10;
            border: 1px solid rgba(238, 191, 99, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            white-space: nowrap;
        }
        
        /* Mode Text Colors */
        .text-mode-apel { color: #F59E0B !important; }
        .text-mode-masuk { color: #3B82F6 !important; }
        .text-mode-pulang { color: #A855F7 !important; }
        
        /* Duplicate Warning */
        .duplicate-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            border-radius: 15px;
            padding: 15px;
            margin: 10px 0;
        }
        
        /* Animasi Shake untuk input */
        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        /* Perbaikan untuk Input Field Manual */
        .gold-input {
            background: rgba(255, 255, 255, 0.12) !important;
            border: 2px solid rgba(238, 191, 99, 0.4) !important;
            color: white !important;
            font-weight: 600 !important;
            font-size: 16px !important;
            transition: all 0.3s ease !important;
        }
        
        .gold-input:focus {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(238, 191, 99, 0.8) !important;
            box-shadow: 0 0 0 3px rgba(238, 191, 99, 0.2) !important;
            outline: none !important;
        }
        
        .gold-input::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
            font-weight: 500 !important;
        }
        
        /* Untuk mobile - pastikan keyboard muncul dengan benar */
        @media (max-width: 640px) {
            .announcement-modal {
                min-width: 300px;
                max-width: 90%;
                padding: 30px 20px;
            }
            
            .scanner-container {
                height: 380px;
                max-width: 95%;
            }
            
            .scan-area {
                width: 280px;
                height: 280px;
            }
            
            .gold-input {
                font-size: 16px !important;
                height: 56px !important;
            }
        }
        
        /* Pastikan input tidak terhalang */
        input[type="text"]:not(.gold-input) {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            border-radius: 12px !important;
        }
        
        /* Hilangkan outline biru di iOS */
        input, textarea, select {
            -webkit-tap-highlight-color: transparent !important;
            outline: none !important;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4">

    <!-- Audio Elements for Sounds -->
    <audio id="success-sound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg">
    </audio>
    <audio id="error-sound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3" type="audio/mpeg">
    </audio>
    <audio id="warning-sound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-alarm-digital-clock-beep-989.mp3" type="audio/mpeg">
    </audio>
    <audio id="duplicate-sound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-retro-game-emergency-alarm-1000.mp3" type="audio/mpeg">
    </audio>

    <!-- Announcement Container -->
    <div id="announcement-container"></div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Main Container -->
    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <!-- Logo/Icon -->
            <div class="mb-6 flex justify-center">
                <div class="w-24 h-24 bg-gradient-to-br from-amber-500 to-amber-600 rounded-3xl flex items-center justify-center shadow-2xl">
                    <i class="fas fa-fingerprint text-4xl text-white"></i>
                </div>
            </div>
            
            <h1 class="text-4xl font-black bg-gradient-to-r from-amber-400 to-amber-300 bg-clip-text text-transparent mb-3">
                SISTEM PRESENSI DIGITAL
            </h1>
            <p class="text-gray-300 text-lg mb-8">Ditjenpas DIY</p>

            <!-- Current Date & Time -->
            <div class="glass-card p-5 rounded-2xl mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-400 font-semibold mb-1">HARI & TANGGAL</p>
                        <p id="current-date" class="text-xl font-bold text-white">
                            Loading...
                        </p>
                        <div class="flex items-center gap-2 mt-2">
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <span id="apel-info" class="text-sm text-green-400 font-medium">
                                Apel pagi bisa dilakukan semua hari
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-400 font-semibold mb-1">JAM SEKARANG</p>
                        <p id="current-time" class="text-xl font-bold text-amber-400">
                            00:00:00
                        </p>
                        <p class="text-sm text-gray-400 mt-1">WIB</p>
                    </div>
                </div>
            </div>

            <!-- Presensi Mode Buttons -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <!-- Apel Pagi -->
                <button id="btn-apel" onclick="setJenisPresensi('apel_pagi')" 
                        class="active-presensi py-5 rounded-xl font-bold text-lg btn-hover group relative overflow-hidden">
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="mb-3">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg">
                                <i class="fas fa-sun text-2xl text-white"></i>
                            </div>
                        </div>
                        <span>APEL PAGI</span>
                        <span class="text-xs text-amber-800 mt-1 font-medium">Semua Hari</span>
                    </div>
                </button>

                <!-- Masuk Harian -->
                <button id="btn-masuk" onclick="setJenisPresensi('harian_masuk')" 
                        class="glass-card py-5 rounded-xl text-white font-bold text-lg btn-hover group relative overflow-hidden hover:bg-white/5 transition-all duration-300">
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="mb-3">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                                <i class="fas fa-door-open text-2xl text-white"></i>
                            </div>
                        </div>
                        <span>MASUK</span>
                        <span class="text-xs text-blue-300/80 mt-1 font-medium">07:35 WIB</span>
                    </div>
                </button>

                <!-- Pulang Harian -->
                <button id="btn-pulang" onclick="setJenisPresensi('harian_pulang')" 
                        class="glass-card py-5 rounded-xl text-white font-bold text-lg btn-hover group relative overflow-hidden hover:bg-white/5 transition-all duration-300">
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="mb-3">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <i class="fas fa-home text-2xl text-white"></i>
                            </div>
                        </div>
                        <span>PULANG</span>
                        <span class="text-xs text-purple-300/80 mt-1 font-medium">15:55 WIB</span>
                    </div>
                </button>
            </div>

            <!-- Active Mode Display -->
            <div class="glass-card p-4 rounded-2xl mb-8 text-center">
                <p class="text-sm text-gray-400 font-semibold mb-2">MODE PRESENSI AKTIF</p>
                <div class="flex items-center justify-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <p id="active-mode-display" class="text-xl font-bold text-amber-400">
                        APEL PAGI
                    </p>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <p id="mode-description" class="text-sm text-gray-300 mt-2">
                    Scan QR Code atau gunakan scanner eksternal
                </p>
            </div>
        </div>

        <!-- Scanner Area -->
        <div class="scanner-container mb-8 relative scanner-box-apel" id="scanner-box">
            <div id="reader" class="w-full h-full"></div>
            
            <!-- Overlay dengan area scanning -->
            <div class="scanner-overlay">
                <div class="scan-area scan-area-apel" id="scan-area">
                    <!-- Corner Markers -->
                    <div class="corner corner-tl"></div>
                    <div class="corner corner-tr"></div>
                    <div class="corner corner-bl"></div>
                    <div class="corner corner-br"></div>
                </div>
            </div>
            
            <!-- Mode Indicator -->
            <div class="mode-indicator mode-apel" id="mode-indicator">
                <i class="fas fa-sun text-mode-apel mr-2"></i>
                <span id="scanner-active-mode" class="font-bold text-mode-apel">APEL PAGI</span>
            </div>
            
            <!-- Status Badge -->
            <div class="status-badge">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span id="scanner-status" class="text-sm font-bold text-green-300">SIAP SCAN</span>
                </div>
            </div>
            
            <!-- Guide Text -->
            <div class="guide-text">
                <p class="text-sm text-gray-300">Arahkan QR Code ke dalam area</p>
            </div>
        </div>

        <!-- Scanner Eksternal Section -->
        <div class="glass-card p-6 rounded-2xl mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-wifi text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Scanner Eksternal</h3>
                        <p class="text-gray-300">Wireless Postronik 2D SCANPRO</p>
                    </div>
                </div>
                <div id="external-status" class="inline-flex items-center px-4 py-2 rounded-full bg-green-900/40 border border-green-400/30">
                    <span class="status-indicator status-active"></span>
                    <span class="text-sm font-semibold text-green-300">SIAP</span>
                </div>
            </div>
            
            <!-- Manual Input Section -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-3">Manual Input (Opsional)</label>
                <div class="flex gap-3">
                    <input type="text" 
                        id="manual-nip" 
                        placeholder="Masukkan NIP manual"
                        inputmode="numeric"  
                        pattern="[0-9]*"     
                        autocomplete="off"  
                        autocorrect="off"   
                        autocapitalize="off"
                        spellcheck="false"  
                        class="flex-1 gold-input px-5 py-4 rounded-xl text-lg placeholder-gray-500">
                    <button id="submit-manual-btn" 
                            class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold px-6 py-4 rounded-xl transition-all duration-300 btn-hover">
                        <i class="fas fa-paper-plane mr-2"></i> KIRIM
                    </button>
                </div>
            </div>
            
            <button onclick="testScannerConnection()" 
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-4 rounded-xl transition-all duration-300 btn-hover">
                <i class="fas fa-sync-alt mr-2"></i> TEST KONEKSI SCANNER
            </button>
            
            <div class="mt-6 p-4 bg-white/5 rounded-xl">
                <p class="text-sm text-gray-300">
                    <i class="fas fa-info-circle text-amber-400 mr-2"></i>
                    Scanner akan menggunakan mode: <span id="scanner-mode" class="text-amber-400 font-bold">APEL PAGI</span>
                </p>
            </div>
        </div>

        <!-- Information Section -->
        <div class="glass-card p-6 rounded-2xl">
            <h4 class="text-lg font-bold text-amber-400 mb-4">
                <i class="fas fa-info-circle mr-3"></i>INFORMASI PRESENSI
            </h4>
            
            <div class="space-y-4">
                <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl">
                    <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-sun text-amber-400"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white mb-1">Apel Pagi</p>
                        <p class="text-sm text-gray-300">Bisa dilakukan di semua hari kerja (Senin - Jumat). Tidak ada batas waktu.</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl">
                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-door-open text-blue-400"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white mb-1">Masuk Harian</p>
                        <p class="text-sm text-gray-300">Hanya untuk peserta magang. Batas waktu: <span class="text-red-400 font-bold">07:35 WIB</span> (terlambat masih bisa presensi).</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl">
                    <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-home text-purple-400"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white mb-1">Pulang Harian</p>
                        <p class="text-sm text-gray-300">Hanya untuk peserta magang. Mulai pukul: <span class="text-green-400 font-bold">15:55 WIB</span>.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <div class="flex items-center justify-between text-sm text-gray-400">
                    <div>
                        <i class="fas fa-server mr-2"></i>
                        Status: <span class="text-green-400 font-medium">Online</span>
                    </div>
                    <div>
                        <i class="fas fa-clock mr-2"></i>
                        <span id="server-status">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ========== KONFIGURASI GLOBAL ==========
        let html5QrCode;
        let currentJenisPresensi = 'apel_pagi';
        let isScanning = false;
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentCameraId = null;
        let lastScannedData = null;
        let lastScanTime = 0;
        let SCAN_COOLDOWN = 800;
        let recentlyPresensi = {}; // Cache untuk presensi terbaru
        
        // Audio Elements
        const successSound = document.getElementById('success-sound');
        const errorSound = document.getElementById('error-sound');
        const warningSound = document.getElementById('warning-sound');
        const duplicateSound = document.getElementById('duplicate-sound');

        // ========== FUNGSI NOTIFIKASI ==========
        function showToast(message, type = 'info', duration = 2000) {
            const container = document.getElementById('toast-container');
            
            const toast = document.createElement('div');
            toast.className = 'toast';
            
            let icon = '';
            let borderColor = '';
            
            switch(type) {
                case 'success': 
                    icon = '<i class="fas fa-check-circle text-green-400"></i>';
                    borderColor = 'border-l-4 border-green-500';
                    break;
                case 'error': 
                    icon = '<i class="fas fa-times-circle text-red-400"></i>';
                    borderColor = 'border-l-4 border-red-500';
                    break;
                case 'warning': 
                    icon = '<i class="fas fa-exclamation-triangle text-yellow-400"></i>';
                    borderColor = 'border-l-4 border-yellow-500';
                    break;
                case 'duplicate':
                    icon = '<i class="fas fa-exclamation-circle text-orange-400"></i>';
                    borderColor = 'border-l-4 border-orange-500';
                    break;
                case 'mode-change':
                    icon = '<i class="fas fa-exchange-alt text-amber-400"></i>';
                    borderColor = 'border-l-4 border-amber-500';
                    break;
                default: 
                    icon = '<i class="fas fa-info-circle text-blue-400"></i>';
                    borderColor = 'border-l-4 border-blue-500';
            }
            
            toast.innerHTML = `
                <div class="flex items-center ${borderColor} pl-3">
                    <div class="mr-3">
                        ${icon}
                    </div>
                    <div>
                        <span class="font-medium">${message}</span>
                    </div>
                </div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.3s reverse forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);
        }
        
        function showAnnouncement(type, message, data) {
            const container = document.getElementById('announcement-container');
            container.innerHTML = '';
            
            const announcement = document.createElement('div');
            
            let announcementClass = 'announcement-modal ';
            if (type === 'duplicate') {
                announcementClass += 'announcement-duplicate';
            } else {
                announcementClass += `announcement-${type}`;
            }
            
            announcement.className = announcementClass;
            
            let icon = '';
            let title = '';
            let titleColor = '';
            let autoCloseTime = 3000;
            
            switch(type) {
                case 'success':
                    icon = '<i class="fas fa-check-circle text-5xl mb-4 text-white"></i>';
                    title = 'BERHASIL';
                    titleColor = 'text-green-300';
                    autoCloseTime = 2500;
                    break;
                case 'error':
                    icon = '<i class="fas fa-times-circle text-5xl mb-4 text-white"></i>';
                    title = 'GAGAL';
                    titleColor = 'text-red-300';
                    autoCloseTime = 3500;
                    break;
                case 'warning':
                case 'duplicate':
                    icon = '<i class="fas fa-exclamation-triangle text-5xl mb-4 text-white"></i>';
                    title = type === 'duplicate' ? 'PRESENSI DUPLIKAT' : 'PERINGATAN';
                    titleColor = 'text-yellow-300';
                    autoCloseTime = 4000;
                    break;
                default:
                    icon = '<i class="fas fa-info-circle text-5xl mb-4 text-white"></i>';
                    title = 'INFORMASI';
                    titleColor = 'text-blue-300';
                    autoCloseTime = 3000;
            }
            
            let details = '';
            if (data && data.nama) {
                let waktuInfo = '';
                if (data.waktu_sebelumnya) {
                    waktuInfo = `
                        <div class="duplicate-warning">
                            <p class="text-sm font-bold text-yellow-400 mb-2">
                                <i class="fas fa-history mr-2"></i>PRESENSI SEBELUMNYA
                            </p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-300">Waktu</p>
                                    <p class="font-bold text-white">${data.waktu_sebelumnya}</p>
                                </div>
                                <div>
                                    <p class="text-gray-300">Status</p>
                                    <p class="font-bold ${data.status_sebelumnya && data.status_sebelumnya.includes('terlambat') ? 'text-yellow-400' : 'text-green-400'}">
                                        ${data.status_sebelumnya || 'Tepat Waktu'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                details = `
                    <div class="mt-6 p-4 bg-white/10 rounded-xl">
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <p class="text-gray-300">Nama</p>
                                <p class="font-bold text-white text-lg">${data.nama}</p>
                            </div>
                            <div>
                                <p class="text-gray-300">NIP</p>
                                <p class="font-bold text-white text-lg">${data.nip}</p>
                            </div>
                            <div>
                                <p class="text-gray-300">Mode</p>
                                <p class="font-bold text-white">${data.jenis_presensi || currentJenisPresensi}</p>
                            </div>
                            <div>
                                <p class="text-gray-300">Waktu Sekarang</p>
                                <p class="font-bold text-white">${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}</p>
                            </div>
                        </div>
                        ${waktuInfo}
                    </div>
                `;
            }
            
            announcement.innerHTML = `
                ${icon}
                <h3 class="text-2xl font-bold mb-2 ${titleColor}">${title}</h3>
                <p class="text-xl mb-6">${message}</p>
                ${details}
                <button onclick="closeAnnouncement()" 
                        class="mt-6 px-8 py-3 bg-white/20 hover:bg-white/30 rounded-full font-bold transition-colors duration-300">
                    TUTUP
                </button>
                <div id="announcement-progress" class="mt-4 h-1 bg-white/30 rounded-full overflow-hidden">
                    <div class="h-full bg-white/70 rounded-full announcement-progress-bar"></div>
                </div>
            `;
            
            container.appendChild(announcement);
            
            const progressBar = announcement.querySelector('.announcement-progress-bar');
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.transition = `width ${autoCloseTime}ms linear`;
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 10);
            }
            
            setTimeout(() => {
                closeAnnouncement();
            }, autoCloseTime);
        }
        
        function closeAnnouncement() {
            const container = document.getElementById('announcement-container');
            const announcement = container.querySelector('.announcement-modal');
            
            if (announcement) {
                announcement.style.animation = 'modalOut 0.3s forwards';
                setTimeout(() => {
                    container.innerHTML = '';
                }, 300);
            }
        }
        
        function playSound(type) {
            try {
                let audio;
                switch(type) {
                    case 'success':
                        audio = successSound;
                        break;
                    case 'error':
                        audio = errorSound;
                        break;
                    case 'warning':
                        audio = warningSound;
                        break;
                    case 'duplicate':
                        audio = duplicateSound;
                        break;
                    case 'scan':
                        const scanSound = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-select-click-1109.mp3');
                        scanSound.volume = 0.2;
                        scanSound.play().catch(() => {});
                        return;
                    case 'mode-change':
                        const modeSound = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-select-click-1109.mp3');
                        modeSound.volume = 0.3;
                        modeSound.play().catch(() => {});
                        return;
                    default:
                        return;
                }
                
                audio.currentTime = 0;
                audio.volume = 0.3;
                audio.play().catch(e => {
                    console.log('Audio play failed:', e);
                });
            } catch (e) {
                console.log('Audio error:', e);
            }
        }

        // ========== FUNGSI PRESENSI ==========
        function setJenisPresensi(jenis) {
            console.log('Mengubah mode presensi ke:', jenis);
            
            // RESET CACHE & STATE
            recentlyPresensi = {};
            lastScannedData = null;
            lastScanTime = 0;
            
            document.getElementById('btn-apel').classList.remove('active-presensi');
            document.getElementById('btn-masuk').classList.remove('active-presensi');
            document.getElementById('btn-pulang').classList.remove('active-presensi');
            
            const buttons = ['btn-apel', 'btn-masuk', 'btn-pulang'];
            buttons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                btn.className = btn.className.replace('active-presensi', '');
                if (!btn.className.includes('glass-card')) {
                    btn.className += ' glass-card py-5 rounded-xl text-white font-bold text-lg btn-hover group relative overflow-hidden hover:bg-white/5 transition-all duration-300';
                }
            });
            
            currentJenisPresensi = jenis;
            
            let displayText = '';
            let description = '';
            let scannerBoxClass = '';
            let modeClass = '';
            let textColorClass = '';
            let iconHtml = '';
            
            switch(jenis) {
                case 'apel_pagi':
                    document.getElementById('btn-apel').className = 'active-presensi py-5 rounded-xl font-bold text-lg btn-hover group relative overflow-hidden';
                    displayText = 'APEL PAGI';
                    description = 'Untuk semua pegawai, bisa dilakukan semua hari';
                    scannerBoxClass = 'scanner-box-apel';
                    modeClass = 'mode-apel';
                    textColorClass = 'text-mode-apel';
                    iconHtml = '<i class="fas fa-sun text-mode-apel mr-2"></i>';
                    break;
                    
                case 'harian_masuk':
                    document.getElementById('btn-masuk').className = 'active-presensi py-5 rounded-xl font-bold text-lg btn-hover group relative overflow-hidden';
                    displayText = 'MASUK HARIAN';
                    description = 'Hanya untuk peserta magang, batas 07:35 WIB';
                    scannerBoxClass = 'scanner-box-masuk';
                    modeClass = 'mode-masuk';
                    textColorClass = 'text-mode-masuk';
                    iconHtml = '<i class="fas fa-door-open text-mode-masuk mr-2"></i>';
                    break;
                    
                case 'harian_pulang':
                    document.getElementById('btn-pulang').className = 'active-presensi py-5 rounded-xl font-bold text-lg btn-hover group relative overflow-hidden';
                    displayText = 'PULANG HARIAN';
                    description = 'Hanya untuk peserta magang, mulai 15:55 WIB';
                    scannerBoxClass = 'scanner-box-pulang';
                    modeClass = 'mode-pulang';
                    textColorClass = 'text-mode-pulang';
                    iconHtml = '<i class="fas fa-home text-mode-pulang mr-2"></i>';
                    break;
            }
            
            const scannerBox = document.getElementById('scanner-box');
            scannerBox.className = scannerBox.className.replace(/\bscanner-box-(apel|masuk|pulang)\b/g, '');
            scannerBox.classList.add(scannerBoxClass);
            
            const scanArea = document.getElementById('scan-area');
            scanArea.className = scanArea.className.replace(/\bscan-area-(apel|masuk|pulang)\b/g, '');
            scanArea.classList.add(`scan-area-${jenis.split('_')[0]}`);
            
            const modeIndicator = document.getElementById('mode-indicator');
            modeIndicator.className = modeIndicator.className.replace(/\bmode-(apel|masuk|pulang)\b/g, '');
            modeIndicator.classList.add(modeClass);
            modeIndicator.innerHTML = `${iconHtml}<span id="scanner-active-mode" class="font-bold ${textColorClass}">${displayText}</span>`;
            
            document.getElementById('active-mode-display').textContent = displayText;
            document.getElementById('scanner-mode').textContent = displayText;
            document.getElementById('mode-description').textContent = description;
            
            // RESET SCANNER STATUS
            updateScannerStatus('SIAP SCAN', 'green');
            
            // CLEAR MANUAL INPUT
            document.getElementById('manual-nip').value = '';
            
            showToast(`Mode presensi diubah ke: ${displayText}`, 'mode-change', 1500);
            playSound('mode-change');
            
            console.log('Mode berhasil diubah ke:', displayText);
        }

        // ========== FUNGSI MANUAL INPUT ==========
        function submitManualPresensi() {
            const inputField = document.getElementById('manual-nip');
            const nip = inputField.value.trim();
            
            if (!nip) {
                showToast('Silakan masukkan NIP', 'warning');
                inputField.focus();
                
                inputField.classList.add('shake-animation');
                setTimeout(() => {
                    inputField.classList.remove('shake-animation');
                }, 500);
                
                return;
            }
            
            if (!/^\d+$/.test(nip)) {
                showToast('NIP harus berupa angka', 'warning');
                inputField.focus();
                inputField.select();
                return;
            }
            
            inputField.blur();
            
            playSound('scan');
            submitPresensi(nip, currentJenisPresensi);
            
            inputField.value = '';
            
            showToast(`Presensi untuk NIP ${nip} sedang diproses...`, 'info', 1500);
        }

        // ========== FUNGSI SCANNER ==========
        function initializeScanner() {
            try {
                html5QrCode = new Html5Qrcode("reader");
                
                const config = {
                    fps: 15,
                    qrbox: 320,
                    aspectRatio: 1.0,
                    rememberLastUsedCamera: true,
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                    showTorchButtonIfSupported: true
                };
                
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length) {
                        let cameraId = cameras[0].id;
                        
                        const backCamera = cameras.find(cam => 
                            cam.label.toLowerCase().includes('back') || 
                            cam.label.toLowerCase().includes('rear') ||
                            cam.label.includes('2')
                        );
                        
                        if (backCamera) {
                            cameraId = backCamera.id;
                        }
                        
                        currentCameraId = cameraId;
                        
                        html5QrCode.start(
                            cameraId,
                            config,
                            onScanSuccess,
                            onScanError
                        ).then(() => {
                            isScanning = true;
                            updateScannerStatus('SIAP SCAN', 'green');
                            console.log('Scanner aktif');
                            
                        }).catch(err => {
                            console.error('Gagal memulai scanner:', err);
                            updateScannerStatus('ERROR', 'red');
                            showToast('Kamera tidak dapat diakses', 'error');
                        });
                    } else {
                        console.error('Tidak ada kamera yang terdeteksi');
                        updateScannerStatus('NO CAMERA', 'red');
                        showToast('Tidak ada kamera yang terdeteksi', 'error');
                    }
                }).catch(err => {
                    console.error('Error getting cameras:', err);
                    showToast('Tidak dapat mengakses kamera', 'error');
                });
            } catch (error) {
                console.error('Error inisialisasi scanner:', error);
                showToast('Scanner gagal diinisialisasi', 'error');
            }
        }
        
        function updateScannerStatus(status, color) {
            const statusElement = document.getElementById('scanner-status');
            
            if (statusElement) {
                statusElement.textContent = status;
                statusElement.className = `text-sm font-bold text-${color}-300`;
                
                const indicator = statusElement.previousElementSibling;
                if (indicator) {
                    indicator.className = `w-3 h-3 rounded-full bg-${color}-500`;
                }
            }
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            const now = Date.now();
            const timeSinceLastScan = now - lastScanTime;
            
            if (timeSinceLastScan < SCAN_COOLDOWN || decodedText === lastScannedData) {
                return;
            }
            
            lastScannedData = decodedText;
            lastScanTime = now;
            
            console.log(`QR Code scanned: ${decodedText} | Mode: ${currentJenisPresensi}`);
            
            playSound('scan');
            updateScannerStatus('PROSES DATA...', 'yellow');
            processQRCode(decodedText);
        }
        
        function onScanError(errorMessage) {
            // Optional
        }

        // ========== PROSES DATA ==========
        function processQRCode(qrData) {
            try {
                let nip = qrData;
                
                try {
                    const qrJson = JSON.parse(qrData);
                    if (qrJson.nip) {
                        nip = qrJson.nip;
                    }
                } catch (e) {
                    nip = qrData;
                }
                
                if (!nip || nip.trim() === '') {
                    showToast('QR Code tidak valid', 'error');
                    playSound('error');
                    updateScannerStatus('SIAP SCAN', 'green');
                    return;
                }
                
                updateScannerStatus('PROSES DATA...', 'yellow');
                
                const cacheKey = `${nip}_${currentJenisPresensi}`;
                const now = new Date();
                const today = now.toDateString();
                
                if (recentlyPresensi[cacheKey] === today) {
                    console.log('Duplikasi terdeteksi di cache lokal');
                    showAnnouncement('duplicate', 
                        `Anda sudah melakukan presensi ${currentJenisPresensi.replace('_', ' ').toUpperCase()} hari ini.`, 
                        { nip: nip, jenis_presensi: currentJenisPresensi }
                    );
                    playSound('duplicate');
                    updateScannerStatus('SIAP SCAN', 'green');
                    return;
                }
                
                submitPresensi(nip, currentJenisPresensi);
                
            } catch (error) {
                console.error('Error processing QR:', error);
                updateScannerStatus('SIAP SCAN', 'green');
            }
        }
        
        // ========== SUBMIT PRESENSI ==========
        function submitPresensi(nip, jenisPresensi) {
            console.log('Submitting presensi:', { nip, jenisPresensi });
            
            showToast('Memproses presensi...', 'info');
            
            fetch('/presensi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    nip: nip.trim(),
                    jenis_presensi: jenisPresensi
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (response.status === 400) {
                    return response.json().then(errorData => {
                        const cacheKey = `${nip}_${jenisPresensi}`;
                        const now = new Date();
                        recentlyPresensi[cacheKey] = now.toDateString();
                        
                        if (errorData.type === 'warning') {
                            return {
                                success: false,
                                type: 'duplicate',
                                message: errorData.message,
                                data: errorData.data || {}
                            };
                        }
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    });
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Parsed response data:', data);
                
                setTimeout(() => {
                    updateScannerStatus('SIAP SCAN', 'green');
                }, 100);
                
                if (data.success) {
                    const cacheKey = `${nip}_${jenisPresensi}`;
                    const now = new Date();
                    recentlyPresensi[cacheKey] = now.toDateString();
                    
                    playSound('success');
                    showAnnouncement('success', data.message, data.data);
                    
                    const scannerBox = document.getElementById('scanner-box');
                    scannerBox.classList.add('success-pulse');
                    setTimeout(() => {
                        scannerBox.classList.remove('success-pulse');
                    }, 500);
                    
                } else {
                    if (data.type === 'duplicate') {
                        playSound('duplicate');
                        showAnnouncement('duplicate', data.message, data.data || {});
                    } else if (data.type === 'warning') {
                        playSound('warning');
                        showAnnouncement('warning', data.message, data.data || {});
                    } else {
                        playSound('error');
                        showAnnouncement('error', data.message, data.data || {});
                    }
                }
            })
            .catch(error => {
                console.error('Error submit presensi:', error);
                updateScannerStatus('SIAP SCAN', 'green');
                
                if (error.message.includes('HTTP 400')) {
                    playSound('duplicate');
                    showAnnouncement('duplicate', 'Anda sudah melakukan presensi untuk mode ini hari ini.', {});
                } else {
                    playSound('error');
                    showAnnouncement('error', 'Terjadi kesalahan: ' + error.message, {});
                }
            });
        }

        // ========== FUNGSI SCANNER EKSTERNAL ==========
        function testScannerConnection() {
            showToast('Menguji koneksi scanner...', 'info');
            
            fetch('/presensi/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    nip: 'TEST123',
                    jenis_presensi: currentJenisPresensi
                })
            })
            .then(response => response.json())
            .then(data => {
                const statusElement = document.getElementById('external-status');
                
                if (data.success) {
                    statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full bg-green-900/40 border border-green-400/30';
                    statusElement.innerHTML = '<span class="status-indicator status-active"></span><span class="text-sm font-semibold text-green-300">TERHUBUNG</span>';
                    
                    showToast('Scanner eksternal terhubung', 'success');
                    playSound('success');
                } else {
                    statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full bg-red-900/40 border border-red-400/30';
                    statusElement.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span><span class="text-sm font-semibold text-red-300">ERROR</span>';
                    
                    showToast('Scanner eksternal error', 'error');
                    playSound('error');
                }
            })
            .catch(error => {
                console.error('Connection test error:', error);
                const statusElement = document.getElementById('external-status');
                statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full bg-red-900/40 border border-red-400/30';
                statusElement.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span><span class="text-sm font-semibold text-red-300">GAGAL</span>';
                
                showToast('Gagal menguji koneksi', 'error');
                playSound('error');
            });
        }

        // ========== FUNGSI UI ==========
        function loadInfoHariIni() {
            fetch('/presensi/info-hari-ini', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('current-date').textContent = 
                    data.hari + ', ' + data.tanggal;
                document.getElementById('server-status').textContent = data.waktu;
            })
            .catch(error => {
                console.error('Error loading info:', error);
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('current-date').textContent = 
                    now.toLocaleDateString('id-ID', options);
            });
        }
        
        function updateDateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Jakarta'
            });
            
            document.getElementById('current-time').textContent = timeString;
        }

        // ========== INISIALISASI ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sistem Presensi Digital - Ditjenpas DIY');
            
            loadInfoHariIni();
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            setTimeout(() => {
                initializeScanner();
            }, 500);
            
            setJenisPresensi('apel_pagi');
            
            // ========== EVENT LISTENERS ==========
            const manualNipInput = document.getElementById('manual-nip');
            const submitManualBtn = document.getElementById('submit-manual-btn');
            
            // Enter key untuk input
            manualNipInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    submitManualPresensi();
                }
            });
            
            // Focus handler untuk mobile
            manualNipInput.addEventListener('touchstart', function(e) {
                e.preventDefault();
                this.focus();
                this.setSelectionRange(this.value.length, this.value.length);
                
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    setTimeout(() => {
                        this.focus();
                    }, 100);
                }
            });
            
            // Click handler
            manualNipInput.addEventListener('click', function(e) {
                this.focus();
                this.setSelectionRange(this.value.length, this.value.length);
            });
            
            // Submit button
            if (submitManualBtn) {
                submitManualBtn.addEventListener('click', submitManualPresensi);
                
                submitManualBtn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    submitManualPresensi();
                });
            }
            
            setTimeout(() => {
                testScannerConnection();
            }, 1500);
        });

        // ========== CLEANUP ==========
        window.addEventListener('beforeunload', function() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().catch(console.error);
            }
        });
    </script>

</body>
</html>