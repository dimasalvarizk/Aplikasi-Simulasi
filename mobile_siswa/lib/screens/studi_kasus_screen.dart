import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class StudiKasusScreen extends StatefulWidget {
  final Map<String, dynamic>? user;

  const StudiKasusScreen({Key? key, this.user}) : super(key: key);

  @override
  State<StudiKasusScreen> createState() => _StudiKasusScreenState();
}

class _StudiKasusScreenState extends State<StudiKasusScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _kasusList = [];
  int _totalSelesai = 0;
  int _totalXpDidapat = 0;
  final Set<int> _inspectedIds = {};
  int? _submittingId;

  int get _userId {
    if (widget.user != null && widget.user!['id'] != null) {
      return int.tryParse(widget.user!['id'].toString()) ?? 0;
    }
    return globalUser.id;
  }

  @override
  void initState() {
    super.initState();
    _loadKasus();
  }

  Future<void> _loadKasus() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final list = await ApiService.getKasusKeamanan(_userId);
      int selesai = 0;
      int xp = 0;

      for (var item in list) {
        if (item is Map) {
          final isCorrect = item['is_correct'] == true || item['is_correct'] == 1;
          if (isCorrect) {
            selesai++;
          }
          final earned = int.tryParse(item['xp_earned']?.toString() ?? '0') ?? 0;
          xp += earned;
        }
      }

      setState(() {
        _kasusList = list;
        _totalSelesai = selesai;
        _totalXpDidapat = xp;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal memuat tantangan siber: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _submitJawaban(int kasusId, int jawaban, String judul) async {
    setState(() {
      _submittingId = kasusId;
    });

    try {
      final res = await ApiService.submitKasusKeamanan(kasusId, _userId, jawaban);
      setState(() {
        _submittingId = null;
      });

      if (res['status'] == 'success') {
        final data = res['data'] ?? {};
        final bool isCorrect = data['is_correct'] == true || data['is_correct'] == 1;
        final int xpEarned = int.tryParse(data['xp_earned']?.toString() ?? '0') ?? 0;
        final int newTotalXp = int.tryParse(data['total_xp']?.toString() ?? '0') ?? 0;
        final String penjelasan = data['penjelasan']?.toString() ?? '';
        final String ciriBahaya = data['ciri_bahaya']?.toString() ?? '';
        final Map<String, dynamic>? badge = data['badge_unlocked'] is Map<String, dynamic>
            ? data['badge_unlocked'] as Map<String, dynamic>
            : null;

        // 1. Perbarui total XP dan level siswa secara langsung di aplikasi
        if (newTotalXp > 0) {
          globalUser.updateXp(newTotalXp);
        } else if (xpEarned > 0) {
          globalUser.updateXp(globalUser.totalXp + xpEarned);
        }

        // 2. Sinkronkan profil siswa (pencapaian lencana dan riwayat aktivitas)
        if (_userId > 0) {
          ApiService.getProfil(_userId).then((profilRes) {
            if (profilRes != null &&
                profilRes['status'] == 'success' &&
                profilRes['data'] is Map<String, dynamic>) {
              globalUser.setFromApi(
                profilRes['data'] as Map<String, dynamic>,
                globalUser.token,
              );
            }
          }).catchError((_) {});
        }

        _showResultDialog(
          isCorrect: isCorrect,
          xpEarned: xpEarned,
          kunciJawaban: data['kunci_jawaban'] == 1 ? 'AMAN' : 'BERBAHAYA',
          penjelasan: penjelasan,
          ciriBahaya: ciriBahaya,
          badge: badge,
        );

        // Reload data agar status terbarui
        _loadKasus();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message']?.toString() ?? 'Gagal memproses jawaban.'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    } catch (e) {
      setState(() {
        _submittingId = null;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Terjadi kesalahan: $e'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  void _showResultDialog({
    required bool isCorrect,
    required int xpEarned,
    required String kunciJawaban,
    required String penjelasan,
    required String ciriBahaya,
    Map<String, dynamic>? badge,
  }) {
    showDialog(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.transparent,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        contentPadding: const EdgeInsets.all(24),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Icon
            Container(
              width: 70,
              height: 70,
              decoration: BoxDecoration(
                color: isCorrect ? Colors.green.shade50 : Colors.red.shade50,
                shape: BoxShape.circle,
                border: Border.all(
                  color: isCorrect ? Colors.green.shade300 : Colors.red.shade300,
                  width: 2,
                ),
              ),
              child: Center(
                child: Icon(
                  isCorrect ? Icons.check_circle_rounded : Icons.warning_amber_rounded,
                  size: 40,
                  color: isCorrect ? Colors.green.shade700 : Colors.red.shade700,
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Title
            Text(
              isCorrect ? 'Analisis Tepat Sekali! 🎉' : 'Waspada! Kurang Tepat ⚠️',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: isCorrect ? Colors.green.shade800 : Colors.red.shade800,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 6),

            // XP Reward
            if (xpEarned > 0)
              Container(
                margin: const EdgeInsets.symmetric(vertical: 8),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.amber.shade100,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.amber.shade300),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.stars_rounded, color: Colors.amber, size: 20),
                    const SizedBox(width: 6),
                    Text(
                      '+$xpEarned XP Diraih!',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: Colors.amber.shade900,
                      ),
                    ),
                  ],
                ),
              ),

            // Badge Unlocked
            if (badge != null)
              Container(
                margin: const EdgeInsets.symmetric(vertical: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.indigo.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.indigo.shade200),
                ),
                child: Row(
                  children: [
                    Text(
                      badge['icon']?.toString() ?? '🕵️‍♂️',
                      style: const TextStyle(fontSize: 28),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Lencana Baru Diraih!',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: Colors.indigo.shade600,
                            ),
                          ),
                          Text(
                            badge['nama']?.toString() ?? 'Detektif Siber',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: Colors.indigo.shade900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

            const SizedBox(height: 8),
            // Penjelasan Edukasi
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.school_rounded, size: 16, color: Colors.indigo.shade700),
                      const SizedBox(width: 6),
                      Text(
                        'Penjelasan Edukasi (Status: $kunciJawaban)',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo.shade900,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    penjelasan,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade800,
                      height: 1.4,
                    ),
                  ),
                  if (ciriBahaya.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Ciri-Ciri: $ciriBahaya',
                      style: TextStyle(
                        fontSize: 11,
                        fontStyle: FontStyle.italic,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(ctx),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0F172A),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: const Text('Lanjutkan Tantangan', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Detektif Siber 🕵️‍♂️',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17),
        ),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF0F172A),
        elevation: 0,
        centerTitle: false,
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Segarkan',
            onPressed: _loadKasus,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.error_outline_rounded, size: 48, color: Colors.redAccent),
                        const SizedBox(height: 12),
                        Text(_errorMessage!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _loadKasus,
                          child: const Text('Coba Lagi'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadKasus,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Pesan Masuk Untuk Dianalisis (${_kasusList.length} Skenario)',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1E293B),
                            ),
                          ),
                          if (_kasusList.isNotEmpty)
                            Text(
                              '$_totalSelesai/${_kasusList.length} Diselesaikan',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF2563EB),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_kasusList.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(32),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: const Center(
                            child: Text(
                              'Belum ada tantangan kasus keamanan saat ini.',
                              style: TextStyle(color: Colors.grey),
                            ),
                          ),
                        )
                      else
                        ..._kasusList.asMap().entries.map((entry) {
                          final idx = entry.key;
                          final k = entry.value as Map<String, dynamic>;
                          return _buildKasusCard(idx, k);
                        }).toList(),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
    );
  }

  Widget _buildKasusCard(int index, Map<String, dynamic> k) {
    final int id = int.tryParse(k['id']?.toString() ?? '0') ?? 0;
    final String platform = (k['platform']?.toString() ?? 'whatsapp').toLowerCase();
    final String judul = k['judul']?.toString() ?? 'Studi Kasus';
    final String pengirim = k['pengirim']?.toString() ?? 'Pengirim';
    final String isiPesan = k['isi_pesan']?.toString() ?? '';
    final String lampiran = k['lampiran_nama']?.toString() ?? '';
    final String tautanTerlihat = k['tautan_terlihat']?.toString() ?? (k['link_url']?.toString() ?? '');
    final String tautanAsli = k['tautan_asli']?.toString() ?? tautanTerlihat;
    final int xpReward = int.tryParse(k['xp_reward']?.toString() ?? '25') ?? 25;
    final bool sudahDikerjakan = k['sudah_dikerjakan'] == true || k['sudah_dikerjakan'] == 1;
    final bool isCorrect = k['is_correct'] == true || k['is_correct'] == 1;
    final bool isInspected = _inspectedIds.contains(id);
    final bool isSubmitting = _submittingId == id;

    // Platform theming
    Color headerBg = const Color(0xFF075E54); // WhatsApp green
    IconData platformIcon = Icons.chat_rounded;
    String platformLabel = 'WhatsApp Chat';

    if (platform == 'sms') {
      headerBg = const Color(0xFFC2410C); // SMS orange
      platformIcon = Icons.sms_rounded;
      platformLabel = 'SMS / Pesan';
    } else if (platform == 'email') {
      headerBg = const Color(0xFF1E40AF); // Email blue
      platformIcon = Icons.email_rounded;
      platformLabel = 'Email Masuk';
    } else if (platform == 'medsos') {
      headerBg = const Color(0xFF7E22CE); // Medsos purple
      platformIcon = Icons.alternate_email_rounded;
      platformLabel = 'Direct Message';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(
          color: sudahDikerjakan
              ? (isCorrect ? Colors.green.shade200 : Colors.red.shade200)
              : Colors.grey.shade200,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header Platform Realistis
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: headerBg,
            child: Row(
              children: [
                CircleAvatar(
                  radius: 16,
                  backgroundColor: Colors.white.withOpacity(0.2),
                  child: Icon(platformIcon, color: Colors.white, size: 16),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '$platformLabel • Kasus #${index + 1}',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.75),
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Text(
                        pengirim,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                if (sudahDikerjakan)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isCorrect ? Colors.green.shade700 : Colors.red.shade700,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          isCorrect ? Icons.check_circle_rounded : Icons.cancel_rounded,
                          color: Colors.white,
                          size: 13,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          isCorrect ? 'Benar' : 'Kurang Tepat',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  )
                else
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '+$xpReward XP',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
              ],
            ),
          ),

          // Judul Kasus & Bubble Pesan
          Container(
            color: const Color(0xFFF1F5F9).withOpacity(0.6),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  judul,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 10),

                // Chat bubble
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: const BorderRadius.only(
                      topRight: Radius.circular(16),
                      bottomLeft: Radius.circular(16),
                      bottomRight: Radius.circular(16),
                      topLeft: Radius.circular(4),
                    ),
                    border: Border.all(color: Colors.grey.shade200),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.02),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isiPesan,
                        style: TextStyle(
                          fontSize: 12.5,
                          color: Colors.grey.shade800,
                          height: 1.45,
                        ),
                      ),

                      // Lampiran File (APK / DOC)
                      if (lampiran.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 34,
                                height: 34,
                                decoration: BoxDecoration(
                                  color: Colors.red.shade100,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Icon(Icons.android_rounded, color: Colors.red.shade700, size: 20),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      lampiran,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF0F172A),
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    Text(
                                      'Berkas Aplikasi • 4.8 MB',
                                      style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],

                      // Tautan terlihat
                      if (tautanTerlihat.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.blue.shade50,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: Colors.blue.shade200),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.link_rounded, size: 16, color: Colors.blue.shade700),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  tautanTerlihat,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontFamily: 'monospace',
                                    color: Colors.blue.shade800,
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],

                      const SizedBox(height: 6),
                      Align(
                        alignment: Alignment.centerRight,
                        child: Text(
                          '10:45 • Terkirim',
                          style: TextStyle(fontSize: 10, color: Colors.grey.shade400),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 12),

                // Tombol Inspeksi Tautan & Asal Pengirim
                InkWell(
                  onTap: () {
                    setState(() {
                      if (isInspected) {
                        _inspectedIds.remove(id);
                      } else {
                        _inspectedIds.add(id);
                      }
                    });
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.indigo.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.search_rounded, size: 16, color: Colors.indigo.shade700),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                isInspected ? 'Tutup Inspeksi Tautan' : '🔍 Inspeksi Tautan Asli & Domain',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.indigo.shade900,
                                ),
                              ),
                            ),
                            Icon(
                              isInspected ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                              size: 18,
                              color: Colors.indigo.shade700,
                            ),
                          ],
                        ),
                        if (isInspected) ...[
                          const Divider(height: 16),
                          const Text(
                            'URL Tujuan Sebenarnya:',
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey),
                          ),
                          const SizedBox(height: 4),
                          SelectableText(
                            tautanAsli.isNotEmpty ? tautanAsli : 'Tidak ada link eksternal.',
                            style: TextStyle(
                              fontSize: 11,
                              fontFamily: 'monospace',
                              fontWeight: FontWeight.bold,
                              color: tautanAsli.contains('.sch.id') || tautanAsli.contains('.go.id')
                                  ? Colors.green.shade800
                                  : Colors.red.shade800,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Petunjuk Analisis: Perhatikan apakah nama domain sama persis dengan situs resmi instansi atau menggunakan ekstensi aneh (.apk, .xyz, free-claim) dan HTTP tanpa enkripsi.',
                            style: TextStyle(fontSize: 10.5, color: Colors.grey.shade600, height: 1.3),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),

                // Hasil dan Penjelasan jika sudah selesai
                if (sudahDikerjakan) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isCorrect ? Colors.green.shade50 : Colors.red.shade50,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: isCorrect ? Colors.green.shade200 : Colors.red.shade200,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              isCorrect ? Icons.check_circle_rounded : Icons.info_outline_rounded,
                              size: 16,
                              color: isCorrect ? Colors.green.shade700 : Colors.red.shade700,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              isCorrect ? 'Analisis Kamu Tepat!' : 'Analisis Kurang Tepat',
                              style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.bold,
                                color: isCorrect ? Colors.green.shade900 : Colors.red.shade900,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          k['penjelasan']?.toString() ?? '',
                          style: TextStyle(fontSize: 11.5, color: Colors.grey.shade800, height: 1.35),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),

          // Tombol Keputusan (Aman vs Berbahaya)
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  sudahDikerjakan
                      ? 'Analisis ulang pesan ini jika ingin mencoba lagi:'
                      : 'Menurut analisismu, apakah pesan di atas aman atau berbahaya?',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 8),
                if (isSubmitting)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(8),
                      child: CircularProgressIndicator(),
                    ),
                  )
                else
                  Row(
                    children: [
                      // Tombol Berbahaya (Phishing / Scam)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _submitJawaban(id, 0, judul),
                          icon: const Icon(Icons.warning_amber_rounded, size: 16, color: Colors.red),
                          label: const Text(
                            'BERBAHAYA',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: Colors.red,
                            ),
                          ),
                          style: OutlinedButton.styleFrom(
                            backgroundColor: Colors.red.shade50.withOpacity(0.5),
                            side: BorderSide(color: Colors.red.shade300),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),

                      // Tombol Aman (Boleh Dibuka)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _submitJawaban(id, 1, judul),
                          icon: const Icon(Icons.shield_rounded, size: 16, color: Colors.green),
                          label: const Text(
                            'AMAN',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: Colors.green,
                            ),
                          ),
                          style: OutlinedButton.styleFrom(
                            backgroundColor: Colors.green.shade50.withOpacity(0.5),
                            side: BorderSide(color: Colors.green.shade300),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
