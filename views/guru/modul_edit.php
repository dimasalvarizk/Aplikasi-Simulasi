<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= base_url('guru/modul') ?>" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Daftar Modul
                </a>
                <span>/</span>
                <span class="text-slate-900 font-semibold"><?= htmlspecialchars($modul['judul']) ?></span>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Edit</span>
            </div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Modul Pembelajaran</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border <?= $modul['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                    <?= $modul['is_active'] ? 'Dipublikasikan (Aktif)' : 'Draft Offline' ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Ubah judul, deskripsi silabus, atau urutan tampil modul pembelajaran.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/modul') ?>" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm">
                Batal
            </a>
            <button type="submit" form="editModulForm" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium shadow-sm transition flex items-center gap-1.5">
                <i class="fas fa-check text-[10px]"></i> Simpan Perubahan
            </button>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Formulir Modul (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <form id="editModulForm" method="POST" action="<?= base_url('guru/modul/edit/' . $modul['id']) ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Card 1: Informasi Pokok Modul -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">1. Informasi Pokok Modul</h3>
                            <p class="text-xs text-slate-500">Judul materi pembelajaran dan ringkasan capaian kompetensi</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 1</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Modul <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" required value="<?= htmlspecialchars($modul['judul']) ?>"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Ringkas & Capaian Pembelajaran</label>
                        <textarea name="deskripsi" rows="4"
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"><?= htmlspecialchars($modul['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Card 2: Urutan & Distribusi -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">2. Pengaturan Urutan & Publikasi</h3>
                            <p class="text-xs text-slate-500">Tentukan letak modul di silabus dan status ketersediaan bagi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 2</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Urutan Tampil</label>
                            <input type="number" name="urutan" value="<?= $modul['urutan'] ?>" min="1"
                                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 mt-1">
                            <span class="text-[10px] text-slate-400 mt-1 block">Modul dengan nomor lebih kecil tampil lebih awal</span>
                        </div>

                        <label class="p-4 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer flex items-start gap-3 transition">
                            <input type="checkbox" name="is_active" value="1" <?= $modul['is_active'] ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Publikasikan Modul Ini</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">Siswa dapat langsung melihat dan membaca bab materi pada modul ini.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau & Navigasi Cepat (1 Kolom) -->
        <div class="space-y-6">
            <!-- Pratinjau Tampilan Siswa -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-eye text-slate-400"></i> Pratinjau Modul Siswa
                    </span>
                    <span class="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-medium">
                        Pratinjau
                    </span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>SMPN 1 Karangmoncol</span>
                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded font-medium text-[10px]">Kelas VII</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-1"><?= htmlspecialchars($modul['judul']) ?></h4>
                    <p class="text-[11px] text-slate-500 line-clamp-2"><?= htmlspecialchars($modul['deskripsi'] ?? 'Materi silabus topologi jaringan.') ?></p>
                    <div class="pt-2 flex items-center justify-between text-[11px] text-slate-400 border-t border-slate-200/60">
                        <span>Status: <?= $modul['is_active'] ? 'Aktif' : 'Draft' ?></span>
                        <span class="text-slate-700 font-medium font-mono">Modul #<?= $modul['urutan'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Akses Cepat ke Bab Materi -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kelola Isi Bab Materi</h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Setelah memperbarui pengaturan modul, Anda dapat langsung menambah atau mengedit bab-bab teori dan tutorial di dalamnya.
                </p>
                <a href="<?= base_url('guru/materi/' . $modul['id']) ?>"
                   class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fas fa-list-ul text-[10px]"></i> Buka Daftar Bab Materi
                </a>
            </div>
        </div>
    </div>
</div>
