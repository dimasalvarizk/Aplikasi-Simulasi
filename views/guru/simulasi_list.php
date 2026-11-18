<div class="space-y-6" x-data="simulasiManager()">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Laboratorium Praktik Jaringan</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs font-semibold text-slate-600">SMP NEGERI 1 KARANGMONCOL</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kelola Soal Tantangan Simulasi Topologi</h1>
            <p class="text-xs text-slate-500 mt-0.5">Buat soal tantangan dan tentukan kriteria kunci jawaban otomatis yang dikerjakan siswa di kanvas.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openCreateModal()"
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm transition">
                <i class="fas fa-plus text-xs"></i>
                <span>Buat Soal Simulasi Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Soal Simulasi</p>
                <p class="text-lg font-bold text-slate-900"><?= count($challenges) ?> Tantangan</p>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Target Peserta</p>
                <p class="text-lg font-bold text-slate-900"><?= $totalSiswa ?> Siswa Kelas VII</p>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Penilaian Otomatis</p>
                <p class="text-lg font-bold text-slate-900">Validasi Kunci Jawaban</p>
            </div>
        </div>
    </div>

    <!-- Daftar Soal Tantangan Simulasi -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php if (empty($challenges)): ?>
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200">
                <i class="fas fa-network-wired text-4xl mb-3 text-slate-300 block"></i>
                <p class="text-sm font-semibold text-slate-700 mb-1">Belum ada soal tantangan simulasi.</p>
                <p class="text-xs text-slate-400 mb-4">Klik tombol di bawah untuk membuat soal simulasi pertama Anda.</p>
                <button type="button" @click="openCreateModal()"
                        class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition inline-flex items-center gap-1.5">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>Buat Soal Simulasi Pertama</span>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($challenges as $c): 
                $lulusCount = (int)($c['jumlah_siswa_lulus'] ?? 0);
                $pctLulus = $totalSiswa > 0 ? min(100, round(($lulusCount / $totalSiswa) * 100)) : 0;
                $isActive = (int)($c['is_active'] ?? 1) === 1;
                $tc = json_decode($c['target_config'] ?? '', true) ?: [];
                $minPc = $tc['min_pc'] ?? 3;
                $minSw = $tc['min_switch'] ?? 1;
                $minCb = $tc['min_cables'] ?? 3;
            ?>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:border-slate-300 transition-all flex flex-col justify-between">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-[10px] rounded-lg uppercase tracking-wider">
                                Topologi <?= htmlspecialchars(ucfirst($c['tipe_topologi'])) ?>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 font-semibold text-[11px] rounded-md">
                                    +<?= $c['xp_reward'] ?> XP
                                </span>
                                <?php if ($isActive): ?>
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold text-[10px] rounded-md">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 font-semibold text-[10px] rounded-md">
                                        Draf
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-2"><?= htmlspecialchars($c['judul']) ?></h3>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed"><?= htmlspecialchars($c['deskripsi'] ?? 'Rangkai arsitektur topologi jaringan ini sesuai instruksi.') ?></p>

                        <!-- Kunci Jawaban Ringkas -->
                        <div class="mt-3 p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                <i class="fas fa-check-circle text-emerald-600"></i> Kunci Penilaian Otomatis:
                            </div>
                            <div class="text-slate-600 flex items-center justify-between font-medium">
                                <span>Syarat Min:</span>
                                <span><?= $minPc ?> PC • <?= $minSw ?> Switch • <?= $minCb ?> Kabel</span>
                            </div>
                        </div>

                        <!-- Modul Terkait -->
                        <?php if (!empty($c['modul_judul'])): ?>
                            <p class="text-[11px] text-slate-400 mt-2.5 flex items-center gap-1">
                                <i class="fas fa-book-open text-[10px]"></i>
                                <span>Modul: <?= htmlspecialchars($c['modul_judul']) ?></span>
                            </p>
                        <?php endif; ?>

                        <!-- Progress Kelulusan Siswa -->
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-slate-500 text-[11px] font-medium">Siswa Lulus:</span>
                                <span class="font-bold text-slate-800 text-[11px]"><?= $lulusCount ?> / <?= $totalSiswa ?> Siswa (<?= $pctLulus ?>%)</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-slate-900 h-2 rounded-full transition-all duration-500" style="width: <?= $pctLulus ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Guru -->
                    <div class="mt-5 pt-4 border-t border-slate-100 space-y-2">
                        <!-- Primary: Buka Canvas -->
                        <a href="<?= base_url('guru/simulasi/' . $c['id']) ?>"
                           class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-play text-[10px]"></i> Buka Canvas & Kunci Jawaban
                        </a>

                        <!-- Secondary: Edit & Hapus -->
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    @click='openEditModal(<?= json_encode($c) ?>)'
                                    class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                                <i class="fas fa-pen text-[10px]"></i> Edit Soal
                            </button>
                            <button type="button" 
                                    @click="deleteChallenge(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['judul'])) ?>')"
                                    class="py-2 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold rounded-xl text-xs transition flex items-center justify-center gap-1.5 border border-rose-200">
                                <i class="fas fa-trash-alt text-[10px]"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 1: TAMBAH SOAL SIMULASI BARU                        -->
    <!-- ========================================================= -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCreateModal = false"
             class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 text-left relative max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Buat Soal Tantangan Simulasi Baru</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Tentukan judul, instruksi, dan kriteria kunci jawaban otomatis untuk siswa.</p>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-sm p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="<?= base_url('guru/simulasi/create') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Judul Soal -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Soal Tantangan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" required placeholder="Contoh: Tantangan 4: Rangkai Topologi Star Ruang Lab 2"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <!-- Tipe Topologi & Modul -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Topologi Jaringan <span class="text-red-500">*</span></label>
                        <select name="tipe_topologi" required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <option value="star">Topologi Star (Bintang)</option>
                            <option value="bus">Topologi Bus (Linear)</option>
                            <option value="ring">Topologi Ring (Cincin)</option>
                            <option value="mesh">Topologi Mesh (Jala)</option>
                            <option value="tree">Topologi Tree (Pohon)</option>
                            <option value="hybrid">Topologi Hybrid (Campuran)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Modul Pembelajaran Terkait <span class="text-red-500">*</span></label>
                        <select name="modul_id" required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <?php foreach ($modulList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['judul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- KRITERIA KUNCI JAWABAN (VALIDASI OTOMATIS) -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <i class="fas fa-check-double text-blue-600"></i> Kunci Jawaban & Kriteria Penilaian Otomatis
                        </span>
                        <span class="text-[10px] text-slate-500 font-medium">Syarat kelulusan siswa</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. PC Client</label>
                            <input type="number" name="min_pc" value="3" min="1" max="12" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. Switch</label>
                            <input type="number" name="min_switch" value="1" min="0" max="6" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. Kabel</label>
                            <input type="number" name="min_cables" value="3" min="1" max="24" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Perangkat Khusus</label>
                            <select name="need_server" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <option value="0">Tidak Wajib</option>
                                <option value="1">Wajib Server</option>
                                <option value="2">Wajib Router</option>
                            </select>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 block leading-tight">
                        Sistem menguji secara presisi apakah rangkaian siswa memenuhi jumlah perangkat di atas dan menyusun kabel sesuai kaidah topologi yang dipilih.
                    </span>
                </div>

                <!-- Reward XP -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reward XP Kelulusan Siswa <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="xp_reward" value="50" min="10" step="5" required
                               class="w-full pl-3.5 pr-12 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition font-bold text-amber-800">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">XP</span>
                    </div>
                </div>

                <!-- Deskripsi Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Ringkas Soal</label>
                    <input type="text" name="deskripsi" placeholder="Penjelasan singkat tujuan topologi ini..."
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <!-- Instruksi Detail untuk Siswa -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Petunjuk & Panduan Pengerjaan Siswa <span class="text-red-500">*</span></label>
                    <textarea name="instruksi" rows="3" required placeholder="Contoh: Rangkai 1 Switch pusat dan hubungkan 4 PC workstation secara simetris menggunakan kabel jaringan."
                              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"></textarea>
                    <span class="text-[10px] text-slate-400 mt-1 block">Teks panduan ini akan dibaca langsung oleh siswa di samping kanvas simulasi.</span>
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-slate-900 focus:ring-slate-900">
                        <span class="text-xs font-semibold text-slate-700">Terbitkan langsung untuk siswa (Status Aktif)</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                        Simpan & Terbitkan Soal
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 2: EDIT SOAL SIMULASI                               -->
    <!-- ========================================================= -->
    <div x-show="showEditModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showEditModal = false"
             class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 text-left relative max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Soal Tantangan Simulasi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah judul, instruksi, atau kriteria kunci jawaban tantangan.</p>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-sm p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form :action="'<?= base_url('guru/simulasi/edit/') ?>' + editData.id" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Judul Soal -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Soal Tantangan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" x-model="editData.judul" required
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <!-- Tipe Topologi & Modul -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Topologi Jaringan <span class="text-red-500">*</span></label>
                        <select name="tipe_topologi" x-model="editData.tipe_topologi" required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <option value="star">Topologi Star (Bintang)</option>
                            <option value="bus">Topologi Bus (Linear)</option>
                            <option value="ring">Topologi Ring (Cincin)</option>
                            <option value="mesh">Topologi Mesh (Jala)</option>
                            <option value="tree">Topologi Tree (Pohon)</option>
                            <option value="hybrid">Topologi Hybrid (Campuran)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Modul Pembelajaran Terkait <span class="text-red-500">*</span></label>
                        <select name="modul_id" x-model="editData.modul_id" required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <?php foreach ($modulList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['judul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- KRITERIA KUNCI JAWABAN (VALIDASI OTOMATIS) -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <i class="fas fa-check-double text-blue-600"></i> Kunci Jawaban & Kriteria Penilaian Otomatis
                        </span>
                        <span class="text-[10px] text-slate-500 font-medium">Syarat kelulusan siswa</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. PC Client</label>
                            <input type="number" name="min_pc" x-model="editData.min_pc" min="1" max="12" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. Switch</label>
                            <input type="number" name="min_switch" x-model="editData.min_switch" min="0" max="6" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min. Kabel</label>
                            <input type="number" name="min_cables" x-model="editData.min_cables" min="1" max="24" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Perangkat Khusus</label>
                            <select name="need_server" x-model="editData.need_server" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 text-center focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <option value="0">Tidak Wajib</option>
                                <option value="1">Wajib Server</option>
                                <option value="2">Wajib Router</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Reward XP -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reward XP Kelulusan Siswa <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="xp_reward" x-model="editData.xp_reward" min="10" step="5" required
                               class="w-full pl-3.5 pr-12 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition font-bold text-amber-800">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">XP</span>
                    </div>
                </div>

                <!-- Deskripsi Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Ringkas Soal</label>
                    <input type="text" name="deskripsi" x-model="editData.deskripsi"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <!-- Instruksi Detail untuk Siswa -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Petunjuk & Panduan Pengerjaan Siswa <span class="text-red-500">*</span></label>
                    <textarea name="instruksi" x-model="editData.instruksi" rows="3" required
                              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"></textarea>
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editData.is_active == 1" class="w-4 h-4 rounded text-slate-900 focus:ring-slate-900">
                        <span class="text-xs font-semibold text-slate-700">Status Soal Aktif (Dapat diakses siswa)</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
function simulasiManager() {
    return {
        showCreateModal: false,
        showEditModal: false,
        editData: {},

        openCreateModal() {
            this.showCreateModal = true;
        },

        openEditModal(challenge) {
            this.editData = Object.assign({}, challenge);
            let tc = {};
            try {
                tc = typeof challenge.target_config === 'string' ? JSON.parse(challenge.target_config) : (challenge.target_config || {});
            } catch(e) {}
            this.editData.min_pc = tc.min_pc !== undefined ? tc.min_pc : 3;
            this.editData.min_switch = tc.min_switch !== undefined ? tc.min_switch : 1;
            this.editData.min_cables = tc.min_cables !== undefined ? tc.min_cables : 3;
            this.editData.need_server = tc.need_server !== undefined ? tc.need_server : 0;
            this.showEditModal = true;
        },

        deleteChallenge(id, judul) {
            Swal.fire({
                title: 'Hapus Soal Simulasi?',
                text: `Apakah Anda yakin ingin menghapus "${judul}"? Seluruh riwayat hasil pengerjaan siswa pada tantangan ini akan ikut terhapus.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '<?= base_url('guru/simulasi/delete/') ?>' + id;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?= $csrf_token ?>';
                    form.appendChild(csrfInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    }
}
</script>
