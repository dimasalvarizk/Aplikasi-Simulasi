import 'dart:io';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_selector/file_selector.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class _PickedAvatar {
  final String? path;
  final String name;
  final Uint8List bytes;
  _PickedAvatar({this.path, required this.name, required this.bytes});
}

class ProfilScreen extends StatefulWidget {
  const ProfilScreen({Key? key}) : super(key: key);

  @override
  State<ProfilScreen> createState() => _ProfilScreenState();
}

class _ProfilScreenState extends State<ProfilScreen> {
  bool _isLoading = true;
  List<dynamic> _badges = [];
  List<dynamic> _activityLog = [];

  @override
  void initState() {
    super.initState();
    _loadProfil();
  }

  Future<void> _loadProfil() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final data = await ApiService.getProfil(globalUser.id);
      if (mounted) {
        setState(() {
          if (data != null) {
            final userObj = (data['user'] as Map<String, dynamic>?) ?? data;
            globalUser.setFromApi(userObj, globalUser.token);

            _badges = data['badges'] as List<dynamic>? ?? [];
            _activityLog = data['aktivitas'] as List<dynamic>? ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      barrierColor: Colors.transparent,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar dari Akun', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
        content: const Text(
          'Apakah Anda yakin ingin keluar?',
          style: TextStyle(fontSize: 13, color: Color(0xFF475569)),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626)),
            onPressed: () {
              Navigator.pop(ctx);
              globalUser.logout();
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(builder: (_) => const LoginScreen()),
                (route) => false,
              );
            },
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }

  final List<Map<String, String>> _presetAvatars = [
    {'file': 'avatar_1.png', 'nama': 'Siswa IT', 'icon': '🧑‍💻', 'color': '0xFF3B82F6'},
    {'file': 'avatar_2.png', 'nama': 'Siswi IT', 'icon': '👩‍💻', 'color': '0xFFEC4899'},
    {'file': 'avatar_3.png', 'nama': 'Cendekia', 'icon': '🧑‍🎓', 'color': '0xFF10B981'},
    {'file': 'avatar_4.png', 'nama': 'Kreatif', 'icon': '👩‍🎨', 'color': '0xFF8B5CF6'},
    {'file': 'avatar_5.png', 'nama': 'Teknisi LAN', 'icon': '👨‍🔧', 'color': '0xFFF59E0B'},
    {'file': 'avatar_6.png', 'nama': 'Bot SMPN 1', 'icon': '🤖', 'color': '0xFF06B6D4'},
    {'file': 'avatar_7.png', 'nama': 'Cyber Gamer', 'icon': '🎮', 'color': '0xFF6366F1'},
    {'file': 'avatar_8.png', 'nama': 'Network Master', 'icon': '🌐', 'color': '0xFF0F172A'},
    {'file': 'avatar_9.png', 'nama': 'Spesialis LAN', 'icon': '🔌', 'color': '0xFF14B8A6'},
    {'file': 'avatar_10.png', 'nama': 'Topologi Pro', 'icon': '🗺️', 'color': '0xFFF43F5E'},
    {'file': 'avatar_11.png', 'nama': 'Arsitek Jaringan', 'icon': '⚡', 'color': '0xFFEA580C'},
    {'file': 'avatar_12.png', 'nama': 'Bintang Sekolah', 'icon': '🏆', 'color': '0xFFEAB308'},
  ];

  Widget _buildAvatarWidget({double size = 52, double radius = 14}) {
    final avatarStr = globalUser.avatar.trim();
    final fallbackInitial = Center(
      child: Text(
        globalUser.nama.isNotEmpty ? globalUser.nama.substring(0, 1).toUpperCase() : 'S',
        style: TextStyle(
          fontSize: size * 0.42,
          fontWeight: FontWeight.w800,
          color: const Color(0xFF4F46E5),
        ),
      ),
    );

    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: ApiService.buildAvatarWidget(
        avatarStr,
        width: size,
        height: size,
        fallback: fallbackInitial,
      ),
    );
  }

