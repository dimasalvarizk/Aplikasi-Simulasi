<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Simulasi Topologi') ?> - SMP Negeri 1 Karangmoncol</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#f0f4ff',
                            100: '#e0e9ff',
                            200: '#c7d7fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        secondary: {
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: width 0.25s ease; }
        .nav-link-active { background-color: #1e293b; color: #ffffff !important; }
        /* Hilangkan background overlay pada popup SweetAlert2 */
        .swal2-container.swal2-backdrop-show,
        .swal2-container.swal2-noanimation {
            background: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
    <?= $extraHead ?? '' ?>
</head>
<body class="bg-slate-50 font-sans text-slate-900" x-data="appLayout()">

<?php
// Flash messages
$flash_success = getFlash('success');
$flash_error   = getFlash('error');
$flash_info    = getFlash('info');
$flash_warning = getFlash('warning');
$user = getUser();
$isGuru = ($user['role'] ?? '') === 'guru';
$isSiswa = ($user['role'] ?? '') === 'siswa';
?>

<!-- Flash message triggers via SweetAlert2 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($flash_success): ?>
    Swal.fire({ icon:'success', title:'Berhasil!', text:<?= json_encode($flash_success) ?>,
        timer:3000, timerProgressBar:true, showConfirmButton:false,
        toast:true, position:'top-end' });
    <?php endif; ?>
    <?php if ($flash_error): ?>
    Swal.fire({ icon:'error', title:'Gagal!', text:<?= json_encode($flash_error) ?>,
        timer:4000, timerProgressBar:true, showConfirmButton:false,
        toast:true, position:'top-end' });
    <?php endif; ?>
    <?php if ($flash_info): ?>
    Swal.fire({ icon:'info', title:'Info', text:<?= json_encode($flash_info) ?>,
        timer:3000, timerProgressBar:true, showConfirmButton:false,
        toast:true, position:'top-end' });
    <?php endif; ?>
    <?php if ($flash_warning): ?>
    Swal.fire({ icon:'warning', title:'Perhatian!', text:<?= json_encode($flash_warning) ?>,
        timer:4000, timerProgressBar:true, showConfirmButton:false,
        toast:true, position:'top-end' });
    <?php endif; ?>
});
</script>

