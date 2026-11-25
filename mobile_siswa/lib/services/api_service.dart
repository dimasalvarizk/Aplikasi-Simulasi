import 'dart:async';
import 'dart:convert';
import 'dart:io' as io;
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

class ApiService {
  /// URL Hosting Online Resmi
  static const String onlineBaseUrl = 'https://smpn1karmon.ct.ws/api';

  /// URL Server Aktif (Default: Hosting Online, otomatis fallback ke Localhost XAMPP)
  static String baseUrl = onlineBaseUrl;
  static bool _isDiscovering = false;
  static Completer<String?>? _discoveryCompleter;

  /// Daftar prioritas server lokal (XAMPP / Localhost / USB / Emulator)
  static final List<String> _localPriorityCandidates = [
    'http://localhost/aplikasi-simulasi/api',
    'http://127.0.0.1/aplikasi-simulasi/api',
    'http://192.168.1.8/aplikasi-simulasi/api',
    'http://10.0.2.2/aplikasi-simulasi/api', // Android Emulator
    'http://localhost:8080/aplikasi-simulasi/api',
    'http://127.0.0.1:8080/aplikasi-simulasi/api',
    'http://192.168.43.1/aplikasi-simulasi/api',
    'http://192.168.18.6/aplikasi-simulasi/api',
    'http://10.34.197.13/aplikasi-simulasi/api',
  ];

