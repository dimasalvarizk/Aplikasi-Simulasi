<div class="space-y-6">
    <!-- Top Hero Card -->
    <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-bold flex-shrink-0 shadow-sm">
                <?= strtoupper(substr($guru['nama'] ?? 'G', 0, 1)) ?>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($guru['nama'] ?? 'Guru Pengampu') ?></h1>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[11px] font-semibold rounded-md border border-blue-200/60">
                        Pengajar Aktif
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">SMP NEGERI 1 KARANGMONCOL • Guru Pengampu Mata Pelajaran Informatika</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-slate-400 font-mono">
                    <span>NIP: <?= htmlspecialchars($guru['nip'] ?? '-') ?></span>
                    <span>•</span>
                    <span>@<?= htmlspecialchars($guru['username'] ?? '') ?></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="#ganti-sandi" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 rounded-xl text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-key text-[10px]"></i> Ganti Sandi
            </a>
            <a href="<?= base_url('guru/dashboard') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-home text-[10px]"></i> Dashboard
            </a>
            <a href="<?= base_url('auth/logout') ?>" class="px-3.5 py-2 bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-sign-out-alt text-[10px]"></i> Keluar Akun
            </a>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Rincian Akun & Informasi (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Informasi Akun & Otoritas</h3>
                    <p class="text-xs text-slate-500">Data login dan hak akses administrator portal pengajar</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Alamat Email Resmi</span>
                        <p class="text-xs font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($guru['email'] ?? '-') ?></p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Hak Akses Sistem</span>
                        <p class="text-xs font-bold text-slate-900 mt-0.5">Guru / Administrator Kelas VII</p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Sesi Masuk Terakhir</span>
                        <p class="text-xs font-bold text-slate-900 mt-0.5"><?= formatDate($guru['last_login'] ?? '', 'd F Y, H:i:s') ?></p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Status Akun</span>
                        <p class="text-xs font-bold text-emerald-700 mt-0.5 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif & Terverifikasi
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card Ganti Kata Sandi -->
            <div id="ganti-sandi" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4" x-data="{ showOld: false, showNew: false, showConfirm: false }">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-key text-amber-500"></i> Keamanan Akun & Ganti Kata Sandi
                        </h3>
                        <p class="text-xs text-slate-500">Perbarui kata sandi untuk melindungi akses portal guru Anda</p>
                    </div>
                    <span class="text-[10px] bg-amber-50 text-amber-800 font-semibold px-2 py-0.5 rounded border border-amber-200">
                        Keamanan
                    </span>
                </div>

                <form action="<?= base_url('guru/ganti-password') ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? generateToken() ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini (Lama) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input :type="showOld ? 'text' : 'password'" name="password_lama" required placeholder="Masukkan kata sandi saat ini"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 pr-10 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            <button type="button" @click="showOld = !showOld" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                                <i :class="showOld ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input :type="showNew ? 'text' : 'password'" name="password_baru" required minlength="6" placeholder="Minimal 6 karakter"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 pr-10 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                                <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                                    <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Minimal 6 karakter kombinasi huruf & angka</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'" name="konfirmasi_password" required minlength="6" placeholder="Ulangi kata sandi baru"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 pr-10 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                                <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                                    <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]"></i> Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
                <div class="border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">Tentang Aplikasi Simulasi Topologi Jaringan</h3>
                    <p class="text-xs text-slate-500">Platform pembelajaran interaktif materi Jaringan Komputer Kelas VII</p>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Aplikasi ini dirancang khusus untuk pembelajaran Informatika materi Jaringan Komputer di <strong>SMP Negeri 1 Karangmoncol</strong>. Pengajar menyusun modul dan evaluasi kuis, dan siswa dapat mempelajari teori serta merakit simulasi kanvas topologi jaringan secara interaktif.
                </p>
            </div>
        </div>

        <!-- Kolom Kanan: Kelas Binaan & Akses Cepat (1 Kolom) -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3.5">
                <div class="pb-2.5 border-b border-slate-100 flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kelas yang Dibina</h4>
                    <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200 font-medium">
                        Semester Gasal
                    </span>
                </div>

                <div class="space-y-2">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                        <div>
                            <strong class="text-slate-900 block font-bold">Kelas VII A</strong>
                            <span class="text-[11px] text-slate-500">Informatika Topologi</span>
                        </div>
                        <a href="<?= base_url('guru/siswa?kelas=VII+A') ?>" class="text-[11px] font-semibold text-blue-700 hover:underline">
                            Lihat Siswa →
                        </a>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                        <div>
                            <strong class="text-slate-900 block font-bold">Kelas VII B</strong>
                            <span class="text-[11px] text-slate-500">Informatika Topologi</span>
                        </div>
                        <a href="<?= base_url('guru/siswa?kelas=VII+B') ?>" class="text-[11px] font-semibold text-blue-700 hover:underline">
                            Lihat Siswa →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pintasan Fitur Pengajar -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-2.5">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Pintasan Fitur</h4>
                <a href="<?= base_url('guru/siswa') ?>" class="block p-2.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 transition border border-transparent hover:border-slate-200">
                    <i class="fas fa-users text-slate-400 mr-2"></i> Data & Progres Siswa
                </a>
                <a href="<?= base_url('guru/modul') ?>" class="block p-2.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 transition border border-transparent hover:border-slate-200">
                    <i class="fas fa-book-open text-slate-400 mr-2"></i> Kelola Modul & Materi
                </a>
                <a href="<?= base_url('guru/kuis') ?>" class="block p-2.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 transition border border-transparent hover:border-slate-200">
                    <i class="fas fa-clipboard-list text-slate-400 mr-2"></i> Bank Kuis & Evaluasi
                </a>
                <a href="<?= base_url('guru/laporan') ?>" class="block p-2.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 transition border border-transparent hover:border-slate-200">
                    <i class="fas fa-chart-bar text-slate-400 mr-2"></i> Laporan Rekap Nilai CSV
                </a>
            </div>
        </div>
    </div>
</div>
