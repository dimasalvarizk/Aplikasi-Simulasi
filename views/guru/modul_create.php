<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= base_url('guru/modul') ?>" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Daftar Modul
                </a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Tambah Modul Baru</span>
            </div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Tambah Modul Pembelajaran Baru</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border bg-blue-50 text-blue-700 border-blue-200">
                    Kelas VII • Kurikulum Informatika
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Buat modul silabus baru untuk materi teori & simulasi topologi jaringan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/modul') ?>" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm">
                Batal
            </a>
            <button type="submit" form="createModulForm" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium shadow-sm transition flex items-center gap-1.5">
                <i class="fas fa-check text-[10px]"></i> Simpan Modul
            </button>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Formulir Modul (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <form id="createModulForm" method="POST" action="<?= base_url('guru/modul/create') ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Card 1: Informasi Pokok Modul -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">1. Informasi Pokok Modul</h3>
                            <p class="text-xs text-slate-500">Judul materi pembelajaran dan ringkasan capaian kompetensi</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 1</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Modul <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" required placeholder="Contoh: Modul 4 - Karakteristik & Cara Kerja Topologi Tree"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Ringkas & Capaian Pembelajaran</label>
                        <textarea name="deskripsi" rows="4" placeholder="Jelaskan secara ringkas kompetensi dasar yang akan dipelajari siswa kelas VII pada modul ini..."
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition"></textarea>
                    </div>
                </div>

                <!-- Card 2: Urutan & Distribusi -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">2. Pengaturan Urutan & Publikasi</h3>
                            <p class="text-xs text-slate-500">Tentukan letak modul di silabus dan status ketersediaan bagi siswa</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">Tahap 2</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Urutan Tampil</label>
                            <input type="number" name="urutan" value="1" min="1"
                                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 mt-1">
                            <span class="text-[10px] text-slate-400 mt-1 block">Modul dengan nomor lebih kecil tampil lebih awal</span>
                        </div>

                        <label class="p-4 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer flex items-start gap-3 transition">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="mt-0.5 w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Publikasikan Modul Ini</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">Siswa dapat langsung melihat dan membaca bab materi pada modul ini.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau & Panduan (1 Kolom) -->
        <div class="space-y-6">
            <!-- Pratinjau Tampilan Siswa -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-eye text-slate-400"></i> Pratinjau Modul Siswa
                    </span>
                    <span class="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-medium">
                        Pratinjau
                    </span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>SMPN 1 Karangmoncol</span>
                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded font-medium text-[10px]">Kelas VII</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm">Judul Modul Anda</h4>
                    <p class="text-[11px] text-slate-500 line-clamp-2">Deskripsi kompetensi modul akan tampil di sini saat siswa membuka menu materi.</p>
                    <div class="pt-2 flex items-center justify-between text-[11px] text-slate-400 border-t border-slate-200/60">
                        <span>0 Bab Tersedia</span>
                        <span class="text-emerald-700 font-medium">Siap Dibaca</span>
                    </div>
                </div>
            </div>

            <!-- Panduan Silabus Topologi -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3 text-xs text-slate-600">
                <h4 class="font-bold text-slate-900 uppercase text-[11px] tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-book-reader text-blue-600"></i> Alur Silabus Kelas VII
                </h4>
                <div class="space-y-2 text-[11px] text-slate-500 leading-relaxed">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <strong class="text-slate-800 block">1. Topologi Dasar</strong>
                        Bus (linier) & Star (switch) untuk pemahaman pondasi.
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <strong class="text-slate-800 block">2. Topologi Menengah</strong>
                        Ring (token ring) & Mesh (redundansi penuh).
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <strong class="text-slate-800 block">3. Topologi Kompleks</strong>
                        Tree (hierarki) & Hybrid (kombinasi jaringan besar).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
