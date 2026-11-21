<div class="space-y-6" x-data="kelolaSoal()" @keydown.escape.window="showCreateModal = false; showEditModal = false">
    <!-- Back Button & Action -->
    <div class="flex items-center justify-between">
        <a href="<?= base_url('guru/kuis') ?>" class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition">
            <i class="fas fa-arrow-left text-[10px]"></i> Kembali ke Bank Kuis
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/kuis/edit/' . $kuis['id']) ?>" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-pencil-alt text-[10px]"></i> Edit Pengaturan Kuis
            </a>
            <button type="button"
                onclick="confirmDelete('<?= base_url('guru/kuis/delete/' . $kuis['id']) ?>', 'Hapus Paket Kuis?', 'Apakah Anda yakin ingin menghapus paket kuis &quot;<?= htmlspecialchars(addslashes($kuis['judul'])) ?>&quot;? Seluruh butir soal dan riwayat nilai siswa pada kuis ini akan ikut terhapus.')"
                class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-xl text-xs font-medium transition shadow-sm flex items-center gap-1.5">
                <i class="fas fa-trash-alt text-[10px]"></i> Hapus Kuis
            </button>
        </div>
    </div>

    <!-- Header Info Kuis -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[11px] font-medium rounded-lg border border-slate-200">
                    <?= htmlspecialchars($modul['judul'] ?? 'Modul Topologi') ?>
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium border <?= $kuis['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                    <?= $kuis['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight mt-1"><?= htmlspecialchars($kuis['judul']) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($kuis['deskripsi'] ?? 'Evaluasi pemahaman materi topologi jaringan.') ?></p>
        </div>

        <div class="flex items-center gap-6 bg-slate-50 p-4 rounded-xl border border-slate-200 flex-shrink-0">
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Durasi</span>
                <h4 class="text-lg font-bold text-slate-900"><?= $kuis['durasi_menit'] ?>m</h4>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Passing</span>
                <h4 class="text-lg font-bold text-blue-700"><?= $kuis['passing_grade'] ?>%</h4>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div class="text-center">
                <span class="text-[10px] text-slate-400 uppercase font-semibold">Jumlah Soal</span>
                <h4 class="text-lg font-bold text-slate-900"><?= count($soal) ?></h4>
            </div>
        </div>
    </div>

    <!-- 2 Section: Butir Soal & Rekap Nilai Siswa -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kolom Kiri: Butir Soal -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Daftar Butir Soal (<?= count($soal) ?>)</h3>
                    <span class="text-[11px] text-slate-400">Pilihan Ganda (A, B, C, D)</span>
                </div>
                <button type="button" @click="openCreateModal()"
                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-plus text-[10px]"></i> Tambah Soal
                </button>
            </div>
            <div class="space-y-3.5 max-h-[520px] overflow-y-auto pr-1">
                <?php if (empty($soal)): ?>
                    <div class="text-center py-10 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                        <i class="fas fa-file-circle-question text-slate-300 text-3xl mb-2"></i>
                        <p class="text-xs font-medium text-slate-600">Belum ada butir soal pada kuis ini.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Klik tombol "Tambah Soal" di atas untuk menambahkan butir soal baru.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($soal as $i => $s): ?>
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                            <div class="flex items-start justify-between">
                                <span class="font-bold text-xs text-slate-900">#<?= $i + 1 ?></span>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-semibold rounded border border-emerald-200/60">
                                        Kunci: Pilihan <?= $s['jawaban_benar'] ?>
                                    </span>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-semibold rounded border border-blue-200/60">
                                        <?= (int)($s['poin'] ?? 25) ?> Poin
                                    </span>
                                    <button type="button"
                                        @click="openEditModal(<?= htmlspecialchars(json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition text-xs"
                                        title="Edit Butir Soal">
                                        <i class="fas fa-pencil-alt text-[10px]"></i>
                                    </button>
                                    <button type="button"
                                        onclick="confirmDelete('<?= base_url('guru/soal/delete/' . $s['id']) ?>', 'Hapus Butir Soal?', 'Apakah Anda yakin ingin menghapus butir soal #<?= $i + 1 ?> ini?')"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition text-xs"
                                        title="Hapus Butir Soal">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($s['pertanyaan']) ?></p>
                            <div class="grid grid-cols-2 gap-1.5 text-[11px] text-slate-600 pt-1">
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'A' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    A. <?= htmlspecialchars($s['opsi_a']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'B' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    B. <?= htmlspecialchars($s['opsi_b']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'C' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    C. <?= htmlspecialchars($s['opsi_c']) ?>
                                </div>
                                <div class="p-1.5 rounded <?= $s['jawaban_benar'] === 'D' ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/60' : 'bg-white border border-slate-200/60' ?>">
                                    D. <?= htmlspecialchars($s['opsi_d']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Hasil Pengerjaan Siswa -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm">Rekap Pengerjaan Siswa (<?= count($hasil) ?>)</h3>
                <span class="text-[11px] text-slate-400">Hasil Evaluasi Belajar</span>
            </div>
            <div class="divide-y divide-slate-100 max-h-[520px] overflow-y-auto">
                <?php if (empty($hasil)): ?>
                    <p class="text-xs text-slate-400 text-center py-8">Belum ada siswa yang mengerjakan kuis ini.</p>
                <?php else: ?>
                    <?php foreach ($hasil as $h): ?>
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-semibold text-slate-900"><?= htmlspecialchars($h['siswa_nama']) ?></h5>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($h['kelas'] ?? 'VII A') ?> • <?= formatDate($h['created_at'], 'd M Y, H:i') ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold <?= $h['skor'] >= $kuis['passing_grade'] ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <?= $h['skor'] ?>
                                </span>
                                <div class="flex items-center gap-1.5 justify-end mt-0.5">
                                    <span class="text-[10px] <?= $h['skor'] >= $kuis['passing_grade'] ? 'text-emerald-600 font-semibold' : 'text-rose-600' ?>">
                                        <?= $h['skor'] >= $kuis['passing_grade'] ? 'Lulus' : 'Remedial' ?>
                                    </span>
                                    <?php if ($h['skor'] >= $kuis['passing_grade']): ?>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <span class="text-[10px] <?= ($h['xp_earned'] ?? 0) > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' ?>">
                                            <?= ($h['xp_earned'] ?? 0) > 0 ? '+' . $h['xp_earned'] . ' XP' : '0 XP (Ulang)' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Butir Soal -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-create-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showCreateModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showCreateModal = false"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showCreateModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                
                <form action="<?= base_url('guru/soal/create') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="kuis_id" value="<?= $kuis['id'] ?>">

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-base font-bold text-slate-900" id="modal-create-title">Tambah Butir Soal Baru</h3>
                                <p class="text-xs text-slate-500">Kuis: <?= htmlspecialchars($kuis['judul']) ?></p>
                            </div>
                            <button type="button" @click="showCreateModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Pertanyaan Soal <span class="text-rose-500">*</span></label>
                            <textarea name="pertanyaan" rows="3" required placeholder="Tuliskan pertanyaan soal pilihan ganda di sini..."
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">A</span>
                                    Opsi Jawaban A <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_a" required placeholder="Jawaban A"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">B</span>
                                    Opsi Jawaban B <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_b" required placeholder="Jawaban B"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">C</span>
                                    Opsi Jawaban C <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_c" required placeholder="Jawaban C"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">D</span>
                                    Opsi Jawaban D <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_d" required placeholder="Jawaban D"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kunci Jawaban Benar <span class="text-rose-500">*</span></label>
                                <select name="jawaban_benar" required
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-emerald-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                                    <option value="A">Pilihan A</option>
                                    <option value="B">Pilihan B</option>
                                    <option value="C">Pilihan C</option>
                                    <option value="D">Pilihan D</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Bobot Poin</label>
                                <input type="number" name="poin" value="25" min="1" max="100"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-medium transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]"></i> Simpan Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Butir Soal -->
    <div x-show="showEditModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showEditModal = false"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                
                <form :action="'<?= base_url('guru/soal/edit/') ?>' + editData.id" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-base font-bold text-slate-900" id="modal-edit-title">Edit Butir Soal</h3>
                                <p class="text-xs text-slate-500">ID Soal: #<span x-text="editData.id"></span></p>
                            </div>
                            <button type="button" @click="showEditModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Pertanyaan Soal <span class="text-rose-500">*</span></label>
                            <textarea name="pertanyaan" x-model="editData.pertanyaan" rows="3" required placeholder="Tuliskan pertanyaan soal pilihan ganda di sini..."
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">A</span>
                                    Opsi Jawaban A <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_a" x-model="editData.opsi_a" required placeholder="Jawaban A"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">B</span>
                                    Opsi Jawaban B <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_b" x-model="editData.opsi_b" required placeholder="Jawaban B"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">C</span>
                                    Opsi Jawaban C <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_c" x-model="editData.opsi_c" required placeholder="Jawaban C"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[11px] border border-slate-200">D</span>
                                    Opsi Jawaban D <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="opsi_d" x-model="editData.opsi_d" required placeholder="Jawaban D"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kunci Jawaban Benar <span class="text-rose-500">*</span></label>
                                <select name="jawaban_benar" x-model="editData.jawaban_benar" required
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-emerald-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                                    <option value="A">Pilihan A</option>
                                    <option value="B">Pilihan B</option>
                                    <option value="C">Pilihan C</option>
                                    <option value="D">Pilihan D</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Bobot Poin</label>
                                <input type="number" name="poin" x-model="editData.poin" min="1" max="100"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-medium transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]"></i> Perbarui Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function kelolaSoal() {
    return {
        showCreateModal: false,
        showEditModal: false,
        editData: {
            id: '',
            pertanyaan: '',
            opsi_a: '',
            opsi_b: '',
            opsi_c: '',
            opsi_d: '',
            jawaban_benar: 'A',
            poin: 25
        },
        openCreateModal() {
            this.showCreateModal = true;
        },
        openEditModal(item) {
            this.editData = {
                id: item.id,
                pertanyaan: item.pertanyaan || '',
                opsi_a: item.opsi_a || '',
                opsi_b: item.opsi_b || '',
                opsi_c: item.opsi_c || '',
                opsi_d: item.opsi_d || '',
                jawaban_benar: item.jawaban_benar || 'A',
                poin: item.poin || 25
            };
            this.showEditModal = true;
        }
    };
}
</script>
