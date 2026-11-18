<?php
$avatarSrc = !empty($siswa['avatar']) 
    ? (str_starts_with($siswa['avatar'], 'http') ? $siswa['avatar'] : base_url($siswa['avatar']))
    : null;

$presetAvatars = [
    ['file' => 'avatar_1.png', 'nama' => 'Siswa IT'],
    ['file' => 'avatar_2.png', 'nama' => 'Siswi IT'],
    ['file' => 'avatar_3.png', 'nama' => 'Cendekia'],
    ['file' => 'avatar_4.png', 'nama' => 'Kreatif'],
    ['file' => 'avatar_5.png', 'nama' => 'Teknisi LAN'],
    ['file' => 'avatar_6.png', 'nama' => 'Bot NetSim'],
    ['file' => 'avatar_7.png', 'nama' => 'Cyber Gamer'],
    ['file' => 'avatar_8.png', 'nama' => 'Network Master'],
    ['file' => 'avatar_9.png', 'nama' => 'Spesialis LAN'],
    ['file' => 'avatar_10.png', 'nama' => 'Topologi Pro'],
    ['file' => 'avatar_11.png', 'nama' => 'Arsitek Jaringan'],
    ['file' => 'avatar_12.png', 'nama' => 'Juara NetSim'],
];
?>

