import 'dart:convert';
import 'dart:io' as io;
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

class ApiService {
  // Otomatis memilih baseUrl default:
  // - Android Emulator: http://10.0.2.2/aplikasi-simulasi/api
  // - Windows / Desktop / Web: http://localhost/aplikasi-simulasi/api
  static String baseUrl = _getDefaultBaseUrl();

  static String _getDefaultBaseUrl() {
    if (!kIsWeb) {
      try {
        if (defaultTargetPlatform == TargetPlatform.android) {
          return 'http://10.0.2.2/aplikasi-simulasi/api';
        }
      } catch (_) {}
    }
    return 'http://localhost/aplikasi-simulasi/api';
  }

  static void setBaseUrl(String url) {
    baseUrl = url.replaceAll(RegExp(r'/+$'), '');
  }

  /// Helper untuk menghasilkan URL gambar lengkap dari avatar pengguna
  /// (mendukung URL eksternal http/https dan path server lokal seperti public/uploads/...)
  static String getFullAvatarUrl(String? avatar) {
    if (avatar == null || avatar.trim().isEmpty) return '';
    final trimmed = avatar.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    // Hapus suffix '/api' dari baseUrl untuk mendapatkan root URL aplikasi
    final rootUrl = baseUrl.replaceAll(RegExp(r'/api/?$'), '');
    final cleanPath = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    return '$rootUrl/$cleanPath';
  }

  // Daftar kandidat URL server untuk auto-fallback
  static List<String> get _candidateUrls {
    final list = <String>[baseUrl];
    if (!kIsWeb) {
      try {
        if (defaultTargetPlatform == TargetPlatform.android) {
          const candidates = [
            'http://192.168.0.193/aplikasi-simulasi/api',
            'http://10.0.2.2/aplikasi-simulasi/api',
            'http://10.0.2.2:8000/api',
            'http://10.0.2.2:8000/aplikasi-simulasi/api',
            'http://192.168.11.207/aplikasi-simulasi/api',
          ];
          for (final c in candidates) {
            if (!list.contains(c)) list.add(c);
          }
        }
      } catch (_) {}
    }
    const fallback = [
      'http://localhost/aplikasi-simulasi/api',
      'http://127.0.0.1:8000/api',
      'http://localhost:8000/api',
    ];
    for (final c in fallback) {
      if (!list.contains(c)) list.add(c);
    }
    return list;
  }

