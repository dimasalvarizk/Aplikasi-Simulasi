import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import 'kuis_screen.dart';
import 'leaderboard_screen.dart';
import 'materi_screen.dart';
import 'profil_screen.dart';
import 'notifikasi_screen.dart';
import 'simulasi_canvas_screen.dart';
import 'simulasi_list_screen.dart';
import 'studi_kasus_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _selectedTabIndex = 0;
  bool _isLoading = true;
  int _unreadNotifCount = 0;

  List<dynamic> _modulList = [];
  List<dynamic> _kuisList = [];
  List<dynamic> _simulasiList = [];

  @override
  void initState() {
    super.initState();
    globalUser.addListener(_onUserUpdated);
    _loadAllData();
  }

  @override
  void dispose() {
    globalUser.removeListener(_onUserUpdated);
    super.dispose();
  }

  void _onUserUpdated() {
    if (mounted) setState(() {});
  }

  Future<void> _loadAllData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final results = await Future.wait([
        ApiService.getModul(globalUser.id),
        ApiService.getKuis(globalUser.id),
        ApiService.getProfil(globalUser.id),
        ApiService.getSimulasi(globalUser.id),
        ApiService.getNotifikasi(globalUser.id),
      ]);

      if (!mounted) return;

      setState(() {
        _modulList = results[0] as List<dynamic>;
        _kuisList = results[1] as List<dynamic>;

        final profilData = results[2] as Map<String, dynamic>?;
        if (profilData != null && profilData['user'] != null) {
          globalUser.setFromApi(profilData['user'], globalUser.token);
        }

        _simulasiList = results[3] as List<dynamic>;

        final notifData = results[4] as Map<String, dynamic>?;
        if (notifData != null) {
          _unreadNotifCount = (notifData['unread_count'] as num?)?.toInt() ?? 0;
        }

        _isLoading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        if (_selectedTabIndex != 0) {
          setState(() {
            _selectedTabIndex = 0;
          });
          return false;
        }
        return true;
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFF8FAFC),
        appBar: _selectedTabIndex == 0
            ? AppBar(
                title: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: Image.asset(
                        'assets/images/logo.jpg',
                        width: 26,
                        height: 26,
                        fit: BoxFit.contain,
                        errorBuilder: (_, __, ___) => const Icon(Icons.school_outlined, size: 20),
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Text(
                      'SMPN 1 Karangmoncol',
                      style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15, letterSpacing: -0.2),
                    ),
                  ],
                ),
                actions: [
                  // XP Pill Clean
                  GestureDetector(
                    onTap: () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfilScreen()));
                    },
                    child: Container(
                      margin: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Text(
                        '${globalUser.totalXp} XP',
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontWeight: FontWeight.w600,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ),
                  // Notification Bell with Badge
                  Stack(
                    alignment: Alignment.center,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.notifications_outlined, size: 22, color: Color(0xFF334155)),
                        tooltip: 'Pemberitahuan',
                        onPressed: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => const NotifikasiScreen()),
                          );
                          _loadAllData();
                        },
                      ),
                      if (_unreadNotifCount > 0)
                        Positioned(
                          right: 8,
                          top: 10,
                          child: Container(
                            padding: const EdgeInsets.all(3),
                            decoration: BoxDecoration(
                              color: const Color(0xFFEF4444),
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 1.5),
                            ),
                            constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                            child: Text(
                              _unreadNotifCount > 9 ? '9+' : '$_unreadNotifCount',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 9,
                                fontWeight: FontWeight.w800,
                                height: 1,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  ),
                  GestureDetector(
                    onTap: () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfilScreen()));
                    },
                    child: Container(
                      margin: const EdgeInsets.only(right: 8, left: 4),
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFFCBD5E1), width: 1),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: ApiService.getFullAvatarUrl(globalUser.avatar).isNotEmpty
                          ? Image.network(
                              ApiService.getFullAvatarUrl(globalUser.avatar),
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => const Icon(Icons.person_outline, size: 18, color: Color(0xFF334155)),
                            )
                          : const Icon(Icons.person_outline, size: 18, color: Color(0xFF334155)),
                    ),
                  ),
                  const SizedBox(width: 4),
                ],
              )
            : null,
        body: IndexedStack(
          index: _selectedTabIndex,
          children: [
            _buildBerandaTab(),
            _buildModulTab(),
            SimulasiListScreen(
              onBack: () => setState(() => _selectedTabIndex = 0),
            ),
            StudiKasusScreen(
              onBack: () => setState(() => _selectedTabIndex = 0),
            ),
            _buildKuisTab(),
            LeaderboardScreen(
              onBack: () => setState(() => _selectedTabIndex = 0),
            ),
          ],
        ),
        bottomNavigationBar: Container(
          decoration: const BoxDecoration(
            border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
          ),
          child: BottomNavigationBar(
            currentIndex: _selectedTabIndex,
            onTap: (index) {
              setState(() {
                _selectedTabIndex = index;
              });
            },
            type: BottomNavigationBarType.fixed,
            backgroundColor: Colors.white,
            selectedItemColor: const Color(0xFF0F172A),
            unselectedItemColor: const Color(0xFF94A3B8),
            selectedFontSize: 10,
            unselectedFontSize: 10,
            selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w700),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500),
            elevation: 0,
            items: const [
              BottomNavigationBarItem(icon: Icon(Icons.home_outlined), activeIcon: Icon(Icons.home), label: 'Beranda'),
              BottomNavigationBarItem(icon: Icon(Icons.menu_book_outlined), activeIcon: Icon(Icons.menu_book), label: 'Modul'),
              BottomNavigationBarItem(icon: Icon(Icons.hub_outlined), activeIcon: Icon(Icons.hub), label: 'Simulasi'),
              BottomNavigationBarItem(icon: Icon(Icons.security_outlined), activeIcon: Icon(Icons.security), label: 'Tantangan'),
              BottomNavigationBarItem(icon: Icon(Icons.assignment_outlined), activeIcon: Icon(Icons.assignment), label: 'Kuis'),
              BottomNavigationBarItem(icon: Icon(Icons.leaderboard_outlined), activeIcon: Icon(Icons.leaderboard), label: 'Peringkat'),
            ],
          ),
        ),
      ),
    );
  }

  // TAB 1: BERANDA INTERAKTIF
  Widget _buildBerandaTab() {
    final int xp = globalUser.totalXp;
    final int level = globalUser.level;
    final int currentLevelMin = (level - 1) * 100;
    final int nextLevelMin = level * 100;
    final int progressInLevel = xp - currentLevelMin;
    final double levelProgress = (progressInLevel / 100).clamp(0.0, 1.0);
    final int xpNeeded = (nextLevelMin - xp).clamp(0, 100);
    final int streak = globalUser.streakDays > 0 ? globalUser.streakDays : 1;

    return RefreshIndicator(
      onRefresh: _loadAllData,
      color: const Color(0xFF0F172A),
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        children: [
          // 1. HERO GAMIFICATION CARD (Gradient Interaktif)
          GestureDetector(
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfilScreen()));
            },
            child: Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF1E1B4B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withOpacity(0.25),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Top Row: Avatar, Nama & Streak
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF38BDF8), Color(0xFF6366F1)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF38BDF8).withOpacity(0.3),
                              blurRadius: 8,
                            ),
                          ],
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: ApiService.getFullAvatarUrl(globalUser.avatar).isNotEmpty
                            ? Image.network(
                                ApiService.getFullAvatarUrl(globalUser.avatar),
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => Center(
                                  child: Text(
                                    (globalUser.nama.isNotEmpty ? globalUser.nama[0] : 'S').toUpperCase(),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 20,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              )
                            : Center(
                                child: Text(
                                  (globalUser.nama.isNotEmpty ? globalUser.nama[0] : 'S').toUpperCase(),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 20,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Flexible(
                                  child: Text(
                                    globalUser.nama.isNotEmpty ? globalUser.nama : 'Siswa',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                const Text('👋', style: TextStyle(fontSize: 14)),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Kelas ${globalUser.kelas.isNotEmpty ? globalUser.kelas : 'VII'} • SMPN 1 Karangmoncol',
                              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11.5),
                            ),
                          ],
                        ),
                      ),
                      // Streak Flame Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEA580C).withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFFF97316).withOpacity(0.4)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Text('🔥', style: TextStyle(fontSize: 12)),
                            const SizedBox(width: 4),
                            Text(
                              '$streak Hari',
                              style: const TextStyle(
                                color: Color(0xFFFDBA74),
                                fontWeight: FontWeight.w700,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 18),

                  // Level & XP Bar
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFF6366F1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              'LEVEL $level',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10.5,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            globalUser.levelTitle,
                            style: const TextStyle(
                              color: Color(0xFFE2E8F0),
                              fontSize: 12.5,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                      Text(
                        '⭐ $xp Total XP',
                        style: const TextStyle(
                          color: Color(0xFFFBBF24),
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 10),

                  // Progress Bar dengan Gradient
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: Stack(
                      children: [
                        Container(
                          height: 8,
                          color: Colors.white.withOpacity(0.1),
                        ),
                        FractionallySizedBox(
                          widthFactor: levelProgress,
                          child: Container(
                            height: 8,
                            decoration: const BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Color(0xFF38BDF8), Color(0xFF818CF8)],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 8),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        '$xp / $nextLevelMin XP • Menuju Level ${level + 1}',
                        style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 11, fontWeight: FontWeight.w600),
                      ),
                      const Text(
                        'Profil & Badges ➔',
                        style: TextStyle(
                          color: Color(0xFF38BDF8),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 18),

          // 2. QUICK STATS MINI ROW
          Row(
            children: [
              Expanded(
                child: _buildMiniStatCard(
                  label: 'Modul',
                  value: '${_modulList.length}',
                  icon: Icons.menu_book,
                  color: const Color(0xFF2563EB),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMiniStatCard(
                  label: 'Kuis',
                  value: '${_kuisList.length}',
                  icon: Icons.assignment_turned_in,
                  color: const Color(0xFF7C3AED),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMiniStatCard(
                  label: 'Simulasi',
                  value: '${_simulasiList.length} Lab',
                  icon: Icons.hub,
                  color: const Color(0xFF059669),
                ),
              ),
            ],
          ),

          const SizedBox(height: 18),

          // TANTANGAN DETEKTIF SIBER (FEATURED BANNER UTAMA)
          GestureDetector(
            onTap: () => setState(() => _selectedTabIndex = 3),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF312E81), Color(0xFF1E1B4B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFF4F46E5), width: 1.5),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF312E81).withOpacity(0.35),
                    blurRadius: 14,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: const Color(0xFF38BDF8).withOpacity(0.18),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFF38BDF8).withOpacity(0.4)),
                    ),
                    child: const Center(
                      child: Icon(
                        Icons.security_rounded,
                        color: Color(0xFF38BDF8),
                        size: 28,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF10B981).withOpacity(0.2),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: const Color(0xFF34D399).withOpacity(0.5)),
                              ),
                              child: const Text(
                                'TANTANGAN SIBER BARU',
                                style: TextStyle(
                                  color: Color(0xFF34D399),
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: const [
                                Text(
                                  'Buka',
                                  style: TextStyle(
                                    color: Color(0xFF818CF8),
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                SizedBox(width: 3),
                                Icon(
                                  Icons.arrow_forward_rounded,
                                  size: 13,
                                  color: Color(0xFF818CF8),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 5),
                        const Text(
                          'Tantangan Detektif Siber',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 14.5,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Deteksi link phishing & malware WhatsApp/SMS (+XP & Badge)',
                          style: TextStyle(
                            color: Color(0xFFCBD5E1),
                            fontSize: 11,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 18),

          // 3. MENU AKSI CEPAT (5 Tombol Interaktif dengan Warna & Ikon)
          const Text(
            'Aktivitas Pembelajaran',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 10),

          Row(
            children: [
              Expanded(
                child: _buildInteractiveActionTile(
                  icon: Icons.hub,
                  title: 'Simulasi Lab',
                  subtitle: 'Rakit Kanvas 2D',
                  badge: '${_simulasiList.length} Lab',
                  gradientColors: const [Color(0xFF2563EB), Color(0xFF1D4ED8)],
                  onTap: () => setState(() => _selectedTabIndex = 2),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildInteractiveActionTile(
                  icon: Icons.security_rounded,
                  title: 'Detektif Siber',
                  subtitle: 'Kasus Phishing',
                  badge: 'Tantangan',
                  gradientColors: const [Color(0xFF4F46E5), Color(0xFF3730A3)],
                  onTap: () => setState(() => _selectedTabIndex = 3),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _buildInteractiveActionTile(
                  icon: Icons.quiz,
                  title: 'Kuis Teori',
                  subtitle: 'Uji Pemahaman',
                  badge: '${_kuisList.length} Soal',
                  gradientColors: const [Color(0xFF7C3AED), Color(0xFF6D28D9)],
                  onTap: () => setState(() => _selectedTabIndex = 4),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildInteractiveActionTile(
                  icon: Icons.menu_book_rounded,
                  title: 'Materi Modul',
                  subtitle: 'Baca Teori Jaringan',
                  badge: '${_modulList.length} Modul',
                  gradientColors: const [Color(0xFF059669), Color(0xFF047857)],
                  onTap: () => setState(() => _selectedTabIndex = 1),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _buildInteractiveActionTile(
                  icon: Icons.emoji_events,
                  title: 'Peringkat Siswa',
                  subtitle: 'Leaderboard Kelas',
                  badge: 'Juara',
                  gradientColors: const [Color(0xFFD97706), Color(0xFFB45309)],
                  onTap: () => setState(() => _selectedTabIndex = 5),
                ),
              ),
            ],
          ),

          const SizedBox(height: 20),

          // 4. HERO BANNER SIMULASI KANVAS INTERAKTIF
          Builder(
            builder: (context) {
              final firstSim = _simulasiList.isNotEmpty ? _simulasiList[0] : null;
              final int simId = firstSim != null ? (firstSim['id'] is int ? firstSim['id'] : (int.tryParse('${firstSim['id']}') ?? 1)) : 1;
              final String simJudul = firstSim != null ? (firstSim['judul'] ?? 'Tantangan Topologi') : 'Rakit Topologi Star di Kanvas';
              final String simTipe = firstSim != null ? (firstSim['tipe_topologi'] ?? 'star') : 'star';
              final String simInstruksi = firstSim != null ? (firstSim['instruksi'] ?? 'Rangkai topologi di kanvas lalu tes ping.') : '1. Tarik Switch & PC ke kanvas.\n2. Hubungkan kabel.\n3. Lakukan tes ping.';
              final int simXp = firstSim != null ? (firstSim['xp_reward'] is int ? firstSim['xp_reward'] : (int.tryParse('${firstSim['xp_reward']}') ?? 50)) : 50;

              return Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: const Color(0xFF2563EB).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFF3B82F6).withOpacity(0.4)),
                      ),
                      child: const Center(
                        child: Text('🌐', style: TextStyle(fontSize: 22)),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF10B981).withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Text(
                                  'PRAKTIK TOPOLOGI',
                                  style: TextStyle(
                                    color: Color(0xFF34D399),
                                    fontSize: 9.5,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ),
                              const Spacer(),
                              Text(
                                '+$simXp XP',
                                style: const TextStyle(
                                  color: Color(0xFFFBBF24),
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            simJudul,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 13.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Tipe: ${simTipe.toUpperCase()} • Buka simulator lab 2D',
                            style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        elevation: 0,
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => SimulasiCanvasScreen(
                              challengeId: simId,
                              judul: simJudul,
                              tipeTopologi: simTipe,
                              instruksi: simInstruksi,
                              xpReward: simXp,
                            ),
                          ),
                        );
                      },
                      child: const Text('Buka', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
              );
            },
          ),

          const SizedBox(height: 22),

          // 5. DAFTAR MODUL TERBARU
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Modul Pembelajaran',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF0F172A),
                ),
              ),
              GestureDetector(
                onTap: () => setState(() => _selectedTabIndex = 1),
                child: const Text(
                  'Lihat Semua ➔',
                  style: TextStyle(fontSize: 12, color: Color(0xFF2563EB), fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          if (_modulList.isEmpty)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Center(
                child: Text('Belum ada modul pembelajaran.', style: TextStyle(color: Color(0xFF64748B), fontSize: 13)),
              ),
            )
          else
            ..._modulList.take(3).map((modul) {
              final String judul = modul['judul'] ?? 'Modul';
              final String deskripsi = modul['deskripsi'] ?? '';
              final int materiCount = (modul['materi_count'] is int)
                  ? modul['materi_count']
                  : (modul['materi'] as List?)?.length ?? (modul['materi_list'] as List?)?.length ?? 0;

              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.02),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                  leading: Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Center(
                      child: Icon(Icons.book_outlined, color: Color(0xFF0F172A), size: 18),
                    ),
                  ),
                  title: Text(
                    judul,
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5, color: Color(0xFF0F172A)),
                  ),
                  subtitle: Text(
                    deskripsi.isNotEmpty ? deskripsi : '$materiCount Materi',
                    style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Text(
                      '$materiCount Bab',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                    ),
                  ),
                  onTap: () => setState(() => _selectedTabIndex = 1),
                ),
              );
            }).toList(),

          const SizedBox(height: 16),

          // 6. TRIVIA JARINGAN KOMPUTER
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFBFDBFE)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('💡', style: TextStyle(fontSize: 18)),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text(
                        'Tahukah Kamu? (Tips Jaringan)',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1E40AF),
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Kabel jaringan UTP Cat6 mampu mentransfer data hingga 1 Gbps (1000 Mbps)! Di topologi Star, Switch cerdas membaca MAC Address sehingga paket data hanya dikirim ke PC tujuan.',
                        style: TextStyle(fontSize: 11.5, color: Color(0xFF1E3A8A), height: 1.35),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // WIDGET MINI STAT CARD
  Widget _buildMiniStatCard({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF0F172A),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 10.5,
                    color: Color(0xFF64748B),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // WIDGET INTERACTIVE ACTION TILE
  Widget _buildInteractiveActionTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required String badge,
    required List<Color> gradientColors,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(colors: gradientColors),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [
                      BoxShadow(
                        color: gradientColors[0].withOpacity(0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Icon(icon, color: Colors.white, size: 18),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    badge,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF334155),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: Color(0xFF0F172A),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              subtitle,
              style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickAction({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            Icon(icon, size: 20, color: const Color(0xFF0F172A)),
            const SizedBox(height: 6),
            Text(
              title,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: Color(0xFF334155)),
            ),
          ],
        ),
      ),
    );
  }

  // TAB 2: MODUL & MATERI PEMBELAJARAN
  Widget _buildModulTab() {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          tooltip: 'Kembali ke Beranda',
          onPressed: () => setState(() => _selectedTabIndex = 0),
        ),
        title: const Text('Modul Pembelajaran', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
      ),
      body: RefreshIndicator(
        onRefresh: _loadAllData,
        color: const Color(0xFF0F172A),
        child: _modulList.isEmpty
            ? const Center(
                child: Text('Belum ada modul pembelajaran', style: TextStyle(color: Color(0xFF64748B))),
              )
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: _modulList.length,
                itemBuilder: (ctx, index) {
                  final modul = _modulList[index];
                  final String modulJudul = modul['judul'] ?? 'Modul ${index + 1}';
                  final List<dynamic> materi = (modul['materi'] as List<dynamic>?) ?? (modul['materi_list'] as List<dynamic>?) ?? [];

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Theme(
                      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                      child: ExpansionTile(
                        initiallyExpanded: index == 0,
                        title: Text(
                          modulJudul,
                          style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w600, fontSize: 14),
                        ),
                        subtitle: Text(
                          '${materi.length} Materi',
                          style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                        ),
                        children: [
                          const Divider(color: Color(0xFFF1F5F9), height: 1),
                          if (materi.isEmpty)
                            const Padding(
                              padding: EdgeInsets.all(14),
                              child: Text('Belum ada materi pada modul ini.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                            )
                          else
                            ...materi.map((m) {
                              final int mId = m['id'] is int ? m['id'] : (int.tryParse('${m['id']}') ?? 0);
                              final String mJudul = m['judul'] ?? 'Materi';
                              final bool isDone = m['is_completed'] == true || m['is_completed'] == 1 || m['is_completed'] == '1' || m['sudah_selesai'] == 1 || m['sudah_selesai'] == '1';
                              final int mXp = m['xp_reward'] is int ? m['xp_reward'] : (int.tryParse('${m['xp_reward']}') ?? 15);

                              return ListTile(
                                dense: true,
                                leading: Icon(
                                  isDone ? Icons.check_circle_outline : Icons.radio_button_unchecked,
                                  color: isDone ? const Color(0xFF16A34A) : const Color(0xFFCBD5E1),
                                  size: 18,
                                ),
                                title: Text(
                                  mJudul,
                                  style: TextStyle(
                                    color: isDone ? const Color(0xFF64748B) : const Color(0xFF0F172A),
                                    fontSize: 13,
                                  ),
                                ),
                                trailing: Text(
                                  '+$mXp XP',
                                  style: const TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.w500),
                                ),
                                onTap: () async {
                                  await Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (_) => MateriScreen(
                                        materiId: mId,
                                        judul: mJudul,
                                        namaModul: modulJudul,
                                      ),
                                    ),
                                  );
                                  _loadAllData();
                                },
                              );
                            }).toList(),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }

  // TAB 4: KUIS EVALUASI
  Widget _buildKuisTab() {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          tooltip: 'Kembali ke Beranda',
          onPressed: () => setState(() => _selectedTabIndex = 0),
        ),
        title: const Text('Kuis Evaluasi', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
      ),
      body: RefreshIndicator(
        onRefresh: _loadAllData,
        color: const Color(0xFF0F172A),
        child: _kuisList.isEmpty
            ? const Center(
                child: Text('Belum ada kuis yang tersedia.', style: TextStyle(color: Color(0xFF64748B))),
              )
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: _kuisList.length,
                itemBuilder: (ctx, index) {
                  final kuis = _kuisList[index];
                  final int kId = kuis['id'] is int ? kuis['id'] : (int.tryParse('${kuis['id']}') ?? 0);
                  final String judul = kuis['judul'] ?? 'Kuis Evaluasi';
                  final int durasi = kuis['durasi_menit'] is int ? kuis['durasi_menit'] : (int.tryParse('${kuis['durasi_menit']}') ?? 15);
                  final int kkm = kuis['kkm'] is int ? kuis['kkm'] : (kuis['passing_grade'] is int ? kuis['passing_grade'] : (int.tryParse('${kuis['passing_grade'] ?? kuis['kkm']}') ?? 70));
                  final int totalSoal = kuis['total_soal'] is int ? kuis['total_soal'] : (kuis['jumlah_soal'] is int ? kuis['jumlah_soal'] : (int.tryParse('${kuis['jumlah_soal'] ?? kuis['total_soal']}') ?? 0));
                  final dynamic rawNilai = kuis['nilai_tertinggi'] ?? kuis['skor_tertinggi'];
                  final double? nilaiTertinggi = rawNilai != null ? (rawNilai is num ? rawNilai.toDouble() : double.tryParse('$rawNilai')) : null;
                  final bool sudahMengerjakan = nilaiTertinggi != null;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          judul,
                          style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w600, fontSize: 14),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '$totalSoal Soal • Durasi $durasi Menit • KKM $kkm',
                          style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            if (sudahMengerjakan)
                              Text(
                                'Skor: ${nilaiTertinggi.toStringAsFixed(0)}',
                                style: TextStyle(
                                  color: nilaiTertinggi >= kkm ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            const Spacer(),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                              onPressed: () async {
                                await Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => KuisScreen(
                                      kuisId: kId,
                                      judul: judul,
                                      durasiMenit: durasi,
                                    ),
                                  ),
                                );
                                _loadAllData();
                              },
                              child: Text(sudahMengerjakan ? 'Ulangi' : 'Mulai'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
      ),
    );
  }
}
