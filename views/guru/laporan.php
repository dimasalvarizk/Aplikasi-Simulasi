<?php
// Hitung rata-rata per kuis dan rata-rata keseluruhan
$totalNilaiSemua = [];
$kuisAverages = [];

foreach ($kuisList as $k) {
    $scoresForKuis = [];
    foreach ($siswaList as $s) {
        if (isset($s['nilai_kuis'][$k['id']])) {
            $scoresForKuis[] = $s['nilai_kuis'][$k['id']];
        }
    }
    $kuisAverages[$k['id']] = count($scoresForKuis) > 0 ? round(array_sum($scoresForKuis) / count($scoresForKuis), 1) : null;
}

foreach ($siswaList as $s) {
    if (!empty($s['rata_nilai']) && $s['rata_nilai'] > 0) {
        $totalNilaiSemua[] = $s['rata_nilai'];
    }
}
$rataRataKelas = count($totalNilaiSemua) > 0 ? round(array_sum($totalNilaiSemua) / count($totalNilaiSemua), 1) : 0;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <span class="text-slate-900 font-bold">SMP NEGERI 1 KARANGMONCOL</span>
                <span>•</span>
                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-200/60 font-medium text-[11px]">Kelas VII</span>
                <span>•</span>
                <span>Rekapitulasi Evaluasi Belajar</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rekapitulasi Nilai & Evaluasi Siswa</h1>
            <p class="text-xs text-slate-500 mt-0.5">Matriks perolehan nilai tiap butir kuis topologi, akumulasi XP gamifikasi, serta kalkulasi rata-rata nilai siswa.</p>
        </div>
        <div>
            <a href="<?= base_url('guru/exportCsv' . ($currentKelas ? '?kelas=' . urlencode($currentKelas) : '')) ?>"
               class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-file-excel text-emerald-400 text-xs"></i>
                <span>Unduh Rekap CSV / Excel</span>
            </a>
        </div>
    </div>

    <!-- Filter & Summary Stats Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="<?= base_url('guru/laporan') ?>" class="flex items-center gap-2.5 w-full md:w-auto">
            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">Filter Kelas:</label>
            <select name="kelas" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelasList as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $currentKelas === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($currentKelas)): ?>
                <a href="<?= base_url('guru/laporan') ?>" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <div class="flex items-center gap-3 flex-wrap text-xs">
            <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-2">
                <span class="text-slate-500">Siswa:</span>
                <span class="font-bold text-slate-900"><?= count($siswaList) ?> Siswa</span>
            </div>
            <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-2">
                <span class="text-slate-500">Paket Kuis:</span>
                <span class="font-bold text-slate-900"><?= count($kuisList) ?> Paket</span>
            </div>
            <div class="px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2">
                <span class="text-emerald-700">Rata-rata Kelas:</span>
                <span class="font-bold text-emerald-800"><?= $rataRataKelas ?> <span class="text-[10px] text-emerald-600 font-normal">/ 100</span></span>
            </div>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Nilai Matriks -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-3 text-center w-12 whitespace-nowrap">No</th>
                        <th class="py-3.5 px-4 text-left min-w-[200px] whitespace-nowrap">Siswa</th>
                        <th class="py-3.5 px-3 text-center min-w-[90px] whitespace-nowrap">Kelas</th>
                        <th class="py-3.5 px-3 text-center min-w-[110px] whitespace-nowrap">NISN</th>
                        <th class="py-3.5 px-3 text-center min-w-[80px] whitespace-nowrap">Level</th>
                        <th class="py-3.5 px-3 text-center min-w-[100px] whitespace-nowrap">Total XP</th>
                        <?php foreach ($kuisList as $k): ?>
                            <th class="py-3.5 px-4 text-center min-w-[150px] whitespace-nowrap">
                                <span class="font-bold text-slate-800 block text-xs truncate max-w-[160px]" title="<?= htmlspecialchars($k['judul']) ?>">
                                    <?= htmlspecialchars($k['judul']) ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                        <th class="py-3.5 px-5 text-center font-bold bg-slate-100 border-l border-slate-200 text-slate-900 whitespace-nowrap min-w-[120px]">
                            Rata Nilai
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($siswaList)): ?>
                        <tr>
                            <td colspan="<?= 7 + count($kuisList) ?>" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada data nilai siswa yang sesuai dengan filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($siswaList as $idx => $s): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- No -->
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 whitespace-nowrap">
                                    <?= $idx + 1 ?>
                                </td>

                                <!-- Nama Siswa -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                            <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <a href="<?= base_url('guru/siswa/' . $s['id']) ?>" class="font-bold text-slate-900 hover:text-blue-700 transition block">
                                                <?= htmlspecialchars($s['nama']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kelas (Never Wrap!) -->
                                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-semibold border border-slate-200 whitespace-nowrap">
                                        <?= htmlspecialchars($s['kelas'] ?? 'VII A') ?>
                                    </span>
                                </td>

                                <!-- NISN -->
                                <td class="py-3.5 px-3 text-center font-mono text-slate-500 text-xs whitespace-nowrap">
                                    <?= htmlspecialchars($s['nisn'] ?? '-') ?>
                                </td>

                                <!-- Level -->
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800 whitespace-nowrap">
                                    Lvl <?= $s['level'] ?>
                                </td>

                                <!-- Total XP -->
                                <td class="py-3.5 px-3 text-center font-bold text-amber-800 font-mono whitespace-nowrap">
                                    <?= number_format($s['total_xp']) ?> XP
                                </td>

                                <!-- Nilai Tiap Kuis -->
                                <?php foreach ($kuisList as $k): ?>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <?php if (isset($s['nilai_kuis'][$k['id']])): ?>
                                            <?php $score = $s['nilai_kuis'][$k['id']]; ?>
                                            <span class="inline-flex items-center justify-center min-w-[46px] px-2 py-0.5 rounded-md text-xs font-bold <?= $score >= 75 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                                <?= $score ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300 font-medium">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <!-- Rata-rata Nilai -->
                                <td class="py-3.5 px-5 text-center whitespace-nowrap bg-slate-50/70 border-l border-slate-200">
                                    <?php if ($s['rata_nilai'] > 0): ?>
                                        <span class="inline-flex items-center justify-center min-w-[50px] px-2.5 py-1 rounded-lg text-xs font-bold <?= $s['rata_nilai'] >= 75 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' ?>">
                                            <?= $s['rata_nilai'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-medium">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <!-- Footer Rata-rata Evaluasi Kelas -->
                <?php if (!empty($siswaList)): ?>
                <tfoot>
                    <tr class="bg-slate-50/90 font-bold text-slate-800 border-t-2 border-slate-200 text-xs whitespace-nowrap">
                        <td colspan="6" class="py-3 px-4 text-right uppercase text-[10px] tracking-wider text-slate-500 whitespace-nowrap">
                            Rata-rata Nilai Evaluasi Kelas:
                        </td>
                        <?php foreach ($kuisList as $k): ?>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <?php if ($kuisAverages[$k['id']] !== null): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold <?= $kuisAverages[$k['id']] >= 75 ? 'text-emerald-700' : 'text-amber-800' ?>">
                                        <?= $kuisAverages[$k['id']] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="py-3 px-5 text-center bg-slate-100 border-l border-slate-200 whitespace-nowrap">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <?= $rataRataKelas ?>
                            </span>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
