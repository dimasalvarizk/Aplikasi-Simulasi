<!-- Include Quill stylesheet & script -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= base_url('guru/modul') ?>" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Daftar Modul
                </a>
                <span>/</span>
                <a href="<?= base_url('guru/materi/' . $modul['id']) ?>" class="hover:text-slate-900 transition">
                    <?= htmlspecialchars($modul['judul']) ?>
                </a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Tambah Bab Baru</span>
            </div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Tambah Bab Materi Pembelajaran</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border bg-blue-50 text-blue-700 border-blue-200">
                    <?= htmlspecialchars($modul['judul']) ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">SMP NEGERI 1 KARANGMONCOL • Tuliskan materi teori interaktif yang akan dipelajari oleh siswa.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('guru/materi/' . $modul['id']) ?>" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-medium transition shadow-sm">
                Batal
            </a>
            <button type="submit" form="materiForm" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium shadow-sm transition flex items-center gap-1.5">
                <i class="fas fa-check text-[10px]"></i> Simpan Materi
            </button>
        </div>
    </div>

    <!-- 2-Column Balanced Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Editor WYSIWYG & Judul (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <form id="materiForm" method="POST" action="<?= base_url('guru/materi/create/' . $modul['id']) ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="konten" id="hiddenKonten">

                <!-- Judul Bab Materi -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Bab Materi <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" required placeholder="Contoh: Pengenalan Topologi Bus & Cara Kerja Terminator"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>

                    <!-- WYSIWYG Editor Container -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Konten Materi Pembelajaran <span class="text-red-500">*</span></label>
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                            <div id="editor" style="min-height: 380px; font-size: 14px;">
                                <h2>Pengenalan Topik Materi</h2>
                                <p>Tuliskan materi pembelajaran teori topologi jaringan di sini secara rinci, jelas, dan mudah dipahami siswa Kelas VII...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs if placed on sidebar, or connected via JS -->
                <input type="hidden" name="urutan" id="hiddenUrutan" value="1">
                <input type="hidden" name="xp_reward" id="hiddenXpReward" value="15">
                <input type="hidden" name="is_active" id="hiddenIsActive" value="1">
            </form>
        </div>

        <!-- Kolom Kanan: Pengaturan Bab & Pratinjau (1 Kolom) -->
        <div class="space-y-6">
            <!-- Pengaturan Bab Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
                <div class="pb-2.5 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Parameter Bab Materi</h4>
                    <p class="text-[11px] text-slate-500">Urutan dan perolehan poin gamifikasi</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Urutan Bab</label>
                    <input type="number" id="inputUrutan" value="1" min="1"
                           onchange="document.getElementById('hiddenUrutan').value = this.value"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    <span class="text-[10px] text-slate-400 mt-1 block">Urutan bab di daftar materi modul</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">XP Reward Membaca</label>
                    <div class="relative">
                        <input type="number" id="inputXp" value="15" min="5" step="5"
                               onchange="document.getElementById('hiddenXpReward').value = this.value"
                               class="w-full pl-3 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-amber-800 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">XP</span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Diterima siswa saat selesai membaca bab ini</span>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="checkActive" checked
                               onchange="document.getElementById('hiddenIsActive').value = this.checked ? 1 : 0"
                               class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                        <span class="text-xs font-semibold text-slate-700">Publikasikan Bab Ini</span>
                    </label>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Siswa dapat langsung membaca bab ini</span>
                </div>
            </div>

            <!-- Tips Penulisan Materi -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-2.5 text-xs text-slate-600">
                <h4 class="font-bold text-slate-900 uppercase text-[11px] tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-lightbulb text-amber-500"></i> Tips Guru
                </h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Siswa SMP kelas VII menyukai materi terstruktur dengan analogi sehari-hari (misal: "Topologi Bus seperti jalur angkot satu arah dengan halte-halte komputer").
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Tuliskan materi pembelajaran...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'code-block'],
                [{ 'color': [] }, { 'background': [] }],
                ['link', 'clean']
            ]
        }
    });

    var form = document.getElementById('materiForm');
    form.onsubmit = function() {
        var hiddenInput = document.getElementById('hiddenKonten');
        hiddenInput.value = quill.root.innerHTML;
        return true;
    };
});
</script>
