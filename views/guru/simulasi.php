<div class="space-y-4">

    <!-- Top Action Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= base_url('guru/simulasi') ?>" 
               class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition text-xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold uppercase rounded-md">
                        Topologi <?= htmlspecialchars(ucfirst($challenge['tipe_topologi'])) ?>
                    </span>
                    <span class="text-xs font-semibold text-amber-700">⭐ Reward Siswa: +<?= $challenge['xp_reward'] ?> XP</span>
                </div>
                <h1 class="text-base sm:text-lg font-bold text-slate-900 line-clamp-1"><?= htmlspecialchars($challenge['judul']) ?></h1>
            </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="flex items-center gap-2 flex-wrap">
            <button id="btnConnectMode" onclick="toggleConnectMode()" 
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-semibold text-xs flex items-center gap-1.5 transition border border-slate-200">
                <i class="fas fa-network-wired text-xs"></i> <span id="connectModeLabel">Pasang Kabel: OFF</span>
            </button>
            <button onclick="testPingAnimation()" 
                    class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-semibold text-xs flex items-center gap-1.5 transition border border-emerald-200">
                <i class="fas fa-paper-plane text-xs"></i> Tes Ping
            </button>
            <button onclick="clearCanvas()" 
                    class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl font-semibold text-xs flex items-center gap-1 transition border border-rose-200" title="Bersihkan Canvas">
                <i class="fas fa-trash-alt text-xs"></i> Bersihkan
            </button>
            <?php 
            $tc = json_decode($challenge['target_config'] ?? '', true) ?: [];
            $hasCanvasKey = !empty($tc['devices']);
            if ($hasCanvasKey): 
            ?>
            <button onclick="loadSavedKey()" 
                    class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl font-semibold text-xs flex items-center gap-1.5 transition border border-blue-200"
                    title="Tampilkan rangkaian kunci jawaban yang telah disimpan sebelumnya di canvas ini">
                <i class="fas fa-eye text-xs"></i> Muat Kunci Jawaban
            </button>
            <?php endif; ?>
            <button onclick="saveCanvasAsKey()" 
                    class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm"
                    title="Simpan rangkaian perangkat dan kabel saat ini sebagai kunci jawaban standar untuk menilai siswa">
                <i class="fas fa-key text-xs"></i> Simpan Kunci Jawaban
            </button>
            <button onclick="submitSimulasiGuru()" 
                    class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm">
                <i class="fas fa-check-circle text-xs"></i> Uji Validasi
            </button>
        </div>
    </div>

    <!-- Main Sandbox Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        
        <!-- Panel Kiri: Toolbar Perangkat & Panduan Guru -->
        <div class="space-y-4">
            
            <!-- Box 1: Toolbar Perangkat -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div>
                    <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Perangkat Jaringan</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Tarik (drag) perangkat ke canvas di sebelah kanan.</p>
                </div>

                <div class="space-y-2">
                    <!-- Switch -->
                    <div draggable="true" ondragstart="dragDevice(event, 'switch')"
                         class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white text-base shadow-sm">
                            <i class="fas fa-server text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Switch (Layer 2)</h4>
                            <span class="text-[10px] text-slate-400">Konsentrator LAN</span>
                        </div>
                    </div>

                    <!-- PC Workstation -->
                    <div draggable="true" ondragstart="dragDevice(event, 'pc')"
                         class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white text-base shadow-sm">
                            <i class="fas fa-desktop text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">PC Workstation</h4>
                            <span class="text-[10px] text-slate-400">Host Komputer Siswa</span>
                        </div>
                    </div>

                    <!-- Router -->
                    <div draggable="true" ondragstart="dragDevice(event, 'router')"
                         class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-rose-600 flex items-center justify-center text-white text-base shadow-sm">
                            <i class="fas fa-route text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Router (Layer 3)</h4>
                            <span class="text-[10px] text-slate-400">Gateway Antar-Jaringan</span>
                        </div>
                    </div>

                    <!-- Hub -->
                    <div draggable="true" ondragstart="dragDevice(event, 'hub')"
                         class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center text-white text-base shadow-sm">
                            <i class="fas fa-hubspot text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Hub (Layer 1)</h4>
                            <span class="text-[10px] text-slate-400">Broadcast Repeater</span>
                        </div>
                    </div>

                    <!-- Server -->
                    <div draggable="true" ondragstart="dragDevice(event, 'server')"
                         class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-base shadow-sm">
                            <i class="fas fa-database text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Server Host</h4>
                            <span class="text-[10px] text-slate-400">Pusat Layanan / DNS</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Box Kunci Penilaian Otomatis -->
            <?php 
            $kMinPc = $tc['min_pc'] ?? 3;
            $kMinSw = $tc['min_switch'] ?? 1;
            $kMinCb = $tc['min_cables'] ?? 3;
            ?>
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kunci Penilaian Sistem</span>
                    <?php if ($hasCanvasKey): ?>
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-md flex items-center gap-1 border border-emerald-200">
                            <i class="fas fa-check text-[9px]"></i> Kunci Canvas Aktif
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-md border border-blue-200">
                            Rubrik Otomatis
                        </span>
                    <?php endif; ?>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Pola Arsitektur:</span>
                        <span class="font-bold text-slate-900 capitalize">Topologi <?= htmlspecialchars($challenge['tipe_topologi']) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Min. Workstation:</span>
                        <span class="font-bold text-slate-900"><?= $kMinPc ?> PC Client</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Min. Konsentrator:</span>
                        <span class="font-bold text-slate-900"><?= $kMinSw ?> Switch</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Min. Jalur Kabel:</span>
                        <span class="font-bold text-slate-900"><?= $kMinCb ?> Kabel</span>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 leading-tight">
                    💡 Rangkai topologi di canvas ini lalu klik tombol <strong>"Simpan Kunci Jawaban"</strong> untuk mengunci jawaban siswa.
                </p>
            </div>

            <!-- Box 2: Panduan Spesifikasi Kurikulum -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm space-y-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Panduan Spesifikasi Tantangan</span>
                <div class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                    <?= $challenge['instruksi'] ?>
                </div>
            </div>

            <!-- Box 3: Siswa yang Telah Lulus -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rekap Siswa Lulus</span>
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-md">
                        <?= count($siswaLulus) ?> Siswa
                    </span>
                </div>
                
                <?php if (empty($siswaLulus)): ?>
                    <p class="text-xs text-slate-400 italic">Belum ada siswa yang lulus tantangan ini.</p>
                <?php else: ?>
                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 text-xs">
                        <?php foreach (array_slice($siswaLulus, 0, 8) as $sl): ?>
                            <div class="py-2 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <p class="font-semibold text-slate-800 truncate"><?= htmlspecialchars($sl['nama']) ?></p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($sl['kelas']) ?></p>
                                </div>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold text-[11px] rounded">
                                    <?= $sl['skor'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Kolom Tengah/Kanan: Canvas Interaktif -->
        <div class="lg:col-span-3">
            <div class="relative bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden h-[620px]" id="canvasArea"
                 ondragover="allowDrop(event)" ondrop="dropDevice(event)">

                <!-- Grid Background Pattern -->
                <div class="absolute inset-0 opacity-15 pointer-events-none"
                     style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;"></div>

                <!-- SVG Cables Layer -->
                <svg id="svgLayer" class="absolute inset-0 w-full h-full pointer-events-none z-10"></svg>

                <!-- Container Perangkat Nodes -->
                <div id="deviceContainer" class="absolute inset-0 z-20"></div>

                <!-- Overlay Info Status di Bawah Canvas -->
                <div class="absolute bottom-4 left-4 right-4 z-30 pointer-events-none flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="px-4 py-2 bg-slate-800/90 backdrop-blur-md rounded-xl border border-slate-700 text-white text-xs flex items-center gap-3 pointer-events-auto shadow-lg">
                        <span id="canvasStats" class="font-semibold">Perangkat: 0 • Kabel: 0</span>
                        <span class="text-slate-500">|</span>
                        <span id="modeStatusText" class="text-blue-400 font-semibold">Mode Pindah Perangkat</span>
                    </div>

                    <div class="px-3.5 py-2 bg-slate-800/90 backdrop-blur-md rounded-xl border border-slate-700 text-slate-300 text-[11px] pointer-events-auto">
                        💡 Aktifkan <strong>Mode Pasang Kabel</strong>, lalu klik 2 perangkat berturut-turut untuk menghubungkan.
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<style>
.device-node {
    position: absolute;
    user-select: none;
    cursor: move;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.device-node:hover {
    transform: scale(1.05);
}
.device-selected {
    box-shadow: 0 0 0 3px #38bdf8, 0 0 20px rgba(56,189,248,0.5) !important;
}
.packet-ping {
    animation: packetMove 1s ease-in-out infinite;
}
@keyframes packetMove {
    0% { r: 4; opacity: 1; }
    50% { r: 8; opacity: 0.6; }
    100% { r: 4; opacity: 1; }
}
</style>

<script>
// Simulator State
const targetTopology = '<?= $challenge['tipe_topologi'] ?>';
const challengeId = <?= (int)$challenge['id'] ?>;
const csrfToken = '<?= $csrf_token ?>';

let devices = [];
let connections = [];
let isConnectMode = false;
let connectStartDeviceId = null;
let deviceCounter = 0;

// Drag from toolbar
function dragDevice(e, type) {
    e.dataTransfer.setData('device_type', type);
}

function allowDrop(e) {
    e.preventDefault();
}

function dropDevice(e) {
    e.preventDefault();
    const type = e.dataTransfer.getData('device_type');
    if (!type) return;

    const canvasRect = document.getElementById('canvasArea').getBoundingClientRect();
    const x = Math.max(20, Math.min(canvasRect.width - 100, e.clientX - canvasRect.left - 40));
    const y = Math.max(20, Math.min(canvasRect.height - 100, e.clientY - canvasRect.top - 40));

    addDeviceToCanvas(type, x, y);
}

function addDeviceToCanvas(type, x, y) {
    deviceCounter++;
    const id = 'dev_' + deviceCounter;
    const name = type.toUpperCase() + '-' + deviceCounter;

    const device = {
        id: id,
        type: type,
        name: name,
        x: x,
        y: y
    };

    devices.push(device);
    renderDeviceNode(device);
    updateStats();
}

function getDeviceIconHtml(type) {
    switch(type) {
        case 'router': return '<i class="fas fa-route text-rose-400 text-lg"></i>';
        case 'switch': return '<i class="fas fa-server text-emerald-400 text-lg"></i>';
        case 'hub': return '<i class="fas fa-hubspot text-amber-400 text-lg"></i>';
        case 'server': return '<i class="fas fa-database text-indigo-400 text-lg"></i>';
        case 'pc': default: return '<i class="fas fa-desktop text-blue-400 text-lg"></i>';
    }
}

function renderDeviceNode(d) {
    const container = document.getElementById('deviceContainer');
    const el = document.createElement('div');
    el.id = d.id;
    el.className = 'device-node bg-slate-800/95 border-2 border-slate-600 rounded-xl p-3 text-center text-white shadow-xl flex flex-col items-center justify-center min-w-[85px]';
    el.style.left = d.x + 'px';
    el.style.top = d.y + 'px';

    el.innerHTML = `
        <span class="block mb-1">${getDeviceIconHtml(d.type)}</span>
        <span class="text-[10px] font-bold tracking-wider block text-slate-200">${d.name}</span>
        <button onclick="deleteDevice('${d.id}', event)" class="absolute -top-2 -right-2 w-5 h-5 bg-rose-600 hover:bg-rose-700 text-white rounded-full text-[9px] flex items-center justify-center font-bold shadow transition">✕</button>
    `;

    // Click handler for connect mode
    el.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
        handleDeviceClick(d.id);
    });

    // Drag inside canvas
    let isDragging = false;
    let startX, startY;

    el.addEventListener('mousedown', (e) => {
        if (isConnectMode || e.target.tagName === 'BUTTON') return;
        isDragging = true;
        startX = e.clientX - el.offsetLeft;
        startY = e.clientY - el.offsetTop;
        el.style.zIndex = '30';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const canvasRect = document.getElementById('canvasArea').getBoundingClientRect();
        let newX = e.clientX - startX;
        let newY = e.clientY - startY;

        newX = Math.max(10, Math.min(canvasRect.width - 95, newX));
        newY = Math.max(10, Math.min(canvasRect.height - 95, newY));

        el.style.left = newX + 'px';
        el.style.top = newY + 'px';

        d.x = newX;
        d.y = newY;

        redrawCables();
    });

    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            el.style.zIndex = '20';
        }
    });

    container.appendChild(el);
}

