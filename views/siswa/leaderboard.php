<div class="max-w-3xl mx-auto space-y-8">
    <!-- Header -->
    <div class="text-center space-y-2">
        <span class="px-3.5 py-1 bg-amber-50 text-amber-700 font-extrabold text-xs rounded-full uppercase tracking-wider">
            🏆 Papan Peringkat
        </span>
        <h1 class="text-3xl font-black text-gray-800">Leaderboard Pembelajaran</h1>
        <p class="text-xs text-gray-500 max-w-md mx-auto">Siswa paling aktif dalam membaca materi, menguasai simulasi topologi, dan meraih skor kuis tertinggi.</p>
    </div>

    <!-- Filter Kelas -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
        <form method="GET" action="<?= base_url('siswa/leaderboard') ?>" class="flex items-center gap-3 w-full">
            <label class="text-xs font-bold text-gray-500 uppercase">Filter Kelas:</label>
            <select name="kelas" onchange="this.form.submit()"
                    class="px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelasList as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $currentKelas === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Podium Top 3 -->
    <?php if (count($leaderboard) >= 3): ?>
        <div class="grid grid-cols-3 gap-3 items-end pt-6 pb-2 text-center">
            <!-- Juara 2 -->
            <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm order-1 flex flex-col items-center">
                <span class="text-3xl mb-1">🥈</span>
                <div class="w-12 h-12 rounded-2xl bg-slate-200 flex items-center justify-center text-slate-700 font-black text-sm mb-2">
                    <?= strtoupper(substr($leaderboard[1]['nama'], 0, 1)) ?>
                </div>
                <h4 class="font-extrabold text-gray-800 text-xs line-clamp-1"><?= htmlspecialchars($leaderboard[1]['nama']) ?></h4>
                <span class="text-[10px] text-gray-400"><?= htmlspecialchars($leaderboard[1]['kelas'] ?? '-') ?></span>
                <span class="mt-2 text-xs font-black text-amber-500">⭐ <?= number_format($leaderboard[1]['total_xp']) ?> XP</span>
            </div>

            <!-- Juara 1 (Lebih Tinggi & Menonjol) -->
            <div class="bg-gradient-to-b from-amber-50 to-white rounded-3xl p-6 border-2 border-amber-300 shadow-xl order-2 flex flex-col items-center relative -translate-y-4">
                <span class="text-4xl mb-1 animate-bounce">👑</span>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-400 to-orange-500 flex items-center justify-center text-white font-black text-xl mb-2 shadow-lg">
                    <?= strtoupper(substr($leaderboard[0]['nama'], 0, 1)) ?>
                </div>
                <h4 class="font-black text-gray-900 text-sm line-clamp-1"><?= htmlspecialchars($leaderboard[0]['nama']) ?></h4>
                <span class="text-[11px] text-gray-500 font-semibold"><?= htmlspecialchars($leaderboard[0]['kelas'] ?? '-') ?></span>
                <span class="mt-2 px-3 py-1 bg-amber-400 text-amber-950 text-xs font-black rounded-full shadow-sm">
                    ⭐ <?= number_format($leaderboard[0]['total_xp']) ?> XP
                </span>
            </div>

            <!-- Juara 3 -->
            <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm order-3 flex flex-col items-center">
                <span class="text-3xl mb-1">🥉</span>
                <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-800 font-black text-sm mb-2">
                    <?= strtoupper(substr($leaderboard[2]['nama'], 0, 1)) ?>
                </div>
                <h4 class="font-extrabold text-gray-800 text-xs line-clamp-1"><?= htmlspecialchars($leaderboard[2]['nama']) ?></h4>
                <span class="text-[10px] text-gray-400"><?= htmlspecialchars($leaderboard[2]['kelas'] ?? '-') ?></span>
                <span class="mt-2 text-xs font-black text-amber-500">⭐ <?= number_format($leaderboard[2]['total_xp']) ?> XP</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- List Tabel Peringkat -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100">
            <?php if (empty($leaderboard)): ?>
                <div class="p-8 text-center text-gray-400 text-xs">Belum ada data peringkat.</div>
            <?php else: ?>
                <?php foreach ($leaderboard as $rank => $s): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50/70 transition <?= $s['id'] == $currentUserId ? 'bg-indigo-50/70' : '' ?>">
                        <div class="flex items-center gap-4">
                            <span class="w-8 text-center font-black text-xs <?= $rank === 0 ? 'text-amber-500 text-sm' : ($rank === 1 ? 'text-gray-400 text-sm' : ($rank === 2 ? 'text-amber-700 text-sm' : 'text-gray-400')) ?>">
                                <?= $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : '#' . ($rank + 1))) ?>
                            </span>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                    <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-xs font-bold text-gray-800"><?= htmlspecialchars($s['nama']) ?></h5>
                                        <?php if ($s['id'] == $currentUserId): ?>
                                            <span class="px-2 py-0.5 bg-primary-600 text-white text-[9px] font-black rounded">Kamu</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[10px] text-gray-400"><?= htmlspecialchars($s['kelas'] ?? 'Siswa') ?> • Lvl <?= $s['level'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded-lg hidden sm:inline-block">
                                🏆 <?= $s['total_badges'] ?> Badges
                            </span>
                            <span class="text-xs font-black text-amber-500">⭐ <?= number_format($s['total_xp']) ?> XP</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
