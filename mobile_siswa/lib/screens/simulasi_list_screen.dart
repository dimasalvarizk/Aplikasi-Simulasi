import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import 'simulasi_canvas_screen.dart';

class SimulasiListScreen extends StatefulWidget {
  final VoidCallback? onBack;
  const SimulasiListScreen({Key? key, this.onBack}) : super(key: key);

  @override
  State<SimulasiListScreen> createState() => _SimulasiListScreenState();
}

class _SimulasiListScreenState extends State<SimulasiListScreen> {
  bool _isLoading = true;
  List<dynamic> _challenges = [];
  String _selectedFilter = 'semua';

  final List<Map<String, dynamic>> _defaultChallenges = [
    {
      'id': 1,
      'judul': 'Tantangan 1: Rangkai Topologi Star Lab Komputer',
      'tipe_topologi': 'star',
      'kesulitan': 'Mudah',
      'xp_reward': 50,
      'deskripsi': 'Rancang topologi Star yang menghubungkan minimal 3 PC client ke 1 switch pusat secara simetris.',
      'instruksi': '1. Tarik 1 Switch ke kanvas.\n2. Tarik minimal 3 PC ke sekeliling Switch.\n3. Hubungkan setiap PC ke Switch menggunakan kabel.\n4. Klik Tes Ping untuk verifikasi.',
      'is_completed': false,
    },
    {
      'id': 2,
      'judul': 'Tantangan 2: Rangkai Jalur Topologi Bus',
      'tipe_topologi': 'bus',
      'kesulitan': 'Sedang',
      'xp_reward': 50,
      'deskripsi': 'Susun 4 unit workstation yang saling terhubung dalam satu lintasan bus lurus secara sekuensial.',
      'instruksi': '1. Tarik minimal 4 PC.\n2. Hubungkan secara linear berantai (PC 1 ke PC 2, PC 2 ke PC 3, dst).\n3. Pastikan kabel tersambung rapat.',
      'is_completed': false,
    },
    {
      'id': 3,
      'judul': 'Tantangan 3: Rangkai Topologi Ring Tertutup',
      'tipe_topologi': 'ring',
      'kesulitan': 'Sedang',
      'xp_reward': 60,
      'deskripsi': 'Buat jalur sirkular tertutup di mana setiap node terhubung tepat ke dua tetangganya.',
      'instruksi': '1. Tarik minimal 4 node ke kanvas.\n2. Hubungkan node pertama ke kedua, kedua ke ketiga, dan terakhir kembali ke node pertama.\n3. Klik Validasi.',
      'is_completed': false,
    },
  ];

  @override
  void initState() {
    super.initState();
    _loadChallenges();
  }

