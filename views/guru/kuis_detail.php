<div class="space-y-6">
    <!-- Back Button & Action -->
    <div class="flex items-center justify-between">
        <a href="<?= base_url('guru/kuis') ?>" class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition">
            <i class="fas fa-arrow-left text-[10px]"></i> Kembali ke Bank Kuis
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/kuis/edit/' . $kuis['id']) ?>" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-pencil-alt text-[10px]"></i> Edit Pengaturan Kuis
            </a>
            <button type="button"
                onclick="confirmDelete('<?= base_url('guru/kuis/delete/' . $kuis['id']) ?>', 'Hapus Paket Kuis?', 'Apakah Anda yakin ingin menghapus paket kuis &quot;<?= htmlspecialchars(addslashes($kuis['judul'])) ?>&quot;? Seluruh butir soal dan riwayat nilai siswa pada kuis ini akan ikut terhapus.')"
                class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-trash-alt text-[10px]"></i> Hapus Kuis
            </button>
        </div>
    </div>

    <!-- Header Info Kuis -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[11px] font-medium rounded-lg border border-slate-200">
                    <?= htmlspecialchars($modul['judul'] ?? 'Modul Topologi') ?>
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium border <?= $kuis['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                    <?= $kuis['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight mt-1"><?= htmlspecialchars($kuis['judul']) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($kuis['deskripsi'] ?? 'Evaluasi pemahaman materi topologi jaringan.') ?></p>
        </div>

        <div class="flex items-center gap-6 bg-slate-50 p-4 rounded-xl border border-slate-200 flex-shrink-0">
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Durasi</span>
                <h4 class="text-lg font-bold text-slate-900"><?= $kuis['durasi_menit'] ?>m</h4>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Passing</span>
                <h4 class="text-lg font-bold text-blue-700"><?= $kuis['passing_grade'] ?>%</h4>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Jumlah Soal</span>
                <h4 class="text-lg font-bold text-slate-900"><?= count($soal) ?></h4>
            </div>
        </div>
    </div>

    <!-- 2 Section: Butir Soal & Rekap Nilai Siswa -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kolom Kiri: Butir Soal -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm">Daftar Butir Soal (<?= count($soal) ?>)</h3>
                <span class="text-[11px] text-slate-400">Pilihan Ganda</span>
            </div>
            <div class="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                <?php if (empty($soal)): ?>
                    <p class="text-xs text-slate-400 text-center py-8">Belum ada butir soal pada kuis ini.</p>
                <?php else: ?>
                    <?php foreach ($soal as $i => $s): ?>
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                            <div class="flex items-start justify-between">
                                <span class="font-bold text-xs text-slate-900">#<?= $i + 1 ?></span>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-semibold rounded border border-emerald-200/60">
                                        Kunci: Pilihan <?= $s['jawaban_benar'] ?>
                                    </span>
                                    <button type="button"
                                        onclick="confirmDelete('<?= base_url('guru/soal/delete/' . $s['id']) ?>', 'Hapus Butir Soal?', 'Apakah Anda yakin ingin menghapus butir soal #<?= $i + 1 ?> ini?')"
                                        class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition text-xs"
                                        title="Hapus Butir Soal">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($s['pertanyaan']) ?></p>
                            <div class="grid grid-cols-2 gap-1.5 text-[11px] text-slate-600 pt-1">
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'A' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    A. <?= htmlspecialchars($s['opsi_a']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'B' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    B. <?= htmlspecialchars($s['opsi_b']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'C' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    C. <?= htmlspecialchars($s['opsi_c']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'D' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    D. <?= htmlspecialchars($s['opsi_d']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Hasil Pengerjaan Siswa -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm">Rekap Pengerjaan Siswa (<?= count($hasil) ?>)</h3>
                <span class="text-[11px] text-slate-400">Hasil Evaluasi Belajar</span>
            </div>
            <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                <?php if (empty($hasil)): ?>
                    <p class="text-xs text-slate-400 text-center py-8">Belum ada siswa yang mengerjakan kuis ini.</p>
                <?php else: ?>
                    <?php foreach ($hasil as $h): ?>
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-semibold text-slate-900"><?= htmlspecialchars($h['siswa_nama']) ?></h5>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($h['kelas'] ?? 'VII A') ?> • <?= formatDate($h['created_at'], 'd M Y, H:i') ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold <?= $h['skor'] >= $kuis['passing_grade'] ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <?= $h['skor'] ?>
                                </span>
                                <div class="flex items-center gap-1.5 justify-end mt-0.5">
                                    <span class="text-[10px] <?= $h['skor'] >= $kuis['passing_grade'] ? 'text-emerald-600 font-semibold' : 'text-rose-600' ?>">
                                        <?= $h['skor'] >= $kuis['passing_grade'] ? 'Lulus' : 'Remedial' ?>
                                    </span>
                                    <?php if ($h['skor'] >= $kuis['passing_grade']): ?>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <span class="text-[10px] <?= ($h['xp_earned'] ?? 0) > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' ?>">
                                            <?= ($h['xp_earned'] ?? 0) > 0 ? '+' . $h['xp_earned'] . ' XP' : '0 XP (Ulang)' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
