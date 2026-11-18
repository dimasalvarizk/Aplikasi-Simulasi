<div class="space-y-6" x-data="kasusManager()">
    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <i class="fas fa-shield-halved mr-1"></i> Cyber Security Challenge
                </span>
                <span class="text-xs text-slate-400">• Detektif Siber</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tantangan Studi Kasus Keamanan Siber</h1>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Buat dan kelola simulasi skenario pesan/chat nyata (WhatsApp, SMS, Email, Medsos) untuk melatih kewaspadaan phishing & malware siswa.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openCreateModal()"
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i> Tambah Kasus Baru
            </button>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (!empty($flash['success'])): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
            <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
            <span><?= htmlspecialchars($flash['success']) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
            <i class="fas fa-exclamation-triangle text-rose-600 text-sm"></i>
            <span><?= htmlspecialchars($flash['error']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-user-secret"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Skenario</span>
                <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= $totalKasus ?> Kasus</div>
                <span class="text-[11px] text-slate-500">Tantangan aktif disajikan</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pengerjaan</span>
                <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= $totalPengerjaan ?> Kali</div>
                <span class="text-[11px] text-slate-500">Jawaban siswa tersimpan</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-bullseye"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tingkat Akurasi Siswa</span>
                <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= $persenAkurasi ?>%</div>
                <span class="text-[11px] text-slate-500">Ketepatan analisis siswa</span>
            </div>
        </div>
    </div>

    <!-- Kasus List Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php if (empty($kasusList)): ?>
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <p class="text-xs text-slate-600 font-medium">Belum ada tantangan kasus keamanan yang dibuat.</p>
                <button type="button" @click="openCreateModal()" class="mt-4 inline-block px-4 py-2 bg-slate-900 text-white rounded-xl font-medium text-xs">
                    Buat Skenario Pertama
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($kasusList as $k): ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-slate-300 transition relative overflow-hidden group">
                    <!-- Top Ribbon Platform -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <?php 
                            $plat = strtolower($k['platform'] ?? 'whatsapp');
                            $platBg = 'bg-slate-100 text-slate-700 border-slate-200';
                            $platIcon = 'fa-comment';
                            if ($plat === 'whatsapp') {
                                $platBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                $platIcon = 'fa-brands fa-whatsapp';
                            } elseif ($plat === 'sms') {
                                $platBg = 'bg-amber-50 text-amber-700 border-amber-200';
                                $platIcon = 'fa-comment-sms';
                            } elseif ($plat === 'email') {
                                $platBg = 'bg-blue-50 text-blue-700 border-blue-200';
                                $platIcon = 'fa-envelope';
                            } elseif ($plat === 'medsos') {
                                $platBg = 'bg-purple-50 text-purple-700 border-purple-200';
                                $platIcon = 'fa-share-nodes';
                            }
                            ?>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 <?= $platBg ?>">
                                <i class="<?= $platIcon ?>"></i> <?= ucfirst($plat) ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600">
                                <?= htmlspecialchars($k['kategori'] ?? 'Kasus') ?>
                            </span>
                        </div>

                        <!-- Status Kunci -->
                        <?php if ((int)$k['is_aman'] === 1): ?>
                            <span class="px-2 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                <i class="fas fa-shield-check"></i> AMAN
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                                <i class="fas fa-triangle-exclamation"></i> BERBAHAYA
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Content Details -->
                    <div class="space-y-2.5">
                        <h3 class="font-bold text-slate-900 text-sm line-clamp-1"><?= htmlspecialchars($k['judul']) ?></h3>

                        <!-- Pengirim Box -->
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Pengirim:</span>
                            <span class="font-medium text-slate-800 line-clamp-1"><?= htmlspecialchars($k['pengirim']) ?></span>
                        </div>

                        <!-- Chat preview bubble -->
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 text-xs text-slate-600 leading-relaxed italic line-clamp-3">
                            "<?= htmlspecialchars($k['isi_pesan']) ?>"
                        </div>

                        <!-- Lampiran / Tautan Terlihat -->
                        <?php if (!empty($k['lampiran_nama']) || !empty($k['tautan_terlihat'])): ?>
                            <div class="p-2 rounded-lg bg-amber-50/50 border border-amber-200/50 text-[11px] text-amber-800 flex items-center gap-2">
                                <i class="fas <?= !empty($k['lampiran_nama']) ? 'fa-file-zipper' : 'fa-link' ?> text-amber-600"></i>
                                <span class="truncate font-mono font-medium"><?= htmlspecialchars($k['lampiran_nama'] ?: $k['tautan_terlihat']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Info & Actions -->
                    <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-between">
                        <div class="text-[11px] text-slate-500">
                            <span class="font-semibold text-emerald-600">⭐ +<?= $k['xp_reward'] ?> XP</span>
                            <span class="mx-1">•</span>
                            <span><?= (int)$k['total_dikerjakan'] ?> siswa (<?= (int)$k['total_benar'] ?> benar)</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="previewKasus(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition text-xs"
                                    title="Pratinjau Skenario Simulasi">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" @click="openEditModal(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition text-xs"
                                    title="Edit Kasus">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" 
                                    onclick="confirmDelete('<?= base_url('guru/kasus-keamanan/delete/' . $k['id']) ?>', 'Hapus Studi Kasus Keamanan?', 'Apakah Anda yakin ingin menghapus kasus &quot;<?= htmlspecialchars(addslashes($k['judul'])) ?>&quot;? Seluruh riwayat analisis siswa pada kasus ini juga akan terhapus.')"
                                    class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition text-xs"
                                    title="Hapus Kasus">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- MODAL CREATE & EDIT KASUS -->
    <div x-show="showFormModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-transparent"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200"
             @click.away="showFormModal = false">
            
            <form :action="isEditMode ? '<?= base_url('guru/kasus-keamanan/edit/') ?>' + formData.id : '<?= base_url('guru/kasus-keamanan/create') ?>'" 
                  method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">

                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900" x-text="isEditMode ? 'Edit Studi Kasus Keamanan' : 'Tambah Studi Kasus Keamanan Baru'"></h3>
                        <p class="text-xs text-slate-500">Tentukan platform pesan, isi cerita, dan tautan/file yang harus dianalisis siswa.</p>
                    </div>
                    <button type="button" @click="showFormModal = false" class="text-slate-400 hover:text-slate-600 text-sm p-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Fields Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Judul Kasus -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Skenario Kasus <span class="text-rose-500">*</span></label>
                        <input type="text" name="judul" x-model="formData.judul" required
                               placeholder="Contoh: Pesan WhatsApp Undangan Pernikahan (.APK)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Platform -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Platform Tampilan <span class="text-rose-500">*</span></label>
                        <select name="platform" x-model="formData.platform" required
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <option value="whatsapp">💬 WhatsApp Chat</option>
                            <option value="sms">📱 SMS / Pesan Singkat</option>
                            <option value="email">✉️ Surat Elektronik (Email)</option>
                            <option value="medsos">🌐 Media Sosial / Direct Message</option>
                        </select>
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Ancaman / Topik <span class="text-rose-500">*</span></label>
                        <input type="text" name="kategori" x-model="formData.kategori" required
                               placeholder="Contoh: Phishing Akun, Malware APK, Link Resmi"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Nama Pengirim -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama / Identitas Pengirim <span class="text-rose-500">*</span></label>
                        <input type="text" name="pengirim" x-model="formData.pengirim" required
                               placeholder="Contoh: +62 813-9821-4402 (Nomor Tak Dikenal) atau Guru Wali Kelas"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Isi Pesan -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pesan / Narasi Skenario <span class="text-rose-500">*</span></label>
                        <textarea name="isi_pesan" x-model="formData.isi_pesan" rows="3" required
                                  placeholder="Tuliskan narasi pesan yang diterima oleh siswa..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                    </div>

                    <!-- Lampiran Nama (opsional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama File Lampiran (Opsional)</label>
                        <input type="text" name="lampiran_nama" x-model="formData.lampiran_nama"
                               placeholder="Contoh: Undangan_Pernikahan.apk"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Tautan Terlihat -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Teks Tautan Yang Terlihat di Chat</label>
                        <input type="text" name="tautan_terlihat" x-model="formData.tautan_terlihat"
                               placeholder="Contoh: https://smpn1karangmoncol.sch.id/... atau bit.ly/..."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Tautan Asli (Jika Link Disamarkan / Diinspeksi) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">URL Asli / Link Tujuan Sebenarnya</label>
                        <input type="text" name="tautan_asli" x-model="formData.tautan_asli"
                               placeholder="Contoh: http://kemdikbud.bantuan-kuota.free-claim.site/login"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Siswa dapat menggunakan tombol 'Inspeksi Tautan' pada aplikasi mobile untuk melihat URL asli ini.</span>
                    </div>

                    <!-- KUNCI JAWABAN (AMAN vs BERBAHAYA) -->
                    <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">Kunci Jawaban Benar <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                                   :class="formData.is_aman == 0 ? 'bg-rose-50 border-rose-300 text-rose-800' : 'bg-white border-slate-200 text-slate-700'">
                                <input type="radio" name="is_aman" value="0" x-model="formData.is_aman" class="text-rose-600">
                                <div>
                                    <div class="text-xs font-bold flex items-center gap-1.5"><i class="fas fa-triangle-exclamation text-rose-500"></i> BERBAHAYA</div>
                                    <span class="text-[10px] opacity-75">Phishing / Scam / Malware</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                                   :class="formData.is_aman == 1 ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-white border-slate-200 text-slate-700'">
                                <input type="radio" name="is_aman" value="1" x-model="formData.is_aman" class="text-emerald-600">
                                <div>
                                    <div class="text-xs font-bold flex items-center gap-1.5"><i class="fas fa-shield-check text-emerald-500"></i> AMAN</div>
                                    <span class="text-[10px] opacity-75">Kanal & domain resmi sekolah</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Ciri Bahaya / Clue -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ciri-Ciri Bahaya / Petunjuk Analisis</label>
                        <textarea name="ciri_bahaya" x-model="formData.ciri_bahaya" rows="2"
                                  placeholder="Contoh: Menggunakan ekstensi .apk, domain gratisan bukan .sch.id atau .go.id, tata bahasa mendesak..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                    </div>

                    <!-- Penjelasan Edukasi Lengkap -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Penjelasan Edukasi untuk Siswa <span class="text-rose-500">*</span></label>
                        <textarea name="penjelasan" x-model="formData.penjelasan" rows="3" required
                                  placeholder="Jelaskan alasan mengapa pesan ini berbahaya/aman setelah siswa memberikan jawaban..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                    </div>

                    <!-- XP Reward -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hadiah XP Siswa</label>
                        <input type="number" name="xp_reward" x-model="formData.xp_reward" min="5" max="100" step="5"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>

                    <!-- Urutan & Status -->
                    <div class="flex items-center gap-4 pt-4">
                        <div class="w-24">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan</label>
                            <input type="number" name="urutan" x-model="formData.urutan" min="0"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        </div>
                        <div class="pt-4">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                                <input type="checkbox" name="is_published" value="1" x-model="formData.is_published" class="rounded text-slate-900">
                                <span>Publikasikan ke Siswa</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="showFormModal = false"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-medium text-xs transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-medium text-xs transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-save text-[11px]"></i> Simpan Studi Kasus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PREVIEW SIMULASI CHAT -->
    <div x-show="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-transparent"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-slate-100 rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border-4 border-slate-800"
             @click.away="showPreviewModal = false">
            
            <!-- Phone Header Bar -->
            <div class="bg-slate-800 text-white px-4 py-3 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <button type="button" @click="showPreviewModal = false" class="text-slate-300 hover:text-white">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs truncate max-w-[170px]" x-text="previewData.pengirim"></div>
                        <div class="text-[10px] text-emerald-400">Online • Terenkripsi</div>
                    </div>
                </div>
                <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Phone Chat Body -->
            <div class="p-4 space-y-3 min-h-[300px] max-h-[420px] overflow-y-auto bg-[#eae6df]">
                <!-- Chat Bubble -->
                <div class="bg-white rounded-2xl rounded-tl-sm p-3.5 shadow-sm max-w-[90%] space-y-2 border border-slate-200/60">
                    <p class="text-xs text-slate-800 leading-relaxed" x-text="previewData.isi_pesan"></p>

                    <!-- Lampiran APK / File -->
                    <template x-if="previewData.lampiran_nama">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs shrink-0">
                                <i class="fas fa-file-arrow-down"></i>
                            </div>
                            <div class="truncate text-xs">
                                <div class="font-bold text-slate-900 truncate" x-text="previewData.lampiran_nama"></div>
                                <div class="text-[10px] text-slate-500">Aplikasi / Dokumen • 4.8 MB</div>
                            </div>
                        </div>
                    </template>

                    <!-- Link Preview Box -->
                    <template x-if="previewData.tautan_terlihat">
                        <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200/60 text-xs text-blue-700 break-all">
                            <i class="fas fa-link mr-1"></i> <span class="font-mono text-[11px]" x-text="previewData.tautan_terlihat"></span>
                        </div>
                    </template>

                    <div class="text-[10px] text-slate-400 text-right mt-1">10:45 • Dibaca</div>
                </div>

                <!-- Inspeksi Info Box -->
                <div class="bg-white/90 backdrop-blur rounded-xl p-3 border border-slate-200 text-xs space-y-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block"><i class="fas fa-magnifying-glass mr-1"></i> Hasil Inspeksi Tautan Asli:</span>
                    <span class="font-mono text-[11px] text-indigo-700 break-all font-semibold" x-text="previewData.tautan_asli || previewData.tautan_terlihat || 'Tidak ada link eksternal'"></span>
                </div>
            </div>

            <!-- Phone Footer / Simulation Actions -->
            <div class="bg-white p-3.5 border-t border-slate-200 space-y-2">
                <div class="text-center text-[10px] font-semibold text-slate-500">Simulasi Keputusan Siswa:</div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="py-2 px-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold text-center">
                        ⚠️ Berbahaya
                    </div>
                    <div class="py-2 px-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold text-center">
                        🛡️ Aman
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function kasusManager() {
    return {
        showFormModal: false,
        showPreviewModal: false,
        isEditMode: false,
        formData: {
            id: null,
            judul: '',
            kategori: 'Phishing',
            platform: 'whatsapp',
            pengirim: '',
            isi_pesan: '',
            lampiran_nama: '',
            tautan_terlihat: '',
            tautan_asli: '',
            is_aman: 0,
            ciri_bahaya: '',
            penjelasan: '',
            xp_reward: 25,
            is_published: 1,
            urutan: 0,
        },
        previewData: {},

        openCreateModal() {
            this.isEditMode = false;
            this.formData = {
                id: null,
                judul: '',
                kategori: 'Phishing',
                platform: 'whatsapp',
                pengirim: '',
                isi_pesan: '',
                lampiran_nama: '',
                tautan_terlihat: '',
                tautan_asli: '',
                is_aman: 0,
                ciri_bahaya: '',
                penjelasan: '',
                xp_reward: 25,
                is_published: 1,
                urutan: 0,
            };
            this.showFormModal = true;
        },

        openEditModal(item) {
            this.isEditMode = true;
            this.formData = {
                id: item.id,
                judul: item.judul || '',
                kategori: item.kategori || 'Phishing',
                platform: item.platform || 'whatsapp',
                pengirim: item.pengirim || '',
                isi_pesan: item.isi_pesan || '',
                lampiran_nama: item.lampiran_nama || '',
                tautan_terlihat: item.tautan_terlihat || item.link_url || '',
                tautan_asli: item.tautan_asli || item.link_url || '',
                is_aman: item.is_aman !== undefined ? item.is_aman : 0,
                ciri_bahaya: item.ciri_bahaya || '',
                penjelasan: item.penjelasan || '',
                xp_reward: item.xp_reward || 25,
                is_published: item.is_published !== undefined ? item.is_published : 1,
                urutan: item.urutan || 0,
            };
            this.showFormModal = true;
        },

        previewKasus(item) {
            this.previewData = item;
            this.showPreviewModal = true;
        }
    };
}
</script>