  Future<void> _loadChallenges() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final list = await ApiService.getSimulasi(globalUser.id);
      if (mounted) {
        setState(() {
          _challenges = list.isNotEmpty ? List<dynamic>.from(list) : List<dynamic>.from(_defaultChallenges);
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _challenges = _defaultChallenges;
          _isLoading = false;
        });
      }
    }
  }

  List<dynamic> get _filteredChallenges {
    if (_selectedFilter == 'semua') return _challenges;
    return _challenges.where((c) {
      final tipe = (c['tipe_topologi'] ?? '').toString().toLowerCase();
      return tipe == _selectedFilter;
    }).toList();
  }

  Color _getTopologyColor(String tipe) {
    switch (tipe.toLowerCase()) {
      case 'star': return const Color(0xFF2563EB);
      case 'bus': return const Color(0xFF059669);
      case 'ring': return const Color(0xFF7C3AED);
      case 'mesh': return const Color(0xFFDC2626);
      case 'tree': return const Color(0xFFD97706);
      default: return const Color(0xFF475569);
    }
  }

  String _getTopologyIcon(String tipe) {
    switch (tipe.toLowerCase()) {
      case 'star': return '⭐';
      case 'bus': return '🚌';
      case 'ring': return '⭕';
      case 'mesh': return '🕸️';
      case 'tree': return '🌳';
      default: return '🌐';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          tooltip: 'Kembali',
          onPressed: () {
            if (widget.onBack != null) {
              widget.onBack!();
            } else if (Navigator.canPop(context)) {
              Navigator.pop(context);
            }
          },
        ),
        title: const Text('Laboratorium Topologi 2D', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 20),
            onPressed: _loadChallenges,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadChallenges,
        color: const Color(0xFF0F172A),
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          children: [
            // 1. BANNER HEADER INTERAKTIF
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(14),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withOpacity(0.2),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: const Color(0xFF38BDF8).withOpacity(0.15),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFF38BDF8).withOpacity(0.3)),
                    ),
                    child: const Center(
                      child: Text('🛠️', style: TextStyle(fontSize: 22)),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Praktik Arsitektur Jaringan',
                          style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Tersedia ${_challenges.length} soal tantangan yang siap dirangkai di kanvas simulasi.',
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11, height: 1.3),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // 2. FILTER CHIPS INTERAKTIF
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildFilterChip('semua', 'Semua (${_challenges.length})'),
                  const SizedBox(width: 6),
                  _buildFilterChip('star', '⭐ Star'),
                  const SizedBox(width: 6),
                  _buildFilterChip('bus', '🚌 Bus'),
                  const SizedBox(width: 6),
                  _buildFilterChip('ring', '⭕ Ring'),
                  const SizedBox(width: 6),
                  _buildFilterChip('tree', '🌳 Tree'),
                  const SizedBox(width: 6),
                  _buildFilterChip('mesh', '🕸️ Mesh'),
                ],
              ),
            ),

            const SizedBox(height: 14),

            // 3. DAFTAR KARTU TANTANGAN
            if (_isLoading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(40),
                  child: CircularProgressIndicator(color: Color(0xFF0F172A)),
                ),
              )
            else if (_filteredChallenges.isEmpty)
              Container(
                padding: const EdgeInsets.all(30),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Center(
                  child: Text('Tidak ada tantangan untuk kategori ini.', style: TextStyle(color: Color(0xFF64748B))),
                ),
              )
            else
              ..._filteredChallenges.map((c) {
                final int id = c['id'] is int ? c['id'] : (int.tryParse('${c['id']}') ?? 0);
                final String judul = c['judul'] ?? 'Tantangan Topologi';
                final String tipe = c['tipe_topologi'] ?? 'star';
                final String kesulitan = c['kesulitan'] ?? 'Sedang';
                final int xp = c['xp_reward'] is int ? c['xp_reward'] : (int.tryParse('${c['xp_reward']}') ?? 50);
                final String deskripsi = c['deskripsi'] ?? '';
                final String instruksi = c['instruksi'] ?? deskripsi;
                final bool isCompleted = c['is_completed'] == true ||
                    c['is_completed'] == 1 ||
                    c['is_completed'] == '1' ||
                    c['sudah_lulus'] == 1 ||
                    c['sudah_lulus'] == '1';

                final topColor = _getTopologyColor(tipe);
                final topIcon = _getTopologyIcon(tipe);

                return Container(
                  margin: const EdgeInsets.only(bottom: 14),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isCompleted ? const Color(0xFF86EFAC) : const Color(0xFFE2E8F0),
                      width: isCompleted ? 1.5 : 1.0,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.02),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Header Card: Badge Topologi & Reward XP
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                            decoration: BoxDecoration(
                              color: topColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: topColor.withOpacity(0.3)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(topIcon, style: const TextStyle(fontSize: 12)),
                                const SizedBox(width: 5),
                                Text(
                                  'Topologi ${tipe.toUpperCase()}',
                                  style: TextStyle(
                                    fontSize: 10.5,
                                    fontWeight: FontWeight.w800,
                                    color: topColor,
                                    letterSpacing: 0.4,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const Spacer(),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: const Color(0xFFFDE68A)),
                            ),
                            child: Text(
                              '+$xp XP',
                              style: const TextStyle(
                                color: Color(0xFFB45309),
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 10),

                      // Judul Tantangan
                      Text(
                        judul,
                        style: const TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF0F172A),
                          height: 1.3,
                        ),
                      ),

                      const SizedBox(height: 6),

                      // Deskripsi Singkat
                      Text(
                        deskripsi.isNotEmpty ? deskripsi : instruksi,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Color(0xFF64748B),
                          height: 1.4,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),

                      const SizedBox(height: 14),

                      // Footer Card: Status & Tombol Aksi
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: isCompleted ? const Color(0xFFECFDF5) : const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  isCompleted ? Icons.check_circle : Icons.schedule,
                                  size: 13,
                                  color: isCompleted ? const Color(0xFF10B981) : const Color(0xFF64748B),
                                ),
                                const SizedBox(width: 5),
                                Text(
                                  isCompleted ? 'Selesai Lulus' : 'Belum Dikerjakan',
                                  style: TextStyle(
                                    color: isCompleted ? const Color(0xFF059669) : const Color(0xFF64748B),
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const Spacer(),
                          ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: isCompleted ? const Color(0xFF0F172A) : const Color(0xFF2563EB),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              elevation: 1,
                            ),
                            onPressed: () async {
                              await Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => SimulasiCanvasScreen(
                                    challengeId: id,
                                    judul: judul,
                                    tipeTopologi: tipe,
                                    instruksi: instruksi,
                                    xpReward: xp,
                                  ),
                                ),
                              );
                              _loadChallenges();
                            },
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(isCompleted ? Icons.open_in_new : Icons.play_arrow, size: 15),
                                const SizedBox(width: 5),
                                Text(
                                  isCompleted ? 'Buka Kanvas' : 'Mulai Praktik',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final bool isSelected = _selectedFilter == key;
    return InkWell(
      onTap: () {
        setState(() {
          _selectedFilter = key;
        });
      },
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFCBD5E1),
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withOpacity(0.2),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  )
                ]
              : [],
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
            color: isSelected ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }
}
