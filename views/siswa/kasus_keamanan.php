<div class="space-y-6" x-data="siswaKasusApp()">
    <!-- Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-semibold mb-3 border border-indigo-500/30">
                <i class="fas fa-shield-halved"></i> Tantangan Analisis Keamanan Siber
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Detektif Siber: Uji Kewaspadaanmu! 🕵️‍♂️</h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-300 leading-relaxed">
                Kamu akan menerima berbagai pesan mencurigakan di WhatsApp, SMS, dan Email. Tugasmu adalah menganalisis setiap pesan: apakah pesan tersebut <b>AMAN</b> atau <b>BERBAHAYA (Phishing/Malware)</b>? Gunakan fitur inspeksi tautan untuk menemukan jebakannya!
            </p>
            
            <!-- Quick Progress Stats -->
            <div class="mt-5 flex flex-wrap items-center gap-4 text-xs font-medium">
                <div class="flex items-center gap-2 bg-white/10 px-3.5 py-1.5 rounded-xl backdrop-blur-sm">
                    <i class="fas fa-bullseye text-indigo-400"></i>
                    <span>Terselesaikan: <b class="text-white"><?= $totalSelesai ?> / <?= $totalKasus ?></b></span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 px-3.5 py-1.5 rounded-xl backdrop-blur-sm">
                    <i class="fas fa-star text-amber-400"></i>
                    <span>XP Diraih: <b class="text-amber-300">+<?= $totalXpDidapat ?> XP</b></span>
                </div>
                <?php if ($hasBadge): ?>
                    <div class="flex items-center gap-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-3.5 py-1.5 rounded-xl">
                        <span>🕵️‍♂️ Lencana Detektif Siber Aktif!</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Alert Flash -->
    <?php if (!empty($flash['success'])): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2.5">
            <i class="fas fa-check-circle text-emerald-600 text-base"></i>
            <span><?= htmlspecialchars($flash['success']) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2.5">
            <i class="fas fa-triangle-exclamation text-rose-600 text-base"></i>
            <span><?= htmlspecialchars($flash['error']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Kasus Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($kasusList as $index => $k): 
            $isDone = !empty($k['sudah_dikerjakan']);
            $isCorrect = !empty($k['is_correct']);
            $plat = strtolower($k['platform'] ?? 'whatsapp');

            $headerBg = 'bg-slate-800';
            $platLabel = 'Pesan Masuk';
            $platIcon = 'fa-comment';
            if ($plat === 'whatsapp') {
                $headerBg = 'bg-[#075E54]';
                $platLabel = 'WhatsApp Chat';
                $platIcon = 'fa-brands fa-whatsapp';
            } elseif ($plat === 'sms') {
                $headerBg = 'bg-amber-700';
                $platLabel = 'SMS / Pesan';
                $platIcon = 'fa-comment-sms';
            } elseif ($plat === 'email') {
                $headerBg = 'bg-blue-800';
                $platLabel = 'Kotak Masuk Email';
                $platIcon = 'fa-envelope';
            } elseif ($plat === 'medsos') {
                $headerBg = 'bg-purple-800';
                $platLabel = 'Direct Message';
                $platIcon = 'fa-share-nodes';
            }
        ?>
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <!-- Platform Phone Style Header -->
                    <div class="<?= $headerBg ?> text-white px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-sm">
                                <i class="<?= $platIcon ?>"></i>
                            </div>
                            <div>
                                <div class="text-[10px] text-white/70 uppercase tracking-wider font-semibold"><?= $platLabel ?> • Kasus #<?= $index + 1 ?></div>
                                <h3 class="font-bold text-xs truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($k['pengirim']) ?></h3>
                            </div>
                        </div>

                        <div>
                            <?php if ($isDone): ?>
                                <?php if ($isCorrect): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/30 text-emerald-200 border border-emerald-400/40 text-[11px] font-bold">
                                        <i class="fas fa-check-circle"></i> Selesai (+<?= $k['xp_earned'] ?> XP)
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full bg-rose-500/30 text-rose-200 border border-rose-400/40 text-[11px] font-bold">
                                        <i class="fas fa-times-circle"></i> Belum Tepat
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full bg-white/20 text-white text-[11px] font-semibold">
                                    ⭐ +<?= $k['xp_reward'] ?> XP
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Body Simulator -->
                    <div class="p-5 bg-slate-50/60 space-y-4">
                        <div class="text-xs font-bold text-slate-800 flex items-center justify-between">
                            <span><?= htmlspecialchars($k['judul']) ?></span>
                            <span class="text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($k['kategori'] ?? '') ?></span>
                        </div>

                        <!-- Chat Message Bubble -->
                        <div class="bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm border border-slate-200/80 space-y-2.5">
                            <p class="text-xs text-slate-800 leading-relaxed font-sans"><?= nl2br(htmlspecialchars($k['isi_pesan'])) ?></p>

                            <!-- Lampiran File Jika Ada -->
                            <?php if (!empty($k['lampiran_nama'])): ?>
                                <div class="p-3 rounded-xl bg-slate-100/80 border border-slate-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs shrink-0">
                                            <i class="fas fa-file-arrow-down"></i>
                                        </div>
                                        <div class="truncate text-xs">
                                            <div class="font-bold text-slate-900 truncate"><?= htmlspecialchars($k['lampiran_nama']) ?></div>
                                            <div class="text-[10px] text-slate-400">File Lampiran Pesan</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase px-2 py-0.5 rounded bg-white border border-slate-200">Unduh</span>
                                </div>
                            <?php endif; ?>

                            <!-- Link di dalam pesan -->
                            <?php if (!empty($k['tautan_terlihat'])): ?>
                                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-mono break-all flex items-center gap-2 border border-blue-200/60">
                                    <i class="fas fa-link shrink-0"></i>
                                    <span class="truncate"><?= htmlspecialchars($k['tautan_terlihat']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="text-[10px] text-slate-400 text-right">Baru saja • Belum diverifikasi</div>
                        </div>

                        <!-- Inspeksi Tautan Toggle Component -->
                        <div x-data="{ openInspect: false }" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm text-xs">
                            <button type="button" @click="openInspect = !openInspect" 
                                    class="w-full flex items-center justify-between text-slate-700 hover:text-slate-900 font-semibold transition">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-magnifying-glass text-indigo-600"></i> Inspeksi Tautan / Asal Pengirim
                                </span>
                                <i class="fas" :class="openInspect ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>

                            <div x-show="openInspect" class="mt-3 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600" style="display: none;">
                                <div><b class="text-slate-700">Tautan Asli / Target URL:</b></div>
                                <div class="p-2 bg-slate-100 rounded-lg font-mono text-[11px] text-slate-900 break-all select-all">
                                    <?= htmlspecialchars($k['tautan_asli'] ?: ($k['tautan_terlihat'] ?: 'Tidak ada link eksternal')) ?>
                                </div>
                                <p class="text-[11px] text-slate-400">
                                    Tips: Perhatikan domain website utama (apakah resmi seperti .sch.id / .go.id atau website mencurigakan) serta ekstensi file (.apk berbahaya jika dari orang asing).
                                </p>
                            </div>
                        </div>

                        <!-- Hasil Evaluasi jika sudah dikerjakan -->
                        <?php if ($isDone): ?>
                            <div class="p-4 rounded-2xl <?= $isCorrect ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : 'bg-rose-50 border border-rose-200 text-rose-900' ?> text-xs space-y-2">
                                <div class="flex items-center gap-2 font-bold">
                                    <i class="fas <?= $isCorrect ? 'fa-check-circle text-emerald-600' : 'fa-times-circle text-rose-600' ?>"></i>
                                    <span><?= $isCorrect ? 'Analisis Kamu Benar!' : 'Analisis Kamu Kurang Tepat!' ?></span>
                                    <span class="text-[10px] font-normal opacity-80">(Kunci: <?= (int)$k['is_aman'] === 1 ? 'AMAN' : 'BERBAHAYA' ?>)</span>
                                </div>
                                <p class="text-xs leading-relaxed"><?= htmlspecialchars($k['penjelasan']) ?></p>
                                <?php if (!empty($k['ciri_bahaya'])): ?>
                                    <div class="mt-2 pt-2 border-t <?= $isCorrect ? 'border-emerald-200' : 'border-rose-200' ?> text-[11px]">
                                        <b>Ciri-Ciri Utama:</b> <?= htmlspecialchars($k['ciri_bahaya']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Button Form -->
                <div class="p-4 sm:p-5 bg-white border-t border-slate-100">
                    <form action="<?= base_url('siswa/kasus-keamanan/submit/' . $k['id']) ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                        <div class="text-[11px] font-semibold text-slate-500 mb-2.5">
                            <?= $isDone ? 'Ingin mencoba menganalisis ulang?' : 'Bagaimana penilaianmu terhadap pesan di atas?' ?>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="jawaban" value="0"
                                    class="py-2.5 px-3 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition flex items-center justify-center gap-2 shadow-sm">
                                <i class="fas fa-triangle-exclamation text-rose-600"></i> BERBAHAYA
                            </button>
                            <button type="submit" name="jawaban" value="1"
                                    class="py-2.5 px-3 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition flex items-center justify-center gap-2 shadow-sm">
                                <i class="fas fa-shield-check text-emerald-600"></i> AMAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function siswaKasusApp() {
    return {};
}
</script>
