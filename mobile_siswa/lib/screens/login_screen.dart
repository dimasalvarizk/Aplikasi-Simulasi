import 'dart:ui';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import 'dashboard_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    // Jalankan auto-discovery diam-diam untuk mencari server di Wi-Fi
    ApiService.autoDiscoverServer();
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final username = _usernameController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      setState(() {
        _errorMessage = 'Username dan password wajib diisi';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.login(username, password);

      if (!mounted) return;

      if (res['status'] == 'success') {
        final userData = res['data']['user'] as Map<String, dynamic>;
        final token = res['data']['token'] as String? ?? '';

        if (userData['role'] != 'siswa') {
          setState(() {
            _isLoading = false;
            _errorMessage = 'Akun ini bukan akun siswa. Guru silakan login via Web.';
          });
          return;
        }

        globalUser.setFromApi(userData, token);

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const DashboardScreen()),
        );
      } else {
        setState(() {
          _isLoading = false;
          _errorMessage = res['message'] ?? 'Login gagal. Periksa username dan password.';
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Koneksi gagal: $e';
      });
    }
  }

  void _showForgotPasswordBottomSheet() {
    final fUsernameController = TextEditingController(text: _usernameController.text.trim());
    final fNisnController = TextEditingController();
    final fKeteranganController = TextEditingController();

    bool isSubmitting = false;
    String? formError;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      barrierColor: Colors.transparent,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.90,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: EdgeInsets.fromLTRB(20, 18, 20, MediaQuery.of(context).viewInsets.bottom + 24),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: const [
                        Icon(Icons.shield_outlined, size: 24, color: Color(0xFFD97706)),
                        SizedBox(width: 8),
                        Text(
                          'Pemulihan Akun Siswa',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, size: 20, color: Color(0xFF64748B)),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFFBEB),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFFDE68A)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Icon(Icons.lock_person_outlined, size: 18, color: Color(0xFFB45309)),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Demi keamanan akun dan mencegah pembajakan oleh orang lain, reset kata sandi dilakukan secara langsung oleh Guru Pengampu (Bpk. Angzal Maolana, S.Pd / Ibu Hana Sifakhatin, S.Pd).',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF92400E), height: 1.35),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                // Error Message if any
                if (formError != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFFCA5A5)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.error_outline_rounded, size: 18, color: Color(0xFFB91C1C)),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            formError!,
                            style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                  ),

                // Username Field
                const Text(
                  'Username Siswa',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: fUsernameController,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Masukkan username siswa',
                    prefixIcon: const Icon(Icons.person_outline_rounded, size: 18),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 12),

                // NISN Field
                const Text(
                  'Nomor Induk Siswa Nasional (NISN)',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: fNisnController,
                  keyboardType: TextInputType.number,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: '10 digit NISN pada kartu pelajar',
                    prefixIcon: const Icon(Icons.badge_outlined, size: 18),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 12),

                // Catatan Alasan Field
                const Text(
                  'Alasan / Keterangan (Opsional)',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: fKeteranganController,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Tulis alasan jika ada (opsional)',
                    prefixIcon: const Icon(Icons.chat_bubble_outline_rounded, size: 18),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 18),

                // Submit Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            final uname = fUsernameController.text.trim();
                            final nisn = fNisnController.text.trim();
                            final ket = fKeteranganController.text.trim();

                            if (uname.isEmpty || nisn.isEmpty) {
                              setModalState(() => formError = 'Username dan NISN wajib diisi.');
                              return;
                            }

                            setModalState(() {
                              isSubmitting = true;
                              formError = null;
                            });

                            try {
                              final res = await ApiService.requestResetSiswa(
                                username: uname,
                                nisn: nisn,
                                keterangan: ket,
                              );

                              if (!mounted) return;

                              if (res['status'] == 'success') {
                                Navigator.pop(ctx);
                                showDialog(
                                  context: context,
                                  barrierColor: Colors.transparent,
                                  builder: (dCtx) => AlertDialog(
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                    title: Row(
                                      children: const [
                                        Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 24),
                                        SizedBox(width: 8),
                                        Text('Permohonan Terkirim', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                                      ],
                                    ),
                                    content: const Text(
                                      'Permohonan pemulihan akun Anda telah dikirimkan ke Guru Pengampu (Bpk. Angzal Maolana, S.Pd / Ibu Hana Sifakhatin, S.Pd).\n\nSilakan temui guru di ruang guru untuk mendapatkan kata sandi sementara Anda.',
                                      style: TextStyle(fontSize: 13, height: 1.4),
                                    ),
                                    actions: [
                                      ElevatedButton(
                                        onPressed: () => Navigator.pop(dCtx),
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: const Color(0xFF0F172A),
                                          foregroundColor: Colors.white,
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                        ),
                                        child: const Text('Mengerti'),
                                      ),
                                    ],
                                  ),
                                );
                              } else {
                                setModalState(() {
                                  isSubmitting = false;
                                  formError = res['message'] ?? 'Gagal mengirimkan permohonan.';
                                });
                              }
                            } catch (e) {
                              setModalState(() {
                                isSubmitting = false;
                                formError = 'Terjadi kesalahan: $e';
                              });
                            }
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: isSubmitting
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Kirim Permohonan ke Guru', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                  ),
                ),

                const SizedBox(height: 16),
                // Teacher Assistance Info Box
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Icon(Icons.school_outlined, size: 16, color: Color(0xFF64748B)),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Tim Pengelola Resmi: Bpk. Angzal Maolana, S.Pd & Ibu Hana Sifakhatin, S.Pd • SMP Negeri 1 Karangmoncol.',
                          style: TextStyle(fontSize: 11, color: Color(0xFF64748B), height: 1.35),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // 1. Gambar Gedung Sekolah SMP Negeri 1 Karangmoncol
          Positioned.fill(
            child: Image.asset(
              'assets/images/school_bg.jpg',
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                color: const Color(0xFF0F172A),
              ),
            ),
          ),

          // 2. Lapisan Overlay Gradien Gelap Elegan untuk Kontras Maksimal
          Positioned.fill(
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    const Color(0xFF0F172A).withOpacity(0.55),
                    const Color(0xFF0F172A).withOpacity(0.85),
                    const Color(0xFF0F172A).withOpacity(0.95),
                  ],
                ),
              ),
            ),
          ),

          // 3. Konten Utama
          SafeArea(
            child: Column(
              children: [
                // Top Bar Navigasi & Identitas
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Badge Sekolah di Atas
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.18),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withOpacity(0.25)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: const [
                            Icon(Icons.school_rounded, size: 15, color: Colors.white),
                            SizedBox(width: 7),
                            Text(
                              'SMPN 1 Karangmoncol',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                letterSpacing: 0.2,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Form Login di Tengah Card Frosted Glass
                Expanded(
                  child: Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Kartu Frosted Glass (Kaca Transparan Modern)
                          ClipRRect(
                            borderRadius: BorderRadius.circular(24),
                            child: BackdropFilter(
                              filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 26),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.95),
                                  borderRadius: BorderRadius.circular(24),
                                  border: Border.all(color: Colors.white.withOpacity(0.6), width: 1.5),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.25),
                                      blurRadius: 24,
                                      offset: const Offset(0, 10),
                                    ),
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.stretch,
                                  children: [
                                    // Logo Sekolah
                                    Center(
                                      child: Container(
                                        padding: const EdgeInsets.all(4),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(16),
                                          boxShadow: [
                                            BoxShadow(
                                              color: const Color(0xFF2563EB).withOpacity(0.12),
                                              blurRadius: 10,
                                              offset: const Offset(0, 4),
                                            ),
                                          ],
                                        ),
                                        child: ClipRRect(
                                          borderRadius: BorderRadius.circular(12),
                                          child: Image.asset(
                                            'assets/images/logo.jpg',
                                            width: 62,
                                            height: 62,
                                            fit: BoxFit.contain,
                                            errorBuilder: (_, __, ___) => const Icon(Icons.school_outlined, size: 48, color: Color(0xFF0F172A)),
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    const Text(
                                      'SMP NEGERI 1 KARANGMONCOL',
                                      style: TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: -0.2,
                                        color: Color(0xFF0F172A),
                                      ),
                                      textAlign: TextAlign.center,
                                    ),
                                    const SizedBox(height: 3),
                                    const Text(
                                      'Simulasi Topologi Jaringan • Kelas VII',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Color(0xFF64748B),
                                        fontWeight: FontWeight.w500,
                                      ),
                                      textAlign: TextAlign.center,
                                    ),
                                    const SizedBox(height: 22),

                                    // Pesan Error
                                    if (_errorMessage != null)
                                      Container(
                                        margin: const EdgeInsets.only(bottom: 16),
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFFEF2F2),
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(color: const Color(0xFFFCA5A5)),
                                        ),
                                        child: Row(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            const Icon(Icons.error_outline_rounded, size: 18, color: Color(0xFFB91C1C)),
                                            const SizedBox(width: 8),
                                            Expanded(
                                              child: Text(
                                                _errorMessage!,
                                                style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),

                                    // Input Username
                                    const Text(
                                      'Username / NISN',
                                      style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                                    ),
                                    const SizedBox(height: 6),
                                    TextField(
                                      controller: _usernameController,
                                      style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                                      decoration: InputDecoration(
                                        hintText: 'Masukkan username atau NISN',
                                        prefixIcon: const Icon(Icons.person_outline_rounded, size: 19, color: Color(0xFF64748B)),
                                        filled: true,
                                        fillColor: const Color(0xFFF8FAFC),
                                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                                        border: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                        ),
                                        enabledBorder: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                        ),
                                        focusedBorder: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 15),

                                    // Input Password
                                    const Text(
                                      'Password',
                                      style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                                    ),
                                    const SizedBox(height: 6),
                                    TextField(
                                      controller: _passwordController,
                                      obscureText: _obscurePassword,
                                      style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                                      decoration: InputDecoration(
                                        hintText: 'Masukkan password',
                                        prefixIcon: const Icon(Icons.lock_outline_rounded, size: 19, color: Color(0xFF64748B)),
                                        suffixIcon: IconButton(
                                          icon: Icon(
                                            _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                                            size: 19,
                                            color: const Color(0xFF64748B),
                                          ),
                                          onPressed: () {
                                            setState(() {
                                              _obscurePassword = !_obscurePassword;
                                            });
                                          },
                                        ),
                                        filled: true,
                                        fillColor: const Color(0xFFF8FAFC),
                                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                                        border: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                        ),
                                        enabledBorder: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                        ),
                                        focusedBorder: OutlineInputBorder(
                                          borderRadius: BorderRadius.circular(12),
                                          borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 8),

                                    // Tombol Lupa Kata Sandi
                                    Align(
                                      alignment: Alignment.centerRight,
                                      child: InkWell(
                                        onTap: _showForgotPasswordBottomSheet,
                                        borderRadius: BorderRadius.circular(6),
                                        child: const Padding(
                                          padding: EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                                          child: Text(
                                            'Lupa Kata Sandi Siswa?',
                                            style: TextStyle(
                                              fontSize: 12,
                                              fontWeight: FontWeight.w600,
                                              color: Color(0xFF2563EB),
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 14),

                                    // Tombol Masuk
                                    ElevatedButton(
                                      onPressed: _isLoading ? null : _handleLogin,
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: const Color(0xFF2563EB),
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 13),
                                        elevation: 2,
                                        shadowColor: const Color(0xFF2563EB).withOpacity(0.35),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(12),
                                        ),
                                      ),
                                      child: _isLoading
                                          ? const SizedBox(
                                              height: 18,
                                              width: 18,
                                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                            )
                                          : const Row(
                                              mainAxisAlignment: MainAxisAlignment.center,
                                              children: [
                                                Text(
                                                  'Masuk Sekarang',
                                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
                                                ),
                                                SizedBox(width: 6),
                                                Icon(Icons.arrow_forward_rounded, size: 17),
                                              ],
                                            ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
