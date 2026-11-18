import 'dart:io';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({Key? key}) : super(key: key);

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> {
  bool _isLoading = true;
  List<dynamic> _ranks = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadLeaderboard();
  }

  Future<void> _loadLeaderboard() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final list = await ApiService.getLeaderboard();
      if (mounted) {
        setState(() {
          _ranks = List<dynamic>.from(list);
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Gagal memuat peringkat: $e';
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Papan Peringkat Siswa', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 20),
            onPressed: _loadLeaderboard,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadLeaderboard,
        color: const Color(0xFF0F172A),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
            : _error != null
                ? Center(child: Text(_error!, style: const TextStyle(color: Color(0xFF64748B))))
                : ListView(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    children: [
                      // HERO PODIUM BANNER
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFD97706), Color(0xFFB45309), Color(0xFF78350F)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFD97706).withOpacity(0.3),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 52,
                              height: 52,
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.2),
                                shape: BoxShape.circle,
                              ),
                              child: const Center(
                                child: Text('🏆', style: TextStyle(fontSize: 26)),
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Peringkat Teratas Kelas VII',
                                    style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Raih XP tertinggi dengan membaca materi, menjawab kuis, dan merangkai topologi!',
                                    style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 11, height: 1.3),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 18),
                      const Text(
                        'Daftar Peringkat Siswa',
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 10),

                      if (_ranks.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(30),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: const Center(
                            child: Text('Belum ada data peringkat', style: TextStyle(color: Color(0xFF94A3B8))),
                          ),
                        )
                      else
                        ..._ranks.asMap().entries.map((entry) {
                          final index = entry.key;
                          final item = entry.value;
                          final int rank = index + 1;
                          final bool isMe = (item['id'] is int ? item['id'] : int.tryParse('${item['id']}')) == globalUser.id;

                          String medal = '$rank';
                          if (rank == 1) medal = '🥇';
                          if (rank == 2) medal = '🥈';
                          if (rank == 3) medal = '🥉';

                          return Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            decoration: BoxDecoration(
                              color: isMe ? const Color(0xFFEFF6FF) : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isMe
                                    ? const Color(0xFF2563EB)
                                    : (rank <= 3 ? const Color(0xFFFDE68A) : const Color(0xFFE2E8F0)),
                                width: isMe ? 1.5 : 1,
                              ),
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
                                // Rank Indicator
                                SizedBox(
                                  width: 32,
                                  child: Center(
                                    child: Text(
                                      medal,
                                      style: TextStyle(
                                        fontSize: rank <= 3 ? 18 : 13,
                                        fontWeight: FontWeight.w800,
                                        color: isMe ? const Color(0xFF2563EB) : const Color(0xFF64748B),
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 10),

                                // Student Avatar
                                Builder(
                                  builder: (ctx) {
                                    String avatarUrl = '';
                                    if (isMe && globalUser.avatar.trim().isNotEmpty) {
                                      avatarUrl = globalUser.avatar.trim();
                                    } else if (item['avatar_url'] != null && item['avatar_url'].toString().trim().isNotEmpty) {
                                      avatarUrl = item['avatar_url'].toString().trim();
                                    } else if (item['avatar'] != null && item['avatar'].toString().trim().isNotEmpty) {
                                      avatarUrl = item['avatar'].toString().trim();
                                    }

                                    final fullAv = ApiService.getFullAvatarUrl(avatarUrl);
                                    final initial = (item['nama'] != null && (item['nama'] as String).isNotEmpty)
                                        ? (item['nama'] as String)[0].toUpperCase()
                                        : 'S';

                                    Widget fallbackInitial = Center(
                                      child: Text(
                                        initial,
                                        style: TextStyle(
                                          color: isMe ? Colors.white : const Color(0xFF334155),
                                          fontWeight: FontWeight.w700,
                                          fontSize: 14,
                                        ),
                                      ),
                                    );

                                    Widget avatarContent = fallbackInitial;
                                    if (avatarUrl.isNotEmpty) {
                                      try {
                                        if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('public/') && File(avatarUrl).existsSync()) {
                                          avatarContent = Image.file(
                                            File(avatarUrl),
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) => fallbackInitial,
                                          );
                                        } else if (fullAv.isNotEmpty && fullAv.startsWith('http')) {
                                          avatarContent = Image.network(
                                            fullAv,
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) => fallbackInitial,
                                          );
                                        }
                                      } catch (_) {
                                        avatarContent = fallbackInitial;
                                      }
                                    }

                                    return Container(
                                      width: 38,
                                      height: 38,
                                      decoration: BoxDecoration(
                                        color: isMe ? const Color(0xFF2563EB) : const Color(0xFFF1F5F9),
                                        shape: BoxShape.circle,
                                        border: Border.all(
                                          color: isMe ? const Color(0xFF2563EB) : const Color(0xFFCBD5E1),
                                          width: isMe ? 1.5 : 1,
                                        ),
                                      ),
                                      clipBehavior: Clip.antiAlias,
                                      child: avatarContent,
                                    );
                                  },
                                ),
                                const SizedBox(width: 12),

                                // Student Name & Class
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Flexible(
                                            child: Text(
                                              item['nama'] ?? '',
                                              style: TextStyle(
                                                fontSize: 13.5,
                                                fontWeight: isMe ? FontWeight.w800 : FontWeight.w600,
                                                color: isMe ? const Color(0xFF1E3A8A) : const Color(0xFF0F172A),
                                              ),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          if (isMe) ...[
                                            const SizedBox(width: 6),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFF2563EB),
                                                borderRadius: BorderRadius.circular(4),
                                              ),
                                              child: const Text(
                                                'KAMU',
                                                style: TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w800),
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        'Kelas ${item['kelas'] ?? 'VII'} • Level ${item['level'] ?? 1}',
                                        style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                      ),
                                    ],
                                  ),
                                ),

                                // XP Badge
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF8FAFC),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: const Color(0xFFE2E8F0)),
                                  ),
                                  child: Text(
                                    '${item['total_xp'] ?? 0} XP',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: Color(0xFF0F172A),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }).toList(),

                      const SizedBox(height: 20),
                    ],
                  ),
      ),
    );
  }
}
