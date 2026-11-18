import 'package:flutter/material.dart';

class KuisHasilScreen extends StatelessWidget {
  final int kuisId;
  final String judul;
  final double nilai;
  final int totalSoal;
  final int benar;
  final int salah;
  final bool lulus;
  final int kkm;
  final int xpEarned;

  const KuisHasilScreen({
    Key? key,
    required this.kuisId,
    required this.judul,
    required this.nilai,
    required this.totalSoal,
    required this.benar,
    required this.salah,
    required this.lulus,
    required this.kkm,
    required this.xpEarned,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Navigator.of(context).popUntil((route) => route.isFirst);
        return false;
      },
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
            tooltip: 'Kembali ke Beranda',
            onPressed: () {
              Navigator.of(context).popUntil((route) => route.isFirst);
            },
          ),
          title: const Text('Hasil Kuis', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
        ),
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Spacer(),

                // Status Minimal
                Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: lulus ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: lulus ? const Color(0xFFBBF7D0) : const Color(0xFFFECACA)),
                    ),
                    child: Text(
                      lulus ? 'LULUS' : 'REMEDIAL',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.5,
                        color: lulus ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Nilai Angka Besar Bersih
                Center(
                  child: Text(
                    nilai.toStringAsFixed(nilai % 1 == 0 ? 0 : 1),
                    style: const TextStyle(
                      fontSize: 56,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF0F172A),
                      letterSpacing: -1,
                    ),
                  ),
                ),
                Center(
                  child: Text(
                    judul,
                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(height: 28),

                // Detail Matriks Minimal
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    children: [
                      _buildRow('Jawaban Benar', '$benar dari $totalSoal'),
                      const Divider(color: Color(0xFFE2E8F0), height: 16),
                      _buildRow('Jawaban Salah', '$salah soal'),
                      const Divider(color: Color(0xFFE2E8F0), height: 16),
                      _buildRow('Batas KKM', '$kkm'),
                      const Divider(color: Color(0xFFE2E8F0), height: 16),
                      _buildRow(
                        'Perolehan XP',
                        xpEarned > 0
                            ? '+$xpEarned XP'
                            : (lulus ? '0 XP (Sudah pernah diklaim)' : '0 XP'),
                      ),
                    ],
                  ),
                ),

                if (lulus && xpEarned == 0) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFBFDBFE)),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.info_outline, size: 16, color: Color(0xFF2563EB)),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Kuis Ulang: Reward XP kuis ini sudah pernah kamu peroleh sebelumnya.',
                            style: TextStyle(fontSize: 11, color: Color(0xFF1E40AF)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const Spacer(),

                // Buttons
                ElevatedButton(
                  onPressed: () {
                    Navigator.of(context).popUntil((route) => route.isFirst);
                  },
                  child: const Text('Kembali ke Beranda'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Kerjakan Ulang'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF0F172A))),
      ],
    );
  }
}