<div class="max-w-4xl mx-auto space-y-8" x-data="{ modalAvatar: false, modalPassword: false, tabAvatar: 'upload', previewUrl: null, selectedPreset: '' }">

    <!-- Profile Header Card -->
    <div class="bg-gradient-to-r from-primary-600 via-indigo-600 to-secondary-600 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <!-- Avatar with Camera Overlay -->
                <div class="relative group flex-shrink-0">
                    <div class="w-20 h-20 rounded-3xl overflow-hidden bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white text-3xl font-black shadow-lg">
                        <?php if ($avatarSrc): ?>
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="<?= htmlspecialchars($siswa['nama']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= strtoupper(substr($siswa['nama'] ?? 'S', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" @click="modalAvatar = true" 
                            class="absolute -bottom-1 -right-1 bg-white text-slate-800 hover:text-primary-600 rounded-full w-7 h-7 flex items-center justify-center shadow-lg transition-transform hover:scale-110 text-xs focus:outline-none" 
                            title="Unggah Foto Profil">
                        <i class="fas fa-camera text-indigo-600"></i>
                    </button>
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black"><?= htmlspecialchars($siswa['nama'] ?? '') ?></h1>
                        <span class="px-2.5 py-0.5 bg-white/20 text-white text-xs font-bold rounded-lg"><?= htmlspecialchars($siswa['kelas'] ?? 'Siswa') ?></span>
                    </div>
                    <p class="text-xs text-white/80 mt-1">NISN: <?= htmlspecialchars($siswa['nisn'] ?? '-') ?> • @<?= htmlspecialchars($siswa['username'] ?? '') ?></p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-amber-300 font-bold">
                        <span>⭐ <?= number_format($siswa['total_xp']) ?> Total XP</span>
                        <span>•</span>
                        <span>🔥 <?= $siswa['streak_days'] ?? 1 ?> Hari Streak</span>
                        <span>•</span>
                        <button type="button" @click="modalAvatar = true" class="underline text-white hover:text-amber-200 transition text-[11px] font-semibold cursor-pointer">
                            <i class="fas fa-image mr-1"></i> Ganti Foto Profil
                        </button>
                        <span>•</span>
                        <button type="button" @click="modalPassword = true" class="underline text-white hover:text-amber-200 transition text-[11px] font-semibold cursor-pointer">
                            <i class="fas fa-key mr-1"></i> Ganti Kata Sandi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Big Level Box -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center min-w-[160px]">
                <span class="text-[10px] text-white/70 uppercase font-bold tracking-wider">Level Saat Ini</span>
                <h3 class="text-3xl font-black text-amber-300 mt-0.5">Lvl <?= $siswa['level'] ?></h3>
                <p class="text-xs text-white/90 font-medium"><?= getLevelTitle($siswa['level']) ?></p>
            </div>
        </div>
    </div>

    <!-- 4 Achievement Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm text-center">
            <span class="text-3xl block mb-1">📝</span>
            <span class="text-xs text-gray-400 font-bold uppercase">Kuis Dikerjakan</span>
            <h4 class="text-xl font-black text-gray-800 mt-1"><?= $totalKuis ?></h4>
        </div>
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm text-center">
            <span class="text-3xl block mb-1">🎯</span>
            <span class="text-xs text-gray-400 font-bold uppercase">Rata-rata Skor</span>
            <h4 class="text-xl font-black text-primary-600 mt-1"><?= $rataSkor ?></h4>
        </div>
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm text-center">
            <span class="text-3xl block mb-1">📖</span>
            <span class="text-xs text-gray-400 font-bold uppercase">Bab Dibaca</span>
            <h4 class="text-xl font-black text-gray-800 mt-1"><?= $totalMateriSelesai ?></h4>
        </div>
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm text-center">
            <span class="text-3xl block mb-1">🖧</span>
            <span class="text-xs text-gray-400 font-bold uppercase">Simulasi Lulus</span>
            <h4 class="text-xl font-black text-emerald-600 mt-1"><?= $totalSimulasiSelesai ?></h4>
        </div>
    </div>

    <!-- Koleksi Badges Lengkap -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-6">
        <div>
            <h3 class="text-lg font-black text-gray-800">Koleksi Lencana Gamifikasi</h3>
            <p class="text-xs text-gray-400 mt-0.5">Lencana diperoleh dengan mengumpulkan XP, naik level, dan menyelesaikan simulasi topologi.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <?php foreach ($badges as $b): ?>
                <div class="p-4 rounded-3xl border text-center transition <?= $b['diraih'] ? 'bg-gradient-to-b from-indigo-50/50 to-white border-primary-200 shadow-sm' : 'bg-gray-50/50 border-gray-100 opacity-50 grayscale' ?>">
                    <div class="text-4xl mb-2"><?= $b['icon'] ?? '🎖️' ?></div>
                    <h5 class="text-xs font-black text-gray-800"><?= htmlspecialchars($b['nama']) ?></h5>
                    <p class="text-[10px] text-gray-500 mt-1 leading-tight line-clamp-2"><?= htmlspecialchars($b['deskripsi'] ?? '') ?></p>
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <?php if ($b['diraih']): ?>
                            <span class="text-[10px] font-bold text-emerald-600 flex items-center justify-center gap-1">
                                <i class="fas fa-check-circle"></i> Diraih
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-gray-400 flex items-center justify-center gap-1">
                                <i class="fas fa-lock"></i> Terkunci
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Riwayat Aktivitas & Perolehan XP -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-lg font-black text-gray-800">Riwayat Perolehan Poin XP</h3>
        <div class="divide-y divide-gray-100">
            <?php if (empty($riwayatXp)): ?>
                <p class="text-xs text-gray-400 text-center py-6">Belum ada aktivitas yang dicatat.</p>
            <?php else: ?>
                <?php foreach ($riwayatXp as $log): ?>
                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-black">
                                +
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-gray-800"><?= htmlspecialchars($log['keterangan'] ?? 'Aktivitas Gamifikasi') ?></h5>
                                <span class="text-[10px] text-gray-400"><?= formatDate($log['created_at'], 'd M Y, H:i') ?></span>
                            </div>
                        </div>
                        <span class="text-xs font-black text-amber-500">+<?= $log['xp_amount'] ?> XP</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Keamanan Akun & Pengaturan Kata Sandi -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-800">Keamanan Akun & Kata Sandi</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Perbarui kata sandi secara berkala atau ganti kata sandi sementara yang diberikan oleh Guru.</p>
                </div>
            </div>
            <button type="button" @click="modalPassword = true" 
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-2 flex-shrink-0">
                <i class="fas fa-key text-amber-400"></i> Ganti Kata Sandi
            </button>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-3 text-xs text-slate-600 leading-relaxed">
            <i class="fas fa-circle-info text-primary-500 mt-0.5 text-sm flex-shrink-0"></i>
            <div>
                <span class="font-bold text-slate-700">Menerima reset kata sandi dari Guru Pengampu?</span>
                <p class="mt-0.5 text-[11.5px] text-slate-500">Jika Guru Pengampu (Bpk. Angzal Maolana, S.Pd / Ibu Hana Sifakhatin, S.Pd) baru saja mereset akun Anda, masukkan kata sandi sementara tersebut pada kolom <em>Kata Sandi Saat Ini</em>, lalu tentukan kata sandi baru pribadi Anda.</p>
            </div>
        </div>
    </div>

    <!-- MODAL UNGGAH & PILIH FOTO PROFIL -->
    <div x-show="modalAvatar" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-transparent flex items-center justify-center p-4">
        <div @click.outside="modalAvatar = false" 
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-white border-b border-slate-100 text-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Ganti Foto Profil Siswa</h3>
                        <p class="text-[11px] text-slate-500">Pilih foto pribadi atau gunakan avatar animasi</p>
                    </div>
                </div>
                <button type="button" @click="modalAvatar = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-slate-100 bg-slate-50 p-2 gap-2">
                <button type="button" @click="tabAvatar = 'upload'" 
                        :class="tabAvatar === 'upload' ? 'bg-white text-indigo-600 font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-2 px-3 rounded-xl text-xs transition text-center">
                    <i class="fas fa-cloud-arrow-up mr-1.5"></i> Unggah File Foto
                </button>
                <button type="button" @click="tabAvatar = 'preset'" 
                        :class="tabAvatar === 'preset' ? 'bg-white text-indigo-600 font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-2 px-3 rounded-xl text-xs transition text-center">
                    <i class="fas fa-user-astronaut mr-1.5"></i> Pilih Avatar Keren
                </button>
            </div>

            <div class="p-6">
                <!-- FORM 1: UNGGAH FILE FOTO -->
                <form x-show="tabAvatar === 'upload'" method="POST" action="<?= base_url('siswa/uploadFoto') ?>" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                    <div class="text-center">
                        <!-- Preview Box -->
                        <div class="w-24 h-24 mx-auto rounded-3xl overflow-hidden border-2 border-dashed border-indigo-200 bg-indigo-50/50 flex items-center justify-center relative shadow-inner">
                            <template x-if="previewUrl">
                                <img :src="previewUrl" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!previewUrl">
                                <?php if ($avatarSrc): ?>
                                    <img src="<?= htmlspecialchars($avatarSrc) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-image text-3xl text-indigo-300"></i>
                                <?php endif; ?>
                            </template>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Pratinjau foto profil baru Anda</p>
                    </div>

                    <!-- File Input Box -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih File Foto (JPG, PNG, WEBP, maks 5MB)</label>
                        <input type="file" name="avatar" accept="image/*" required
                               @change="const file = $event.target.files[0]; if (file) { previewUrl = URL.createObjectURL(file); }"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-white">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="modalAvatar = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-2">
                            <i class="fas fa-check"></i> Simpan Foto Profil
                        </button>
                    </div>
                </form>

                <!-- FORM 2: PILIH AVATAR KOLEKSI -->
                <form x-show="tabAvatar === 'preset'" method="POST" action="<?= base_url('siswa/uploadFoto') ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                    <input type="hidden" name="preset_avatar" :value="selectedPreset">

                    <p class="text-xs text-slate-500">Pilih salah satu karakter avatar keren yang mewakili kepribadianmu:</p>

                    <div class="grid grid-cols-4 gap-3 max-h-64 overflow-y-auto p-1">
                        <?php foreach ($presetAvatars as $pa): ?>
                            <div @click="selectedPreset = '<?= $pa['file'] ?>'"
                                 :class="selectedPreset === '<?= $pa['file'] ?>' ? 'ring-2 ring-indigo-600 bg-indigo-50 border-indigo-400 scale-105' : 'border-slate-200 hover:bg-slate-50'"
                                 class="border rounded-2xl p-2 text-center cursor-pointer transition transform flex flex-col items-center">
                                <img src="<?= base_url('public/img/avatars/' . $pa['file']) ?>" alt="<?= $pa['nama'] ?>" class="w-12 h-12 rounded-xl object-contain">
                                <span class="text-[10px] font-bold text-slate-700 mt-1.5 truncate max-w-full"><?= $pa['nama'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <span class="text-[11px] text-slate-500 font-medium" x-text="selectedPreset ? 'Dipilih: ' + selectedPreset : 'Pilih salah satu avatar di atas'"></span>
                        <div class="flex gap-2">
                            <button type="button" @click="modalAvatar = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="!selectedPreset" 
                                    :class="selectedPreset ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                                    class="px-5 py-2.5 text-xs font-bold rounded-xl shadow-md transition flex items-center gap-2">
                                <i class="fas fa-check"></i> Terapkan Avatar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL GANTI KATA SANDI SISWA -->
    <div x-show="modalPassword" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-transparent flex items-center justify-center p-4">
        <div @click.outside="modalPassword = false" 
             class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-white border-b border-slate-100 text-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Ganti Kata Sandi Siswa</h3>
                        <p class="text-[11px] text-slate-500">Perbarui kata sandi akunmu</p>
                    </div>
                </div>
                <button type="button" @click="modalPassword = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form action="<?= base_url('siswa/update-password') ?>" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? generateToken()) ?>">

                <div class="p-3 bg-amber-50 border border-amber-200/70 rounded-2xl text-[11.5px] text-amber-900 flex items-start gap-2.5">
                    <i class="fas fa-shield-alt text-amber-600 mt-0.5 text-xs flex-shrink-0"></i>
                    <span>Masukkan kata sandi sementara yang diberikan Guru, lalu buat kata sandi baru minimal 6 karakter.</span>
                </div>

                <!-- Kata Sandi Lama / Sementara -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Saat Ini / Sementara <span class="text-rose-500">*</span></label>
                    <input type="password" name="current_password" required placeholder="Contoh: password123" 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition">
                    <span class="text-[10.5px] text-slate-400 mt-1 block">Kata sandi yang Anda gunakan saat login.</span>
                </div>

                <!-- Kata Sandi Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter" 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition">
                </div>

                <!-- Konfirmasi Kata Sandi Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ulangi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Ulangi kata sandi baru" 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition">
                </div>

                <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-slate-100">
                    <button type="button" @click="modalPassword = false" 
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i> Simpan Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