  Future<_PickedAvatar?> _pickImageFile() async {
    // 1. Khusus Desktop Windows: Gunakan FileSelector resmi Windows (native Explorer dialog)
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.windows) {
      try {
        const typeGroup = XTypeGroup(
          label: 'File Foto',
          extensions: ['jpg', 'jpeg', 'png', 'webp'],
        );
        final file = await openFile(acceptedTypeGroups: [typeGroup]);
        if (file != null) {
          final bytes = await file.readAsBytes();
          return _PickedAvatar(
            path: file.path,
            name: file.name,
            bytes: bytes,
          );
        }
      } catch (e) {
        debugPrint('FileSelector Windows error: $e');
      }

      // Fallback sekunder OpenFileDialog jika diperlukan
      try {
        const psScript = r'''
Add-Type -AssemblyName System.Windows.Forms
$d = New-Object System.Windows.Forms.OpenFileDialog
$d.Title = "Pilih Foto Profil"
$d.Filter = "File Gambar (*.jpg;*.jpeg;*.png;*.webp)|*.jpg;*.jpeg;*.png;*.webp"
$d.RestoreDirectory = $true
$f = New-Object System.Windows.Forms.Form
$f.TopMost = $true
if ($d.ShowDialog($f) -eq [System.Windows.Forms.DialogResult]::OK) {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    [Console]::Out.Write($d.FileName)
}
$f.Dispose()
''';
        final res = await Process.run('powershell', [
          '-STA',
          '-NoProfile',
          '-ExecutionPolicy',
          'Bypass',
          '-Command',
          psScript,
        ]);
        final path = res.stdout.toString().trim();
        if (path.isNotEmpty && File(path).existsSync()) {
          final bytes = await File(path).readAsBytes();
          final name = path.split(RegExp(r'[/\\]')).last;
          return _PickedAvatar(
            path: path,
            name: name,
            bytes: bytes,
          );
        }
      } catch (e) {
        debugPrint('Windows OpenFileDialog error: $e');
      }
      return null;
    }

    // 2. Di Android, iOS, atau Web: Gunakan ImagePicker resmi (buka galeri foto HP / file dialog)
    try {
      final picker = ImagePicker();
      final XFile? image = await picker.pickImage(
        source: ImageSource.gallery,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 85,
      );
      if (image != null) {
        final bytes = await image.readAsBytes();
        return _PickedAvatar(
          path: image.path,
          name: image.name,
          bytes: bytes,
        );
      }
    } catch (e) {
      debugPrint('ImagePicker gallery error: $e');
    }

