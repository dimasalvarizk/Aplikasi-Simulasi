<div class="space-y-8">
    <!-- Hero Gamification Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-primary-600 via-indigo-600 to-secondary-600 rounded-3xl p-6 md:p-8 text-white shadow-xl">
        <!-- Background Orbs -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-40 top-0 w-32 h-32 bg-amber-400/20 rounded-full blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold tracking-wide">
                    <span>🔥 Streak Aktif: <?= $streak ?? 1 ?> Hari Berturut-turut</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-black">Halo, <?= htmlspecialchars(getUser()['nama'] ?? 'Siswa Teladan') ?>! 🚀</h1>
                <p class="text-white/80 text-sm max-w-xl">
                    Jadilah arsitek jaringan andal! Selesaikan modul pembelajaran, rangkai topologi di canvas interaktif, dan kumpulkan poin XP untuk naik level.
                </p>
            </div>

            <!-- Level & XP Badge Centerpiece -->
            <div class="flex-shrink-0 bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-3xl text-center min-w-[220px]">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-400 to-orange-500 text-white text-2xl font-black shadow-lg mb-2">
                    <?= $level ?>
                </div>
                <h3 class="font-extrabold text-base text-white"><?= $levelTitle ?></h3>
                <p class="text-xs text-amber-300 font-bold mt-0.5">⭐ <?= number_format($xp) ?> XP Terkumpul</p>

                <!-- XP Bar -->
                <div class="mt-3">
                    <div class="flex justify-between text-[11px] text-white/80 mb-1 font-semibold">
                        <span>Menuju Lvl <?= $level + 1 ?></span>
                        <span><?= $xpProgress ?>%</span>
                    </div>
                    <div class="h-2.5 bg-black/20 rounded-full overflow-hidden p-0.5">
                        <div class="h-full bg-gradient-to-r from-amber-300 to-amber-500 rounded-full xp-bar"
                             style="width: <?= $xpProgress ?>%"></div>
                    </div>
                    <p class="text-[10px] text-white/60 mt-1">Butuh <?= max(0, $xpNextLevel - $xp) ?> XP lagi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm card-hover flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-primary-600 text-xl font-bold">
                ⭐
            </div>
            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Total XP</span>
                <h4 class="text-xl font-black text-gray-800"><?= number_format($xp) ?></h4>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm card-hover flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-600 text-xl font-bold">
                🏆
            </div>
            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Badges</span>
                <h4 class="text-xl font-black text-gray-800"><?= count($badges) ?> Diraih</h4>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm card-hover flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl font-bold">
                🖧
            </div>
            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Simulasi</span>
                <h4 class="text-xl font-black text-gray-800">3 Tantangan</h4>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm card-hover flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 text-xl font-bold">
                🔥
            </div>
            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Streak</span>
                <h4 class="text-xl font-black text-gray-800"><?= $streak ?? 1 ?> Hari</h4>
            </div>
        </div>
    </div>

    <!-- 2 Column: Modul Silabus & Quick Sandbox / Leaderboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kolom Kiri: Modul Pembelajaran Interaktif -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-gray-800">Silabus Materi Topologi</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Pilih modul pembelajaran untuk membaca teori dan klaim reward XP</p>
                </div>
            </div>

            <div class="space-y-4">
                <?php if (empty($modul)): ?>
                    <div class="bg-white rounded-3xl p-8 text-center text-gray-400 text-sm border border-gray-100">
                        Belum ada modul yang dibuka oleh guru.
                    </div>
                <?php else: ?>
                    <?php foreach ($modul as $m): ?>
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm card-hover flex flex-col md:flex-row md:items-center justify-between gap-5">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-primary-500 to-indigo-600 flex items-center justify-center text-white text-xl font-black flex-shrink-0 shadow-md">
                                    #<?= $m['urutan'] ?>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="font-extrabold text-gray-800 text-base"><?= htmlspecialchars($m['judul']) ?></h3>
                                    <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed"><?= htmlspecialchars($m['deskripsi'] ?? '') ?></p>
                                    <div class="flex items-center gap-3 pt-1 text-xs text-gray-400">
                                        <span><i class="fas fa-book-reader text-primary-500 mr-1"></i> <?= $m['jumlah_materi'] ?> Bab Pembelajaran</span>
                                        <span>•</span>
                                        <span class="text-emerald-600 font-semibold"><?= $m['progress_pct'] ?>% Selesai</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0 pt-2 md:pt-0">
                                <a href="<?= base_url('siswa/materi?modul_id=' . $m['id']) ?>"
                                   class="w-full md:w-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                                    Buka Materi <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Card Banner Canvas Simulasi -->
            <div class="relative overflow-hidden bg-gradient-to-br from-gray-900 to-indigo-950 rounded-3xl p-6 md:p-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 border border-indigo-800/40">
                <div class="space-y-2">
                    <span class="px-3 py-1 bg-indigo-500/30 text-indigo-300 text-xs font-bold rounded-full">✨ Fitur Unggulan</span>
                    <h3 class="text-xl font-black">Canvas Simulasi Topologi Interaktif</h3>
                    <p class="text-xs text-gray-300 max-w-md leading-relaxed">
                        Rangkai perangkat Router, Switch, Hub, dan PC menggunakan kabel koneksi secara drag-and-drop. Uji apakah topologi Star, Bus, atau Ring yang kamu susun sudah valid!
                    </p>
                </div>
                <a href="<?= base_url('siswa/simulasi') ?>"
                   class="px-6 py-3 bg-gradient-to-r from-amber-400 to-orange-500 text-gray-900 font-black rounded-2xl hover:opacity-95 transition shadow-lg text-sm flex-shrink-0 flex items-center gap-2">
                    <i class="fas fa-play"></i> Mulai Simulasi
                </a>
            </div>
        </div>

        <!-- Kolom Kanan: Badges & Leaderboard Mini -->
        <div class="space-y-6">
            <!-- Badges Koleksi -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-gray-800 text-sm uppercase tracking-wider">Lencana Koleksi</h3>
                    <a href="<?= base_url('siswa/profil') ?>" class="text-xs font-bold text-primary-600 hover:underline">Semua</a>
                </div>

                <?php if (empty($badges)): ?>
                    <p class="text-xs text-gray-400 text-center py-6">Selesaikan materi untuk meraih lencana pertamamu!</p>
                <?php else: ?>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ($badges as $b): ?>
                            <div class="p-3 bg-gray-50 rounded-2xl text-center border border-gray-100 hover:border-primary-200 transition" title="<?= htmlspecialchars($b['deskripsi'] ?? '') ?>">
                                <span class="text-3xl block mb-1"><?= $b['icon'] ?? '🎖️' ?></span>
                                <span class="text-[10px] font-bold text-gray-700 block truncate"><?= htmlspecialchars($b['nama']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mini Leaderboard -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-gray-800 text-sm uppercase tracking-wider">Papan Peringkat</h3>
                    <a href="<?= base_url('siswa/leaderboard') ?>" class="text-xs font-bold text-primary-600 hover:underline">Lihat Semua</a>
                </div>

                <div class="divide-y divide-gray-100">
                    <?php if (empty($topSiswa)): ?>
                        <p class="text-xs text-gray-400 text-center py-6">Belum ada data peringkat.</p>
                    <?php else: ?>
                        <?php foreach ($topSiswa as $rank => $ts): ?>
                            <div class="py-3 flex items-center justify-between <?= ($ts['id'] == (getUser()['id'] ?? 0)) ? 'bg-indigo-50/60 -mx-3 px-3 rounded-2xl' : '' ?>">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 text-center text-xs font-black <?= $rank === 0 ? 'text-amber-500 text-base' : ($rank === 1 ? 'text-gray-400 text-sm' : ($rank === 2 ? 'text-amber-700' : 'text-gray-400')) ?>">
                                        <?= $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : '#' . ($rank + 1))) ?>
                                    </span>
                                    <div>
                                        <h5 class="text-xs font-bold text-gray-800"><?= htmlspecialchars($ts['nama']) ?></h5>
                                        <span class="text-[10px] text-gray-400"><?= htmlspecialchars($ts['kelas'] ?? 'Siswa') ?></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-amber-500">⭐ <?= number_format($ts['total_xp']) ?></span>
                                    <span class="text-[10px] text-gray-400 block">Lvl <?= $ts['level'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
