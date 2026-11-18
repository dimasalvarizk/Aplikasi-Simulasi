<div class="space-y-6" x-data="kuisBuilder()">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= base_url('guru/kuis') ?>" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Bank Kuis
                </a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Buat Paket Kuis Baru</span>
            </div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Buat Kuis Baru & Bank Butir Soal</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border bg-blue-50 text-blue-700 border-blue-200">
                    Evaluasi Siswa Kelas VII
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Susun judul kuis, modul materi acuan, durasi waktu, passing grade, dan butir-butir soal pilihan ganda.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/kuis') ?>" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm">
                Batal
            </a>
            <button type="submit" form="createKuisForm" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium shadow-sm transition flex items-center gap-1.5">
                <i class="fas fa-check text-[10px]"></i> Simpan & Publikasikan Kuis
            </button>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Formulir Kuis & Soal (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <form id="createKuisForm" method="POST" action="<?= base_url('guru/kuis/create') ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Card 1: Pengaturan Dasar Kuis -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">1. Pengaturan Dasar Kuis</h3>
                            <p class="text-xs text-slate-500">Pilih modul acuan dan petunjuk bagi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 1</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Modul Materi Terkait</label>
                            <select name="modul_id" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <?php foreach ($modulList as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['judul']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Kuis <span class="text-red-500">*</span></label>
                            <input type="text" name="judul" required placeholder="Contoh: Evaluasi Karakteristik Topologi Star & Bus"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi / Petunjuk Pengerjaan</label>
                        <textarea name="deskripsi" rows="2" placeholder="Petunjuk pengerjaan bagi siswa..."
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:bg-white transition"></textarea>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Durasi</label>
                            <div class="relative">
                                <input type="number" name="durasi_menit" value="15" min="5"
                                       class="w-full pl-2.5 pr-12 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-slate-400">Mnt</span>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Passing Grade</label>
                            <div class="relative">
                                <input type="number" name="passing_grade" value="70" min="0" max="100"
                                       class="w-full pl-2.5 pr-8 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-blue-700">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-slate-400">%</span>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">XP Reward</label>
                            <div class="relative">
                                <input type="number" name="xp_reward" value="50" min="10" step="5"
                                       class="w-full pl-2.5 pr-8 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-amber-800">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-slate-400">XP</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-600">
                            <span class="text-xs font-semibold text-slate-800">Terbitkan Langsung ke Siswa (Status Aktif)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="acak_soal" value="1" checked class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                            <span class="text-xs font-semibold text-slate-700">Acak Urutan Butir Soal</span>
                        </label>
                    </div>
                </div>

                <!-- Card 2: Butir Soal Pilihan Ganda -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">2. Butir Soal Pilihan Ganda</h3>
                            <p class="text-xs text-slate-500">Tuliskan pertanyaan dengan 4 opsi pilihan (A, B, C, D) dan tentukan kunci jawabannya</p>
                        </div>
                        <button type="button" @click="tambahSoal()"
                                class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm">
                            <i class="fas fa-plus text-[10px]"></i> Tambah Butir Soal
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(soal, index) in soalList" :key="index">
                            <div class="p-5 bg-slate-50/60 rounded-xl border border-slate-200 shadow-sm space-y-3 relative">
                                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                                    <span class="px-2.5 py-0.5 bg-slate-900 text-white rounded-lg text-xs font-bold" x-text="'Butir Soal #' + (index + 1)"></span>
                                    <button type="button" @click="hapusSoal(index)" x-show="soalList.length > 1"
                                            class="text-red-500 hover:text-red-700 text-xs font-medium flex items-center gap-1">
                                        <i class="fas fa-trash text-[10px]"></i> Hapus Butir
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pertanyaan Soal</label>
                                    <textarea name="soal_pertanyaan[]" rows="2" required placeholder="Tuliskan pertanyaan soal di sini..."
                                              class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pilihan A</label>
                                        <input type="text" name="soal_a[]" required placeholder="Teks opsi A"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pilihan B</label>
                                        <input type="text" name="soal_b[]" required placeholder="Teks opsi B"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pilihan C</label>
                                        <input type="text" name="soal_c[]" required placeholder="Teks opsi C"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pilihan D</label>
                                        <input type="text" name="soal_d[]" required placeholder="Teks opsi D"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-emerald-800 mb-1">Kunci Jawaban Benar</label>
                                    <select name="soal_benar[]" class="w-44 px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold rounded-xl text-xs">
                                        <option value="A">Pilihan A</option>
                                        <option value="B">Pilihan B</option>
                                        <option value="C">Pilihan C</option>
                                        <option value="D">Pilihan D</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau & Ringkasan (1 Kolom) -->
        <div class="space-y-6">
            <!-- Ringkasan Kuis -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Ringkasan Paket Kuis</h4>
                    <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200 font-medium">
                        Live Counter
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                        <span>Total Butir Soal</span>
                        <span class="font-bold text-slate-900" x-text="soalList.length + ' Soal'"></span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                        <span>Bobot per Soal</span>
                        <span class="font-bold text-slate-900" x-text="Math.round(100 / Math.max(1, soalList.length)) + ' Poin'"></span>
                    </div>
                    <div class="flex justify-between py-1 text-slate-600">
                        <span>Target Penyelesaian</span>
                        <span class="font-bold text-slate-900">Siswa Kelas VII</span>
                    </div>
                </div>
            </div>

            <!-- Panduan Penyusunan Soal -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-2.5 text-xs text-slate-600">
                <h4 class="font-bold text-slate-900 uppercase text-[11px] tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-lightbulb text-amber-500"></i> Tips Guru
                </h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Untuk pemula di Kelas VII, sertakan soal analisis visual topologi jaringan (misal: "Jika kabel putus pada topologi Bus, apa dampaknya bagi seluruh jaringan?").
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function kuisBuilder() {
    return {
        soalList: [
            { id: 1 },
            { id: 2 }
        ],
        tambahSoal() {
            this.soalList.push({ id: Date.now() });
        },
        hapusSoal(index) {
            if (this.soalList.length > 1) {
                this.soalList.splice(index, 1);
            }
        }
    }
}
</script>