function handleDeviceClick(devId) {
    if (!isConnectMode) return;

    if (!connectStartDeviceId) {
        connectStartDeviceId = devId;
        const el = document.getElementById(devId);
        if (el) el.classList.add('device-selected');
        document.getElementById('modeStatusText').textContent = 'Pilih perangkat kedua untuk menyambung kabel...';
    } else {
        if (connectStartDeviceId === devId) {
            // Cancel selection
            const el = document.getElementById(devId);
            if (el) el.classList.remove('device-selected');
            connectStartDeviceId = null;
            document.getElementById('modeStatusText').textContent = 'Mode Pasang Kabel: Klik perangkat pertama';
            return;
        }

        // Add connection
        const exists = connections.some(c => 
            (c.from === connectStartDeviceId && c.to === devId) || 
            (c.from === devId && c.to === connectStartDeviceId)
        );

        if (!exists) {
            connections.push({ from: connectStartDeviceId, to: devId });
            redrawCables();
            updateStats();
        }

        const startEl = document.getElementById(connectStartDeviceId);
        if (startEl) startEl.classList.remove('device-selected');
        connectStartDeviceId = null;
        document.getElementById('modeStatusText').textContent = 'Kabel terpasang! Klik perangkat lain untuk menghubungkan';
    }
}

