<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Tantangan Simulasi Topologi Jaringan</h1>
            <p class="text-sm text-gray-500 mt-1">Uji kemampuan praktis merangkai arsitektur topologi jaringan komputer secara visual di canvas drag-and-drop.</p>
        </div>
    </div>

    <!-- Grid Tantangan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($challenges)): ?>
            <div class="col-span-full bg-white rounded-3xl p-12 text-center text-gray-400 border border-gray-100">
                <i class="fas fa-network-wired text-5xl mb-3 text-gray-300 block"></i>
                <p>Belum ada tantangan simulasi aktif.</p>
            </div>
        <?php else: ?>
            <?php foreach ($challenges as $c): ?>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm card-hover flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-extrabold text-xs rounded-xl uppercase tracking-wider">
                                Topologi <?= ucfirst($c['tipe_topologi']) ?>
                            </span>
                            <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 font-extrabold text-xs rounded-lg">
                                ⭐ +<?= $c['xp_reward'] ?> XP
                            </span>
                        </div>

                        <h3 class="font-extrabold text-gray-800 text-lg line-clamp-2"><?= htmlspecialchars($c['judul']) ?></h3>
                        <p class="text-xs text-gray-500 mt-2 line-clamp-3 leading-relaxed"><?= htmlspecialchars($c['deskripsi'] ?? 'Rangkai topologi ini sesuai spesifikasi.') ?></p>

                        <!-- Status Lulus -->
                        <?php if (!empty($c['sudah_lulus'])): ?>
                            <div class="mt-4 p-3 rounded-2xl bg-emerald-50 text-emerald-800 text-xs font-bold flex items-center justify-between">
                                <span>Status Tantangan:</span>
                                <span>✅ Berhasil Diselesaikan</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="<?= base_url('siswa/simulasi/' . $c['id']) ?>"
                           class="w-full py-3 bg-gradient-to-r from-primary-600 to-indigo-600 hover:opacity-95 text-white font-extrabold rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-play"></i> Buka Canvas Simulasi
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