  static bool isOnlineUrl(String url) {
    final lower = url.toLowerCase();
    return lower.startsWith('https://') ||
        (lower.contains('.') &&
         !lower.contains('192.168.') &&
         !lower.contains('10.') &&
         !lower.contains('127.0.0.1') &&
         !lower.contains('localhost'));
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

  /// Headers bawaan untuk semua request HTTP agar kompatibel dengan hosting online maupun lokal
  static const String _defaultUserAgent = 'Googlebot/2.1 (+http://www.google.com/bot.html)';

  static Map<String, String> _buildHeaders({bool isJson = false}) {
    final Map<String, String> headers = {
      'Accept': 'application/json',
      'User-Agent': _defaultUserAgent,
    };
    if (isJson) {
      headers['Content-Type'] = 'application/json; charset=UTF-8';
    }
    return headers;
  }

  /// Tes apakah suatu URL server merespon API dengan status 200
  static Future<bool> testServer(String candidateUrl, {int timeoutMs = 2500}) async {
    try {
      final clean = sanitizeUrl(candidateUrl);
      final uri = Uri.parse('$clean/');
      final res = await http.get(
        uri,
        headers: _buildHeaders(),
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

  /// Pindai dan temukan server API secara otomatis (Dual-Mode: Hosting Online & Localhost XAMPP)
  static Future<String?> autoDiscoverServer({bool force = false}) async {
    if (_isDiscovering && _discoveryCompleter != null) {
      return _discoveryCompleter!.future;
    }

    // Jika baseUrl saat ini sudah merespon dan tidak dipaksa re-scan, gunakan langsung
    if (!force) {
      final isCurrentOk = await testServer(baseUrl, timeoutMs: 1500);
      if (isCurrentOk) return baseUrl;
    }

    _isDiscovering = true;
    _discoveryCompleter = Completer<String?>();

    try {
      // 1. Cek Server Online Hosting dan Server Lokal Prioritas secara paralel cepat
      final List<String> fastCandidates = [
        onlineBaseUrl,
        ..._localPriorityCandidates.map(sanitizeUrl),
      ];

      final fastCompleter = Completer<String?>();
      int pending = fastCandidates.length;

      for (final candidate in fastCandidates) {
        testServer(candidate, timeoutMs: 1800).then((ok) {
          if (ok && !fastCompleter.isCompleted) {
            fastCompleter.complete(candidate);
          } else {
            pending--;
            if (pending <= 0 && !fastCompleter.isCompleted) {
              fastCompleter.complete(null);
            }
          }
        }).catchError((_) {
          pending--;
          if (pending <= 0 && !fastCompleter.isCompleted) {
            fastCompleter.complete(null);
          }
        });
      }

      final fastFound = await fastCompleter.future.timeout(
        const Duration(seconds: 2, milliseconds: 200),
        onTimeout: () => null,
      );

      if (fastFound != null) {
        baseUrl = fastFound;
        _isDiscovering = false;
        _discoveryCompleter?.complete(fastFound);
        return fastFound;
      }

      // 2. Jika fast candidates belum merespon, lakukan scan luas pada interface dan subnet Wi-Fi
      final allCandidates = await getSmartCandidateUrls();
      final remaining = allCandidates.where((u) => !fastCandidates.contains(u)).toList();

      final broadCompleter = Completer<String?>();
      int bPending = remaining.length;

      for (final rUrl in remaining) {
        testServer(rUrl, timeoutMs: 1200).then((ok) {
          if (ok && !broadCompleter.isCompleted) {
            broadCompleter.complete(rUrl);
          } else {
            bPending--;
            if (bPending <= 0 && !broadCompleter.isCompleted) {
              broadCompleter.complete(null);
            }
          }
        }).catchError((_) {
          bPending--;
          if (bPending <= 0 && !broadCompleter.isCompleted) {
            broadCompleter.complete(null);
          }
        });
      }

      final broadFound = await broadCompleter.future.timeout(
        const Duration(seconds: 2),
        onTimeout: () => null,
      );

      if (broadFound != null) {
        baseUrl = broadFound;
        _isDiscovering = false;
        _discoveryCompleter?.complete(broadFound);
        return broadFound;
      }
    } catch (_) {}

    _isDiscovering = false;
    _discoveryCompleter?.complete(null);
    return null;
  }

  // Helper HTTP POST dengan auto-fallback dan dual-mode otomatis (Hosting Online <-> Localhost XAMPP)
  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> body) async {
    baseUrl = sanitizeUrl(baseUrl);
    final String currentBase = baseUrl;
    final bool currentIsOnline = isOnlineUrl(currentBase);

    // 1. Coba server utama yang sedang aktif terlebih dahulu
    try {
      final uri = Uri.parse('$currentBase$endpoint');
      final response = await http.post(
        uri,
        headers: _buildHeaders(isJson: true),
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 7));

      if (response.statusCode == 200) {
        final responseBody = utf8.decode(response.bodyBytes);
        final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
        baseUrl = currentBase; // Server aktif terverifikasi!
        return decoded;
      }
    } catch (_) {}

    // 2. Jika server utama gagal, otomatis beralih ke server alternatif (Dual-Mode: Online <-> Localhost)
    final List<String> fallbackServers = [];
    if (currentIsOnline) {
      // Jika hosting online gagal/offline, otomatis coba server localhost laptop
      fallbackServers.addAll(_localPriorityCandidates);
    } else {
      // Jika localhost laptop mati/tidak dijangkau, otomatis coba server hosting online
      fallbackServers.add(onlineBaseUrl);
      fallbackServers.addAll(getAlternateFolderUrls(currentBase));
    }

    for (final fallback in fallbackServers) {
      final cleanFallback = sanitizeUrl(fallback);
      if (cleanFallback == currentBase) continue;

      try {
        final uri = Uri.parse('$cleanFallback$endpoint');
        final response = await http.post(
          uri,
          headers: _buildHeaders(isJson: true),
          body: jsonEncode(body),
        ).timeout(const Duration(seconds: 4));

        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
          baseUrl = cleanFallback; // Sukses beralih server otomatis!
          return decoded;
        }
      } catch (_) {}
    }

    // 3. Jika fallback cepat belum berhasil, jalankan auto-discovery penuh
    final found = await autoDiscoverServer(force: true);
    if (found != null && found != currentBase) {
      baseUrl = found;
      try {
        final retryUri = Uri.parse('$baseUrl$endpoint');
        final retryRes = await http.post(
          retryUri,
          headers: _buildHeaders(isJson: true),
          body: jsonEncode(body),
        ).timeout(const Duration(seconds: 6));

        final retryBody = utf8.decode(retryRes.bodyBytes);
        return jsonDecode(retryBody) as Map<String, dynamic>;
      } catch (_) {}
    }

    return {
      'status': 'error',
      'message': 'Gagal menghubungi server (Hosting maupun Localhost). Periksa koneksi internet atau pastikan XAMPP laptop menyala!'
    };
  }

  // Helper HTTP GET dengan auto-fallback dan dual-mode otomatis (Hosting Online <-> Localhost XAMPP)
  static Future<Map<String, dynamic>> get(String endpoint) async {
    baseUrl = sanitizeUrl(baseUrl);
    final String currentBase = baseUrl;
    final bool currentIsOnline = isOnlineUrl(currentBase);

    // 1. Coba server utama yang sedang aktif terlebih dahulu
    try {
      final uri = Uri.parse('$currentBase$endpoint');
      final response = await http.get(
        uri,
        headers: _buildHeaders(),
      ).timeout(const Duration(seconds: 7));

      if (response.statusCode == 200) {
        final responseBody = utf8.decode(response.bodyBytes);
        final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
        baseUrl = currentBase;
        return decoded;
      }
    } catch (_) {}

    // 2. Jika server utama gagal, otomatis beralih ke server alternatif (Dual-Mode: Online <-> Localhost)
    final List<String> fallbackServers = [];
    if (currentIsOnline) {
      fallbackServers.addAll(_localPriorityCandidates);
    } else {
      fallbackServers.add(onlineBaseUrl);
      fallbackServers.addAll(getAlternateFolderUrls(currentBase));
    }

    for (final fallback in fallbackServers) {
      final cleanFallback = sanitizeUrl(fallback);
      if (cleanFallback == currentBase) continue;

      try {
        final uri = Uri.parse('$cleanFallback$endpoint');
        final response = await http.get(
          uri,
          headers: _buildHeaders(),
        ).timeout(const Duration(seconds: 4));

        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final decoded = jsonDecode(responseBody) as Map<String, dynamic>;
          baseUrl = cleanFallback;
          return decoded;
        }
      } catch (_) {}
    }

    // 3. Jika fallback cepat belum berhasil, jalankan auto-discovery penuh
    final found = await autoDiscoverServer(force: true);
    if (found != null && found != currentBase) {
      baseUrl = found;
      try {
        final retryUri = Uri.parse('$baseUrl$endpoint');
        final retryRes = await http.get(
          retryUri,
          headers: _buildHeaders(),
        ).timeout(const Duration(seconds: 6));

        final retryBody = utf8.decode(retryRes.bodyBytes);
        return jsonDecode(retryBody) as Map<String, dynamic>;
      } catch (_) {}
    }

    return {
      'status': 'error',
      'message': 'Gagal memuat data dari server (Hosting maupun Localhost).'
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