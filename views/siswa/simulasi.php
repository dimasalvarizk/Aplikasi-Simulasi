<div class="space-y-4">
    <!-- Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-3xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= base_url('siswa/simulasi') ?>" class="w-9 h-9 rounded-xl bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-600 transition text-xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] font-extrabold uppercase rounded-lg">Topologi <?= ucfirst($challenge['tipe_topologi']) ?></span>
                    <span class="text-xs font-bold text-amber-500">⭐ +<?= $challenge['xp_reward'] ?> XP</span>
                </div>
                <h1 class="text-lg font-extrabold text-gray-800 line-clamp-1"><?= htmlspecialchars($challenge['judul']) ?></h1>
            </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="flex items-center gap-2 flex-wrap">
            <button id="btnConnectMode" onclick="toggleConnectMode()" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition">
                <i class="fas fa-network-wired"></i> <span id="connectModeLabel">Mode Pasang Kabel: OFF</span>
            </button>
            <button onclick="testPingAnimation()" class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition">
                <i class="fas fa-paper-plane"></i> Tes Ping Paket
            </button>
            <button onclick="clearCanvas()" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-xs flex items-center gap-1 transition" title="Bersihkan Canvas">
                <i class="fas fa-trash-alt"></i> Bersihkan
            </button>
            <button onclick="submitSimulasi()" class="px-5 py-2 bg-gradient-to-r from-primary-600 to-indigo-600 hover:opacity-95 text-white font-extrabold rounded-xl text-xs flex items-center gap-1.5 transition shadow-md">
                <i class="fas fa-check-circle"></i> Validasi & Klaim XP
            </button>
        </div>
    </div>

    <!-- Main Sandbox Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Panel Kiri: Toolbar Perangkat Jaringan (Draggable) -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
            <div>
                <h3 class="font-extrabold text-gray-800 text-xs uppercase tracking-wider">Perangkat Jaringan</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Tarik (drag) perangkat ke canvas di sebelah kanan.</p>
            </div>

            <div class="space-y-2.5">
                <!-- Device: Switch -->
                <div draggable="true" ondragstart="dragDevice(event, 'switch')"
                     class="p-3 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-primary-300 rounded-2xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white text-lg shadow-sm">
                        🟢
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-xs">Switch (Layer 2)</h4>
                        <span class="text-[10px] text-gray-400">Konsentrator LAN</span>
                    </div>
                </div>

                <!-- Device: PC Client -->
                <div draggable="true" ondragstart="dragDevice(event, 'pc')"
                     class="p-3 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-primary-300 rounded-2xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white text-lg shadow-sm">
                        💻
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-xs">PC Workstation</h4>
                        <span class="text-[10px] text-gray-400">Host Komputer</span>
                    </div>
                </div>

                <!-- Device: Router -->
                <div draggable="true" ondragstart="dragDevice(event, 'router')"
                     class="p-3 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-primary-300 rounded-2xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-600 flex items-center justify-center text-white text-lg shadow-sm">
                        🔴
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-xs">Router (Layer 3)</h4>
                        <span class="text-[10px] text-gray-400">Gateway Jaringan</span>
                    </div>
                </div>

                <!-- Device: Hub -->
                <div draggable="true" ondragstart="dragDevice(event, 'hub')"
                     class="p-3 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-primary-300 rounded-2xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-white text-lg shadow-sm">
                        🟡
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-xs">Hub (Layer 1)</h4>
                        <span class="text-[10px] text-gray-400">Broadcast Repeater</span>
                    </div>
                </div>

                <!-- Device: Server -->
                <div draggable="true" ondragstart="dragDevice(event, 'server')"
                     class="p-3 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-primary-300 rounded-2xl cursor-grab active:cursor-grabbing transition flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center text-white text-lg shadow-sm">
                        🖥️
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-xs">Server Host</h4>
                        <span class="text-[10px] text-gray-400">Pusat Layanan</span>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Instruksi Tantangan -->
            <div class="pt-4 border-t border-gray-100 space-y-2">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">Panduan Tantangan</span>
                <div class="text-xs text-gray-600 leading-relaxed bg-indigo-50/50 p-3.5 rounded-2xl border border-indigo-100/60">
                    <?= $challenge['instruksi'] ?>
                </div>
            </div>
        </div>

        <!-- Kolom Tengah/Kanan: Canvas Interaktif -->
        <div class="lg:col-span-3">
            <div class="relative bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden h-[580px]" id="canvasArea"
                 ondragover="allowDrop(event)" ondrop="dropDevice(event)">

                <!-- Grid Background Pattern -->
                <div class="absolute inset-0 opacity-10 pointer-events-none"
                     style="background-image: radial-gradient(#6366f1 1px, transparent 1px); background-size: 24px 24px;"></div>

                <!-- SVG Cables Layer -->
                <svg id="svgLayer" class="absolute inset-0 w-full h-full pointer-events-none z-10"></svg>

                <!-- Container Perangkat Nodes -->
                <div id="deviceContainer" class="absolute inset-0 z-20"></div>

                <!-- Overlay Info Status di Bawah Canvas -->
                <div class="absolute bottom-4 left-4 right-4 z-30 pointer-events-none flex items-center justify-between">
                    <div class="px-4 py-2 bg-slate-800/90 backdrop-blur-md rounded-2xl border border-slate-700/80 text-white text-xs flex items-center gap-3 pointer-events-auto shadow-lg">
                        <span id="canvasStats">Perangkat: 0 • Kabel: 0</span>
                        <span class="text-slate-500">|</span>
                        <span id="modeStatusText" class="text-indigo-400 font-semibold">Mode Pindah Perangkat</span>
                    </div>

                    <div class="px-4 py-2 bg-slate-800/90 backdrop-blur-md rounded-2xl border border-slate-700/80 text-slate-300 text-[11px] pointer-events-auto">
                        💡 Tips: Aktifkan <strong>Mode Pasang Kabel</strong>, lalu klik 2 perangkat berturut-turut untuk menghubungkan.
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
    box-shadow: 0 0 0 3px #6366f1, 0 0 20px rgba(99,102,241,0.5) !important;
}
.packet-ping {
    animation: packetMove 1s ease-in-out infinite;
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

    let ip;
    if (type === 'pc') {
        ip = '192.168.1.' + (10 + deviceCounter);
    } else if (type === 'server') {
        ip = '192.168.1.' + (100 + deviceCounter);
    } else if (type === 'router') {
        ip = '192.168.1.1';
    } else if (type === 'switch') {
        ip = '192.168.1.' + (240 + (deviceCounter % 10));
    } else {
        ip = 'Layer 1 (No IP)';
    }

    const mac = '00:1A:2B:3C:4D:' + deviceCounter.toString(16).padStart(2, '0').toUpperCase();

    const device = {
        id: id,
        type: type,
        name: name,
        ip: ip,
        mac: mac,
        subnet: '255.255.255.0',
        gateway: '192.168.1.1',
        x: x,
        y: y
    };

    devices.push(device);
    renderDeviceNode(device);
    updateStats();
}

