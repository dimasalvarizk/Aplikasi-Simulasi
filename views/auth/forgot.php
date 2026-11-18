<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Pusat Pemulihan Akun — SMP Negeri 1 Karangmoncol') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 relative bg-slate-900" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.82) 0%, rgba(15, 23, 42, 0.94) 100%), url('<?= base_url('public/images/school_bg.jpg') ?>') center center / cover no-repeat fixed;">

<?php
$flash_error = getFlash('error');
$flash_success = getFlash('success');
$defaultTab = (!empty($initialRole) && in_array($initialRole, ['guru', 'siswa'], true)) ? $initialRole : (isset($_GET['role']) && $_GET['role'] === 'siswa' ? 'siswa' : 'guru');
?>

<!-- Container Utama: Form di Kiri, Identitas di Kanan -->
<div class="w-full max-w-4xl bg-white/95 backdrop-blur-md border border-white/40 rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 relative z-10" x-data="forgotForm('<?= $defaultTab ?>')">

    <!-- SEBELAH KIRI: FORM ATUR ULANG KATA SANDI / PERMOHONAN -->
    <div class="order-2 lg:order-1 lg:col-span-7 p-6 sm:p-8 md:p-10 flex flex-col justify-between">
        <div>
            <!-- Selector Tab Peran (Clean & Minimalist) -->
            <div class="mb-5">
                <div class="flex p-1 bg-slate-100 rounded-2xl border border-slate-200">
                    <button type="button" 
                            @click="switchRole('guru')" 
                            :class="tab === 'guru' ? 'bg-white text-slate-900 shadow-xs font-bold border border-slate-200/80' : 'text-slate-500 hover:text-slate-800 font-medium'" 
                            class="flex-1 py-2 px-3 rounded-xl text-xs transition text-center">
                        Guru / Pengelola
                    </button>
                    <button type="button" 
                            @click="switchRole('siswa')" 
                            :class="tab === 'siswa' ? 'bg-white text-slate-900 shadow-xs font-bold border border-slate-200/80' : 'text-slate-500 hover:text-slate-800 font-medium'" 
                            class="flex-1 py-2 px-3 rounded-xl text-xs transition text-center">
                        Siswa
                    </button>
                </div>
            </div>

            <!-- Flash Message Alerts -->
            <?php if ($flash_error): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs">
                <span><?= htmlspecialchars($flash_error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($flash_success): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-xs">
                <span><?= htmlspecialchars($flash_success) ?></span>
            </div>
            <?php endif; ?>

            <!-- ============================================== -->
            <!-- TAB 1: GURU / PENGELOLA (DENGAN MASTER KEY)     -->
            <!-- ============================================== -->
            <div x-show="tab === 'guru'" x-cloak>
                <div class="mb-5">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 uppercase tracking-wider mb-2">
                        Pemulihan Akun Pengelola
                    </span>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Atur Ulang Kata Sandi Guru</h1>
                    <p class="text-xs text-slate-500 mt-1">Verifikasi identitas pendidik resmi menggunakan NIP dan Kunci Keamanan Master.</p>
                </div>

                <form action="<?= base_url('auth/resetPassword') ?>" method="POST" @submit="handleTeacherSubmit">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? generateToken() ?>">
                    <input type="hidden" name="role" value="guru">

                    <!-- Username Guru -->
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="guru_username">
                            Username Guru / Email Dinas
                        </label>
                        <input type="text" id="guru_username" name="username" required
                               placeholder="Contoh: angzal atau hana"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 bg-white focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                    </div>

                    <!-- NIP Guru -->
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="guru_nip">
                            Nomor Induk Pegawai (NIP)
                        </label>
                        <input type="text" id="guru_nip" name="identifier" required
                               placeholder="18 digit NIP resmi guru"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 bg-white focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                    </div>

                    <!-- Kunci Keamanan Master -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700" for="master_key">
                                Kunci Keamanan Master <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Otoritas Pengelola</span>
                        </div>
                        <div class="relative">
                            <input :type="showMaster ? 'text' : 'password'"
                                   id="master_key" name="master_key" required
                                   placeholder="Masukkan kode otorisasi master"
                                   class="w-full px-3.5 py-2.5 pr-10 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                            <button type="button" @click="showMaster = !showMaster"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                                <i :class="showMaster ? 'fa-eye-slash' : 'fa-eye'" class="fas text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Kata Sandi Baru -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="guru_pass">Kata Sandi Baru</label>
                            <input :type="showPass ? 'text' : 'password'"
                                   id="guru_pass" name="password" required minlength="6"
                                   placeholder="Minimal 6 karakter"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 bg-white focus:outline-none focus:border-slate-900 transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="guru_confirm">Konfirmasi Sandi</label>
                            <input :type="showPass ? 'text' : 'password'"
                                   id="guru_confirm" name="password_confirmation" required minlength="6"
                                   placeholder="Ulangi kata sandi"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 bg-white focus:outline-none focus:border-slate-900 transition-colors">
                        </div>
                    </div>

                    <!-- Submit Button Guru -->
                    <button type="submit"
                            :disabled="loading"
                            class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-colors flex items-center justify-center shadow-sm">
                        <span x-show="!loading">Verifikasi & Simpan Kata Sandi</span>
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

            <!-- ============================================== -->
            <!-- TAB 2: SISWA (OTORITAS TERPUSAT MELALUI GURU)   -->
            <!-- ============================================== -->
            <div x-show="tab === 'siswa'" x-cloak>
                <div class="mb-4">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 uppercase tracking-wider mb-2">
                        Akun Siswa
                    </span>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Pemulihan Akun Siswa Terpusat</h1>
                    <p class="text-xs text-slate-500 mt-1">Demi keamanan akun, pengaturan ulang kata sandi dilakukan melalui Guru Pengampu.</p>
                </div>

                <!-- Info Box Perlindungan -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl mb-4 text-xs text-slate-700 space-y-1.5">
                    <span class="font-bold text-slate-800 block">Petunjuk Pemulihan:</span>
                    <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-600 leading-relaxed">
                        <li>Hubungi Guru Pengampu (Bpk. Angzal Maolana, S.Pd / Ibu Hana Sifakhatin, S.Pd).</li>
                        <li>Guru akan mereset kata sandi Anda dan memberikan kata sandi sementara.</li>
                        <li>Masuk kembali menggunakan kata sandi sementara tersebut, lalu perbarui di menu Profil.</li>
                    </ol>
                </div>

                <!-- Form Kirim Permohonan Cepat ke Guru -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-800 mb-1">
                        Kirim Permohonan ke Portal Guru
                    </h4>
                    <p class="text-[11px] text-slate-500 mb-3">Guru akan menerima pemberitahuan di portal pengajar.</p>

                    <form action="<?= base_url('auth/requestResetSiswa') ?>" method="POST" @submit="loading = true">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? generateToken() ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1" for="req_username">Username Siswa</label>
                                <input type="text" id="req_username" name="username" required
                                       placeholder="Contoh: siswa1"
                                       class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 bg-white focus:outline-none focus:border-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1" for="req_nisn">NISN Siswa</label>
                                <input type="text" id="req_nisn" name="nisn" required
                                       placeholder="10 digit NISN resmi"
                                       class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 bg-white focus:outline-none focus:border-slate-900">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1" for="req_keterangan">Keterangan (Opsional)</label>
                            <input type="text" id="req_keterangan" name="keterangan"
                                   placeholder="Contoh: Lupa kata sandi akun"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 bg-white focus:outline-none focus:border-slate-900">
                        </div>

                        <button type="submit"
                                :disabled="loading"
                                class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition-colors flex items-center justify-center shadow-sm">
                            Kirim Permohonan ke Guru
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tombol Kembali ke Login -->
            <div class="mt-4 text-center">
                <a href="<?= base_url('login') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-block transition">
                    &larr; Kembali ke Halaman Masuk
                </a>
            </div>
        </div>

        <!-- Catatan Keamanan Footer -->
        <div class="mt-5 pt-3 border-t border-slate-100 text-[11px] text-slate-400">
            SMP Negeri 1 Karangmoncol • Sistem Pembelajaran Terpadu
        </div>
    </div>

    <!-- SEBELAH KANAN: LOGO SEKOLAH & IDENTITAS RESMI -->
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
            Pusat Layanan Keamanan & Pemulihan Akun Pembelajaran Topologi Jaringan Komputer
        </p>

        <!-- Status Pengelola Resmi (Clean & Minimalist) -->
        <div class="mt-6 w-full max-w-xs bg-white/80 rounded-xl p-3.5 border border-slate-200/70 text-left shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Tim Pengelola Resmi:</span>
            <div class="text-[11px] font-semibold text-slate-800 mb-1">
                &bull; Bpk. Angzal Maolana, S.Pd
            </div>
            <div class="text-[11px] font-semibold text-slate-800">
                &bull; Ibu Hana Sifakhatin, S.Pd
            </div>
        </div>
    </div>

</div>

<script>
function forgotForm(initialRole) {
    return {
        tab: initialRole || 'guru',
        showPass: false,
        showMaster: false,
        loading: false,

        switchRole(role) {
            this.tab = role;
        },

        handleTeacherSubmit(e) {
            const pass = document.getElementById('guru_pass').value;
            const confirm = document.getElementById('guru_confirm').value;
            if (pass !== confirm) {
                alert('Konfirmasi kata sandi baru tidak cocok!');
                e.preventDefault();
                return;
            }
            if (pass.length < 6) {
                alert('Kata sandi baru minimal 6 karakter!');
                e.preventDefault();
                return;
            }
            this.loading = true;
        }
    }
}
</script>

</body>
</html>