function toggleConnectMode() {
    isConnectMode = !isConnectMode;
    const btn = document.getElementById('btnConnectMode');
    const label = document.getElementById('connectModeLabel');
    const statusText = document.getElementById('modeStatusText');

    if (isConnectMode) {
        btn.classList.remove('bg-slate-100', 'text-slate-800');
        btn.classList.add('bg-blue-600', 'text-white');
        label.textContent = 'Mode Pasang Kabel: ON';
        statusText.textContent = 'Mode Pasang Kabel: Klik 2 perangkat berturut-turut';
    } else {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-slate-100', 'text-slate-800');
        label.textContent = 'Mode Pasang Kabel: OFF';
        statusText.textContent = 'Mode Pindah Perangkat (Drag-and-Drop)';
        if (connectStartDeviceId) {
            const startEl = document.getElementById(connectStartDeviceId);
            if (startEl) startEl.classList.remove('device-selected');
            connectStartDeviceId = null;
        }
    }
}

function redrawCables() {
    const svg = document.getElementById('svgLayer');
    svg.innerHTML = '';

    connections.forEach((conn, index) => {
        const fromDev = devices.find(d => d.id === conn.from);
        const toDev = devices.find(d => d.id === conn.to);
        if (!fromDev || !toDev) return;

        const x1 = fromDev.x + 42;
        const y1 = fromDev.y + 40;
        const x2 = toDev.x + 42;
        const y2 = toDev.y + 40;

        // Background cable glow
        const glow = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        glow.setAttribute('x1', x1);
        glow.setAttribute('y1', y1);
        glow.setAttribute('x2', x2);
        glow.setAttribute('y2', y2);
        glow.setAttribute('stroke', '#0284c7');
        glow.setAttribute('stroke-width', '5');
        glow.setAttribute('opacity', '0.25');
        svg.appendChild(glow);

        // Core cable
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('stroke', '#38bdf8');
        line.setAttribute('stroke-width', '2.5');
        line.setAttribute('stroke-linecap', 'round');
        svg.appendChild(line);
    });
}