<div class="flex h-screen overflow-hidden">

    <!-- ===== SIDEBAR ===== -->
    <aside class="bg-slate-900 border-r border-slate-800 sidebar-transition flex-shrink-0 relative z-30"
           :class="sidebarOpen ? 'w-64' : 'w-16'"
           x-cloak>
        <!-- Logo -->
        <div class="flex items-center px-4 py-4 border-b border-slate-800"
             :class="sidebarOpen ? 'justify-between' : 'justify-center'">
            <div class="flex items-center space-x-3" x-show="sidebarOpen">
                <img src="<?= getSchoolLogoSrc() ?>" alt="Logo SMPN 1 Karangmoncol" class="w-8 h-8 rounded object-contain bg-white p-0.5 shadow-sm">
                <div>
                    <div class="text-white font-bold text-xs leading-tight">SMPN 1 KARANGMONCOL</div>
                    <div class="text-slate-400 text-[10px]">Simulasi Topologi</div>
                </div>
            </div>
            <div x-show="!sidebarOpen" class="w-8 h-8 rounded overflow-hidden bg-white p-0.5 shadow-sm">
                <img src="<?= getSchoolLogoSrc() ?>" alt="Logo" class="w-full h-full object-contain">
            </div>
            <button @click="sidebarOpen = !sidebarOpen" x-show="sidebarOpen"
                    class="text-slate-400 hover:text-white transition-colors p-1 rounded hover:bg-slate-800">
                <i class="fas fa-chevron-left text-xs"></i>
            </button>
        </div>

        <!-- User Info -->
        <div class="px-3 py-3 border-b border-slate-800">
            <div class="flex items-center" :class="sidebarOpen ? 'space-x-3' : 'justify-center'">
                <img src="<?= htmlspecialchars(getAvatarUrl($user['avatar'] ?? '', $user['nama'] ?? 'Guru')) ?>" 
                     alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-slate-700"
                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nama'] ?? 'Guru') ?>&background=4f46e5&color=fff&bold=true';">
                <div x-show="sidebarOpen" class="min-w-0">
                    <p class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($user['nama'] ?? 'User') ?></p>
                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300">
                        <?= $isGuru ? 'Guru / Pengelola' : 'Siswa' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
            <?php if ($isGuru): ?>
            <!-- GURU MENU -->
            <?php
            $guruMenu = [
                ['url' => base_url('guru/dashboard'), 'icon' => 'fa-gauge-high',      'label' => 'Dashboard',         'key' => 'dashboard'],
                ['url' => base_url('guru/simulasi'),  'icon' => 'fa-diagram-project', 'label' => 'Simulasi Topologi', 'key' => 'simulasi'],
                ['url' => base_url('guru/kasus-keamanan'), 'icon' => 'fa-shield-halved', 'label' => 'Tantangan Siber', 'key' => 'kasus-keamanan'],
                ['url' => base_url('guru/siswa'),     'icon' => 'fa-users',           'label' => 'Data Siswa',        'key' => 'siswa'],
                ['url' => base_url('guru/modul'),     'icon' => 'fa-book-open',       'label' => 'Modul',             'key' => 'modul'],
                ['url' => base_url('guru/kuis'),      'icon' => 'fa-clipboard-list',  'label' => 'Bank Kuis',         'key' => 'kuis'],
                ['url' => base_url('guru/laporan'),   'icon' => 'fa-chart-bar',       'label' => 'Laporan',           'key' => 'laporan'],
                ['url' => base_url('guru/profil'),    'icon' => 'fa-user-cog',        'label' => 'Profil',            'key' => 'profil'],
            ];
            foreach ($guruMenu as $m):
                $active = (($currentPage ?? '') === $m['key']);
            ?>
            <a href="<?= $m['url'] ?>"
               class="group flex items-center rounded-lg px-3 py-2 text-xs font-medium transition-colors relative
                      <?= $active ? 'nav-link-active' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="fas <?= $m['icon'] ?> w-4 text-center flex-shrink-0 <?= $active ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                <span x-show="sidebarOpen" class="ml-3"><?= $m['label'] ?></span>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- SISWA MENU -->
            <?php
            $siswaMenu = [
                ['url' => base_url('siswa/dashboard'),  'icon' => 'fa-gauge-high',   'label' => 'Dashboard',    'key' => 'dashboard'],
                ['url' => base_url('siswa/materi'),     'icon' => 'fa-book-open',    'label' => 'Materi',       'key' => 'materi'],
                ['url' => base_url('siswa/simulasi'),   'icon' => 'fa-diagram-project','label' => 'Simulasi',   'key' => 'simulasi'],
                ['url' => base_url('siswa/kasus-keamanan'), 'icon' => 'fa-shield-halved', 'label' => 'Detektif Siber', 'key' => 'kasus-keamanan'],
                ['url' => base_url('siswa/kuis'),       'icon' => 'fa-clipboard-list','label' => 'Kuis',        'key' => 'kuis'],
                ['url' => base_url('siswa/nilai'),      'icon' => 'fa-star',         'label' => 'Nilai & XP',   'key' => 'nilai'],
                ['url' => base_url('siswa/profil'),     'icon' => 'fa-user-circle',  'label' => 'Profil',       'key' => 'profil'],
            ];
            foreach ($siswaMenu as $m):
                $active = (($currentPage ?? '') === $m['key']);
            ?>
            <a href="<?= $m['url'] ?>"
               class="group flex items-center rounded-lg px-3 py-2 text-xs font-medium transition-colors relative
                      <?= $active ? 'nav-link-active' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="fas <?= $m['icon'] ?> w-4 text-center flex-shrink-0 <?= $active ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                <span x-show="sidebarOpen" class="ml-3"><?= $m['label'] ?></span>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer: Toggle & Logout -->
        <div class="border-t border-slate-800 p-2 space-y-1">
            <button @click="sidebarOpen = !sidebarOpen" x-show="!sidebarOpen"
                    class="w-full flex justify-center text-slate-400 hover:text-white p-1.5 rounded hover:bg-slate-800 transition-colors">
                <i class="fas fa-chevron-right text-xs"></i>
            </button>
            <a href="<?= base_url('auth/logout') ?>"
               @click.prevent="confirmLogout"
               class="flex items-center rounded-lg px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-red-400 transition-colors"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="fas fa-sign-out-alt w-4 text-center flex-shrink-0"></i>
                <span x-show="sidebarOpen" class="ml-3">Keluar</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

        <!-- ===== HEADER ===== -->
        <header class="bg-white border-b border-slate-200 flex-shrink-0 z-20">
            <div class="flex items-center justify-between px-6 h-16">

                <!-- Left: Breadcrumb -->
                <div class="flex items-center space-x-3">
                    <nav class="flex items-center space-x-1 text-sm text-gray-500">
                        <a href="<?= base_url($isGuru ? 'guru/dashboard' : 'siswa/dashboard') ?>"
                           class="hover:text-primary-600 transition-colors">
                            <i class="fas fa-home text-xs"></i>
                        </a>
                        <?php if (!empty($breadcrumb)): ?>
                            <?php foreach ($breadcrumb as $bc): ?>
                            <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                            <?php if (!empty($bc['url'])): ?>
                            <a href="<?= $bc['url'] ?>" class="hover:text-primary-600 transition-colors"><?= htmlspecialchars($bc['label']) ?></a>
                            <?php else: ?>
                            <span class="text-gray-700 font-medium"><?= htmlspecialchars($bc['label']) ?></span>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                            <span class="text-gray-700 font-medium"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Right: XP bar (siswa) + Notifications + User -->
                <div class="flex items-center space-x-4">

                    <?php if ($isSiswa && isset($user['xp'])): ?>
                    <!-- XP Bar mini di header -->
                    <div class="hidden md:flex items-center space-x-2 bg-indigo-50 rounded-xl px-3 py-1.5 border border-indigo-100">
                        <div class="level-badge text-white text-xs font-bold px-2 py-0.5 rounded-lg">
                            Lv.<?= calculateLevel($user['xp'] ?? 0) ?>
                        </div>
                        <div class="flex flex-col w-28">
                            <div class="flex justify-between text-xs text-indigo-700 mb-0.5">
                                <span class="font-medium"><?= getLevelTitle(calculateLevel($user['xp'] ?? 0)) ?></span>
                                <span><?= $user['xp'] ?? 0 ?> XP</span>
                            </div>
                            <div class="h-1.5 bg-indigo-100 rounded-full overflow-hidden">
                                <div class="h-full xp-gradient rounded-full xp-bar xp-pulse"
                                     style="width: <?= min(100, ($user['xp'] ?? 0) % 100) ?>%"></div>
                            </div>
                        </div>
                        <i class="fas fa-bolt text-amber-500 text-sm"></i>
                    </div>
                    <?php endif; ?>

                    <!-- Notification Bell Real-Time -->
                    <div class="relative" x-data="realtimeNotificationCenter()" x-init="start()" @click.outside="open = false">
                        <button @click="open = !open"
                                class="relative p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors focus:outline-none"
                                :class="{'bg-slate-100 text-slate-900': open}">
                            <i class="fas fa-bell text-sm" :class="{'animate-bounce text-indigo-600': hasNew, 'text-slate-700': unreadCount > 0}"></i>
                            <!-- Real-time Unread Badge -->
                            <template x-if="unreadCount > 0">
                                <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center ring-2 ring-white shadow-sm"
                                      x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                            </template>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">

                            <!-- Header -->
                            <div class="px-4 py-3 bg-slate-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-bell text-xs text-indigo-400"></i>
                                    <span class="text-white font-bold text-xs tracking-wide">Pemberitahuan Sistem</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="unreadCount > 0">
                                        <span class="bg-rose-500 text-white text-[10px] font-bold rounded-md px-2 py-0.5"
                                              x-text="unreadCount + ' Baru'"></span>
                                    </template>
                                    <template x-if="unreadCount > 0">
                                        <button @click.stop="markAllAsRead()" class="text-[10px] text-slate-300 hover:text-white underline cursor-pointer">
                                            Tandai dibaca
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Notifications List -->
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                <template x-if="loading && items.length === 0">
                                    <div class="p-6 text-center text-slate-400 text-xs">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Memuat pemberitahuan...
                                    </div>
                                </template>

                                <template x-if="!loading && items.length === 0">
                                    <div class="p-8 text-center text-slate-400">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">Tidak ada notifikasi baru</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Semua aktivitas siswa telah terpantau.</p>
                                    </div>
                                </template>

                                <template x-for="item in items" :key="item.id">
                                    <div @click="openItem(item)"
                                         class="flex items-start px-4 py-3 hover:bg-slate-50 transition-colors cursor-pointer relative"
                                         :class="{'bg-indigo-50/40': !item.is_read}">
                                        <!-- Unread Dot Indicator -->
                                        <span x-show="!item.is_read" class="absolute left-1.5 top-5 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>

                                        <!-- Icon Badge -->
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                             :class="{
                                                 'bg-emerald-50 text-emerald-700 border border-emerald-200': item.tipe === 'kuis',
                                                 'bg-blue-50 text-blue-700 border border-blue-200': item.tipe === 'simulasi',
                                                 'bg-indigo-50 text-indigo-700 border border-indigo-200': item.tipe === 'materi',
                                                 'bg-amber-50 text-amber-700 border border-amber-200': item.tipe === 'siswa',
                                                 'bg-slate-100 text-slate-700 border border-slate-200': !['kuis','simulasi','materi','siswa'].includes(item.tipe)
                                             }">
                                            <i class="fas" :class="item.icon || 'fa-bell'"></i>
                                        </div>

                                        <!-- Content -->
                                        <div class="ml-3 flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-xs font-bold text-slate-900 truncate" x-text="item.judul"></p>
                                                <span class="text-[10px] text-slate-400 flex-shrink-0 ml-2" x-text="item.time_ago"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug line-clamp-2" x-text="item.pesan"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Footer -->
                            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 text-center">
                                <a href="<?= base_url($isGuru ? 'guru/laporan' : 'siswa/profil') ?>" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition">
                                    <?= $isGuru ? 'Lihat Rekap Seluruh Aktivitas & Laporan →' : 'Lihat Progres & Badges Belajar →' ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Avatar + Dropdown -->
                    <div class="relative" x-data="{open:false}">
                        <button @click="open=!open"
                                class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                            <img src="<?= htmlspecialchars(getAvatarUrl($user['avatar'] ?? '', $user['nama'] ?? 'Guru')) ?>" 
                                 alt="Avatar" class="w-8 h-8 rounded-xl object-cover shadow-sm border border-slate-200"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nama'] ?? 'Guru') ?>&background=4f46e5&color=fff&bold=true';">
                            <div class="hidden md:block text-left">
                                <p class="text-xs font-semibold text-slate-900 leading-tight"><?= htmlspecialchars($user['nama'] ?? '') ?></p>
                                <p class="text-[11px] text-slate-400"><?= $isGuru ? 'Guru / Pengelola' : ucfirst($user['role'] ?? '') ?> • SMPN 1 Karangmoncol</p>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] text-slate-400 hidden md:block"></i>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak x-transition
                             class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                                <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars($user['nama'] ?? '') ?></p>
                                <p class="text-[11px] text-slate-500 font-mono">@<?= htmlspecialchars($user['username'] ?? '') ?></p>
                            </div>
                            <div class="py-1 text-xs">
                                <a href="<?= base_url(($isGuru ? 'guru' : 'siswa') . '/profil') ?>"
                                   class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                    <i class="fas fa-user-circle w-4 mr-2.5 text-slate-400"></i> Profil Saya
                                </a>
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="<?= base_url('auth/logout') ?>"
                                   @click.prevent="confirmLogout"
                                   class="flex items-center px-4 py-2 text-rose-600 hover:bg-rose-50 transition-colors font-medium">
                                    <i class="fas fa-sign-out-alt w-4 mr-2.5"></i> Keluar
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- ===== PAGE CONTENT ===== -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-6 animate-fade-up">
                <?= $content ?? '' ?>
            </div>
        </main>

    </div><!-- end main -->