  // Helper HTTP POST dengan auto-discovery server
  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> body) async {
    final candidates = _candidateUrls;
    String lastError = '';

    for (final candidate in candidates) {
      try {
        final uri = Uri.parse('$candidate$endpoint');
        final response = await http.post(
          uri,
          headers: {
            'Content-Type': 'application/json; charset=UTF-8',
            'Accept': 'application/json',
          },
          body: jsonEncode(body),
        ).timeout(const Duration(seconds: 4));

        final responseBody = utf8.decode(response.bodyBytes);
        final decoded = jsonDecode(responseBody) as Map<String, dynamic>;

        // Sukses tersambung, set baseUrl ke server yang aktif ini
        baseUrl = candidate;
        return decoded;
      } catch (e) {
        lastError = '$e';
      }
    }

    return {
      'status': 'error',
      'message': 'Gagal menghubungi server ($baseUrl). Pastikan Apache/PHP & MySQL menyala! ($lastError)'
    };
  }

  // Helper HTTP GET dengan auto-discovery server
  static Future<Map<String, dynamic>> get(String endpoint) async {
    final candidates = _candidateUrls;
    String lastError = '';

    for (final candidate in candidates) {
      try {
        final uri = Uri.parse('$candidate$endpoint');
        final response = await http.get(
          uri,
          headers: {
            'Accept': 'application/json',
          },
        ).timeout(const Duration(seconds: 4));

        final responseBody = utf8.decode(response.bodyBytes);
        final decoded = jsonDecode(responseBody) as Map<String, dynamic>;

        baseUrl = candidate;
        return decoded;
      } catch (e) {
        lastError = '$e';
      }
    }

    return {
      'status': 'error',
      'message': 'Gagal menghubungi server ($baseUrl). ($lastError)'
    };
  }

  // 1. Login Siswa
  static Future<Map<String, dynamic>> login(String username, String password) async {
    return await post('/login', {
      'username': username,
      'password': password,
    });
  }

  // 1b. Atur Ulang Kata Sandi Siswa (Forgot Password Mandiri via NISN)
  static Future<Map<String, dynamic>> resetPasswordSiswa({
    required String username,
    required String nisn,
    required String newPassword,
  }) async {
    return await post('/forgot-password', {
      'username': username,
      'nisn': nisn,
      'new_password': newPassword,
    });
  }

  // 1c. Permohonan Reset Kata Sandi Siswa ke Guru Pengelola
  static Future<Map<String, dynamic>> requestResetSiswa({
    required String username,
    required String nisn,
    String? keterangan,
  }) async {
    return await post('/request-reset-password', {
      'username': username,
      'nisn': nisn,
      'keterangan': keterangan ?? '',
    });
  }

  // 1d. Ganti Kata Sandi Siswa (Mandiri setelah reset oleh Guru)
  static Future<Map<String, dynamic>> updatePassword({
    required int userId,
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    return await post('/profil/update-password', {
      'user_id': userId,
      'current_password': currentPassword,
      'new_password': newPassword,
      'confirm_password': confirmPassword,
    });
  }

  // 2. Daftar Modul
  static Future<List<dynamic>> getModul([int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/modul$q');
    if (res['status'] == 'success' && res['data'] is List) {
      return res['data'] as List<dynamic>;
    }
    return [];
  }

  // 3. Detail Materi
  static Future<Map<String, dynamic>?> getMateri(int materiId, [int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/materi/$materiId$q');
    if (res['status'] == 'success' && res['data'] is Map<String, dynamic>) {
      return res['data'] as Map<String, dynamic>;
    }
    return null;
  }

  // 4. Tandai Materi Selesai
  static Future<Map<String, dynamic>> materiSelesai(int materiId, int userId) async {
    return await post('/materi/$materiId/selesai', {'user_id': userId});
  }

  // Alias untuk kompatibilitas
  static Future<Map<String, dynamic>> selesaiMateri(int materiId, int userId) async {
    return await materiSelesai(materiId, userId);
  }

  // 5. Daftar Kuis
  static Future<List<dynamic>> getKuis([int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/kuis$q');
    if (res['status'] == 'success' && res['data'] is List) {
      return res['data'] as List<dynamic>;
    }
    return [];
  }

  // 6. Detail Soal Kuis
  static Future<Map<String, dynamic>?> getKuisDetail(int kuisId, [int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/kuis/$kuisId$q');
    if (res['status'] == 'success' && res['data'] is Map<String, dynamic>) {
      return res['data'] as Map<String, dynamic>;
    }
    return null;
  }

  // 7. Submit Jawaban Kuis
  static Future<Map<String, dynamic>> submitKuis(int kuisId, int userId, Map<String, dynamic> jawaban) async {
    return await post('/kuis/$kuisId/submit', {
      'user_id': userId,
      'jawaban': jawaban,
    });
  }

  // 8. Daftar Simulasi
  static Future<List<dynamic>> getSimulasi([int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/simulasi$q');
    if (res['status'] == 'success' && res['data'] is List) {
      return res['data'] as List<dynamic>;
    }
    return [];
  }

  // 9. Detail Tantangan Simulasi
  static Future<Map<String, dynamic>?> getSimulasiDetail(int simulasiId, [int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/simulasi/$simulasiId$q');
    if (res['status'] == 'success' && res['data'] is Map<String, dynamic>) {
      return res['data'] as Map<String, dynamic>;
    }
    return null;
  }

  // 10. Submit Evaluasi Simulasi
  static Future<Map<String, dynamic>> submitSimulasi(int simulasiId, int userId, Map<String, dynamic> canvasData) async {
    return await post('/simulasi/$simulasiId/submit', {
      'user_id': userId,
      'canvas_data': canvasData,
    });
  }

  // 11. Profil Siswa
  static Future<Map<String, dynamic>?> getProfil([int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/profil$q');
    if (res['status'] == 'success' && res['data'] is Map<String, dynamic>) {
      return res['data'] as Map<String, dynamic>;
    }
    return null;
  }

  // 12. Papan Peringkat (Leaderboard)
  static Future<List<dynamic>> getLeaderboard() async {
    final res = await get('/leaderboard');
    if (res['status'] == 'success' && res['data'] is List) {
      return res['data'] as List<dynamic>;
    }
    return [];
  }

  // 13. Notifikasi Siswa
  static Future<Map<String, dynamic>> getNotifikasi([int? userId]) async {
    final q = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/notifikasi$q');
    if (res['status'] == 'success' && res['data'] is Map<String, dynamic>) {
      return res['data'] as Map<String, dynamic>;
    }
    return {'unread_count': 0, 'notifications': []};
  }

  // 14. Tandai 1 Notifikasi Dibaca
  static Future<Map<String, dynamic>> markNotifikasiRead(int notifId) async {
    final res = await post('/notifikasi/$notifId/read', {});
    return res;
  }

  // 15. Tandai Semua Notifikasi Dibaca
  static Future<Map<String, dynamic>> markAllNotifikasiRead([int? userId]) async {
    final res = await post('/notifikasi/read-all', {'user_id': userId ?? 0});
    return res;
  }

  // 16. Update Avatar Siswa
  static Future<Map<String, dynamic>> updateAvatar(
    int userId, {
    String? avatarUrl,
    String? presetAvatar,
  }) async {
    final body = <String, dynamic>{'user_id': userId};
    if (avatarUrl != null && avatarUrl.isNotEmpty) {
      body['avatar_url'] = avatarUrl;
    }
    if (presetAvatar != null && presetAvatar.isNotEmpty) {
      body['preset_avatar'] = presetAvatar;
    }
    return await post('/profil/avatar', body);
  }

  // 17. Upload Avatar via Base64
  static Future<Map<String, dynamic>> uploadAvatarBase64(int userId, String base64Data) async {
    return await post('/profil/avatar', {
      'user_id': userId,
      'avatar_base64': base64Data,
    });
  }

  // 18. Upload Avatar via File Path
  static Future<Map<String, dynamic>> uploadAvatarFile(int userId, String filePath) async {
    try {
      final file = io.File(filePath);
      if (!await file.exists()) {
        return {'status': 'error', 'message': 'File tidak ditemukan di jalur: $filePath'};
      }
      final bytes = await file.readAsBytes();
      if (bytes.length > 5 * 1024 * 1024) {
        return {'status': 'error', 'message': 'Ukuran file foto maksimal 5 MB.'};
      }
      final ext = filePath.split('.').last.toLowerCase();
      final allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
      if (!allowed.contains(ext)) {
        return {'status': 'error', 'message': 'Format berkas tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.'};
      }
      final mime = (ext == 'jpg' || ext == 'jpeg')
          ? 'image/jpeg'
          : (ext == 'png' ? 'image/png' : (ext == 'webp' ? 'image/webp' : 'image/gif'));
      final base64Str = 'data:$mime;base64,' + base64Encode(bytes);
      return await uploadAvatarBase64(userId, base64Str);
    } catch (e) {
      return {'status': 'error', 'message': 'Gagal membaca berkas: $e'};
    }
  }
}