function deleteDevice(devId, event) {
    if (event) event.stopPropagation();

    devices = devices.filter(d => d.id !== devId);
    connections = connections.filter(c => c.from !== devId && c.to !== devId);

    const el = document.getElementById(devId);
    if (el) el.remove();

    redrawCables();
    updateStats();
}

function clearCanvas() {
    Swal.fire({
        title: 'Bersihkan Canvas?',
        text: 'Semua perangkat dan kabel yang telah dirangkai akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            devices = [];
            connections = [];
            connectStartDeviceId = null;
            document.getElementById('deviceContainer').innerHTML = '';
            document.getElementById('svgLayer').innerHTML = '';
            updateStats();
        }
    });
}

function testPingAnimation() {
    if (connections.length === 0) {
        Swal.fire({ icon: 'info', title: 'Belum Ada Jalur', text: 'Pasang minimal satu kabel antar-perangkat untuk menguji ping paket.' });
        return;
    }

    const svg = document.getElementById('svgLayer');
    
    connections.forEach((conn) => {
        const fromDev = devices.find(d => d.id === conn.from);
        const toDev = devices.find(d => d.id === conn.to);
        if (!fromDev || !toDev) return;

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('r', '6');
        circle.setAttribute('fill', '#22c55e');
        circle.setAttribute('filter', 'drop-shadow(0 0 6px #22c55e)');

        const animX = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
        animX.setAttribute('attributeName', 'cx');
        animX.setAttribute('from', fromDev.x + 42);
        animX.setAttribute('to', toDev.x + 42);
        animX.setAttribute('dur', '1.2s');
        animX.setAttribute('repeatCount', '3');

        const animY = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
        animY.setAttribute('attributeName', 'cy');
        animY.setAttribute('from', fromDev.y + 40);
        animY.setAttribute('to', toDev.y + 40);
        animY.setAttribute('dur', '1.2s');
        animY.setAttribute('repeatCount', '3');

        circle.appendChild(animX);
        circle.appendChild(animY);
        svg.appendChild(circle);

        setTimeout(() => { circle.remove(); }, 3600);
    });

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Ping Paket Transmisi Berjalan!',
        showConfirmButton: false,
        timer: 3000
    });
}

