<div class="space-y-6" x-data="{
    openAddModal: false,
    openEditModal: false,
    openResetModal: false,
    resetStudent: {
        id: '',
        nama: '',
        username: '',
        new_password: 'password123'
    },
    editData: {
        id: '',
        nama: '',
        username: '',
        nisn: '',
        kelas: 'VII A',
        email: '',
        is_active: 1
    }
}">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Data Siswa & Kemajuan Belajar</h1>
            <p class="text-xs text-slate-500 mt-1">SMP NEGERI 1 KARANGMONCOL • Kelola data siswa kelas VII, pantau perolehan XP, level, dan rekapitulasi nilai.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="openAddModal = true" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Tambah Siswa</span>
            </button>
            <a href="<?= base_url('guru/laporan') ?>" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-medium rounded-xl transition shadow-sm text-xs flex items-center gap-2">
                <i class="fas fa-file-csv text-slate-400"></i>
                <span>Unduh Rekap CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="<?= base_url('guru/siswa') ?>" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="flex-1 w-full relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari nama, NISN, email, atau username siswa..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <select name="kelas" class="w-full md:w-44 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= ($kelas ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-xl transition text-xs flex items-center gap-1.5">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <?php if (!empty($search) || !empty($kelas)): ?>
                    <a href="<?= base_url('guru/siswa') ?>" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium rounded-xl transition text-xs">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Data Siswa -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] font-semibold tracking-wider border-b border-slate-200">
                        <th class="py-3 px-5 whitespace-nowrap">Siswa</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">Kelas</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">NISN</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">Level & XP</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">Lencana</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">Rata Nilai</th>
                        <th class="py-3 px-5 whitespace-nowrap">Terakhir Aktif</th>
                        <th class="py-3 px-5 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    <?php if (empty($siswa)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <p class="text-xs">Tidak ada data siswa yang sesuai.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($siswa as $s): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl overflow-hidden bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            <?php if (!empty($s['avatar'])): ?>
                                                <img src="<?= str_starts_with($s['avatar'], 'http') ? htmlspecialchars($s['avatar']) : base_url($s['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($s['nama']) ?></div>
                                            <div class="text-[11px] text-slate-400 font-mono">@<?= htmlspecialchars($s['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200 whitespace-nowrap">
                                        <?= htmlspecialchars($s['kelas'] ?? 'VII A') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap font-mono text-slate-500 text-xs">
                                    <?= htmlspecialchars($s['nisn'] ?? '-') ?>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 bg-amber-50 text-amber-800 font-medium rounded-lg text-[11px] border border-amber-200/60 whitespace-nowrap">
                                        Lvl <?= $s['level'] ?> • <?= number_format($s['total_xp']) ?> XP
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-700 font-medium rounded-lg text-[11px] border border-slate-200 whitespace-nowrap">
                                        <?= (int)($s['total_badges'] ?? 0) ?> Lencana
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center font-semibold whitespace-nowrap">
                                    <?php $nilai = round((float)($s['rata_nilai'] ?? 0), 1); ?>
                                    <span class="<?= $nilai >= 75 ? 'text-emerald-700' : ($nilai > 0 ? 'text-amber-700' : 'text-slate-400') ?>">
                                        <?= $nilai > 0 ? $nilai : '-' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-500 text-[11px] whitespace-nowrap">
                                    <?= timeAgo($s['last_login'] ?? '') ?>
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= base_url('guru/siswa/' . $s['id']) ?>"
                                           class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-[11px] font-medium transition"
                                           title="Detail Progres Belajar">
                                            Progres
                                        </a>
                                        <button type="button"
                                                @click="resetStudent = {
                                                    id: '<?= $s['id'] ?>',
                                                    nama: '<?= htmlspecialchars(addslashes($s['nama'])) ?>',
                                                    username: '<?= htmlspecialchars(addslashes($s['username'])) ?>',
                                                    new_password: 'password123'
                                                }; openResetModal = true"
                                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-[11px] font-semibold transition flex items-center gap-1"
                                                title="Reset Kata Sandi Siswa">
                                            <i class="fas fa-key text-[10px]"></i>
                                            <span>Reset Sandi</span>
                                        </button>
                                        <button type="button"
                                                @click="editData = {
                                                    id: '<?= $s['id'] ?>',
                                                    nama: '<?= htmlspecialchars(addslashes($s['nama'])) ?>',
                                                    username: '<?= htmlspecialchars(addslashes($s['username'])) ?>',
                                                    nisn: '<?= htmlspecialchars(addslashes($s['nisn'] ?? '')) ?>',
                                                    kelas: '<?= htmlspecialchars(addslashes($s['kelas'] ?? 'VII A')) ?>',
                                                    email: '<?= htmlspecialchars(addslashes($s['email'] ?? '')) ?>',
                                                    is_active: 1
                                                }; openEditModal = true"
                                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-[11px] font-medium transition"
                                                title="Edit Siswa">
                                            Edit
                                        </button>
                                        <button type="button"
                                                onclick="confirmDeleteSiswa(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama'])) ?>')"
                                                class="px-2.5 py-1 bg-white hover:bg-red-50 text-red-600 border border-red-200 rounded-lg text-[11px] font-medium transition"
                                                title="Hapus Siswa">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPage > 1): ?>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <span>Menampilkan <?= count($siswa) ?> dari total <?= $totalData ?> siswa</span>
                <div class="flex items-center gap-1">
                    <?php for ($p = 1; $p <= $totalPage; $p++): ?>
                        <a href="<?= base_url('guru/siswa?page=' . $p . '&q=' . urlencode($search) . '&kelas=' . urlencode($kelas)) ?>"
                           class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-medium transition <?= $page == $p ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: TAMBAH SISWA BARU                   -->
    <!-- ========================================== -->
    <div x-show="openAddModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-xl overflow-hidden"
             @click.outside="openAddModal = false">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Siswa Baru</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Daftarkan akun siswa SMP Negeri 1 Karangmoncol</p>
                </div>
                <button type="button" @click="openAddModal = false" class="text-slate-400 hover:text-slate-600 transition text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="<?= base_url('guru/siswa/create') ?>" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Muhammad Rizky Pratama"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required placeholder="Contoh: rizky_vii"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">Digunakan siswa untuk masuk (login) ke aplikasi</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                        <select name="kelas" required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <option value="VII A">Kelas VII A</option>
                            <option value="VII B">Kelas VII B</option>
                            <option value="VII C">Kelas VII C</option>
                            <option value="VII D">Kelas VII D</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN (Nomor Induk)</label>
                        <input type="text" name="nisn" placeholder="Contoh: 0051234567"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Siswa</label>
                        <input type="email" name="email" placeholder="Opsional (otomatis terisi)"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Awal</label>
                    <input type="text" name="password" placeholder="Default: password123"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    <span class="text-[10px] text-slate-500 mt-1 block">Biarkan kosong jika ingin menggunakan password default: <strong class="text-slate-700">password123</strong></span>
                </div>

                <!-- Modal Footer -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openAddModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium transition shadow-sm">
                        Simpan Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDIT DATA SISWA                     -->
    <!-- ========================================== -->
    <div x-show="openEditModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-xl overflow-hidden"
             @click.outside="openEditModal = false">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Perbarui Data Siswa</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Ubah rincian akun atau atur ulang kata sandi</p>
                </div>
                <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 transition text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="<?= base_url('guru/siswa/update') ?>" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <input type="hidden" name="id" :value="editData.id">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" required x-model="editData.nama"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required x-model="editData.username"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                        <select name="kelas" required x-model="editData.kelas"
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                            <option value="VII A">Kelas VII A</option>
                            <option value="VII B">Kelas VII B</option>
                            <option value="VII C">Kelas VII C</option>
                            <option value="VII D">Kelas VII D</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN (Nomor Induk)</label>
                        <input type="text" name="nisn" x-model="editData.nisn"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" x-model="editData.email"
                               class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reset Kata Sandi</label>
                    <input type="text" name="password" placeholder="Kosongkan jika kata sandi tidak ingin diubah"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 focus:bg-white transition">
                    <span class="text-[10px] text-slate-400 mt-1 block">Hanya isi jika siswa lupa kata sandi</span>
                </div>

                <!-- Modal Footer -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openEditModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: RESET KATA SANDI SISWA (OTORITAS GURU)  -->
    <!-- ============================================== -->
    <div x-show="openResetModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden"
             @click.away="openResetModal = false">

            <div class="px-6 py-4 border-b border-slate-100 bg-amber-50/70 flex items-center justify-between">
                <div class="flex items-center gap-2.5 text-amber-900">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                        <i class="fas fa-key text-xs"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold">Reset Kata Sandi Siswa</h3>
                        <p class="text-[11px] text-amber-700/80">Otoritas Pengelola Pembelajaran</p>
                    </div>
                </div>
                <button type="button" @click="openResetModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form action="<?= base_url('guru/siswa/resetPassword') ?>" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? generateToken() ?>">
                <input type="hidden" name="id" :value="resetStudent.id">

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                    <div class="font-bold text-slate-800" x-text="resetStudent.nama"></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Username: <span class="font-mono text-slate-700" x-text="'@' + resetStudent.username"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kata Sandi Baru Sementara <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="new_password" required minlength="6"
                           x-model="resetStudent.new_password"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:bg-white transition">
                    <span class="text-[10px] text-slate-500 mt-1.5 block leading-relaxed">
                        Berikan kata sandi sementara ini kepada siswa. Siswa dapat login kembali dan menggantinya di menu Profil Siswa.
                    </span>
                </div>

                <!-- Template Kata Sandi Cepat -->
                <div class="flex items-center gap-1.5 pt-1">
                    <span class="text-[10px] text-slate-400">Pilihan Cepat:</span>
                    <button type="button" @click="resetStudent.new_password = 'password123'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">password123</button>
                    <button type="button" @click="resetStudent.new_password = 'smpn1#123'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">smpn1#123</button>
                    <button type="button" @click="resetStudent.new_password = 'siswa' + Math.floor(100 + Math.random() * 900)" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-[10px] text-slate-600 font-mono">Acak</button>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openResetModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
                        <i class="fas fa-check text-xs"></i>
                        <span>Simpan Kata Sandi Siswa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script Delete Confirmation -->
<script>
function confirmDeleteSiswa(id, nama) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Data Siswa?',
            text: 'Apakah Anda yakin ingin menghapus siswa "' + nama + '"? Seluruh riwayat kuis, materi, dan XP siswa akan ikut terhapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitDeleteForm(id);
            }
        });
    } else {
        if (confirm('Apakah Anda yakin ingin menghapus siswa "' + nama + '"?')) {
            submitDeleteForm(id);
        }
    }
}

function submitDeleteForm(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('guru/siswa/delete/') ?>' + id;

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = 'csrf_token';
    token.value = '<?= $csrf_token ?? '' ?>';
    form.appendChild(token);

    document.body.appendChild(form);
    form.submit();
}
</script>
