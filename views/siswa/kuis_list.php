<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Kuis & Uji Pemahaman Topologi</h1>
            <p class="text-sm text-gray-500 mt-1">Selesaikan kuis pilihan ganda sebelum waktu habis untuk membuktikan pemahamanmu dan raih poin XP!</p>
        </div>
    </div>

    <!-- Grid Kuis -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($kuisList)): ?>
            <div class="col-span-full bg-white rounded-3xl p-12 text-center text-gray-400 border border-gray-100">
                <i class="fas fa-clipboard-check text-5xl mb-3 text-gray-300 block"></i>
                <p>Belum ada kuis yang tersedia saat ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($kuisList as $k): ?>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm card-hover flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs rounded-xl">
                                <?= htmlspecialchars($k['modul_judul'] ?? 'Modul Jaringan') ?>
                            </span>
                            <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 font-extrabold text-xs rounded-lg">
                                ⭐ +<?= $k['xp_reward'] ?> XP
                            </span>
                        </div>

                        <h3 class="font-extrabold text-gray-800 text-base line-clamp-2"><?= htmlspecialchars($k['judul']) ?></h3>
                        <p class="text-xs text-gray-500 mt-2 line-clamp-2 leading-relaxed"><?= htmlspecialchars($k['deskripsi'] ?? 'Uji pemahaman topik ini.') ?></p>

                        <!-- Parameter Kuis -->
                        <div class="grid grid-cols-2 gap-3 my-5 p-3.5 bg-gray-50 rounded-2xl text-center">
                            <div>
                                <span class="text-[10px] text-gray-400 font-semibold block uppercase">Waktu Ujian</span>
                                <span class="text-xs font-black text-gray-800">⏱️ <?= $k['durasi_menit'] ?> Menit</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 font-semibold block uppercase">Passing Grade</span>
                                <span class="text-xs font-black text-primary-600">🎯 <?= $k['passing_grade'] ?>%</span>
                            </div>
                        </div>

                        <!-- Status Percobaan -->
                        <?php if ($k['total_attempt'] > 0): ?>
                            <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-800 text-xs font-bold flex items-center justify-between mb-4">
                                <span>Nilai Tertinggi:</span>
                                <span class="text-sm font-black"><?= $k['skor_tertinggi'] ?> / 100</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <a href="<?= base_url('siswa/kuis/' . $k['id']) ?>"
                           class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-play"></i> <?= $k['total_attempt'] > 0 ? 'Kerjakan Ulang Kuis' : 'Mulai Kerjakan Kuis' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