</div><!-- end flex container -->

<!-- Reward Overlay (XP/Badge popup) -->
<div id="rewardOverlay" class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none hidden">
    <div class="bg-white rounded-3xl shadow-2xl p-8 text-center animate-slide-in max-w-sm pointer-events-auto" id="rewardCard">
        <div class="text-6xl mb-3" id="rewardEmoji">🏆</div>
        <h3 class="text-xl font-bold text-gray-800 mb-1" id="rewardTitle">Level Up!</h3>
        <p class="text-gray-500 text-sm" id="rewardDesc">Kamu naik ke level berikutnya!</p>
        <button onclick="document.getElementById('rewardOverlay').classList.add('hidden')"
                class="mt-5 px-6 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-xl font-medium text-sm hover:opacity-90 transition-opacity">
            Awesome! 🎉
        </button>
    </div>
</div>

<script>
function appLayout() {
    return {
        sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
        init() {
            this.$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val));
        },
        confirmLogout() {
            Swal.fire({
                title: 'Keluar dari Akun?',
                text: 'Kamu akan keluar dari aplikasi SMP Negeri 1 Karangmoncol.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6366f1',
                borderRadius: '1rem',
            }).then(result => {
                if (result.isConfirmed) window.location.href = '<?= base_url('auth/logout') ?>';
            });
        }
    }
}

