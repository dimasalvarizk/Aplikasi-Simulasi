<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Modul Pembelajaran</h1>
            <p class="text-xs text-slate-500 mt-1">SMP NEGERI 1 KARANGMONCOL • Susun silabus pembelajaran topologi jaringan komputer ke dalam bab dan materi interaktif.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/modul/create') ?>" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i> Tambah Modul Baru
            </a>
        </div>
    </div>

    <!-- Modul Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php if (empty($modul)): ?>
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fas fa-folder-open"></i>
                </div>
                <p class="text-xs text-slate-600 font-medium">Belum ada modul pembelajaran yang dibuat.</p>
                <a href="<?= base_url('guru/modul/create') ?>" class="mt-4 inline-block px-4 py-2 bg-slate-900 text-white rounded-xl font-medium text-xs">
                    Buat Modul Pertama
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($modul as $m): ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200">
                                Modul #<?= $m['urutan'] ?>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-medium border <?= $m['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                                <?= $m['is_active'] ? 'Dipublikasikan' : 'Draft' ?>
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-900 text-sm line-clamp-1"><?= htmlspecialchars($m['judul']) ?></h3>
                        <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed"><?= htmlspecialchars($m['deskripsi'] ?? 'Materi silabus topologi jaringan.') ?></p>
                    </div>

                    <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">
                            <i class="fas fa-file-alt text-slate-400 mr-1 text-[11px]"></i> <?= $m['jumlah_materi'] ?> Bab Materi
                        </span>
                        <div class="flex items-center gap-1.5">
                            <a href="<?= base_url('guru/materi/' . $m['id']) ?>"
                               class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg transition text-xs font-medium"
                               title="Kelola Isi Materi">
                                Isi Materi
                            </a>
                            <a href="<?= base_url('guru/modul/edit/' . $m['id']) ?>"
                               class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg transition text-xs font-medium"
                               title="Edit Modul">
                                Edit
                            </a>
                            <button type="button"
                               onclick="confirmDelete('<?= base_url('guru/modul/delete/' . $m['id']) ?>', 'Hapus Modul Pembelajaran?', 'Apakah Anda yakin ingin menghapus modul &quot;<?= htmlspecialchars(addslashes($m['judul'])) ?>&quot; beserta seluruh materi di dalamnya? Tindakan ini tidak dapat dibatalkan.')"
                               class="px-2.5 py-1.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 rounded-lg transition text-xs font-medium"
                               title="Hapus Modul">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