function updateStats() {
    document.getElementById('canvasStats').textContent = `Perangkat: ${devices.length} • Kabel: ${connections.length}`;
}

function submitSimulasiGuru() {
    if (devices.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Canvas Masih Kosong', text: 'Tarik perangkat jaringan dan hubungkan kabel terlebih dahulu.' });
        return;
    }

    Swal.fire({
        title: 'Memvalidasi Desain Topologi...',
        text: 'Menguji kelaikan struktur arsitektur topologi kurikulum...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const canvasData = {
        devices: devices.map(d => ({ id: d.id, type: d.type, name: d.name, x: d.x, y: d.y })),
        connections: connections.map(c => ({ from: c.from, to: c.to, fromId: c.from, toId: c.to }))
    };

    fetch('<?= base_url('guru/simulasi/submit/' . $challenge['id']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            canvas_data: canvasData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            const res = data.data;
            if (res.is_correct) {
                Swal.fire({
                    icon: 'success',
                    title: 'Topologi Sesuai Kriteria Kurikulum!',
                    text: res.feedback,
                    footer: `Skor Desain: ${res.skor}/100. Konfigurasi ini memenuhi rubrik penilaian pembelajaran.`
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Struktur Topologi Kurang Tepat',
                    text: res.feedback,
                    footer: `Skor Evaluasi: ${res.skor}/100. Sesuaikan susunan kabel konsentrator atau host.`
                });
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Koneksi Gagal', text: 'Gagal menghubungi server evaluasi.' });
    });
}

function saveCanvasAsKey() {
    if (devices.length === 0) {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Canvas Masih Kosong', 
            text: 'Silakan tarik perangkat dan hubungkan kabel terlebih dahulu di canvas sebelum disimpan sebagai kunci jawaban.' 
        });
        return;
    }

    Swal.fire({
        title: 'Simpan Sebagai Kunci Jawaban?',
        text: `Rangkaian ${devices.length} perangkat dan ${connections.length} kabel di canvas ini akan dijadikan standar kunci jawaban siswa untuk tantangan ini.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan Kunci Jawaban',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyimpan Kunci Jawaban...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const canvasData = {
                devices: devices.map(d => ({ id: d.id, type: d.type, name: d.name, x: d.x, y: d.y })),
                connections: connections.map(c => ({ from: c.from, to: c.to, fromId: c.from, toId: c.to }))
            };

            fetch('<?= base_url('guru/simulasi/saveKey/' . $challenge['id']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    canvas_data: canvasData
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Kunci Jawaban Tersimpan!',
                        text: data.message || 'Rangkaian topologi di canvas berhasil dijadikan acuan standar penilaian otomatis siswa.'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Koneksi Gagal', text: 'Gagal menghubungi server.' });
            });
        }
    });
}

const savedKeyDevices = <?= json_encode($tc['devices'] ?? []) ?>;
const savedKeyConnections = <?= json_encode($tc['connections'] ?? []) ?>;

function loadSavedKey() {
    if (!savedKeyDevices || savedKeyDevices.length === 0) {
        Swal.fire({ 
            icon: 'info', 
            title: 'Belum Ada Kunci Canvas', 
            text: 'Kunci jawaban belum pernah dirangkai dan disimpan di canvas ini. Anda bisa merangkai sekarang lalu klik Simpan Kunci Jawaban.' 
        });
        return;
    }

    // Bersihkan canvas
    devices = [];
    connections = [];
    document.getElementById('deviceContainer').innerHTML = '';
    document.getElementById('svgLayer').innerHTML = '';
    deviceCounter = 0;

    // Render node perangkat dari kunci jawaban
    savedKeyDevices.forEach(d => {
        addDeviceToCanvas(d.type, d.x, d.y);
    });

    // Pasang koneksi kabel
    setTimeout(() => {
        connections = savedKeyConnections.slice();
        redrawCables();
        updateStats();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Kunci Jawaban Ditampilkan di Canvas!',
            showConfirmButton: false,
            timer: 2500
        });
    }, 150);
}
</script>
