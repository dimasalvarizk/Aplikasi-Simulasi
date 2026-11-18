import 'dart:async';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import 'kuis_hasil_screen.dart';

class KuisScreen extends StatefulWidget {
  final int kuisId;
  final String judul;
  final int durasiMenit;

  const KuisScreen({
    Key? key,
    required this.kuisId,
    required this.judul,
    required this.durasiMenit,
  }) : super(key: key);

  @override
  State<KuisScreen> createState() => _KuisScreenState();
}

class _KuisScreenState extends State<KuisScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _soalList = [];
  int _currentIndex = 0;
  final Map<String, String> _jawaban = {};

  Timer? _timer;
  int _remainingSeconds = 0;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _remainingSeconds = widget.durasiMenit * 60;
    _loadKuisDetail();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds <= 1) {
        timer.cancel();
        _autoSubmitTimeUp();
      } else {
        if (mounted) {
          setState(() {
            _remainingSeconds--;
          });
        }
      }
    });
  }

  Future<void> _loadKuisDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await ApiService.getKuisDetail(widget.kuisId);
      if (!mounted) return;

      final soalData = data != null ? (data['soal'] ?? data['soal_list']) : null;
      if (soalData != null && soalData is List && soalData.isNotEmpty) {
        setState(() {
          _soalList = soalData;
          _isLoading = false;
        });
        _startTimer();
      } else {
        setState(() {
          _error = 'Belum ada butir soal pada kuis ini.';
          _isLoading = false;
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = 'Koneksi error: $e';
        _isLoading = false;
      });
    }
  }

  void _autoSubmitTimeUp() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Waktu telah habis. Mengirim jawaban otomatis...')),
    );
    _submitKuis();
  }

  Future<void> _submitKuis() async {
    if (_isSubmitting) return;

    _timer?.cancel();

    setState(() {
      _isSubmitting = true;
    });

    try {
      final res = await ApiService.submitKuis(widget.kuisId, globalUser.id, _jawaban);

      if (!mounted) return;

      if (res['status'] == 'success') {
        final data = res['data'] as Map<String, dynamic>;
        final int xpEarned = data['xp_earned'] ?? 0;
        final int totalXp = data['total_xp'] ?? (globalUser.totalXp + xpEarned);
        globalUser.updateXp(totalXp);

        final num rawNilai = data['nilai'] ?? data['skor'] ?? 0;
        final num rawTotal = data['total_soal'] ?? data['total'] ?? 0;
        final num rawBenar = data['benar'] ?? 0;
        final num rawSalah = data['salah'] ?? (rawTotal - rawBenar);
        final num rawKkm   = data['kkm'] ?? data['passing_grade'] ?? 70;

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => KuisHasilScreen(
              kuisId: widget.kuisId,
              judul: widget.judul,
              nilai: rawNilai.toDouble(),
              totalSoal: rawTotal.toInt(),
              benar: rawBenar.toInt(),
              salah: rawSalah.toInt(),
              lulus: data['lulus'] == true || data['lulus'] == 1 || data['lulus'] == '1',
              kkm: rawKkm.toInt(),
              xpEarned: xpEarned,
            ),
          ),
        );
      } else {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal mengirim kuis')),
        );
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error pengiriman: $e')),
      );
    }
  }

  void _confirmSubmit() {
    final unanswered = _soalList.length - _jawaban.length;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Kumpulkan Jawaban?', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
        content: Text(
          unanswered > 0
              ? 'Terdapat $unanswered soal yang belum dijawab. Kumpulkan sekarang?'
              : 'Semua soal sudah dijawab. Yakin ingin mengumpulkan?',
          style: const TextStyle(fontSize: 13, color: Color(0xFF475569)),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Kembali')),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _submitKuis();
            },
            child: const Text('Kumpulkan'),
          ),
        ],
      ),
    );
  }

  String _formatTimer(int totalSecs) {
    final m = totalSecs ~/ 60;
    final s = totalSecs % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.judul, style: const TextStyle(fontSize: 14))),
        body: const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A))),
      );
    }

    if (_error != null || _soalList.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.judul, style: const TextStyle(fontSize: 14))),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(_error ?? 'Belum ada soal', style: const TextStyle(color: Color(0xFF64748B))),
                const SizedBox(height: 12),
                ElevatedButton(onPressed: () => Navigator.pop(context), child: const Text('Kembali')),
              ],
            ),
          ),
        ),
      );
    }

    final currentSoal = _soalList[_currentIndex];
    final String currentSoalId = currentSoal['id'].toString();
    final String? selectedAnswer = _jawaban[currentSoalId];

    final Map<String, String> pilihan = {
      'A': currentSoal['pilihan_a'] ?? currentSoal['opsi_a'] ?? '',
      'B': currentSoal['pilihan_b'] ?? currentSoal['opsi_b'] ?? '',
      'C': currentSoal['pilihan_c'] ?? currentSoal['opsi_c'] ?? '',
      'D': currentSoal['pilihan_d'] ?? currentSoal['opsi_d'] ?? '',
    };
    final opsiE = currentSoal['pilihan_e'] ?? currentSoal['opsi_e'];
    if (opsiE != null && opsiE.toString().trim().isNotEmpty) {
      pilihan['E'] = opsiE.toString();
    }

    final isLowTime = _remainingSeconds <= 60;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(widget.judul, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
        actions: [
          Center(
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: isLowTime ? const Color(0xFFFEF2F2) : const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: isLowTime ? const Color(0xFFF87171) : const Color(0xFFE2E8F0),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.timer_outlined,
                    size: 14,
                    color: isLowTime ? const Color(0xFFDC2626) : const Color(0xFF0F172A),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    _formatTimer(_remainingSeconds),
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: isLowTime ? const Color(0xFFDC2626) : const Color(0xFF0F172A),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // 1. PROGRESS BAR HALUS
          LinearProgressIndicator(
            value: (_currentIndex + 1) / _soalList.length,
            backgroundColor: const Color(0xFFE2E8F0),
            valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF2563EB)),
            minHeight: 3,
          ),

          // 2. INTERACTIVE NUMBER PILLS STRIP (Ketuk untuk melompat ke nomor soal)
          Container(
            height: 48,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _soalList.length,
              itemBuilder: (ctx, i) {
                final sId = _soalList[i]['id'].toString();
                final isAnswered = _jawaban.containsKey(sId);
                final isCurrent = i == _currentIndex;

                return GestureDetector(
                  onTap: () {
                    setState(() {
                      _currentIndex = i;
                    });
                  },
                  child: Container(
                    width: 34,
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    decoration: BoxDecoration(
                      color: isCurrent
                          ? const Color(0xFF0F172A)
                          : (isAnswered ? const Color(0xFFDCFCE7) : const Color(0xFFF8FAFC)),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: isCurrent
                            ? const Color(0xFF0F172A)
                            : (isAnswered ? const Color(0xFF86EFAC) : const Color(0xFFCBD5E1)),
                        width: isCurrent ? 2 : 1,
                      ),
                    ),
                    child: Center(
                      child: Text(
                        '${i + 1}',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: isCurrent
                              ? Colors.white
                              : (isAnswered ? const Color(0xFF15803D) : const Color(0xFF64748B)),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),

          // 3. KONTEN PERTANYAAN & OPSI JAWABAN INTERAKTIF
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'Soal ${_currentIndex + 1} dari ${_soalList.length}',
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF2563EB),
                          ),
                        ),
                      ),
                      const Spacer(),
                      Text(
                        '${_jawaban.length}/${_soalList.length} Terjawab',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Kotak Pertanyaan
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.02),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Text(
                      currentSoal['pertanyaan'] ?? '',
                      style: const TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF0F172A),
                        height: 1.5,
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),
                  const Text(
                    'Pilih Jawaban yang Benar:',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                  ),
                  const SizedBox(height: 10),

                  // Pilihan Jawaban A, B, C, D
                  ...pilihan.entries.map((entry) {
                    final key = entry.key;
                    final val = entry.value;
                    final bool isChosen = selectedAnswer == key;

                    return GestureDetector(
                      onTap: () {
                        setState(() {
                          _jawaban[currentSoalId] = key;
                        });
                      },
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(
                          color: isChosen ? const Color(0xFFEFF6FF) : Colors.white,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isChosen ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
                            width: isChosen ? 2.0 : 1.0,
                          ),
                          boxShadow: isChosen
                              ? [
                                  BoxShadow(
                                    color: const Color(0xFF2563EB).withOpacity(0.12),
                                    blurRadius: 8,
                                    offset: const Offset(0, 2),
                                  ),
                                ]
                              : [],
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 28,
                              height: 28,
                              alignment: Alignment.center,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: isChosen ? const Color(0xFF2563EB) : const Color(0xFFF1F5F9),
                              ),
                              child: Text(
                                key,
                                style: TextStyle(
                                  color: isChosen ? Colors.white : const Color(0xFF475569),
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                val,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: isChosen ? const Color(0xFF1E3A8A) : const Color(0xFF334155),
                                  fontWeight: isChosen ? FontWeight.w600 : FontWeight.normal,
                                  height: 1.35,
                                ),
                              ),
                            ),
                            if (isChosen)
                              const Icon(Icons.check_circle, size: 18, color: Color(0xFF2563EB)),
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ],
              ),
            ),
          ),

          // 4. BOTTOM BAR NAVIGASI INTERAKTIF
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: SafeArea(
              child: Row(
                children: [
                  if (_currentIndex > 0)
                    OutlinedButton.icon(
                      icon: const Icon(Icons.arrow_back, size: 16),
                      onPressed: () => setState(() => _currentIndex--),
                      label: const Text('Sebelumnya'),
                      style: OutlinedButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  const Spacer(),
                  if (_currentIndex < _soalList.length - 1)
                    ElevatedButton.icon(
                      icon: const Icon(Icons.arrow_forward, size: 16),
                      onPressed: () => setState(() => _currentIndex++),
                      label: const Text('Selanjutnya'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    )
                  else
                    ElevatedButton.icon(
                      icon: const Icon(Icons.send_rounded, size: 16),
                      onPressed: _isSubmitting ? null : _confirmSubmit,
                      label: _isSubmitting
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Kumpulkan Jawaban'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF10B981),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
