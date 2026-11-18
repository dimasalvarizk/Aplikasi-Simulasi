<div class="space-y-6" x-data="{ openResetModal: false, newPassword: 'password123' }">
    <!-- Back Button -->
    <div>
        <a href="<?= base_url('guru/siswa') ?>" class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition">
            <i class="fas fa-arrow-left text-[10px]"></i>
            <span>Kembali ke Data Siswa</span>
        </a>
    </div>

    <!-- Student Profile Header Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                <?php if (!empty($siswa['avatar'])): ?>
                    <img src="<?= str_starts_with($siswa['avatar'], 'http') ? htmlspecialchars($siswa['avatar']) : base_url($siswa['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($siswa['nama']) ?></h1>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[11px] font-medium rounded-lg border border-slate-200">
                        <?= htmlspecialchars($siswa['kelas'] ?? 'VII A') ?>
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1 font-mono">
                    NISN: <?= htmlspecialchars($siswa['nisn'] ?? '-') ?> • @<?= htmlspecialchars($siswa['username']) ?> • <?= htmlspecialchars($siswa['email']) ?>
                </p>
                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                    <span><i class="fas fa-clock text-slate-400 mr-1 text-[11px]"></i> Terakhir login: <?= timeAgo($siswa['last_login'] ?? '') ?></span>
                    <span><i class="fas fa-calendar-check text-slate-400 mr-1 text-[11px]"></i> Streak: <?= $siswa['streak_days'] ?? 1 ?> Hari</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div class="text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Level</span>
                    <h4 class="text-xl font-bold text-slate-900">Lvl <?= $siswa['level'] ?></h4>
                    <p class="text-[11px] text-slate-500"><?= getLevelTitle($siswa['level']) ?></p>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Total XP</span>
                    <h4 class="text-xl font-bold text-amber-700"><?= number_format($siswa['total_xp']) ?> XP</h4>
                    <p class="text-[11px] text-slate-500">Poin Gamifikasi</p>
                </div>
            </div>
            <button type="button" @click="openResetModal = true"
                    class="p-4 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-xl text-xs font-semibold transition flex flex-col items-center justify-center gap-1 shadow-xs"
                    title="Reset Kata Sandi Siswa Ini">
                <i class="fas fa-key text-base text-amber-600"></i>
                <span class="text-[10px] font-bold whitespace-nowrap">Reset Sandi</span>
            </button>
        </div>
    </div>

    <!-- 3 Columns: Badges, Kuis History, Simulasi Progress -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Badges Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-sm">Lencana / Badges (<?= count($badges) ?>)</h3>
                <span class="text-[11px] text-slate-400">Pencapaian</span>
            </div>
            <?php if (empty($badges)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">Belum ada lencana yang diraih.</div>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-2.5">
                    <?php foreach ($badges as $b): ?>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-700 flex-shrink-0">
                                <i class="fas fa-medal text-amber-600"></i>
                            </div>
                            <div>
                                <h5 class="text-xs font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($b['nama']) ?></h5>
                                <p class="text-[10px] text-slate-400 mt-0.5"><?= formatDate($b['earned_at'], 'd M Y') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Riwayat Evaluasi Kuis -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-sm">Riwayat Kuis (<?= count($riwayatKuis) ?>)</h3>
                <span class="text-[11px] text-slate-400">Hasil Evaluasi</span>
            </div>
            <?php if (empty($riwayatKuis)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">Belum pernah mengerjakan kuis.</div>
            <?php else: ?>
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                    <?php foreach ($riwayatKuis as $k): ?>
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($k['kuis_judul']) ?></h5>
                                <span class="text-[10px] text-slate-400"><?= formatDate($k['created_at'], 'd M Y, H:i') ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold <?= $k['skor'] >= ($k['passing_grade'] ?? 70) ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <?= $k['skor'] ?>
                                </span>
                                <div class="flex items-center gap-1.5 justify-end mt-0.5">
                                    <span class="text-[10px] <?= $k['skor'] >= ($k['passing_grade'] ?? 70) ? 'text-emerald-600 font-semibold' : 'text-rose-600' ?>">
                                        <?= $k['skor'] >= ($k['passing_grade'] ?? 70) ? 'Lulus' : 'Remedial' ?>
                                    </span>
                                    <?php if ($k['skor'] >= ($k['passing_grade'] ?? 70)): ?>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <span class="text-[10px] <?= ($k['xp_earned'] ?? 0) > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' ?>">
                                            <?= ($k['xp_earned'] ?? 0) > 0 ? '+' . $k['xp_earned'] . ' XP' : '0 XP (Ulang)' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Riwayat Tantangan Simulasi -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-sm">Simulasi Topologi (<?= count($riwayatSimulasi) ?>)</h3>
                <span class="text-[11px] text-slate-400">Praktek Canvas</span>
            </div>
            <?php if (empty($riwayatSimulasi)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">Belum ada aktivitas simulasi.</div>
            <?php else: ?>
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                    <?php foreach ($riwayatSimulasi as $sim): ?>
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($sim['challenge_judul']) ?></h5>
                                <span class="text-[10px] text-slate-400">Topologi <?= ucfirst($sim['tipe_topologi']) ?> • <?= timeAgo($sim['created_at']) ?></span>
                            </div>
                            <div>
                                <?php if ($sim['is_correct']): ?>
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[10px] font-medium rounded-lg">Berhasil</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200/60 text-[10px] font-medium rounded-lg">Perlu Revisi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Reset Kata Sandi Siswa Detail -->
    <div x-show="openResetModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden"
             @click.away="openResetModal = false">

            <div class="px-6 py-4 border-b border-slate-100 bg-amber-50/70 flex items-center justify-between">
                <div class="flex items-center gap-2.5 text-amber-900">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                        <i class="fas fa-key text-xs"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold">Reset Kata Sandi Siswa</h3>
                        <p class="text-[11px] text-amber-700/80">Otoritas Pengelola Pembelajaran</p>
                    </div>
                </div>
                <button type="button" @click="openResetModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form action="<?= base_url('guru/siswa/resetPassword') ?>" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? generateToken() ?>">
                <input type="hidden" name="id" value="<?= (int)$siswa['id'] ?>">

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                    <div class="font-bold text-slate-800"><?= htmlspecialchars($siswa['nama']) ?></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Username: <span class="font-mono text-slate-700">@<?= htmlspecialchars($siswa['username']) ?></span> • Kelas: <?= htmlspecialchars($siswa['kelas'] ?? 'VII A') ?></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kata Sandi Baru Sementara <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="new_password" required minlength="6"
                           x-model="newPassword"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:bg-white transition">
                    <span class="text-[10px] text-slate-500 mt-1.5 block leading-relaxed">
                        Berikan kata sandi sementara ini kepada siswa. Siswa dapat login kembali dan menggantinya di menu Profil Siswa.
                    </span>
                </div>

                <!-- Template Kata Sandi Cepat -->
                <div class="flex items-center gap-1.5 pt-1">
                    <span class="text-[10px] text-slate-400">Pilihan Cepat:</span>
                    <button type="button" @click="newPassword = 'password123'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">password123</button>
                    <button type="button" @click="newPassword = 'smpn1#123'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">smpn1#123</button>
                    <button type="button" @click="newPassword = 'siswa' + Math.floor(100 + Math.random() * 900)" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">Acak</button>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openResetModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
                        <i class="fas fa-check text-xs"></i>
                        <span>Simpan Kata Sandi Siswa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
