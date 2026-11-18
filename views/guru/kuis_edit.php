<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= base_url('guru/kuis') ?>" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Bank Kuis
                </a>
                <span>/</span>
                <a href="<?= base_url('guru/kuis/detail/' . $kuis['id']) ?>" class="hover:text-slate-900 transition">
                    <?= htmlspecialchars($kuis['judul']) ?>
                </a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Pengaturan</span>
            </div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Pengaturan Kuis</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border <?= $kuis['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                    <?= $kuis['is_active'] ? 'Dipublikasikan (Aktif)' : 'Draft Offline' ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Ubah parameter durasi ujian, standar KKM passing grade, dan status publikasi soal.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/kuis/detail/' . $kuis['id']) ?>" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm">
                Batal
            </a>
            <button type="submit" form="editKuisForm" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium shadow-sm transition flex items-center gap-1.5">
                <i class="fas fa-check text-[10px]"></i> Simpan Perubahan
            </button>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Formulir Pengaturan (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <form id="editKuisForm" method="POST" action="<?= base_url('guru/kuis/edit/' . $kuis['id']) ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Card 1: Informasi Dasar Kuis -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">1. Informasi Pokok Kuis</h3>
                            <p class="text-xs text-slate-500">Judul materi ujian dan petunjuk pengerjaan bagi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 1</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Paket Kuis <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" required value="<?= htmlspecialchars($kuis['judul']) ?>"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"
                               placeholder="Contoh: Kuis Penguasaan Topologi Star">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi / Petunjuk untuk Siswa</label>
                        <textarea name="deskripsi" rows="3"
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"
                                  placeholder="Tuliskan petunjuk pengerjaan, ketentuan remedial, dan cakupan materi..."><?= htmlspecialchars($kuis['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Card 2: Parameter Kelulusan & Gamifikasi -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">2. Batas Waktu, KKM, & Reward XP</h3>
                            <p class="text-xs text-slate-500">Aturan penilaian dan reward poin gamifikasi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 2</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi Ujian</label>
                            <div class="relative mt-1">
                                <input type="number" name="durasi_menit" value="<?= $kuis['durasi_menit'] ?>" min="5" max="180"
                                       class="w-full pl-3 pr-14 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">Menit</span>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Waktu hitung mundur otomatis di HP siswa</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">KKM / Passing Grade</label>
                            <div class="relative mt-1">
                                <input type="number" name="passing_grade" value="<?= $kuis['passing_grade'] ?>" min="0" max="100"
                                       class="w-full pl-3 pr-10 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-blue-700 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">%</span>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Standar kelulusan minimum Kelas VII</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">XP Reward Kelulusan</label>
                            <div class="relative mt-1">
                                <input type="number" name="xp_reward" value="<?= $kuis['xp_reward'] ?>" min="10" step="5"
                                       class="w-full pl-3 pr-10 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-amber-800 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">XP</span>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Poin naik level jika nilai >= KKM</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Pelaksanaan & Status Kuis -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">3. Pengaturan Pelaksanaan Ujian</h3>
                            <p class="text-xs text-slate-500">Opsi anti-contek dan status ketersediaan kuis bagi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 3</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="p-4 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer flex items-start gap-3 transition">
                            <input type="checkbox" name="acak_soal" value="1" <?= $kuis['acak_soal'] ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Acak Urutan Butir Soal</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">Urutan butir soal akan diacak otomatis pada setiap akun siswa saat ujian berlangsung.</p>
                            </div>
                        </label>

                        <label class="p-4 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer flex items-start gap-3 transition">
                            <input type="checkbox" name="is_active" value="1" <?= $kuis['is_active'] ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Publikasikan Kuis</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">Siswa dapat langsung melihat dan mengerjakan paket kuis evaluasi ini.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau Tampilan & Navigasi Cepat (1 Kolom) -->
        <div class="space-y-6">
            <!-- Pratinjau Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-eye text-slate-400"></i> Pratinjau Tampilan Kuis
                    </span>
                    <span class="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-medium">
                        Pratinjau Siswa
                    </span>
                </div>

                <!-- Mockup Card Siswa -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>SMPN 1 Karangmoncol</span>
                        <span class="font-bold text-blue-700">Kelas VII</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2"><?= htmlspecialchars($kuis['judul']) ?></h4>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($kuis['deskripsi'] ?? 'Evaluasi pemahaman materi topologi jaringan.') ?></p>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 p-2 bg-white rounded-lg border border-slate-200/60 text-center">
                        <div>
                            <span class="text-[9px] text-slate-400 block">Waktu</span>
                            <span class="text-[11px] font-bold text-slate-800"><?= $kuis['durasi_menit'] ?>m</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 block">KKM</span>
                            <span class="text-[11px] font-bold text-blue-700"><?= $kuis['passing_grade'] ?>%</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 block">Reward</span>
                            <span class="text-[11px] font-bold text-amber-800">+<?= $kuis['xp_reward'] ?> XP</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Kelola Soal & Bank Soal -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Butir Soal Terdaftar</h4>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-bold border border-slate-200">
                        <?= count($soal ?? []) ?> Soal
                    </span>
                </div>
                <p class="text-[11px] text-slate-500">Edit pilihan ganda, kunci jawaban, dan butir soal kuis ini.</p>
                <a href="<?= base_url('guru/kuis/detail/' . $kuis['id']) ?>"
                   class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fas fa-list-check text-[10px]"></i> Kelola Butir Soal & Rekap
                </a>
            </div>

            <!-- Tips & Standar Penilaian -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-2.5 text-xs text-slate-600">
                <h4 class="font-bold text-slate-900 uppercase text-[11px] tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-lightbulb text-amber-500"></i> Panduan Guru
                </h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Standar KKM 75% direkomendasikan untuk materi topologi dasar (Bus, Star, Ring). Siswa yang belum mencapai nilai KKM akan mendapat status <strong class="text-rose-600">Remedial</strong> dan dapat mengulang kuis sesuai arahan pengajar.
                </p>
            </div>
        </div>
    </div>
</div>
