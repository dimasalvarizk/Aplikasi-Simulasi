<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<div class="max-w-2xl mx-auto space-y-8 text-center py-6">
    <!-- Card Utama Hasil Kuis -->
    <div class="bg-white rounded-3xl p-8 md:p-12 border border-gray-100 shadow-xl space-y-6 relative overflow-hidden">
        <!-- Glow Circle -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-<?= $hasil['status'] === 'lulus' ? 'emerald' : 'rose' ?>-500/10 rounded-full blur-2xl"></div>

        <div>
            <span class="px-3.5 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs rounded-full uppercase tracking-wider">
                Evaluasi Kuis Selesai
            </span>
            <h1 class="text-2xl font-black text-gray-800 mt-2"><?= htmlspecialchars($hasil['kuis_judul']) ?></h1>
            <p class="text-xs text-gray-400 mt-1"><?= formatDate($hasil['created_at'], 'd F Y, H:i') ?></p>
        </div>

        <!-- Big Score Circle -->
        <div class="py-4">
            <div class="inline-flex flex-col items-center justify-center w-40 h-40 rounded-full border-8 <?= $hasil['status'] === 'lulus' ? 'border-emerald-500 bg-emerald-50/40 text-emerald-700' : 'border-rose-500 bg-rose-50/40 text-rose-700' ?> shadow-lg">
                <span class="text-xs font-bold uppercase tracking-wider">Skor Kamu</span>
                <span class="text-5xl font-black tracking-tight"><?= (int)$hasil['skor'] ?></span>
                <span class="text-xs font-semibold mt-1">Passing: <?= $hasil['passing_grade'] ?? 70 ?>%</span>
            </div>
        </div>

        <!-- Status & Badge Box -->
        <?php if ($hasil['status'] === 'lulus'): ?>
            <div class="p-5 bg-emerald-50 border border-emerald-200 rounded-3xl space-y-2">
                <div class="inline-flex items-center gap-2 text-emerald-800 font-black text-lg">
                    <span>🎉</span> SELAMAT! KAMU LULUS KUIS
                </div>
                <p class="text-xs text-emerald-700 max-w-md mx-auto">
                    Kerja bagus! Kamu berhasil membuktikan penguasaanmu pada materi ini dan berhak mendapatkan reward gamifikasi.
                </p>
                <div class="pt-2">
                    <span class="inline-block px-4 py-1.5 bg-emerald-600 text-white font-extrabold text-sm rounded-xl shadow-md animate-bounce">
                        ⭐ +<?= $hasil['xp_earned'] ?> XP Ditambahkan!
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="p-5 bg-rose-50 border border-rose-200 rounded-3xl space-y-2">
                <div class="inline-flex items-center gap-2 text-rose-800 font-black text-lg">
                    <span>⚠️</span> BELUM MENCAPAI PASSING GRADE
                </div>
                <p class="text-xs text-rose-700 max-w-md mx-auto">
                    Jangan patah semangat! Kamu bisa membaca ulang materi teori di modul lalu mencoba kuis ini kembali.
                </p>
            </div>
        <?php endif; ?>

        <!-- Detail Benar / Salah -->
        <div class="grid grid-cols-2 gap-4 pt-2">
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                <span class="text-xs text-gray-400 font-bold uppercase block">Jawaban Benar</span>
                <span class="text-2xl font-black text-emerald-600">✅ <?= $hasil['jumlah_benar'] ?> Soal</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                <span class="text-xs text-gray-400 font-bold uppercase block">Total Soal</span>
                <span class="text-2xl font-black text-gray-800">📝 <?= $hasil['jumlah_soal'] ?> Soal</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= base_url('siswa/kuis/' . $hasil['kuis_id']) ?>"
               class="w-full sm:w-auto px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl text-xs transition">
                <i class="fas fa-redo mr-1"></i> Ulangi Kuis
            </a>
            <a href="<?= base_url('siswa/simulasi') ?>"
               class="w-full sm:w-auto px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl text-xs transition shadow-lg">
                <i class="fas fa-diagram-project mr-1"></i> Ke Simulasi Topologi
            </a>
            <a href="<?= base_url('siswa/dashboard') ?>"
               class="w-full sm:w-auto px-6 py-3 text-gray-500 hover:text-gray-800 font-bold text-xs transition">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<?php if ($hasil['status'] === 'lulus'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
    });
});
</script>
<?php endif; ?>