function getDeviceIcon(type) {
    switch(type) {
        case 'router': return '🔴';
        case 'switch': return '🟢';
        case 'hub': return '🟡';
        case 'server': return '🖥️';
        case 'pc': default: return '💻';
    }
}

function renderDeviceNode(d) {
    const container = document.getElementById('deviceContainer');
    const el = document.createElement('div');
    el.id = d.id;
    el.className = 'device-node bg-slate-900/95 border-2 border-slate-600 rounded-2xl p-2.5 text-center text-white shadow-xl flex flex-col items-center justify-center min-w-[92px] hover:border-sky-400 transition cursor-pointer select-none';
    el.style.left = d.x + 'px';
    el.style.top = d.y + 'px';

    el.innerHTML = `
        <span class="text-2xl block mb-0.5">${getDeviceIcon(d.type)}</span>
        <span class="text-[10px] font-black tracking-wider block text-slate-100">${d.name}</span>
        <span class="text-[8px] font-mono bg-slate-950/90 text-sky-300 px-1.5 py-0.5 rounded border border-slate-700/80 mt-1 block">${d.ip}</span>
        <button onclick="deleteDevice('${d.id}', event)" class="absolute -top-2 -right-2 w-5 h-5 bg-rose-600 hover:bg-rose-700 text-white rounded-full text-[9px] flex items-center justify-center font-bold shadow transition" title="Hapus Perangkat">✕</button>
    `;

    // Click handler: connect mode or show details
    el.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
        if (isConnectMode) {
            handleDeviceClick(d.id);
        } else {
            showDeviceDetailsModal(d);
        }
    });

    // Drag inside canvas
    let isDragging = false;
    let startX, startY;

    el.addEventListener('mousedown', (e) => {
        if (isConnectMode || e.target.tagName === 'BUTTON') return;
        isDragging = true;
        startX = e.clientX - el.offsetLeft;
        startY = e.clientY - el.offsetTop;
        el.style.zIndex = '50';

        const onMouseMove = (ev) => {
            if (!isDragging) return;
            const canvasRect = document.getElementById('canvasArea').getBoundingClientRect();
            let newX = Math.max(10, Math.min(canvasRect.width - 100, ev.clientX - startX));
            let newY = Math.max(10, Math.min(canvasRect.height - 100, ev.clientY - startY));

            el.style.left = newX + 'px';
            el.style.top = newY + 'px';

            d.x = newX;
            d.y = newY;
            drawConnections();
        };

        const onMouseUp = () => {
            isDragging = false;
            el.style.zIndex = '20';
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    container.appendChild(el);
}

function showDeviceDetailsModal(d) {
    const connectedEdges = connections.filter(c => c.from === d.id || c.to === d.id);
    const neighborNames = connectedEdges.map(c => {
        const otherId = c.from === d.id ? c.to : c.from;
        const other = devices.find(x => x.id === otherId);
        return other ? other.name : 'Unknown';
    });

    const isHost = d.type === 'pc' || d.type === 'server';

    Swal.fire({
        title: `<div class="flex items-center gap-2 text-base text-gray-800 font-extrabold text-left">${getDeviceIcon(d.type)} Konfigurasi ${d.name}</div>`,
        html: `
            <div class="space-y-2 text-left text-xs bg-gray-50 p-3 rounded-xl border border-gray-200 mt-2">
                <div class="flex justify-between py-1 border-b border-gray-200">
                    <span class="text-gray-500">Tipe Perangkat:</span>
                    <span class="font-bold text-gray-800 uppercase">${d.type}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-200">
                    <span class="text-gray-500">IPv4 Address:</span>
                    <span class="font-mono font-bold text-indigo-700">${d.ip}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-200">
                    <span class="text-gray-500">Subnet Mask:</span>
                    <span class="font-mono text-gray-700">${d.subnet}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-200">
                    <span class="text-gray-500">Default Gateway:</span>
                    <span class="font-mono text-gray-700">${d.gateway}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-200">
                    <span class="text-gray-500">MAC Address:</span>
                    <span class="font-mono text-gray-600">${d.mac}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500">Kabel Terhubung:</span>
                    <span class="font-bold text-gray-800">${connectedEdges.length} Port (${neighborNames.length ? neighborNames.join(', ') : 'Belum terpasang'})</span>
                </div>
            </div>
        `,
        showCancelButton: isHost,
        confirmButtonText: isHost ? '🚀 Tes Ping dari Sini' : 'Tutup',
        cancelButtonText: 'Tutup',
        confirmButtonColor: '#4f46e5'
    }).then((res) => {
        if (res.isConfirmed && isHost) {
            testPingAnimation(d.id);
        }
    });
}

function handleDeviceClick(deviceId) {
    if (!isConnectMode) return;

    if (!connectStartDeviceId) {
        connectStartDeviceId = deviceId;
        document.getElementById(deviceId)?.classList.add('device-selected');
        document.getElementById('modeStatusText').textContent = 'Pilih perangkat kedua untuk memasang kabel...';
    } else {
        if (connectStartDeviceId !== deviceId) {
            addCableConnection(connectStartDeviceId, deviceId);
        }
        document.getElementById(connectStartDeviceId)?.classList.remove('device-selected');
        connectStartDeviceId = null;
        document.getElementById('modeStatusText').textContent = 'Mode Pasang Kabel Aktif';
    }
}

function toggleConnectMode() {
    isConnectMode = !isConnectMode;
    const btn = document.getElementById('btnConnectMode');
    const label = document.getElementById('connectModeLabel');
    const status = document.getElementById('modeStatusText');

    if (isConnectMode) {
        btn.classList.replace('bg-indigo-50', 'bg-indigo-600');
        btn.classList.replace('text-indigo-700', 'text-white');
        label.textContent = 'Mode Pasang Kabel: ON';
        status.textContent = 'Klik perangkat pertama, lalu klik perangkat kedua untuk memasang kabel.';
    } else {
        btn.classList.replace('bg-indigo-600', 'bg-indigo-50');
        btn.classList.replace('text-white', 'text-indigo-700');
        label.textContent = 'Mode Pasang Kabel: OFF';
        status.textContent = 'Mode Pindah Perangkat (Drag & Drop)';
        if (connectStartDeviceId) {
            document.getElementById(connectStartDeviceId)?.classList.remove('device-selected');
            connectStartDeviceId = null;
        }
    }
}

function addCableConnection(fromId, toId) {
    // Hindari duplikasi
    const exists = connections.some(c => 
        (c.from === fromId && c.to === toId) || (c.from === toId && c.to === fromId)
    );
    if (exists) return;

    connections.push({ from: fromId, to: toId });
    drawConnections();
    updateStats();
}

function deleteDevice(id, event) {
    event.stopPropagation();
    devices = devices.filter(d => d.id !== id);
    connections = connections.filter(c => c.from !== id && c.to !== id);
    document.getElementById(id)?.remove();
    drawConnections();
    updateStats();
}

function clearCanvas() {
    devices = [];
    connections = [];
    document.getElementById('deviceContainer').innerHTML = '';
    document.getElementById('svgLayer').innerHTML = '';
    updateStats();
}

function drawConnections() {
    const svg = document.getElementById('svgLayer');
    svg.innerHTML = '';

    connections.forEach((c, idx) => {
        const fromEl = document.getElementById(c.from);
        const toEl = document.getElementById(c.to);
        if (!fromEl || !toEl) return;

        const x1 = fromEl.offsetLeft + fromEl.offsetWidth / 2;
        const y1 = fromEl.offsetTop + fromEl.offsetHeight / 2;
        const x2 = toEl.offsetLeft + toEl.offsetWidth / 2;
        const y2 = toEl.offsetTop + toEl.offsetHeight / 2;

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('stroke', '#6366f1');
        line.setAttribute('stroke-width', '3');
        line.setAttribute('stroke-linecap', 'round');
        line.setAttribute('stroke-dasharray', '6 3');

        svg.appendChild(line);
    });
}

function findNetworkPath(startId, endId) {
    if (startId === endId) return [startId];
    const queue = [[startId]];
    const visited = new Set([startId]);

    while (queue.length > 0) {
        const path = queue.shift();
        const current = path[path.length - 1];

        const neighbors = [];
        connections.forEach(c => {
            if (c.from === current) neighbors.push(c.to);
            if (c.to === current) neighbors.push(c.from);
        });

        for (const nb of neighbors) {
            if (!visited.has(nb)) {
                visited.add(nb);
                const newPath = [...path, nb];
                if (nb === endId) return newPath;
                queue.push(newPath);
            }
        }
    }
    return null;
}

function testPingAnimation(preselectedSourceId = null) {
    const hosts = devices.filter(d => d.type === 'pc' || d.type === 'server');
    if (hosts.length < 2) {
        Swal.fire({
            icon: 'warning',
            title: 'Perangkat Belum Cukup',
            text: 'Tarik minimal 2 PC Client / Server ke canvas untuk melakukan tes ping komunikasi data!'
        });
        return;
    }

    let defaultSource = preselectedSourceId || hosts[0].id;
    let defaultTarget = hosts.find(h => h.id !== defaultSource)?.id || hosts[1].id;

    let sourceOptions = hosts.map(h => `<option value="${h.id}" ${h.id === defaultSource ? 'selected' : ''}>${h.name} (${h.ip})</option>`).join('');
    let targetOptions = hosts.map(h => `<option value="${h.id}" ${h.id === defaultTarget ? 'selected' : ''}>${h.name} (${h.ip})</option>`).join('');

    Swal.fire({
        title: '<div class="text-base font-extrabold text-gray-800 flex items-center gap-2"><i class="fas fa-terminal text-indigo-600"></i> Uji Ping Konektivitas (ICMP)</div>',
        html: `
            <div class="text-left space-y-3 mt-2 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">1. Komputer Pengirim (Source):</label>
                    <select id="pingSource" class="w-full border border-gray-300 rounded-xl p-2.5 bg-gray-50 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                        ${sourceOptions}
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">2. Komputer Tujuan (Target IP):</label>
                    <select id="pingTarget" class="w-full border border-gray-300 rounded-xl p-2.5 bg-gray-50 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                        ${targetOptions}
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '🚀 Jalankan Ping Test',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#4f46e5',
        preConfirm: () => {
            const sId = document.getElementById('pingSource').value;
            const tId = document.getElementById('pingTarget').value;
            if (sId === tId) {
                Swal.showValidationMessage('Komputer pengirim dan penerima tidak boleh sama!');
                return false;
            }
            return { sourceId: sId, targetId: tId };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        executePingSimulation(result.value.sourceId, result.value.targetId);
    });
}

function executePingSimulation(sourceId, targetId) {
    const source = devices.find(d => d.id === sourceId);
    const target = devices.find(d => d.id === targetId);
    if (!source || !target) return;

    const path = findNetworkPath(sourceId, targetId);
    const targetType = '<?= strtolower($challenge['tipe_topologi'] ?? '') ?>';

    let isValid = true;
    let diagnosticMsg = '';

    if (!path) {
        isValid = false;
        diagnosticMsg = 'Kabel terputus! Tidak ditemukan rute fisik yang menghubungkan ' + source.name + ' ke ' + target.name + '.';
    } else if (targetType === 'bus') {
        const forbidden = devices.filter(d => ['switch', 'hub', 'router'].includes(d.type));
        if (forbidden.length > 0) {
            isValid = false;
            diagnosticMsg = 'Hardware Collision: Ditemukan perangkat ' + forbidden.map(f => f.name).join(', ') + ' di Topologi Bus! Topologi Bus murni menggunakan kabel coaxial antar-PC tanpa switch.';
        } else {
            for (let d of devices) {
                const deg = connections.filter(c => c.from === d.id || c.to === d.id).length;
                if (deg > 2) {
                    isValid = false;
                    diagnosticMsg = 'Bus Impedance Mismatch: Terdeteksi percabangan bintang pada ' + d.name + ' (' + deg + ' kabel). Topologi Bus harus rantai lurus tanpa cabang!';
                    break;
                }
            }
        }
    } else if (targetType === 'star') {
        if (path.length === 2) {
            const devA = devices.find(d => d.id === path[0]);
            const devB = devices.find(d => d.id === path[1]);
            if ((devA.type === 'pc' || devA.type === 'server') && (devB.type === 'pc' || devB.type === 'server')) {
                isValid = false;
                diagnosticMsg = 'Pelanggaran Topologi Star: Terdeteksi kabel langsung antar-PC (' + devA.name + ' ke ' + devB.name + '). Semua paket harus melewati Switch pusat!';
            }
        }
    }

    const pathNames = path ? path.map(id => devices.find(d => d.id === id)?.name || id) : [];

    if (isValid && path) {
        // Animate along path
        animatePathPacket(path);
    }

    // Show realistic Command Prompt terminal modal
    showCmdTerminalModal(isValid, source, target, pathNames, diagnosticMsg);
}

function animatePathPacket(pathIds) {
    const svg = document.getElementById('svgLayer');
    for (let i = 0; i < pathIds.length - 1; i++) {
        const fromEl = document.getElementById(pathIds[i]);
        const toEl = document.getElementById(pathIds[i + 1]);
        if (!fromEl || !toEl) continue;

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('r', '6');
        circle.setAttribute('fill', '#10b981');

        const x1 = fromEl.offsetLeft + fromEl.offsetWidth / 2;
        const y1 = fromEl.offsetTop + fromEl.offsetHeight / 2;
        const x2 = toEl.offsetLeft + toEl.offsetWidth / 2;
        const y2 = toEl.offsetTop + toEl.offsetHeight / 2;

        const animateX = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
        animateX.setAttribute('attributeName', 'cx');
        animateX.setAttribute('from', x1);
        animateX.setAttribute('to', x2);
        animateX.setAttribute('dur', '1.2s');
        animateX.setAttribute('repeatCount', '3');

        const animateY = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
        animateY.setAttribute('attributeName', 'cy');
        animateY.setAttribute('from', y1);
        animateY.setAttribute('to', y2);
        animateY.setAttribute('dur', '1.2s');
        animateY.setAttribute('repeatCount', '3');

        circle.appendChild(animateX);
        circle.appendChild(animateY);
        svg.appendChild(circle);

        setTimeout(() => circle.remove(), 3600);
    }
}

function showCmdTerminalModal(success, source, target, pathNames, diagnosticMsg) {
    let bodyOutput = '';
    if (success) {
        bodyOutput = `
            <div class="text-emerald-400">Reply from ${target.ip}: bytes=32 time=1ms TTL=64</div>
            <div class="text-emerald-400">Reply from ${target.ip}: bytes=32 time=2ms TTL=64</div>
            <div class="text-emerald-400">Reply from ${target.ip}: bytes=32 time=1ms TTL=64</div>
            <div class="text-emerald-400">Reply from ${target.ip}: bytes=32 time=1ms TTL=64</div>
            <div class="mt-2 text-slate-300">
                Ping statistics for ${target.ip}:<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Packets: Sent = 4, Received = 4, Lost = 0 (0% loss),<br>
                Approximate round trip times in milli-seconds:<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Minimum = 1ms, Maximum = 2ms, Average = 1ms
            </div>
            <div class="mt-3 p-2 bg-emerald-950/60 border border-emerald-700/60 rounded text-emerald-300 font-sans">
                <div class="font-bold">✓ STATUS: SUKSES (Koneksi Stabil)</div>
                <div class="text-[11px] font-mono mt-0.5">Rute Transmisi: ${pathNames.join(' ➔ ')}</div>
            </div>
        `;
    } else {
        bodyOutput = `
            <div class="text-rose-400">Request timed out.</div>
            <div class="text-rose-400">Request timed out.</div>
            <div class="text-rose-400">Destination host unreachable.</div>
            <div class="text-rose-400">Request timed out.</div>
            <div class="mt-2 text-slate-300">
                Ping statistics for ${target.ip}:<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Packets: Sent = 4, Received = 0, Lost = 4 (100% loss)
            </div>
            <div class="mt-3 p-2 bg-rose-950/60 border border-rose-700/60 rounded text-rose-300 font-sans">
                <div class="font-bold">✗ STATUS: GAGAL / TIMED OUT</div>
                <div class="text-[11px] mt-0.5">${diagnosticMsg}</div>
            </div>
        `;
    }

    Swal.fire({
        width: 600,
        background: '#0c0c0c',
        showConfirmButton: true,
        confirmButtonText: 'Tutup Terminal',
        confirmButtonColor: '#334155',
        html: `
            <div class="text-left font-mono text-xs">
                <!-- Title bar -->
                <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-800 text-slate-400 font-sans">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-terminal text-sky-400 text-xs"></i>
                        <span class="font-bold text-slate-200">Administrator: Command Prompt (Ping Test)</span>
                    </div>
                </div>
                <!-- Console text -->
                <div class="text-slate-400 mb-2">
                    Microsoft Windows [Version 10.0.19045]<br>
                    (c) SMPN 1 Karangmoncol Network Simulator.
                </div>
                <div class="text-white font-bold mb-2">
                    C:\\Users\\Siswa&gt; ping ${target.ip} -n 4
                </div>
                <div class="text-slate-300 mb-1">
                    Pinging ${target.ip} with 32 bytes of data:
                </div>
                ${bodyOutput}
            </div>
        `
    });
}

function updateStats() {
    document.getElementById('canvasStats').textContent = `Perangkat: ${devices.length} • Kabel: ${connections.length}`;
}

function submitSimulasi() {
    if (devices.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Canvas Kosong', text: 'Tarik perangkat jaringan dan hubungkan kabel terlebih dahulu.' });
        return;
    }

    Swal.fire({
        title: 'Mengevaluasi Topologi...',
        text: 'Sistem sedang memvalidasi arsitektur graf topologi...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const canvasData = {
        devices: devices.map(d => ({ id: d.id, type: d.type, name: d.name, x: d.x, y: d.y })),
        connections: connections.map(c => ({ fromId: c.from, toId: c.to }))
    };

    fetch('<?= base_url('siswa/simulasiSubmit/' . $challenge['id']) ?>', {
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
                    title: '🎉 Topologi Berhasil Divalidasi!',
                    text: res.feedback,
                    footer: res.xp_earned > 0 ? `⭐ +${res.xp_earned} XP Berhasil Ditambahkan!` : 'Kamu telah menyelesaikan tantangan ini.'
                }).then(() => {
                    window.location.href = '<?= base_url('siswa/simulasi') ?>';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Topologi Belum Sesuai',
                    text: res.feedback,
                    footer: `Skor Kamu: ${res.skor}/100. Periksa kembali struktur kabel dan perangkat Anda.`
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
</script>
