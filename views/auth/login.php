<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SMP Negeri 1 Karangmoncol</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Sembunyikan ikon mata bawaan browser (Edge/IE) agar tidak dobel dengan tombol custom */
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 relative bg-slate-900" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.78) 0%, rgba(15, 23, 42, 0.92) 100%), url('<?= base_url('public/images/school_bg.jpg') ?>') center center / cover no-repeat fixed;">

<?php
$flash_error = getFlash('error');
$flash_success = getFlash('success');
?>

<!-- Container Utama: Login di Kiri, Logo di Kanan -->
<div class="w-full max-w-4xl bg-white/95 backdrop-blur-md border border-white/40 rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 relative z-10" x-data="loginForm()">

    <!-- SEBELAH KIRI: FORM LOGIN -->
    <div class="order-2 lg:order-1 lg:col-span-7 p-6 sm:p-8 md:p-10 flex flex-col justify-between">
        <div>
            <!-- Header Form (Clean & Minimalist) -->
            <div class="mb-6">
                <span class="inline-block px-3 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 tracking-wide uppercase mb-3">
                    Portal Pembelajaran
                </span>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Masuk ke Akun</h1>
                <p class="text-xs text-slate-500 mt-1">Masukkan username dan password untuk mengakses materi dan simulasi topologi.</p>
            </div>

            <?php if ($flash_error): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs flex items-start gap-2">
                <span><?= htmlspecialchars($flash_error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($flash_success): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-xs flex items-start gap-2">
                <span><?= htmlspecialchars($flash_success) ?></span>
            </div>
            <?php endif; ?>

            <!-- Form Section -->
            <form action="<?= base_url('auth/login') ?>" method="POST" @submit="handleSubmit">
                <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">

                <!-- Username -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="username">
                        Username / NIP / NISN
                    </label>
                    <input type="text" id="username" name="username" required
                           x-ref="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Masukkan username atau NIP/NISN"
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 bg-white focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700" for="password">Password</label>
                        <a href="<?= base_url('auth/forgot') ?>" class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 transition">
                            Lupa Password?
                        </a>
                    </div>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'"
                               id="password" name="password" required
                               placeholder="Masukkan password"
                               class="w-full px-3.5 py-2.5 pr-10 border border-slate-300 rounded-xl text-sm text-slate-900 bg-white focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        <button type="button" @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i :class="showPass ? 'fa-eye-slash' : 'fa-eye'" class="fas text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        :disabled="loading"
                        class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-colors flex items-center justify-center shadow-sm">
                    <span x-show="!loading">Masuk</span>
                    <span x-show="loading" class="flex items-center space-x-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span>Memproses...</span>
                    </span>
                </button>
            </form>
        </div>

        <!-- Footer Text -->
        <div class="mt-8 pt-4 border-t border-slate-100 text-xs text-slate-400 text-left">
            &copy; <?= date('Y') ?> SMP Negeri 1 Karangmoncol. Hak Cipta Dilindungi.
        </div>
    </div>

    <!-- SEBELAH KANAN: LOGO SEKOLAH & IDENTITAS -->
    <div class="order-1 lg:order-2 lg:col-span-5 bg-gradient-to-b from-slate-50/90 to-blue-50/40 border-b lg:border-b-0 lg:border-l border-slate-200/80 p-8 sm:p-10 flex flex-col items-center justify-center text-center">
        <!-- Logo Sekolah -->
        <div class="w-36 h-36 sm:w-44 sm:h-44 md:w-48 md:h-48 bg-white rounded-2xl p-4 shadow-lg shadow-slate-200/60 border border-slate-200/80 flex items-center justify-center mb-5 transition-transform hover:scale-105 duration-300">
            <img src="<?= getSchoolLogoSrc() ?>" 
                 alt="Logo SMP Negeri 1 Karangmoncol" 
                 class="w-full h-full object-contain">
        </div>

        <span class="inline-block px-3 py-1 bg-blue-100/70 text-blue-800 text-[11px] font-semibold rounded-full uppercase tracking-wider mb-2">
            Kabupaten Purbalingga
        </span>
        <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">SMP NEGERI 1 KARANGMONCOL</h2>
        <p class="text-xs text-slate-500 max-w-xs leading-relaxed mt-1.5">
            Media Pembelajaran & Simulasi Interaktif Topologi Jaringan Komputer Kelas VII
        </p>
    </div>

</div>

<script>
function loginForm() {
    return {
        showPass: false,
        loading: false,
        handleSubmit(e) {
            this.loading = true;
        }
    }
}
</script>

</body>
</html>
