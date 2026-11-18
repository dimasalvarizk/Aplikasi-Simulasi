<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="<?= base_url('guru/modul') ?>" class="hover:text-slate-900 transition">Daftar Modul</a>
                <span>/</span>
                <span class="text-slate-700 font-semibold"><?= htmlspecialchars($modul['judul']) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Bab Materi Pembelajaran</h1>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Siswa membaca bab-bab materi ini dan memperoleh poin XP setiap kali menyelesaikan satu bab.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/modul') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-medium rounded-xl text-xs transition">
                <i class="fas fa-arrow-left text-[10px] mr-1"></i> Kembali
            </a>
            <a href="<?= base_url('guru/materi/create/' . $modul['id']) ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i> Tambah Bab Materi
            </a>
        </div>
    </div>

    <!-- Daftar Bab Materi -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Daftar Bab dalam <?= htmlspecialchars($modul['judul']) ?></h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Seluruh bab di bawah ini dapat diakses dan dipelajari langsung oleh siswa</p>
            </div>
            <span class="text-xs text-slate-500 font-medium">Total: <?= count($materi) ?> Bab</span>
        </div>

        <div class="divide-y divide-slate-100">
            <?php if (empty($materi)): ?>
                <div class="p-12 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <p class="text-xs text-slate-600 font-medium">Belum ada materi dalam modul ini.</p>
                    <a href="<?= base_url('guru/materi/create/' . $modul['id']) ?>" class="mt-4 inline-block px-4 py-2 bg-slate-900 text-white rounded-xl font-medium text-xs">
                        Buat Bab Pertama
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($materi as $m): ?>
                    <div class="p-4 sm:p-5 flex items-center justify-between hover:bg-slate-50/60 transition">
                        <div class="flex items-center gap-3.5">
                            <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs border border-slate-200 flex-shrink-0">
                                <?= $m['urutan'] ?>
                            </span>
                            <div>
                                <h4 class="font-bold text-slate-900 text-xs sm:text-sm"><?= htmlspecialchars($m['judul']) ?></h4>
                                <div class="flex items-center gap-2.5 mt-1 text-[11px] text-slate-400">
                                    <span class="text-amber-800 font-medium bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60">+<?= $m['xp_reward'] ?> XP Reward</span>
                                    <span>•</span>
                                    <span><?= formatDate($m['created_at'], 'd M Y') ?></span>
                                    <span>•</span>
                                    <span class="font-medium <?= $m['is_active'] ? 'text-emerald-700' : 'text-slate-400' ?>">
                                        <?= $m['is_active'] ? 'Aktif' : 'Draft' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <a href="<?= base_url('guru/materi/edit/' . $m['id']) ?>"
                               class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg transition text-xs font-medium flex items-center gap-1.5">
                                Edit
                            </a>
                            <button type="button"
                               onclick="confirmDelete('<?= base_url('guru/materi/delete/' . $m['id']) ?>', 'Hapus Bab Materi?', 'Apakah Anda yakin ingin menghapus bab materi &quot;<?= htmlspecialchars(addslashes($m['judul'])) ?>&quot;? Tindakan ini tidak dapat dibatalkan.')"
                               class="px-2.5 py-1.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 rounded-lg transition text-xs font-medium"
                               title="Hapus">
                                Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