// Global: show reward popup
function showReward(emoji, title, desc) {
    document.getElementById('rewardEmoji').textContent = emoji;
    document.getElementById('rewardTitle').textContent = title;
    document.getElementById('rewardDesc').textContent = desc;
    document.getElementById('rewardOverlay').classList.remove('hidden');
}

// Global modern delete confirmation dialog (SweetAlert2)
function confirmDelete(url, title, text) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title || 'Hapus Data?',
            text: text || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1.5 text-xs"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-100',
                confirmButton: 'rounded-xl px-5 py-2.5 font-medium text-sm shadow-sm',
                cancelButton: 'rounded-xl px-5 py-2.5 font-medium text-sm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    } else {
        if (confirm(text || 'Apakah Anda yakin ingin menghapus?')) {
            window.location.href = url;
        }
    }
}

// Real-time Notification Center (Alpine.js Component)
function realtimeNotificationCenter() {
    return {
        open: false,
        unreadCount: 0,
        items: [],
        loading: false,
        hasNew: false,
        pollTimer: null,

        start() {
            this.fetchNotifications();
            // Polling interval 5 detik agar selalu real-time
            this.pollTimer = setInterval(() => {
                this.fetchNotifications(true);
            }, 5000);
        },

        async fetchNotifications(isPoll = false) {
            if (!isPoll) this.loading = true;
            try {
                const url = '<?= $isGuru ? base_url("guru/notificationsPoll") : base_url("siswa/notificationsPoll") ?>';
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.status === 'success' && json.data) {
                        const prevCount = this.unreadCount;
                        this.unreadCount = json.data.unread_count || 0;
                        this.items = json.data.notifications || [];

                        // Efek bouncing jika ada aktivitas siswa baru yang masuk
                        if (isPoll && this.unreadCount > prevCount) {
                            this.hasNew = true;
                            setTimeout(() => { this.hasNew = false; }, 3000);
                        }
                    }
                }
            } catch (err) {
                // Diamkan saat background error sementara
            } finally {
                if (!isPoll) this.loading = false;
            }
        },

        async openItem(item) {
            if (!item.is_read) {
                item.is_read = 1;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                const markUrl = '<?= $isGuru ? base_url("guru/markNotificationRead/") : base_url("siswa/markNotificationRead/") ?>' + item.id;
                fetch(markUrl, { method: 'POST' });
            }
            if (item.link) {
                window.location.href = item.link;
            }
        },

        async markAllAsRead() {
            this.items.forEach(i => i.is_read = 1);
            this.unreadCount = 0;
            try {
                const markAllUrl = '<?= $isGuru ? base_url("guru/markAllNotificationsRead") : base_url("siswa/markAllNotificationsRead") ?>';
                await fetch(markAllUrl, { method: 'POST' });
            } catch (e) {}
        }
    };
}
</script>

<?= $extraScripts ?? '' ?>
</body>
</html>
