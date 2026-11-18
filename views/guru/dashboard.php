<div class="space-y-6">
    <!-- Header Greeting with School Identity -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1.5">
                <span class="text-slate-900 font-bold">SMP NEGERI 1 KARANGMONCOL</span>
                <span>•</span>
                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-200/60 font-medium text-[11px]">Kelas VII</span>
                <span>•</span>
                <span class="text-slate-500 text-[11px]">Informatika Jaringan Komputer</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Selamat Datang, <?= htmlspecialchars(getUser()['nama'] ?? 'Bapak/Ibu Guru') ?></h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola silabus modul pembelajaran, bank soal kuis, serta pantau perakitan kanvas topologi dan XP siswa secara langsung.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= base_url('guru/siswa') ?>" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl text-xs transition flex items-center gap-1.5 shadow-sm">
                <i class="fas fa-user-plus text-[10px]"></i> Kelola Siswa
            </a>
            <a href="<?= base_url('guru/modul/create') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-medium rounded-xl text-xs border border-slate-200 transition flex items-center gap-1.5 shadow-sm">
                <i class="fas fa-plus text-[10px]"></i> Tambah Modul
            </a>
            <a href="<?= base_url('guru/kuis/create') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-medium rounded-xl text-xs border border-slate-200 transition flex items-center gap-1.5 shadow-sm">
                <i class="fas fa-file-alt text-[10px]"></i> Buat Kuis
            </a>
        </div>
    </div>

    <!-- 4 Clean Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Siswa Aktif</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mt-2"><?= $totalSiswa ?></h3>
            <p class="text-xs text-slate-400 mt-0.5">Siswa terdaftar Kelas VII</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Modul Topologi</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fas fa-book-open"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mt-2"><?= $totalModul ?></h3>
            <p class="text-xs text-slate-400 mt-0.5">Paket silabus materi</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Paket Kuis Ujian</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mt-2"><?= $totalKuis ?></h3>
            <p class="text-xs text-slate-400 mt-0.5">Evaluasi kompetensi</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Nilai</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mt-2"><?= $rataNilai ?> <span class="text-xs font-normal text-slate-400">/ 100</span></h3>
            <p class="text-xs text-slate-400 mt-0.5">Hasil evaluasi belajar</p>
        </div>
    </div>

    <!-- Interactive Topology Modules Showcase -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Topologi Jaringan yang Dipelajari Siswa</h3>
                <p class="text-[11px] text-slate-500">Materi teori & tantangan kanvas interaktif yang dipelajari siswa:</p>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">Standar Kurikulum Informatika VII</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
            <?php
            $topologiList = [
                ['name' => 'Bus',    'type' => 'Linier Backbone',  'tag' => 'Terminator'],
                ['name' => 'Star',   'type' => 'Pusat Switch/Hub', 'tag' => 'Terpopuler'],
                ['name' => 'Ring',   'type' => 'Token Passing',    'tag' => 'Sirkular'],
                ['name' => 'Mesh',   'type' => 'Redundansi Penuh', 'tag' => 'Toleransi Error'],
                ['name' => 'Tree',   'type' => 'Hierarki Cabang',  'tag' => 'Kombinasi'],
                ['name' => 'Hybrid', 'type' => 'Multi-Topologi',   'tag' => 'Skala Besar'],
            ];
            foreach ($topologiList as $top):
            ?>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 hover:border-slate-300 transition text-center">
                <span class="inline-block px-1.5 py-0.5 bg-white border border-slate-200 text-slate-700 font-bold text-[10px] rounded mb-1">
                    <?= $top['name'] ?>
                </span>
                <p class="text-[11px] font-semibold text-slate-800 leading-tight"><?= $top['type'] ?></p>
                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $top['tag'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2 Column Section: Modul & Leaderboard Siswa -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Modul Pembelajaran Aktif -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Silabus Modul Pembelajaran</h3>
                    <p class="text-xs text-slate-500">Daftar modul yang dapat dibaca dan disimulasikan oleh siswa</p>
                </div>
                <a href="<?= base_url('guru/modul') ?>" class="text-xs font-semibold text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg transition">
                    Kelola Semua Modul
                </a>
            </div>

            <div class="space-y-3">
                <?php if (empty($modul)): ?>
                    <div class="text-center py-10 text-slate-400 text-xs">Belum ada modul yang dibuat.</div>
                <?php else: ?>
                    <?php foreach ($modul as $m): ?>
                        <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition flex items-center justify-between bg-slate-50/40">
                            <div class="pr-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded border border-slate-200">
                                        Modul #<?= $m['urutan'] ?>
                                    </span>
                                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm"><?= htmlspecialchars($m['judul']) ?></h4>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-1"><?= htmlspecialchars($m['deskripsi'] ?? 'Materi topologi jaringan komputer.') ?></p>
                                <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
                                    <span><i class="fas fa-file-alt text-slate-400 mr-1"></i> <?= $m['jumlah_materi'] ?> Bab Materi</span>
                                    <span>•</span>
                                    <span class="<?= $m['is_active'] ? 'text-emerald-700 font-medium' : 'text-slate-400' ?>">
                                        <?= $m['is_active'] ? 'Dipublikasikan' : 'Draft' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="<?= base_url('guru/materi/' . $m['id']) ?>" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-medium transition shadow-sm">
                                    Kelola Materi
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Peringkat Siswa -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Peringkat & XP Siswa</h3>
                        <p class="text-xs text-slate-500">Aktivitas belajar siswa kelas VII</p>
                    </div>
                    <a href="<?= base_url('guru/siswa') ?>" class="text-xs font-semibold text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg transition">
                        Semua Siswa
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($siswaAktif)): ?>
                        <div class="text-center py-8 text-slate-400 text-xs">Belum ada siswa terdaftar.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($siswaAktif, 0, 5) as $i => $s): ?>
                            <div class="py-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-md <?= $i === 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : ($i === 1 ? 'bg-slate-200 text-slate-700' : ($i === 2 ? 'bg-amber-50 text-amber-900' : 'bg-slate-100 text-slate-500')) ?> text-[10px] font-bold flex items-center justify-center">
                                        #<?= $i + 1 ?>
                                    </span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900 leading-tight"><?= htmlspecialchars($s['nama']) ?></h5>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($s['kelas'] ?? 'VII A') ?> • Lvl <?= $s['level'] ?></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-900 font-mono">
                                        <?= number_format($s['total_xp']) ?> XP
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rekap Nilai Button -->
            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="<?= base_url('guru/laporan') ?>" class="w-full py-2.5 px-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-medium rounded-xl text-center text-xs transition flex items-center justify-center gap-2 shadow-sm">
                    <i class="fas fa-file-excel text-emerald-600"></i> Unduh Rekap Nilai Siswa (CSV/Excel)
                </a>
            </div>
        </div>
    </div>
</div>
