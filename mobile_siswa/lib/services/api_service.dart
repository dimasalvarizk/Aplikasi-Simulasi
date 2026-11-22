import 'dart:async';
import 'dart:convert';
import 'dart:io' as io;
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

class ApiService {
  // Otomatis memilih baseUrl default (di Android langsung mengarah ke IP laptop atau auto-scan)
  static String baseUrl = _getDefaultBaseUrl();
  static bool _isDiscovering = false;
  static Completer<String?>? _discoveryCompleter;

  static String _getDefaultBaseUrl() {
    return 'https://smpn1karmon.ct.ws/api';
  }

  static final List<String> _knownAppPaths = [
    'aplikasi-simulasi-main1',
    'Aplikasi-Simulasi-main1',
    'aplikasi-simulasi',
    'Aplikasi-Simulasi-main',
    'Aplikasi-Simulasi',
  ];

  /// Membersihkan dan menormalkan URL yang diketik/ditempel
  /// Mendukung domain online (HTTPS) maupun IP lokal/XAMPP
  static String sanitizeUrl(String raw) {
    var url = raw.trim();
    if (url.isEmpty) return 'https://smpn1karmon.ct.ws/api';

    // Bersihkan leading slash jika tidak sengaja terketik
    while (url.startsWith('/')) {
      url = url.substring(1).trim();
    }

    // Perbaiki jika pengguna tidak sengaja menempel di tengah teks lain
    if (url.contains('http://') || url.contains('https://')) {
      final lastIdx = url.lastIndexOf(RegExp(r'https?://'));
      if (lastIdx > 0) {
        url = url.substring(lastIdx);
      }
    }

    // Pertahankan https jika diberikan atau jika berupa domain hosting
    final bool isHttps = url.toLowerCase().startsWith('https://') || (!url.toLowerCase().startsWith('http://') && url.contains('.'));
    url = url.replaceAll(RegExp(r'^(https?://)+', caseSensitive: false), isHttps ? 'https://' : 'http://');
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = isHttps ? 'https://$url' : 'http://$url';
    }

    url = url.replaceAll(RegExp(r'/+$'), '');

    final uri = Uri.tryParse(url);
    if (uri != null) {
      final host = uri.host.toLowerCase();
      // Hanya sisipkan /aplikasi-simulasi jika targetnya adalah IP lokal XAMPP atau localhost
      final isLocal = host == 'localhost' || host == '127.0.0.1' || host.startsWith('192.168.') || host.startsWith('10.');
      if (isLocal && (uri.path.isEmpty || uri.path == '/' || uri.path == '/api')) {
        final scheme = uri.scheme.isNotEmpty ? uri.scheme : 'http';
        final hostPart = uri.hasPort ? '${uri.host}:${uri.port}' : uri.host;
        return '$scheme://$hostPart/aplikasi-simulasi/api';
      }
    }