    return null;
  }

    return null;
  }

  Future<void> _updateAvatarPreset(String filename) async {
    final avatarPath = 'public/img/avatars/$filename';

    // 1. Perbarui state lokal secara instan agar UI berganti tanpa jeda
    setState(() {
      globalUser.updateAvatar(avatarPath);
    });

    if (mounted) {
      Navigator.pop(context); // Tutup modal pemilihan
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Foto profil avatar karakter berhasil diterapkan!'),
          backgroundColor: Color(0xFF16A34A),
          duration: Duration(seconds: 2),
        ),
      );
    }

    // 2. Sinkronkan pembaruan ke database server
    try {
      final res = await ApiService.updateAvatar(globalUser.id, presetAvatar: filename);
      if (res['status'] == 'success') {
        final newUrl = res['data']?['avatar_url']?.toString() ?? res['data']?['avatar']?.toString() ?? avatarPath;
        if (mounted) {
          setState(() {
            globalUser.updateAvatar(newUrl);
          });
          _loadProfil();
        }
      }
    } catch (e) {
      debugPrint('Sinkronisasi avatar ke server tertunda: $e');
    }
  }

  void _showAvatarPickerBottomSheet() {
    _PickedAvatar? pickedAvatar;
    bool isUploading = false;
    String? uploadError;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      barrierColor: Colors.transparent,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          final fileSizeText = pickedAvatar != null
              ? '${(pickedAvatar!.bytes.length / 1024).toStringAsFixed(1)} KB'
              : '';

          return Container(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.88,
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
                          Icon(Icons.photo_camera_rounded, size: 22, color: Color(0xFF2563EB)),
                          SizedBox(width: 8),
                          Text(
                            'Ganti Foto Profil Siswa',
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
                  const Text(
                    'Unggah foto asli Anda dari komputer/perangkat atau pilih avatar karakter SMPN 1 Karangmoncol.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                  ),
                  const SizedBox(height: 16),

                  // ==========================================
                  // 1. UNGGAH FOTO (SIMPEL & BERSIH)
                  // ==========================================
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      children: [
                        if (pickedAvatar == null) ...[
                          InkWell(
                            onTap: isUploading
                                ? null
                                : () async {
                                    try {
                                      final picked = await _pickImageFile();
                                      if (picked != null) {
                                        setModalState(() {
                                          pickedAvatar = picked;
                                          uploadError = null;
                                        });
                                      }
                                    } catch (err) {
                                      setModalState(() {
                                        uploadError = 'Gagal membuka galeri: $err';
                                      });
                                    }
                                  },
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFCBD5E1)),
                              ),
                              child: Column(
                                children: const [
                                  Icon(Icons.cloud_upload_rounded, size: 36, color: Color(0xFF2563EB)),
                                  SizedBox(height: 8),
                                  Text(
                                    'Pilih Foto dari Perangkat',
                                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
                                  ),
                                  SizedBox(height: 3),
                                  Text(
                                    'Pilih file foto JPG, PNG, atau WEBP',
                                    style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ] else ...[
                          // Preview Foto Terpilih
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFF93C5FD)),
                            ),
                            child: Row(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.memory(
                                    pickedAvatar!.bytes,
                                    width: 48,
                                    height: 48,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      width: 48,
                                      height: 48,
                                      color: const Color(0xFFE2E8F0),
                                      child: const Icon(Icons.broken_image, size: 24, color: Color(0xFF94A3B8)),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        pickedAvatar!.name,
                                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        fileSizeText,
                                        style: const TextStyle(fontSize: 11, color: Color(0xFF16A34A), fontWeight: FontWeight.w600),
                                      ),
                                    ],
                                  ),
                                ),
                                TextButton(
                                  onPressed: () {
                                    setModalState(() {
                                      pickedAvatar = null;
                                      uploadError = null;
                                    });
                                  },
                                  child: const Text('Ganti', style: TextStyle(fontSize: 11, color: Color(0xFFDC2626))),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 10),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF2563EB),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 11),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                elevation: 0,
                              ),
                              icon: isUploading
                                  ? const SizedBox(
                                      width: 16,
                                      height: 16,
                                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                    )
                                  : const Icon(Icons.check_circle_outline_rounded, size: 18),
                              label: Text(
                                isUploading ? 'Mengunggah...' : 'Simpan Foto Ini',
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                              ),
                              onPressed: isUploading
                                  ? null
                                  : () async {
                                      setModalState(() {
                                        isUploading = true;
                                        uploadError = null;
                                      });
                                      try {
                                        final res = await ApiService.uploadAvatarBytes(
                                          globalUser.id,
                                          pickedAvatar!.bytes,
                                          pickedAvatar!.name,
                                        );
                                        if (res['status'] == 'success') {
                                          final newUrl = res['data']?['avatar_url']?.toString() ?? res['data']?['avatar']?.toString() ?? '';
                                          if (mounted) {
                                            setState(() {
                                              globalUser.updateAvatar(newUrl);
                                            });
                                            Navigator.pop(ctx);
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(
                                                content: Text('Foto profil berhasil disimpan!'),
                                                backgroundColor: Color(0xFF16A34A),
                                                duration: Duration(seconds: 3),
                                              ),
                                            );
                                            _loadProfil();
                                          }
                                        } else {
                                          setModalState(() {
                                            uploadError = res['message'] ?? 'Gagal mengunggah foto.';
                                          });
                                        }
                                      } catch (e) {
                                        setModalState(() {
                                          uploadError = 'Gagal upload: $e';
                                        });
                                      } finally {
                                        setModalState(() {
                                          isUploading = false;
                                        });
                                      }
                                    },
                            ),
                          ),
                        ],

                        if (uploadError != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            uploadError!,
                            style: const TextStyle(color: Color(0xFFDC2626), fontSize: 11, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ],
                    ),
                  ),

                  const SizedBox(height: 18),
                  Row(
                    children: const [
                      Expanded(child: Divider(color: Color(0xFFE2E8F0))),
                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: 10),
                        child: Text('ATAU PILIH AVATAR KARAKTER', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF94A3B8))),
                      ),
                      Expanded(child: Divider(color: Color(0xFFE2E8F0))),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // ==========================================
                  // 2. BAGIAN PRESET AVATAR KARAKTER (12 PRESETS)
                  // ==========================================
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _presetAvatars.length,
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 4,
                      mainAxisSpacing: 10,
                      crossAxisSpacing: 10,
                      childAspectRatio: 0.85,
                    ),
                    itemBuilder: (context, index) {
                      final item = _presetAvatars[index];
                      final colorInt = int.parse(item['color'] ?? '0xFF3B82F6');
                      final presetPngUrl = ApiService.getFullAvatarUrl('public/img/avatars/${item['file']}');

                      return InkWell(
                        onTap: () => _updateAvatarPreset(item['file']!),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          decoration: BoxDecoration(
                            color: Color(colorInt).withOpacity(0.08),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Color(colorInt).withOpacity(0.3)),
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Image.asset(
                                  'assets/avatars/${item['file']}',
                                  width: 36,
                                  height: 36,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, __, ___) => Image.network(
                                    presetPngUrl,
                                    width: 36,
                                    height: 36,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Text(item['icon'] ?? '🧑‍💻', style: const TextStyle(fontSize: 26)),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 5),
                              Text(
                                item['nama'] ?? '',
                                textAlign: TextAlign.center,
                                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showChangePasswordBottomSheet() {
    final currentPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    bool obscureCurrent = true;
    bool obscureNew = true;
    bool obscureConfirm = true;
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
                        Icon(Icons.lock_reset_rounded, size: 24, color: Color(0xFF2563EB)),
                        SizedBox(width: 8),
                        Text(
                          'Ganti Kata Sandi Siswa',
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
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFBFDBFE)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Icon(Icons.shield_outlined, size: 18, color: Color(0xFF2563EB)),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Masukkan kata sandi saat ini (atau kata sandi sementara dari guru), lalu buat kata sandi baru minimal 6 karakter.',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF1E40AF), height: 1.35),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                // Error alert
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

                // Input 1: Kata Sandi Saat Ini / Sementara
                const Text(
                  'Kata Sandi Saat Ini / Sementara',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: currentPasswordController,
                  obscureText: obscureCurrent,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Masukkan kata sandi saat ini',
                    prefixIcon: const Icon(Icons.lock_clock_outlined, size: 18),
                    suffixIcon: IconButton(
                      icon: Icon(obscureCurrent ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 18),
                      onPressed: () => setModalState(() => obscureCurrent = !obscureCurrent),
                    ),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 12),

                // Input 2: Kata Sandi Baru
                const Text(
                  'Kata Sandi Baru',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: newPasswordController,
                  obscureText: obscureNew,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Minimal 6 karakter',
                    prefixIcon: const Icon(Icons.lock_outline, size: 18),
                    suffixIcon: IconButton(
                      icon: Icon(obscureNew ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 18),
                      onPressed: () => setModalState(() => obscureNew = !obscureNew),
                    ),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 12),

                // Input 3: Konfirmasi Kata Sandi Baru
                const Text(
                  'Ulangi Kata Sandi Baru',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: confirmPasswordController,
                  obscureText: obscureConfirm,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Ulangi kata sandi baru',
                    prefixIcon: const Icon(Icons.check_circle_outline, size: 18),
                    suffixIcon: IconButton(
                      icon: Icon(obscureConfirm ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 18),
                      onPressed: () => setModalState(() => obscureConfirm = !obscureConfirm),
                    ),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                  ),
                ),
                const SizedBox(height: 18),

                // Tombol Simpan
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            final currentPwd = currentPasswordController.text.trim();
                            final newPwd = newPasswordController.text.trim();
                            final confirmPwd = confirmPasswordController.text.trim();

                            if (currentPwd.isEmpty || newPwd.isEmpty || confirmPwd.isEmpty) {
                              setModalState(() => formError = 'Semua kolom kata sandi wajib diisi.');
                              return;
                            }
                            if (newPwd.length < 6) {
                              setModalState(() => formError = 'Kata sandi baru minimal harus 6 karakter.');
                              return;
                            }
                            if (newPwd != confirmPwd) {
                              setModalState(() => formError = 'Konfirmasi kata sandi baru tidak cocok.');
                              return;
                            }

                            setModalState(() {
                              isSubmitting = true;
                              formError = null;
                            });

                            try {
                              final res = await ApiService.updatePassword(
                                userId: globalUser.id,
                                currentPassword: currentPwd,
                                newPassword: newPwd,
                                confirmPassword: confirmPwd,
                              );

                              if (!mounted) return;

                              if (res['status'] == 'success') {
                                Navigator.pop(ctx);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Kata sandi berhasil diperbarui!'),
                                    backgroundColor: Color(0xFF16A34A),
                                  ),
                                );
                              } else {
                                setModalState(() {
                                  isSubmitting = false;
                                  formError = res['message'] ?? 'Gagal memperbarui kata sandi.';
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
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: isSubmitting
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text(
                            'Simpan Kata Sandi Baru',
                            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                          ),
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
    final int xp = globalUser.totalXp;
    final int level = globalUser.level;
    final int currentLevelMin = (level - 1) * 100;
    final int nextLevelMin = level * 100;
    final int progressInLevel = xp - currentLevelMin;
    final double levelProgress = (progressInLevel / 100).clamp(0.0, 1.0);
    final int xpNeeded = (nextLevelMin - xp).clamp(0, 100);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          tooltip: 'Kembali',
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Profil Siswa', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, size: 19, color: Color(0xFF64748B)),
            tooltip: 'Keluar',
            onPressed: _confirmLogout,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadProfil,
        color: const Color(0xFF0F172A),
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          children: [
            // User Information Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      // Clickable Avatar with Camera Badge
                      GestureDetector(
                        onTap: _showAvatarPickerBottomSheet,
                        child: Stack(
                          children: [
                            Container(
                              width: 52,
                              height: 52,
                              decoration: BoxDecoration(
                                color: const Color(0xFFEEF2FF),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFFC7D2FE), width: 1.5),
                              ),
                              clipBehavior: Clip.antiAlias,
                              child: _buildAvatarWidget(size: 52, radius: 14),
                            ),
                            Positioned(
                              right: -2,
                              bottom: -2,
                              child: Container(
                                padding: const EdgeInsets.all(4),
                                decoration: const BoxDecoration(
                                  color: Color(0xFF2563EB),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.camera_alt, size: 11, color: Colors.white),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    globalUser.nama.isNotEmpty ? globalUser.nama : 'Siswa',
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                      color: Color(0xFF0F172A),
                                    ),
                                  ),
                                ),
                                InkWell(
                                  onTap: _showAvatarPickerBottomSheet,
                                  borderRadius: BorderRadius.circular(6),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFEFF6FF),
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(color: const Color(0xFFBFDBFE)),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: const [
                                        Icon(Icons.edit, size: 10, color: Color(0xFF2563EB)),
                                        SizedBox(width: 4),
                                        Text('Ubah', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFF2563EB))),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Kelas ${globalUser.kelas.isNotEmpty ? globalUser.kelas : 'VII'} • SMP Negeri 1 Karangmoncol',
                              style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  const Divider(color: Color(0xFFF1F5F9)),
                  const SizedBox(height: 10),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Level $level — ${globalUser.levelTitle}',
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        '$xp Total XP',
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF2563EB)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: levelProgress,
                      backgroundColor: const Color(0xFFF1F5F9),
                      valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF0F172A)),
                      minHeight: 6,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '$xp / $nextLevelMin XP • Butuh $xpNeeded XP lagi menuju Level ${level + 1}',
                    style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Card Keamanan & Ganti Kata Sandi
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x05000000),
                    blurRadius: 8,
                    offset: Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.shield_outlined, size: 20, color: Color(0xFFD97706)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text(
                              'Keamanan Kata Sandi',
                              style: TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                            SizedBox(height: 2),
                            Text(
                              'Ganti kata sandi sementara dari guru / perbarui sandi',
                              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Icon(Icons.info_outline, size: 16, color: Color(0xFF2563EB)),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Jika baru saja menerima reset password sementara dari Guru (Bpk. Angzal Maolana / Ibu Hana Sifakhatin), segera ganti ke kata sandi baru pribadi Anda.',
                            style: TextStyle(fontSize: 11, color: Color(0xFF475569), height: 1.35),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 11),
                        side: const BorderSide(color: Color(0xFFCBD5E1)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                      ),
                      onPressed: _showChangePasswordBottomSheet,
                      icon: const Icon(Icons.key_rounded, size: 16, color: Color(0xFFFBBF24)),
                      label: const Text(
                        'Ganti Kata Sandi Siswa',
                        style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Pencapaian (Badges) Minimalist
            const Text(
              'Lencana Pencapaian',
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),

            if (_badges.isEmpty)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Text('Belum ada lencana yang tersedia.', style: TextStyle(color: Color(0xFF64748B), fontSize: 12)),
              )
            else
              ..._badges.map((b) {
                final bool unlocked = b['unlocked'] == true ||
                    b['is_unlocked'] == true ||
                    b['diraih'] == 1 ||
                    b['diraih'] == '1' ||
                    b['diraih'] == true;
                final String nama = b['nama'] ?? 'Lencana';
                final String deskripsi = b['deskripsi'] ?? '';

                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: unlocked ? const Color(0xFFF0FDF4) : Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: unlocked ? const Color(0xFF86EFAC) : const Color(0xFFE2E8F0),
                      width: unlocked ? 1.2 : 1.0,
                    ),
                    boxShadow: unlocked
                        ? [
                            BoxShadow(
                              color: const Color(0xFF16A34A).withOpacity(0.06),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            )
                          ]
                        : [],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: unlocked ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(
                          unlocked ? Icons.emoji_events_rounded : Icons.lock_outline_rounded,
                          size: 20,
                          color: unlocked ? const Color(0xFF16A34A) : const Color(0xFF94A3B8),
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
                                    nama,
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: unlocked ? const Color(0xFF0F172A) : const Color(0xFF64748B),
                                    ),
                                  ),
                                ),
                                if (unlocked) ...[
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF16A34A),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: const Text(
                                      'DIRAIH',
                                      style: TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w800),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              deskripsi,
                              style: TextStyle(
                                fontSize: 11,
                                color: unlocked ? const Color(0xFF475569) : const Color(0xFF94A3B8),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }).toList(),

            const SizedBox(height: 20),

            // Riwayat Aktivitas Minimalist
            const Text(
              'Riwayat Aktivitas',
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),

            if (_activityLog.isEmpty)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Text('Belum ada riwayat perolehan XP.', style: TextStyle(color: Color(0xFF64748B), fontSize: 12)),
              )
            else
              ..._activityLog.map((act) {
                final int xpAmount = act['xp'] is int
                    ? act['xp']
                    : (act['xp_amount'] is int
                        ? act['xp_amount']
                        : (int.tryParse('${act['xp'] ?? act['xp_amount']}') ?? 0));
                final String judul = act['aktivitas'] ?? act['judul'] ?? act['keterangan'] ?? 'Aktivitas Belajar';
                final String tgl = act['tanggal'] ?? act['created_at'] ?? '';

                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.bolt_rounded, size: 18, color: Color(0xFF2563EB)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(judul, style: const TextStyle(fontSize: 12.5, color: Color(0xFF0F172A), fontWeight: FontWeight.w600)),
                            if (tgl.isNotEmpty)
                              Text(tgl, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0FDF4),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFBBF7D0)),
                        ),
                        child: Text(
                          '+$xpAmount XP',
                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF16A34A)),
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
