<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-gray-400">
        <a href="<?= base_url('siswa/dashboard') ?>" class="hover:text-primary-600">Beranda</a>
        <span>/</span>
        <span class="text-gray-600 font-semibold"><?= htmlspecialchars($materi['modul_judul'] ?? 'Modul') ?></span>
        <span>/</span>
        <span class="text-gray-900 font-bold"><?= htmlspecialchars($materi['judul']) ?></span>
    </div>

    <!-- Layout 2 Kolom: Konten Utama & Sidebar Bab -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Kolom Kiri: Konten Pembelajaran -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-3xl p-6 md:p-10 border border-gray-100 shadow-sm space-y-6">
                <!-- Header Bab -->
                <div class="border-b border-gray-100 pb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs rounded-xl">
                            Bab <?= $materi['urutan'] ?>
                        </span>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 mt-2"><?= htmlspecialchars($materi['judul']) ?></h1>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-3 py-1.5 bg-amber-50 text-amber-700 font-bold text-xs rounded-xl flex items-center gap-1.5">
                            ⭐ +<?= $materi['xp_reward'] ?> XP Reward
                        </span>
                    </div>
                </div>

                <!-- Konten Pembacaan (HTML Rich Text) -->
                <div class="prose max-w-none text-gray-700 leading-relaxed text-sm md:text-base space-y-4">
                    <?= $materi['konten'] ?>
                </div>

                <!-- Footer Navigasi & Tombol Selesai -->
                <div class="pt-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <?php if ($prevId): ?>
                            <a href="<?= base_url('siswa/materi/' . $prevId) ?>"
                               class="px-5 py-2.5 bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold rounded-2xl text-xs transition flex items-center gap-2">
                                <i class="fas fa-arrow-left"></i> Bab Sebelumnya
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-3">
                        <?php if ($sudahBaca): ?>
                            <span class="px-4 py-2.5 bg-emerald-50 text-emerald-700 font-bold rounded-2xl text-xs flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> Selesai Dibaca (+<?= $materi['xp_reward'] ?> XP)
                            </span>
                        <?php else: ?>
                            <span class="px-4 py-2.5 bg-indigo-50 text-indigo-700 font-bold rounded-2xl text-xs flex items-center gap-2">
                                <i class="fas fa-star text-amber-500"></i> XP Berhasil Diklaim!
                            </span>
                        <?php endif; ?>

                        <?php if ($nextId): ?>
                            <a href="<?= base_url('siswa/materi/' . $nextId) ?>"
                               class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl text-xs transition shadow-md flex items-center gap-2">
                                Bab Berikutnya <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('siswa/simulasi') ?>"
                               class="px-5 py-2.5 bg-gradient-to-r from-amber-400 to-orange-500 text-gray-900 font-black rounded-2xl text-xs transition shadow-md flex items-center gap-2">
                                Lanjut Praktik Simulasi <i class="fas fa-play"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Silabus Navigasi Modul -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-extrabold text-gray-800 text-sm uppercase tracking-wider">Bab Materi dalam Modul</h3>

                <div class="space-y-2">
                    <?php foreach ($allMateri as $am): ?>
                        <a href="<?= base_url('siswa/materi/' . $am['id']) ?>"
                           class="p-3 rounded-2xl border transition flex items-center justify-between text-xs font-bold <?= $am['id'] == $materi['id'] ? 'bg-indigo-50 border-primary-300 text-primary-700' : 'bg-gray-50/60 border-gray-100 text-gray-700 hover:bg-gray-100' ?>">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-white flex items-center justify-center text-[11px] font-black <?= $am['id'] == $materi['id'] ? 'text-primary-600' : 'text-gray-400' ?>">
                                    <?= $am['urutan'] ?>
                                </span>
                                <span class="line-clamp-1"><?= htmlspecialchars($am['judul']) ?></span>
                            </div>
                            <?php if (!empty($am['sudah_selesai'])): ?>
                                <i class="fas fa-check-circle text-emerald-500 text-sm flex-shrink-0"></i>
                            <?php else: ?>
                                <i class="far fa-circle text-gray-300 text-sm flex-shrink-0"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <a href="<?= base_url('siswa/kuis') ?>" class="w-full py-2.5 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold rounded-xl text-xs text-center block transition">
                        Uji Pemahaman di Kuis →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