    // Pastikan berakhiran /api jika belum ada
    if (!url.endsWith('/api') && !url.contains('/api/')) {
      url = '$url/api';
    }
    return url;
  }

  /// Menghasilkan URL alternatif untuk mengantisipasi perbedaan nama folder di laptop lain
  /// Contoh: /aplikasi-simulasi/ <--> /Aplikasi-Simulasi-main1/
  static List<String> getAlternateFolderUrls(String currentUrl) {
    final List<String> alternates = [];
    final current = sanitizeUrl(currentUrl);
    for (final folder in _knownAppPaths) {
      for (final target in _knownAppPaths) {
        if (folder != target && current.contains('/$folder')) {
          final candidate = current.replaceAll('/$folder', '/$target');
          if (!alternates.contains(candidate) && candidate != current) {
            alternates.add(candidate);
          }
        }
      }
    }
    return alternates;
  }

  static void setBaseUrl(String url) {
    baseUrl = sanitizeUrl(url);
  }

  /// Helper untuk menghasilkan URL gambar lengkap dari avatar pengguna
  static String getFullAvatarUrl(String? avatar) {
    if (avatar == null || avatar.trim().isEmpty) return '';
    final trimmed = avatar.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    final rootUrl = baseUrl.replaceAll(RegExp(r'/api/?$'), '');
    final cleanPath = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    return '$rootUrl/$cleanPath';
  }

  /// Widget cerdas penampil avatar: memprioritaskan asset lokal offline, file lokal, baru URL server
  static Widget buildAvatarWidget(
    String? avatar, {
    double? width,
    double? height,
    BoxFit fit = BoxFit.cover,
    Widget? fallback,
  }) {
    final defFallback = fallback ?? const Icon(Icons.person_outline, size: 20, color: Color(0xFF334155));
    if (avatar == null || avatar.trim().isEmpty) return defFallback;
    final trimmed = avatar.trim();

    // 1. Cek apakah ini preset avatar karakter (bisa langsung dimuat dari asset lokal tanpa koneksi)
    final fileName = trimmed.split(RegExp(r'[/\\]')).last.trim();
    if (RegExp(r'^avatar_[0-9]+\.(png|svg)$').hasMatch(fileName)) {
      final pngAsset = 'assets/avatars/${fileName.replaceAll('.svg', '.png')}';
      return Image.asset(
        pngAsset,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (_, __, ___) {
          final fullUrl = getFullAvatarUrl(trimmed);
          if (fullUrl.isNotEmpty && fullUrl.startsWith('http')) {
            return Image.network(
              fullUrl,
              width: width,
              height: height,
              fit: fit,
              errorBuilder: (_, __, ___) => defFallback,
            );
          }
          return defFallback;
        },
      );
    }

    // 2. Cek apakah file lokal di disk komputer/HP (hanya untuk platform non-Web)
    if (!kIsWeb) {
      try {
        if (!trimmed.startsWith('http') && !trimmed.startsWith('public/') && io.File(trimmed).existsSync()) {
          return Image.file(
            io.File(trimmed),
            width: width,
            height: height,
            fit: fit,
            errorBuilder: (_, __, ___) => defFallback,
          );
        }
      } catch (_) {}
    }

    // 3. Muat dari URL server atau external
    final fullUrl = getFullAvatarUrl(trimmed);
    if (fullUrl.isNotEmpty && fullUrl.startsWith('http')) {
      return Image.network(
        fullUrl,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (_, __, ___) => defFallback,
      );
    }

    return defFallback;
  }

  /// Tes apakah suatu URL server merespon API dengan status 200
  static Future<bool> testServer(String candidateUrl, {int timeoutMs = 1200}) async {
    try {
      final clean = sanitizeUrl(candidateUrl);
      final uri = Uri.parse('$clean/');
      final res = await http.get(
        uri,
        headers: {'Accept': 'application/json'},
      ).timeout(Duration(milliseconds: timeoutMs));

      if (res.statusCode == 200) {
        final body = utf8.decode(res.bodyBytes);
        if (body.contains('status') || body.contains('Karangmoncol') || body.contains('API Ready') || body.contains('endpoints')) {
          return true;
        }
      }
    } catch (_) {}
    return false;
  }

  /// Kumpulkan kandidat IP lokal cerdas dari interface Wi-Fi / Hotspot perangkat
  static Future<List<String>> getSmartCandidateUrls() async {
    final Set<String> candidates = {
      baseUrl,
      // Target USB Kabel via ADB Reverse (port 8080 dan port 80)
      'http://localhost:8080/aplikasi-simulasi/api',
      'http://127.0.0.1:8080/aplikasi-simulasi/api',
      'http://localhost:8080/Aplikasi-Simulasi-main1/api',
      'http://127.0.0.1:8080/Aplikasi-Simulasi-main1/api',
      'http://localhost/aplikasi-simulasi/api',
      'http://127.0.0.1/aplikasi-simulasi/api',
      'http://localhost/Aplikasi-Simulasi-main1/api',
      // IP Laptop saat ini (Wi-Fi 192.168.1.8)
      'http://192.168.1.8/aplikasi-simulasi/api',
      'http://192.168.1.8/Aplikasi-Simulasi-main1/api',
      'http://192.168.1.8:8080/aplikasi-simulasi/api',
      // IP Umum Hotspot & Wi-Fi
      'http://192.168.43.1/aplikasi-simulasi/api',
      'http://192.168.43.1/Aplikasi-Simulasi-main1/api',
      'http://192.168.18.6/aplikasi-simulasi/api',
      'http://192.168.18.6/Aplikasi-Simulasi-main1/api',
      'http://10.34.197.13/aplikasi-simulasi/api',
      'http://10.34.197.13/Aplikasi-Simulasi-main1/api',
      'http://10.0.2.2/aplikasi-simulasi/api',     // Android Emulator
      'http://10.0.2.2/Aplikasi-Simulasi-main1/api',
      'http://192.168.0.193/aplikasi-simulasi/api',
    };

    final Set<String> discoveredSubnets = {
      '10.34.197.',
      '192.168.18.',
      '192.168.43.',
      '192.168.1.',
      '192.168.0.',
    };

    if (!kIsWeb) {
      try {
        final interfaces = await io.NetworkInterface.list(
          type: io.InternetAddressType.IPv4,
          includeLoopback: false,
        );

        for (final iface in interfaces) {
          for (final addr in iface.addresses) {
            final ip = addr.address;
            final lastDot = ip.lastIndexOf('.');
            if (lastDot != -1) {
              discoveredSubnets.add(ip.substring(0, lastDot + 1));
            }
          }
        }
      } catch (_) {}
    }

    // Bangun daftar target IP untuk setiap subnet secara otomatis
    for (final subnet in discoveredSubnets) {
      final hostOctets = [1, 6, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 15, 20, 100, 101, 102];
      for (final octet in hostOctets) {
        for (final folder in _knownAppPaths) {
          candidates.add('http://$subnet$octet/$folder/api');
        }
      }
    }

    return candidates.map(sanitizeUrl).toList();
  }

  /// Pindai dan temukan server API secara otomatis tanpa perlu setting manual (Fast Parallel Race)
  static Future<String?> autoDiscoverServer({bool force = false}) async {
    if (_isDiscovering && _discoveryCompleter != null) {
      return _discoveryCompleter!.future;
    }

    // Jika baseUrl saat ini sudah merespon dan tidak dipaksa re-scan, gunakan langsung
    if (!force) {
      final isCurrentOk = await testServer(baseUrl, timeoutMs: 600);
      if (isCurrentOk) return baseUrl;
    }

    _isDiscovering = true;
    _discoveryCompleter = Completer<String?>();

    try {
      final allCandidates = await getSmartCandidateUrls();

      // Cek prioritas pertama secara paralel super cepat (IP laptop teman/personal, hotspot, emulator)
      final priority = [
        'http://10.34.197.13/Aplikasi-Simulasi-main1/api',
        'http://10.34.197.13/aplikasi-simulasi-main1/api',
        'http://10.34.197.13/aplikasi-simulasi/api',
        'http://192.168.18.6/Aplikasi-Simulasi-main1/api',
        'http://192.168.18.6/aplikasi-simulasi-main1/api',
        'http://192.168.18.6/aplikasi-simulasi/api',
        'http://192.168.43.1/Aplikasi-Simulasi-main1/api',
        'http://192.168.43.1/aplikasi-simulasi-main1/api',
        'http://10.0.2.2/aplikasi-simulasi/api',
        'http://localhost/Aplikasi-Simulasi-main1/api',
      ].map(sanitizeUrl).toList();

      final priorityCompleter = Completer<String?>();
      int pPending = priority.length;
      for (final pUrl in priority) {
        testServer(pUrl, timeoutMs: 800).then((ok) {
          if (ok && !priorityCompleter.isCompleted) {
            priorityCompleter.complete(pUrl);
          } else {
            pPending--;
            if (pPending <= 0 && !priorityCompleter.isCompleted) {
              priorityCompleter.complete(null);
            }
          }
        }).catchError((_) {
          pPending--;
          if (pPending <= 0 && !priorityCompleter.isCompleted) {
            priorityCompleter.complete(null);
          }
        });
      }

      final pFound = await priorityCompleter.future.timeout(
        const Duration(milliseconds: 900),
        onTimeout: () => null,
      );

      if (pFound != null) {
        baseUrl = pFound;
        _isDiscovering = false;
        _discoveryCompleter?.complete(pFound);
        return pFound;
      }

      // Jika belum ditemukan di prioritas, lakukan race paralel pada seluruh kandidat
      final raceCompleter = Completer<String?>();
      final remaining = allCandidates.where((u) => !priority.contains(u)).toList();
      int rPending = remaining.length;

      for (final rUrl in remaining) {
        testServer(rUrl, timeoutMs: 1200).then((ok) {
          if (ok && !raceCompleter.isCompleted) {
            raceCompleter.complete(rUrl);
          } else {
            rPending--;
            if (rPending <= 0 && !raceCompleter.isCompleted) {
              raceCompleter.complete(null);
            }
          }
        }).catchError((_) {
          rPending--;
          if (rPending <= 0 && !raceCompleter.isCompleted) {
            raceCompleter.complete(null);
          }
        });
      }

      final rFound = await raceCompleter.future.timeout(
        const Duration(seconds: 2),
        onTimeout: () => null,
      );

      if (rFound != null) {
        baseUrl = rFound;
        _isDiscovering = false;
        _discoveryCompleter?.complete(rFound);
        return rFound;
      }
    } catch (_) {}

    _isDiscovering = false;
    _discoveryCompleter?.complete(null);
    return null;
  }

  // Helper HTTP POST dengan auto-fallback folder dan auto-discovery otomatis
  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> body) async {
    baseUrl = sanitizeUrl(baseUrl);
    final candidates = [baseUrl, ...getAlternateFolderUrls(baseUrl)];

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

        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
          baseUrl = candidate; // Server aktif terverifikasi!
          return decoded;
        }
      } catch (_) {}
    }

    // Jika seluruh folder di IP tersebut gagal, lakukan scan otomatis ke seluruh jaringan
    final found = await autoDiscoverServer(force: true);
    if (found != null && found != baseUrl) {
      baseUrl = found;
      try {
        final retryUri = Uri.parse('$baseUrl$endpoint');
        final retryRes = await http.post(
          retryUri,
          headers: {
            'Content-Type': 'application/json; charset=UTF-8',
            'Accept': 'application/json',
          },
          body: jsonEncode(body),
        ).timeout(const Duration(seconds: 5));

        final retryBody = utf8.decode(retryRes.bodyBytes);
        return jsonDecode(retryBody) as Map<String, dynamic>;
      } catch (_) {}
    }

    return {
      'status': 'error',
      'message': 'Gagal menghubungi server ($baseUrl). Pastikan Apache XAMPP menyala dan Port 80 tidak diblokir Firewall!'
    };
  }

  // Helper HTTP GET dengan auto-fallback folder dan auto-discovery otomatis
  static Future<Map<String, dynamic>> get(String endpoint) async {
    baseUrl = sanitizeUrl(baseUrl);
    final candidates = [baseUrl, ...getAlternateFolderUrls(baseUrl)];

    for (final candidate in candidates) {
      try {
        final uri = Uri.parse('$candidate$endpoint');
        final response = await http.get(
          uri,
          headers: {
            'Accept': 'application/json',
          },
        ).timeout(const Duration(seconds: 4));

        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
          baseUrl = candidate;
          return decoded;
        }
      } catch (_) {}
    }

    final found = await autoDiscoverServer(force: true);
    if (found != null && found != baseUrl) {
      baseUrl = found;
      try {
        final retryUri = Uri.parse('$baseUrl$endpoint');
        final retryRes = await http.get(
          retryUri,
          headers: {'Accept': 'application/json'},
        ).timeout(const Duration(seconds: 5));

        final retryBody = utf8.decode(retryRes.bodyBytes);
        return jsonDecode(retryBody) as Map<String, dynamic>;
      } catch (_) {}
    }

    return {
      'status': 'error',
      'message': 'Gagal menghubungi server ($baseUrl).'
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

  // 18. Upload Avatar via Uint8List Bytes (universal: Web, Windows, Android, iOS)
  static Future<Map<String, dynamic>> uploadAvatarBytes(
    int userId,
    Uint8List bytes,
    String filename,
  ) async {
    try {
      if (bytes.length > 5 * 1024 * 1024) {
        return {'status': 'error', 'message': 'Ukuran file foto maksimal 5 MB.'};
      }
      final ext = filename.split('.').last.toLowerCase();
      final allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
      if (!allowed.contains(ext)) {
        return {'status': 'error', 'message': 'Format berkas tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.'};
      }
      final mime = (ext == 'jpg' || ext == 'jpeg')
          ? 'image/jpeg'
          : (ext == 'png' ? 'image/png' : (ext == 'webp' ? 'image/webp' : 'image/gif'));
      final base64Str = 'data:$mime;base64,${base64Encode(bytes)}';
      return await uploadAvatarBase64(userId, base64Str);
    } catch (e) {
      return {'status': 'error', 'message': 'Gagal membaca berkas: $e'};
    }
  }

  // 19. Upload Avatar via File Path (khusus platform non-Web)
  static Future<Map<String, dynamic>> uploadAvatarFile(int userId, String filePath) async {
    if (kIsWeb) {
      return {'status': 'error', 'message': 'Unggah via path tidak didukung di Web. Gunakan uploadAvatarBytes.'};
    }
    try {
      final file = io.File(filePath);
      if (!await file.exists()) {
        return {'status': 'error', 'message': 'File tidak ditemukan di jalur: $filePath'};
      }
      final bytes = await file.readAsBytes();
      final filename = filePath.split(RegExp(r'[/\\]')).last;
      return await uploadAvatarBytes(userId, bytes, filename);
    } catch (e) {
      return {'status': 'error', 'message': 'Gagal membaca berkas: $e'};
    }
  }

  // 19. Ambil Daftar Tantangan Studi Kasus Keamanan Siber
  static Future<List<dynamic>> getKasusKeamanan([int? userId]) async {
    final query = (userId != null && userId > 0) ? '?user_id=$userId' : '';
    final res = await get('/kasus-keamanan$query');
    if (res['data'] is Map && res['data']['list'] is List) {
      return List<dynamic>.from(res['data']['list']);
    }
    if (res['data'] is List) {
      return List<dynamic>.from(res['data']);
    }
    return [];
  }

  // 20. Submit Keputusan Studi Kasus Keamanan
  static Future<Map<String, dynamic>> submitKasusKeamanan(
    int kasusId,
    int userId,
    int jawaban,
  ) async {
    return await post('/kasus-keamanan/$kasusId/submit', {
      'user_id': userId,
      'jawaban': jawaban,
    });
  }
}