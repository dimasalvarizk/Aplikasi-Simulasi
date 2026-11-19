<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Bank Kuis & Evaluasi Topologi</h1>
            <p class="text-xs text-slate-500 mt-1">SMP NEGERI 1 KARANGMONCOL • Kelola paket ujian kuis pilihan ganda, passing grade, durasi, dan XP reward siswa.</p>
        </div>
        <div>
            <a href="<?= base_url('guru/kuis/create') ?>" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i> Buat Kuis Baru
            </a>
        </div>
    </div>

    <!-- Grid Kuis -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php if (empty($kuis)): ?>
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <p class="text-xs text-slate-600 font-medium">Belum ada paket kuis yang dibuat.</p>
                <a href="<?= base_url('guru/kuis/create') ?>" class="mt-4 inline-block px-4 py-2 bg-slate-900 text-white rounded-xl font-medium text-xs">
                    Buat Kuis Sekarang
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($kuis as $k): ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-medium text-[11px] rounded-lg border border-slate-200">
                                <?= htmlspecialchars($k['modul_judul'] ?? 'Modul Topologi') ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-medium border <?= $k['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                                <?= $k['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-900 text-sm line-clamp-1">
                            <a href="<?= base_url('guru/kuis/detail/' . $k['id']) ?>" class="hover:text-blue-600 transition" title="Buka Detail & Soal">
                                <?= htmlspecialchars($k['judul']) ?>
                            </a>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed"><?= htmlspecialchars($k['deskripsi'] ?? 'Evaluasi pemahaman konsep topologi jaringan.') ?></p>

                        <div class="grid grid-cols-3 gap-2 my-3.5 p-2.5 bg-slate-50 rounded-xl border border-slate-200/60 text-center text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Durasi</span>
                                <span class="text-xs font-bold text-slate-800"><?= $k['durasi_menit'] ?> Menit</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Passing</span>
                                <span class="text-xs font-bold text-blue-700"><?= $k['passing_grade'] ?>%</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Reward</span>
                                <span class="text-xs font-bold text-amber-800"><?= $k['xp_reward'] ?> XP</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a href="<?= base_url('guru/kuis/detail/' . $k['id']) ?>" class="text-xs text-slate-500 hover:text-slate-800 font-medium transition flex items-center" title="Buka Detail Soal & Rekap Nilai">
                            <i class="fas fa-question-circle text-slate-400 mr-1 text-[11px]"></i> <?= $k['jumlah_soal'] ?> Soal
                        </a>
                        <div class="flex items-center gap-1.5">
                            <a href="<?= base_url('guru/kuis/edit/' . $k['id']) ?>"
                               class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg transition text-xs font-medium flex items-center gap-1"
                               title="Edit Pengaturan Kuis">
                                <i class="fas fa-pencil-alt text-[10px]"></i> Edit
                            </a>
                            <button type="button"
                               onclick="confirmDelete('<?= base_url('guru/kuis/delete/' . $k['id']) ?>', 'Hapus Paket Kuis?', 'Apakah Anda yakin ingin menghapus paket kuis &quot;<?= htmlspecialchars(addslashes($k['judul'])) ?>&quot;? Seluruh butir soal dan riwayat nilai siswa pada kuis ini akan ikut terhapus.')"
                               class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-lg transition text-xs font-medium flex items-center gap-1"
                               title="Hapus Kuis">
                                <i class="fas fa-trash-alt text-[10px]"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
