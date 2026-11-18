import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class NotifikasiScreen extends StatefulWidget {
  const NotifikasiScreen({Key? key}) : super(key: key);

  @override
  State<NotifikasiScreen> createState() => _NotifikasiScreenState();
}

class _NotifikasiScreenState extends State<NotifikasiScreen> {
  bool _isLoading = true;
  List<dynamic> _notifications = [];
  int _unreadCount = 0;

  @override
  void initState() {
    super.initState();
    _loadNotifikasi();
  }

  Future<void> _loadNotifikasi() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getNotifikasi(globalUser.id);
      if (mounted) {
        setState(() {
          _unreadCount = (res['unread_count'] as num?)?.toInt() ?? 0;
          _notifications = (res['notifications'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _markAllRead() async {
    if (_unreadCount == 0) return;
    setState(() {
      _unreadCount = 0;
      for (var item in _notifications) {
        if (item is Map<String, dynamic>) {
          item['is_read'] = 1;
        }
      }
    });

    try {
      await ApiService.markAllNotifikasiRead(globalUser.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Semua notifikasi ditandai sudah dibaca'),
            duration: Duration(seconds: 2),
            backgroundColor: Color(0xFF0F172A),
          ),
        );
      }
    } catch (_) {}
  }

  Future<void> _onTapNotification(Map<String, dynamic> item) async {
    final int notifId = (item['id'] as num?)?.toInt() ?? 0;
    final int isRead = (item['is_read'] as num?)?.toInt() ?? 0;

    if (isRead == 0 && notifId > 0) {
      setState(() {
        item['is_read'] = 1;
        _unreadCount = (_unreadCount - 1).clamp(0, 999);
      });
      ApiService.markNotifikasiRead(notifId);
    }

    // Tampilkan modal detail pesan
    showModalBottomSheet(
      context: context,
      barrierColor: Colors.transparent,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                _buildLeadingIcon(item['tipe']?.toString() ?? ''),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item['judul']?.toString() ?? 'Pemberitahuan',
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF0F172A),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        item['time_ago']?.toString() ?? '',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Text(
              item['pesan']?.toString() ?? '',
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF334155),
                height: 1.5,
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0F172A),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                onPressed: () => Navigator.pop(ctx),
                child: const Text('Tutup', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLeadingIcon(String tipe) {
    IconData iconData;
    Color bgColor;
    Color iconColor;

    switch (tipe.toLowerCase()) {
      case 'kuis':
        iconData = Icons.assignment_turned_in_rounded;
        bgColor = const Color(0xFFECFDF5);
        iconColor = const Color(0xFF059669);
        break;
      case 'simulasi':
        iconData = Icons.hub_rounded;
        bgColor = const Color(0xFFEFF6FF);
        iconColor = const Color(0xFF2563EB);
        break;
      case 'materi':
        iconData = Icons.menu_book_rounded;
        bgColor = const Color(0xFFEEF2FF);
        iconColor = const Color(0xFF4F46E5);
        break;
      case 'streak':
        iconData = Icons.local_fire_department_rounded;
        bgColor = const Color(0xFFFFF7ED);
        iconColor = const Color(0xFFEA580C);
        break;
      case 'badge':
        iconData = Icons.workspace_premium_rounded;
        bgColor = const Color(0xFFFEF3C7);
        iconColor = const Color(0xFFD97706);
        break;
      default:
        iconData = Icons.notifications_rounded;
        bgColor = const Color(0xFFF1F5F9);
        iconColor = const Color(0xFF475569);
    }

    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Icon(iconData, color: iconColor, size: 20),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Pemberitahuan',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 16,
                color: Color(0xFF0F172A),
              ),
            ),
            if (_unreadCount > 0)
              Text(
                '$_unreadCount belum dibaca',
                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.normal),
              ),
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          if (_notifications.isNotEmpty)
            TextButton.icon(
              onPressed: _unreadCount > 0 ? _markAllRead : null,
              icon: Icon(
                Icons.done_all_rounded,
                size: 16,
                color: _unreadCount > 0 ? const Color(0xFF2563EB) : const Color(0xFF94A3B8),
              ),
              label: Text(
                'Tandai Dibaca',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: _unreadCount > 0 ? const Color(0xFF2563EB) : const Color(0xFF94A3B8),
                ),
              ),
            ),
          const SizedBox(width: 4),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadNotifikasi,
        color: const Color(0xFF0F172A),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(strokeWidth: 2.5))
            : _notifications.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      SizedBox(height: MediaQuery.of(context).size.height * 0.25),
                      Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              width: 64,
                              height: 64,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: const Icon(Icons.notifications_off_outlined, size: 30, color: Color(0xFF94A3B8)),
                            ),
                            const SizedBox(height: 16),
                            const Text(
                              'Belum Ada Pemberitahuan',
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                            const SizedBox(height: 4),
                            const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 40),
                              child: Text(
                                'Aktivitas kuis, simulasi, membaca materi, dan perolehan XP kamu akan muncul di sini.',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    itemCount: _notifications.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final item = _notifications[index] as Map<String, dynamic>;
                      final bool isRead = (item['is_read'] as num?)?.toInt() == 1;

                      return InkWell(
                        onTap: () => _onTapNotification(item),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: isRead ? Colors.white : const Color(0xFFF8FAFF),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isRead ? const Color(0xFFE2E8F0) : const Color(0xFFBFDBFE),
                              width: isRead ? 1 : 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.02),
                                blurRadius: 4,
                                offset: const Offset(0, 1),
                              ),
                            ],
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildLeadingIcon(item['tipe']?.toString() ?? ''),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            item['judul']?.toString() ?? 'Pemberitahuan',
                                            style: TextStyle(
                                              fontWeight: isRead ? FontWeight.w600 : FontWeight.w700,
                                              fontSize: 13,
                                              color: const Color(0xFF0F172A),
                                            ),
                                          ),
                                        ),
                                        if (!isRead)
                                          Container(
                                            width: 7,
                                            height: 7,
                                            margin: const EdgeInsets.only(left: 6),
                                            decoration: const BoxDecoration(
                                              color: Color(0xFF2563EB),
                                              shape: BoxShape.circle,
                                            ),
                                          ),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      item['pesan']?.toString() ?? '',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: isRead ? const Color(0xFF64748B) : const Color(0xFF334155),
                                        height: 1.35,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Row(
                                      children: [
                                        const Icon(Icons.access_time_rounded, size: 11, color: Color(0xFF94A3B8)),
                                        const SizedBox(width: 4),
                                        Text(
                                          item['time_ago']?.toString() ?? '',
                                          style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
      ),
    );
  }
}
