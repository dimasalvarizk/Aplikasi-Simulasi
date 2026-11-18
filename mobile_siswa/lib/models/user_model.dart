import 'package:flutter/foundation.dart';

class UserModel extends ChangeNotifier {
  int id = 0;
  String nama = '';
  String username = '';
  String email = '';
  String kelas = 'VII';
  String nisn = '';
  int totalXp = 0;
  int level = 1;
  String levelTitle = 'Pemula Jaringan';
  int streakDays = 1;
  String avatar = '';
  String token = '';

  bool get isLoggedIn => id > 0;

  void setFromApi(Map<String, dynamic> data, String authToken) {
    id = data['id'] ?? 0;
    nama = data['nama'] ?? '';
    username = data['username'] ?? '';
    email = data['email'] ?? '';
    kelas = (data['kelas'] != null && data['kelas'].toString().isNotEmpty) ? data['kelas'].toString() : 'VII';
    nisn = data['nisn'] ?? '';
    totalXp = data['total_xp'] ?? 0;
    level = data['level'] ?? 1;
    levelTitle = data['level_title'] ?? 'Pemula';
    streakDays = data['streak_days'] ?? 1;
    avatar = (data['avatar_url'] != null && data['avatar_url'].toString().isNotEmpty)
        ? data['avatar_url'].toString()
        : (data['avatar'] != null ? data['avatar'].toString() : '');
    token = authToken;
    notifyListeners();
  }

  void updateAvatar(String newAvatar) {
    avatar = newAvatar;
    notifyListeners();
  }

  void updateXp(int newXp) {
    totalXp = newXp;
    level = (totalXp / 100).floor() + 1;
    notifyListeners();
  }

  void logout() {
    id = 0;
    nama = '';
    username = '';
    token = '';
    notifyListeners();
  }
}

// Global instance sederhana
final globalUser = UserModel();
