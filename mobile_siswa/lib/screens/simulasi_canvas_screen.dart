import 'dart:math';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class DeviceNode {
  final String id;
  final String type;
  final String name;
  final String ipAddress;
  final String subnetMask;
  final String defaultGateway;
  final String macAddress;
  Offset position;

  DeviceNode({
    required this.id,
    required this.type,
    required this.name,
    required this.ipAddress,
    this.subnetMask = '255.255.255.0',
    this.defaultGateway = '192.168.1.1',
    required this.macAddress,
    required this.position,
  });

  String get label {
    switch (type) {
      case 'router': return 'ROUTER';
      case 'switch': return 'SWITCH';
      case 'hub': return 'HUB';
      case 'server': return 'SERVER';
      case 'pc': default: return 'PC';
    }
  }

  bool get isHost => type == 'pc' || type == 'server';

  IconData get iconData {
    switch (type) {
      case 'router': return Icons.router_rounded;
      case 'switch': return Icons.device_hub_rounded;
      case 'hub': return Icons.hub_rounded;
      case 'server': return Icons.dns_rounded;
      case 'pc': default: return Icons.desktop_windows_rounded;
    }
  }

  Color get tagColor {
    switch (type) {
      case 'router': return const Color(0xFFEF4444);
      case 'switch': return const Color(0xFF10B981);
      case 'hub': return const Color(0xFFF59E0B);
      case 'server': return const Color(0xFF8B5CF6);
      case 'pc': default: return const Color(0xFF3B82F6);
    }
  }
}

class CableConnection {
  final String fromId;
  final String toId;

  CableConnection({required this.fromId, required this.toId});
}

class SimulasiCanvasScreen extends StatefulWidget {
  final int challengeId;
  final String judul;
  final String tipeTopologi;
  final String instruksi;
  final int xpReward;

  const SimulasiCanvasScreen({
    Key? key,
    required this.challengeId,
    required this.judul,
    required this.tipeTopologi,
    required this.instruksi,
    required this.xpReward,
  }) : super(key: key);

  @override
  State<SimulasiCanvasScreen> createState() => _SimulasiCanvasScreenState();
}

class _SimulasiCanvasScreenState extends State<SimulasiCanvasScreen> with SingleTickerProviderStateMixin {
  final List<DeviceNode> _devices = [];
  final List<CableConnection> _connections = [];
  int _counter = 0;

  bool _isConnectMode = false;
  String? _connectFirstDeviceId;

  late AnimationController _pingController;
  bool _isPingActive = false;
  List<String>? _activePingPathIds;

  @override
  void initState() {
    super.initState();
    _pingController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _pingController.dispose();
    super.dispose();
  }

  String get _targetGuideline {
    switch (widget.tipeTopologi.toLowerCase()) {
      case 'star':
        return 'Target: 1 Switch/Hub pusat terhubung ke minimal 3 PC (tanpa kabel antar-PC)';
      case 'bus':
        return 'Target: Minimal 4 PC berantai lurus kabel coaxial (DILARANG pakai Switch/Hub/Router)';
      case 'ring':
        return 'Target: Rangkaian melingkar tertutup (setiap node terhubung tepat ke 2 tetangga)';
      case 'mesh':
        return 'Target: Rangkaian redundant multi-jalur (setiap node minimal 2 kabel)';
      case 'tree':
        return 'Target: Hierarki pohon (Switch pusat terhubung ke Switch cabang dan PC)';
      default:
        return 'Target: Rancang topologi sesuai panduan instruksi tantangan';
    }
  }

  void _addDevice(String type) {
    setState(() {
      _counter++;
      final size = MediaQuery.of(context).size;
      final startX = (size.width / 2) + (((_counter % 3) - 1) * 75.0);
      final startY = 160.0 + (((_counter * 55.0) % 220.0));

      String name = '${type.toUpperCase()}-$_counter';

      String ip;
      if (type == 'pc') {
        ip = '192.168.1.${10 + _counter}';
      } else if (type == 'server') {
        ip = '192.168.1.${100 + _counter}';
      } else if (type == 'router') {
        ip = '192.168.1.1';
      } else if (type == 'switch') {
        ip = '192.168.1.${240 + (_counter % 10)}';
      } else {
        ip = 'Layer 1 (Tanpa IP)';
      }

      String hexId = _counter.toRadixString(16).padLeft(2, '0').toUpperCase();
      String mac = '00:1A:2B:3C:4D:$hexId';

      _devices.add(DeviceNode(
        id: 'dev_${DateTime.now().millisecondsSinceEpoch}_$_counter',
        type: type,
        name: name,
        ipAddress: ip,
        macAddress: mac,
        position: Offset(startX, startY),
      ));
    });

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Perangkat ${type.toUpperCase()} ditambahkan. Geser untuk mengatur posisi.'),
        duration: const Duration(milliseconds: 1300),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _removeDevice(String deviceId) {
    setState(() {
      _devices.removeWhere((d) => d.id == deviceId);
      _connections.removeWhere((c) => c.fromId == deviceId || c.toId == deviceId);
      if (_connectFirstDeviceId == deviceId) {
        _connectFirstDeviceId = null;
      }
    });

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Perangkat dan kabel yang terhubung telah dihapus.'),
        duration: Duration(milliseconds: 1000),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _handleNodeTap(DeviceNode node) {
    if (!_isConnectMode) {
      _showDeviceDetails(node);
      return;
    }

    if (_connectFirstDeviceId == null) {
      setState(() {
        _connectFirstDeviceId = node.id;
      });
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('🔌 Node awal: ${node.name}. Sekarang ketuk perangkat tujuan untuk memasang kabel.'),
          duration: const Duration(seconds: 3),
          backgroundColor: const Color(0xFF2563EB),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } else {
      if (_connectFirstDeviceId == node.id) {
        setState(() {
          _connectFirstDeviceId = null;
        });
        return;
      }

      final alreadyConnected = _connections.any((c) =>
          (c.fromId == _connectFirstDeviceId && c.toId == node.id) ||
          (c.fromId == node.id && c.toId == _connectFirstDeviceId));

      if (alreadyConnected) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('⚠️ Kabel ini sudah terpasang antarkedua perangkat.'),
            duration: Duration(seconds: 2),
            behavior: SnackBarBehavior.floating,
          ),
        );
      } else {
        setState(() {
          _connections.add(CableConnection(
            fromId: _connectFirstDeviceId!,
            toId: node.id,
          ));
          _connectFirstDeviceId = null;
        });

        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('✅ Kabel berhasil terhubung ke ${node.name}!'),
            duration: const Duration(milliseconds: 1500),
            backgroundColor: const Color(0xFF10B981),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  void _clearCanvas() {
    showDialog(
      context: context,
      barrierColor: Colors.transparent,
      builder: (ctx) => AlertDialog(
        title: const Text('Bersihkan Kanvas?', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        content: const Text('Semua perangkat dan kabel yang sudah Anda susun akan dihapus dari kanvas.', style: TextStyle(fontSize: 13)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626)),
            onPressed: () {
              Navigator.pop(ctx);
              setState(() {
                _devices.clear();
                _connections.clear();
                _connectFirstDeviceId = null;
                _activePingPathIds = null;
                _counter = 0;
              });
            },
            child: const Text('Ya, Bersihkan', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  // ====================================================================
  // MODAL INFORMASI & KONFIGURASI PERANGKAT
  // ====================================================================
  void _showDeviceDetails(DeviceNode dev) {
    final connectedCables = _connections.where((c) => c.fromId == dev.id || c.toId == dev.id).toList();
    final neighborNames = connectedCables.map((c) {
      final otherId = c.fromId == dev.id ? c.toId : c.fromId;
      final other = _devices.firstWhere((d) => d.id == otherId, orElse: () => dev);
      return other.name;
    }).toList();

    showModalBottomSheet(
      context: context,
      barrierColor: Colors.transparent,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: dev.tagColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: dev.tagColor.withOpacity(0.3)),
                  ),
                  child: Icon(dev.iconData, color: dev.tagColor, size: 28),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(dev.name, style: const TextStyle(color: Color(0xFF0F172A), fontSize: 16, fontWeight: FontWeight.bold)),
                      Text('${dev.label} • ${dev.type.toUpperCase()}', style: TextStyle(color: dev.tagColor, fontSize: 11, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
                IconButton(icon: const Icon(Icons.close, color: Color(0xFF64748B)), onPressed: () => Navigator.pop(ctx)),
              ],
            ),
            const SizedBox(height: 16),
            _buildInfoRow('IPv4 Address', dev.ipAddress),
            _buildInfoRow('Subnet Mask', dev.subnetMask),
            _buildInfoRow('Default Gateway', dev.defaultGateway),
            _buildInfoRow('MAC Address', dev.macAddress),
            _buildInfoRow('Status Port', '${connectedCables.length} Kabel (${neighborNames.isEmpty ? "Belum terpasang kabel" : neighborNames.join(", ")})'),
            const SizedBox(height: 18),
            if (dev.isHost)
              SizedBox(
                width: double.infinity,
                height: 44,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.network_ping, size: 18),
                  label: Text('Mulai Ping dari ${dev.name}'),
                  onPressed: () {
                    Navigator.pop(ctx);
                    _showPingSelectorDialog(dev);
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF64748B), fontSize: 12)),
          Text(value, style: const TextStyle(color: Color(0xFF0F172A), fontSize: 12, fontWeight: FontWeight.w600, fontFamily: 'monospace')),
        ],
      ),
    );
  }

  // ====================================================================
  // PATHFINDING & REALISTIC PING TEST (ICMP SIMULATOR)
  // ====================================================================
  List<String>? _findPath(String startId, String endId) {
    if (startId == endId) return [startId];
    final queue = <List<String>>[[startId]];
    final visited = <String>{startId};

    while (queue.isNotEmpty) {
      final path = queue.removeAt(0);
      final current = path.last;

      final neighbors = <String>[];
      for (var c in _connections) {
        if (c.fromId == current) neighbors.add(c.toId);
        if (c.toId == current) neighbors.add(c.fromId);
      }

      for (var nb in neighbors) {
        if (!visited.contains(nb)) {
          visited.add(nb);
          final newPath = List<String>.from(path)..add(nb);
          if (nb == endId) {
            return newPath;
          }
          queue.add(newPath);
        }
      }
    }
    return null;
  }

  void _showPingSelectorDialog([DeviceNode? preselectedSource]) {
    final hosts = _devices.where((d) => d.type == 'pc' || d.type == 'server').toList();
    if (hosts.length < 2) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Diperlukan minimal 2 unit PC/Server di canvas untuk melakukan pengujian Ping.'),
          backgroundColor: Color(0xFFDC2626),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    String sourceId = preselectedSource != null && hosts.any((h) => h.id == preselectedSource.id)
        ? preselectedSource.id
        : hosts.first.id;
    String targetId = hosts.firstWhere((h) => h.id != sourceId).id;

    showModalBottomSheet(
      context: context,
      barrierColor: Colors.transparent,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final sourceDev = hosts.firstWhere((h) => h.id == sourceId);
          final targetDev = hosts.firstWhere((h) => h.id == targetId);

          return Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.network_ping, color: Color(0xFF2563EB), size: 24),
                    const SizedBox(width: 10),
                    const Expanded(
                      child: Text(
                        'Uji Konektivitas Ping (ICMP)',
                        style: TextStyle(color: Color(0xFF0F172A), fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: Color(0xFF64748B)),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                const Text(
                  'Pilih komputer pengirim dan komputer tujuan untuk menguji transmisi paket ICMP Echo.',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 12),
                ),
                const SizedBox(height: 18),

                // Source Selector
                const Text('1. Komputer Pengirim (Source):', style: TextStyle(color: Color(0xFF2563EB), fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: sourceId,
                      isExpanded: true,
                      dropdownColor: Colors.white,
                      items: hosts.map((d) {
                        return DropdownMenuItem<String>(
                          value: d.id,
                          child: Text('${d.name} (${d.ipAddress})', style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13)),
                        );
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() {
                            sourceId = val;
                            if (targetId == sourceId) {
                              targetId = hosts.firstWhere((h) => h.id != sourceId).id;
                            }
                          });
                        }
                      },
                    ),
                  ),
                ),
                const SizedBox(height: 14),

                // Target Selector
                const Text('2. Komputer Tujuan (Target IP):', style: TextStyle(color: Color(0xFF059669), fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: targetId,
                      isExpanded: true,
                      dropdownColor: Colors.white,
                      items: hosts.where((h) => h.id != sourceId).map((d) {
                        return DropdownMenuItem<String>(
                          value: d.id,
                          child: Text('${d.name} (${d.ipAddress})', style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13)),
                        );
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() {
                            targetId = val;
                          });
                        }
                      },
                    ),
                  ),
                ),
                const SizedBox(height: 22),

                // Execute Button
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.play_arrow_rounded),
                    label: Text('Kirim Ping dari ${sourceDev.name} ➔ ${targetDev.name}'),
                    onPressed: () {
                      Navigator.pop(ctx);
                      _executePingTest(sourceDev, targetDev);
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _executePingTest(DeviceNode source, DeviceNode target) {
    final pathIds = _findPath(source.id, target.id);

    bool isValid = true;
    String diagnosticMsg = '';
    final topologyType = widget.tipeTopologi.toLowerCase().trim();

    // Topology strict rules check during Ping
    if (pathIds == null) {
      isValid = false;
      diagnosticMsg = 'Kabel transmisi terputus! Tidak ditemukan rute fisik yang menyambungkan ${source.name} ke ${target.name}.';
    } else if (topologyType == 'bus') {
      final forbidden = _devices.where((d) => ['switch', 'hub', 'router'].contains(d.type)).toList();
      if (forbidden.isNotEmpty) {
        isValid = false;
        final names = forbidden.map((f) => '${f.name} (${f.label})').join(', ');
        diagnosticMsg = 'Hardware Collision: Ditemukan perangkat terlarang ($names) pada Topologi Bus! Topologi Bus murni berantai kabel coaxial antar-PC tanpa Switch/Hub.';
      } else {
        for (var d in _devices) {
          final deg = _connections.where((c) => c.fromId == d.id || c.toId == d.id).length;
          if (deg > 2) {
            isValid = false;
            diagnosticMsg = 'Bus Impedance Mismatch: Terdeteksi percabangan bintang pada ${d.name} ($deg kabel). Topologi Bus tidak boleh memiliki cabang!';
            break;
          }
        }
      }
    } else if (topologyType == 'star') {
      if (pathIds.length == 2) {
        final devA = _devices.firstWhere((d) => d.id == pathIds[0]);
        final devB = _devices.firstWhere((d) => d.id == pathIds[1]);
        if (devA.isHost && devB.isHost) {
          isValid = false;
          diagnosticMsg = 'Pelanggaran Topologi Star: Paket dikirim melalui kabel langsung antarkomputer (${devA.name} ke ${devB.name}). Seluruh lalu lintas harus melalui Switch pusat!';
        }
      }
    }

    final pathNames = pathIds != null
        ? pathIds.map((id) => _devices.firstWhere((d) => d.id == id).name).toList()
        : <String>[];

    if (isValid) {
      setState(() {
        _isPingActive = true;
        _activePingPathIds = pathIds;
      });

      _pingController.forward(from: 0.0).then((_) {
        if (mounted) {
          setState(() {
            _isPingActive = false;
            _activePingPathIds = null;
          });
          _showCmdPingDialog(
            success: true,
            sourceName: source.name,
            targetName: target.name,
            targetIp: target.ipAddress,
            pathNodes: pathNames,
            diagnosticMessage: 'Koneksi fisik stabil dan transmisi paket ICMP berhasil!',
          );
        }
      });
    } else {
      _showCmdPingDialog(
        success: false,
        sourceName: source.name,
        targetName: target.name,
        targetIp: target.ipAddress,
        pathNodes: pathNames,
        diagnosticMessage: diagnosticMsg,
      );
    }
  }

  void _showCmdPingDialog({
    required bool success,
    required String sourceName,
    required String targetName,
    required String targetIp,
    required List<String> pathNodes,
    required String diagnosticMessage,
  }) {
    showDialog(
      context: context,
      barrierColor: Colors.transparent,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0), width: 1.5),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.08),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Title Bar
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: const BoxDecoration(
                  color: Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(15)),
                  border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.terminal, size: 16, color: Color(0xFF2563EB)),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Text(
                        'Administrator: Command Prompt (Ping Test)',
                        style: TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          fontFamily: 'monospace',
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    InkWell(
                      onTap: () => Navigator.pop(ctx),
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Icon(Icons.close, size: 14, color: Color(0xFF64748B)),
                      ),
                    ),
                  ],
                ),
              ),

              // CMD Body Output
              Container(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'Microsoft Windows [Version 10.0.19045]\n(c) SMPN 1 Karangmoncol Network Simulator.\n',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontFamily: 'monospace'),
                    ),
                    Text(
                      'C:\\Users\\Siswa> ping $targetIp -n 4\n',
                      style: const TextStyle(color: Colors.white, fontSize: 11.5, fontWeight: FontWeight.w700, fontFamily: 'monospace'),
                    ),
                    Text(
                      'Pinging $targetIp with 32 bytes of data:',
                      style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 11, fontFamily: 'monospace'),
                    ),
                    const SizedBox(height: 4),
                    if (success) ...[
                      for (int i = 1; i <= 4; i++)
                        Text(
                          'Reply from $targetIp: bytes=32 time=${1 + (i % 2)}ms TTL=64',
                          style: const TextStyle(color: Color(0xFF34D399), fontSize: 11, fontFamily: 'monospace'),
                        ),
                      const SizedBox(height: 8),
                      Text(
                        'Ping statistics for $targetIp:\n    Packets: Sent = 4, Received = 4, Lost = 0 (0% loss),\nApproximate round trip times in milli-seconds:\n    Minimum = 1ms, Maximum = 2ms, Average = 1ms',
                        style: const TextStyle(color: Color(0xFFE2E8F0), fontSize: 10.5, fontFamily: 'monospace'),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF065F46).withOpacity(0.35),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFF059669)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Icon(Icons.check_circle, size: 14, color: Color(0xFF34D399)),
                                SizedBox(width: 6),
                                Text(
                                  'STATUS: SUKSES (Koneksi Stabil)',
                                  style: TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.bold, fontSize: 11),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Rute Transmisi: ${pathNodes.join(" ➔ ")}',
                              style: const TextStyle(color: Color(0xFFE2E8F0), fontSize: 11, fontFamily: 'monospace'),
                            ),
                          ],
                        ),
                      ),
                    ] else ...[
                      const Text('Request timed out.', style: TextStyle(color: Color(0xFFF87171), fontSize: 11, fontFamily: 'monospace')),
                      const Text('Request timed out.', style: TextStyle(color: Color(0xFFF87171), fontSize: 11, fontFamily: 'monospace')),
                      const Text('Destination host unreachable.', style: TextStyle(color: Color(0xFFF87171), fontSize: 11, fontFamily: 'monospace')),
                      const Text('Request timed out.', style: TextStyle(color: Color(0xFFF87171), fontSize: 11, fontFamily: 'monospace')),
                      const SizedBox(height: 8),
                      Text(
                        'Ping statistics for $targetIp:\n    Packets: Sent = 4, Received = 0, Lost = 4 (100% loss)',
                        style: const TextStyle(color: Color(0xFFE2E8F0), fontSize: 10.5, fontFamily: 'monospace'),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF991B1B).withOpacity(0.3),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFEF4444)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Icon(Icons.cancel, size: 14, color: Color(0xFFF87171)),
                                SizedBox(width: 6),
                                Text(
                                  'STATUS: GAGAL / UNREACHABLE',
                                  style: TextStyle(color: Color(0xFFF87171), fontWeight: FontWeight.bold, fontSize: 11),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              diagnosticMessage,
                              style: const TextStyle(color: Color(0xFFFCA5A5), fontSize: 11),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),

              // Close Terminal Button
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: const BoxDecoration(
                  color: Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.vertical(bottom: Radius.circular(15)),
                  border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
                ),
                alignment: Alignment.centerRight,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Tutup Output'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ====================================================================
  // VALIDASI TOPOLOGI & SUBMISSION
  // ====================================================================
  Future<void> _submitValidation() async {
    if (_devices.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Kanvas masih kosong. Tambahkan perangkat dan hubungkan kabel terlebih dahulu!'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    final canvasData = {
      'devices': _devices.map((d) => {
        'id': d.id,
        'type': d.type,
        'name': d.name,
      }).toList(),
      'connections': _connections.map((c) => {
        'fromId': c.fromId,
        'toId': c.toId,
      }).toList(),
    };

    showDialog(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.transparent,
      builder: (_) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.symmetric(horizontal: 24, vertical: 20),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(color: Color(0xFF0F172A)),
                SizedBox(width: 18),
                Text('Memvalidasi Topologi...', style: TextStyle(fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ),
      ),
    );

    try {
      final res = await ApiService.submitSimulasi(widget.challengeId, globalUser.id, canvasData);
      if (!mounted) return;
      Navigator.pop(context); // Tutup loading

      if (res['status'] == 'success') {
        final data = res['data'];
        final bool isValid = data['valid'] == true || data['is_correct'] == true;
        final int xpEarned = data['xp_earned'] ?? (isValid ? widget.xpReward : 0);
        final String pesan = data['message'] ?? 'Evaluasi selesai';

        if (isValid) {
          globalUser.updateXp(globalUser.totalXp + xpEarned);
          _showValidationDialog(true, '🎉 Arsitektur Topologi Benar!', pesan, xpEarned);
        } else {
          _showValidationDialog(false, '⚠️ Topologi Belum Sesuai', pesan, 0);
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal validasi')),
        );
      }
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Koneksi gagal: $e')),
      );
    }
  }

  void _showValidationDialog(bool valid, String title, String message, int xp) {
    showDialog(
      context: context,
      barrierColor: Colors.transparent,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Icon(
              valid ? Icons.check_circle : Icons.warning_amber_rounded,
              color: valid ? const Color(0xFF10B981) : const Color(0xFFEF4444),
              size: 26,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: valid ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(message, style: const TextStyle(fontSize: 13, color: Color(0xFF334155), height: 1.45)),
            if (valid && xp > 0) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [Color(0xFFD1FAE5), Color(0xFFA7F3D0)]),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFF6EE7B7)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text('⭐', style: TextStyle(fontSize: 16)),
                    const SizedBox(width: 8),
                    Text('+$xp XP Berhasil Diperoleh!',
                        style: const TextStyle(color: Color(0xFF065F46), fontWeight: FontWeight.w800, fontSize: 13)),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: valid ? const Color(0xFF10B981) : const Color(0xFF0F172A),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              if (valid) Navigator.pop(context);
            },
            child: Text(valid ? 'Selesai & Kembali' : 'Coba Perbaiki'),
          ),
        ],
      ),
    );
  }

  void _showInstruksiDialog() {
    showDialog(
      context: context,
      barrierColor: Colors.transparent,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.info_outline, color: Color(0xFF2563EB), size: 22),
            SizedBox(width: 8),
            Expanded(
              child: Text('Panduan Simulasi', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                widget.judul,
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  widget.instruksi.replaceAll('<br>', '\n').replaceAll(RegExp(r'<[^>]*>'), ''),
                  style: const TextStyle(fontSize: 12.5, color: Color(0xFF334155), height: 1.45),
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Langkah Praktik:\n1. Tekan tombol perangkat di bawah (Switch, PC, dll).\n2. Geser perangkat ke posisi yang rapi.\n3. Tekan "Pasang Kabel" untuk menyambungkan perangkat.\n4. Tekan "Tes Ping" untuk memilih pengirim & penerima dan menguji paket ICMP.\n5. Tekan "Validasi Topologi" untuk klaim XP.',
                style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B), height: 1.4),
              ),
            ],
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Mengerti'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0F1D), // Dark Tech Blueprint Canvas
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        elevation: 0,
        titleSpacing: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actionsIconTheme: const IconThemeData(color: Colors.white),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          tooltip: 'Kembali',
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              widget.judul,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Colors.white),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 1),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1E3A8A),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    'Topologi ${widget.tipeTopologi.toUpperCase()}',
                    style: const TextStyle(fontSize: 8.5, fontWeight: FontWeight.w700, color: Color(0xFF93C5FD)),
                  ),
                ),
                const SizedBox(width: 5),
                Text(
                  '⭐ +${widget.xpReward} XP',
                  style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: Color(0xFFFBBF24)),
                ),
              ],
            ),
          ],
        ),
        actions: [
          // Tombol Panduan
          InkWell(
            onTap: _showInstruksiDialog,
            borderRadius: BorderRadius.circular(8),
            child: Container(
              margin: const EdgeInsets.symmetric(vertical: 10),
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFF38BDF8), width: 1.2),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.help_outline_rounded, size: 14, color: Color(0xFF38BDF8)),
                  SizedBox(width: 4),
                  Text(
                    'Panduan',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF38BDF8),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 6),

          // Tombol Bersihkan Kanvas
          InkWell(
            onTap: _clearCanvas,
            borderRadius: BorderRadius.circular(8),
            child: Container(
              margin: const EdgeInsets.symmetric(vertical: 10),
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFEF4444).withOpacity(0.8), width: 1.2),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.delete_sweep_rounded, size: 14, color: Color(0xFFF87171)),
                  SizedBox(width: 4),
                  Text(
                    'Bersihkan',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFFFCA5A5),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 10),
        ],
      ),
      body: Column(
        children: [
          // 1. COLLAPSIBLE TARGET BANNER
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: const BoxDecoration(
              color: Color(0xFF1E293B),
              border: Border(bottom: BorderSide(color: Color(0xFF334155), width: 1.0)),
            ),
            child: Row(
              children: [
                const Icon(Icons.flag_rounded, size: 15, color: Color(0xFF38BDF8)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _targetGuideline,
                    style: const TextStyle(
                      fontSize: 11.5,
                      color: Color(0xFFE2E8F0),
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),

          // 2. CANVAS AREA (Interactive Nodes & Glowing Cables)
          Expanded(
            child: Stack(
              children: [
                // Background Tap Target & Cable Painter
                Positioned.fill(
                  child: GestureDetector(
                    onTap: () {
                      if (_connectFirstDeviceId != null) {
                        setState(() {
                          _connectFirstDeviceId = null;
                        });
                      }
                    },
                    child: CustomPaint(
                      painter: TopologyPainter(
                        devices: _devices,
                        connections: _connections,
                        pingProgress: _pingController.value,
                        isPingActive: _isPingActive,
                        activePathIds: _activePingPathIds,
                      ),
                    ),
                  ),
                ),

                // Device Node Widgets
                for (var dev in _devices) _buildDeviceWidget(dev),

                // Floating Controls Toolbar (Top)
                Positioned(
                  top: 10,
                  left: 12,
                  right: 12,
                  child: Row(
                    children: [
                      // Toggle Pasang Kabel
                      InkWell(
                        onTap: () {
                          setState(() {
                            _isConnectMode = !_isConnectMode;
                            _connectFirstDeviceId = null;
                          });
                        },
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
                          decoration: BoxDecoration(
                            color: _isConnectMode ? const Color(0xFF2563EB) : const Color(0xFF1E293B).withOpacity(0.92),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: _isConnectMode ? const Color(0xFF60A5FA) : const Color(0xFF334155),
                              width: _isConnectMode ? 1.5 : 1.0,
                            ),
                            boxShadow: _isConnectMode
                                ? [
                                    BoxShadow(
                                      color: const Color(0xFF2563EB).withOpacity(0.4),
                                      blurRadius: 10,
                                      offset: const Offset(0, 2),
                                    )
                                  ]
                                : [],
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.cable,
                                size: 15,
                                color: _isConnectMode ? Colors.white : const Color(0xFF94A3B8),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                _isConnectMode ? 'Mode Kabel: ON' : 'Pasang Kabel',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: _isConnectMode ? Colors.white : const Color(0xFFE2E8F0),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),

                      // Button Tes Ping (Interactive ICMP)
                      InkWell(
                        onTap: () => _showPingSelectorDialog(),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
                          decoration: BoxDecoration(
                            color: _isPingActive
                                ? const Color(0xFF059669)
                                : const Color(0xFF1E293B).withOpacity(0.92),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: _isPingActive ? const Color(0xFF34D399) : const Color(0xFF334155),
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.network_ping,
                                size: 15,
                                color: _isPingActive ? Colors.white : const Color(0xFF38BDF8),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                _isPingActive ? 'Menguji...' : 'Tes Ping',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: _isPingActive ? Colors.white : const Color(0xFFE2E8F0),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      const Spacer(),

                      // Node & Cable Counters Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B).withOpacity(0.92),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFF334155)),
                        ),
                        child: Text(
                          '${_devices.length} Node • ${_connections.length} Kabel',
                          style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),

                // Hint Guide Banner when in Cable Mode
                if (_isConnectMode)
                  Positioned(
                    top: 52,
                    left: 12,
                    right: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E3A8A).withOpacity(0.95),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFF3B82F6)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.touch_app, size: 14, color: Color(0xFF93C5FD)),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              _connectFirstDeviceId == null
                                  ? '1. Ketuk perangkat PERTAMA yang ingin dihubungkan.'
                                  : '2. Ketuk perangkat KEDUA untuk memasang kabel koneksi!',
                              style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),

          // 3. BOTTOM DEVICE DOCK & VALIDATION BUTTON
          Container(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              border: const Border(top: BorderSide(color: Color(0xFF1E293B), width: 1.5)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.5),
                  blurRadius: 16,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: SafeArea(
              top: false,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Horizontal list of device buttons
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildDeviceAddButton('Switch', 'switch', Icons.device_hub_rounded, const Color(0xFF10B981)),
                        const SizedBox(width: 8),
                        _buildDeviceAddButton('PC Client', 'pc', Icons.desktop_windows_rounded, const Color(0xFF3B82F6)),
                        const SizedBox(width: 8),
                        _buildDeviceAddButton('Router', 'router', Icons.router_rounded, const Color(0xFFEF4444)),
                        const SizedBox(width: 8),
                        _buildDeviceAddButton('Hub', 'hub', Icons.hub_rounded, const Color(0xFFF59E0B)),
                        const SizedBox(width: 8),
                        _buildDeviceAddButton('Server', 'server', Icons.dns_rounded, const Color(0xFF8B5CF6)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Validation CTA Button
                  SizedBox(
                    width: double.infinity,
                    height: 44,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 3,
                      ),
                      onPressed: _submitValidation,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.check_circle_outline, size: 18),
                          const SizedBox(width: 8),
                          Text(
                            'Validasi Topologi & Klaim +${widget.xpReward} XP',
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                          ),
                        ],
                      ),
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

  // WIDGET DEVICE NODE DENGAN IP ADDRESS, MATERIAL ICON, DRAG GESTURE, DAN TOMBOL HAPUS
  Widget _buildDeviceWidget(DeviceNode dev) {
    final bool isSelected = dev.id == _connectFirstDeviceId;
    const double width = 86;
    const double height = 80;

    return Positioned(
      left: dev.position.dx - (width / 2),
      top: dev.position.dy - (height / 2),
      child: GestureDetector(
        onPanUpdate: (details) {
          setState(() {
            dev.position += details.delta;
          });
        },
        onTap: () => _handleNodeTap(dev),
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            // Card Utama Node
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: width,
              height: height,
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? const Color(0xFF38BDF8) : dev.tagColor.withOpacity(0.85),
                  width: isSelected ? 2.5 : 1.5,
                ),
                boxShadow: [
                  if (isSelected)
                    BoxShadow(
                      color: const Color(0xFF38BDF8).withOpacity(0.55),
                      blurRadius: 16,
                      spreadRadius: 2,
                    )
                  else
                    BoxShadow(
                      color: Colors.black.withOpacity(0.4),
                      blurRadius: 6,
                      offset: const Offset(0, 3),
                    ),
                ],
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Icon Perangkat
                  Icon(
                    dev.iconData,
                    size: 22,
                    color: isSelected ? const Color(0xFF38BDF8) : dev.tagColor,
                  ),
                  const SizedBox(height: 2),
                  // Nama Node
                  Text(
                    dev.name,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      letterSpacing: -0.2,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 2),
                  // IP Address Badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(color: const Color(0xFF334155), width: 0.8),
                    ),
                    child: Text(
                      dev.ipAddress,
                      style: const TextStyle(
                        color: Color(0xFF94A3B8),
                        fontSize: 7.5,
                        fontFamily: 'monospace',
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),

            // Tombol Hapus Cepat (✕) di Pojok Kanan Atas
            Positioned(
              top: -6,
              right: -6,
              child: GestureDetector(
                onTap: () => _removeDevice(dev.id),
                child: Container(
                  width: 20,
                  height: 20,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEF4444),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 1.5),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.35),
                        blurRadius: 4,
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Icon(Icons.close, size: 12, color: Colors.white),
                  ),
                ),
              ),
            ),

            // Badge Node Sumber Terpilih
            if (isSelected)
              Positioned(
                bottom: -16,
                left: 0,
                right: 0,
                child: Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0284C7),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text(
                      'SUMBER',
                      style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.w900),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildDeviceAddButton(String label, String type, IconData icon, Color color) {
    return InkWell(
      onTap: () => _addDevice(type),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.5)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 15, color: color),
            const SizedBox(width: 6),
            Text(
              '+ $label',
              style: const TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// CUSTOM PAINTER: CYBER CANVAS DOTS & GLOWING CABLES
class TopologyPainter extends CustomPainter {
  final List<DeviceNode> devices;
  final List<CableConnection> connections;
  final double pingProgress;
  final bool isPingActive;
  final List<String>? activePathIds;

  TopologyPainter({
    required this.devices,
    required this.connections,
    required this.pingProgress,
    required this.isPingActive,
    this.activePathIds,
  });

  @override
  void paint(Canvas canvas, Size size) {
    // 1. Grid Tech Blueprint Dots
    final dotPaint = Paint()
      ..color = const Color(0xFF38BDF8).withOpacity(0.12)
      ..style = PaintingStyle.fill;

    for (double x = 12; x < size.width; x += 24) {
      for (double y = 12; y < size.height; y += 24) {
        canvas.drawCircle(Offset(x, y), 1.2, dotPaint);
      }
    }

    // 2. Kabel Koneksi Jaringan
    final cablePaint = Paint()
      ..color = const Color(0xFF64748B)
      ..strokeWidth = 2.5
      ..strokeCap = StrokeCap.round;

    final glowCablePaint = Paint()
      ..color = const Color(0xFF38BDF8).withOpacity(0.35)
      ..strokeWidth = 5.0
      ..strokeCap = StrokeCap.round;

    final activePathCablePaint = Paint()
      ..color = const Color(0xFF10B981).withOpacity(0.7)
      ..strokeWidth = 5.5
      ..strokeCap = StrokeCap.round;

    for (var conn in connections) {
      final fromDevList = devices.where((d) => d.id == conn.fromId);
      final toDevList = devices.where((d) => d.id == conn.toId);

      if (fromDevList.isNotEmpty && toDevList.isNotEmpty) {
        final fromDev = fromDevList.first;
        final toDev = toDevList.first;

        if (fromDev != toDev) {
          // Check if this cable is on the active ping path
          bool isOnActivePath = false;
          if (activePathIds != null && activePathIds!.length >= 2) {
            for (int i = 0; i < activePathIds!.length - 1; i++) {
              final a = activePathIds![i];
              final b = activePathIds![i + 1];
              if ((conn.fromId == a && conn.toId == b) || (conn.fromId == b && conn.toId == a)) {
                isOnActivePath = true;
                break;
              }
            }
          }

          if (isOnActivePath) {
            canvas.drawLine(fromDev.position, toDev.position, activePathCablePaint);
          } else {
            canvas.drawLine(fromDev.position, toDev.position, glowCablePaint);
          }
          canvas.drawLine(fromDev.position, toDev.position, cablePaint);

          // Animasi Tes Ping
          if (isPingActive && (isOnActivePath || activePathIds == null)) {
            final packetPos = Offset.lerp(fromDev.position, toDev.position, pingProgress)!;

            final packetGlow = Paint()..color = const Color(0xFF10B981).withOpacity(0.45);
            canvas.drawCircle(packetPos, 8.0, packetGlow);

            final packetCore = Paint()..color = const Color(0xFF34D399);
            canvas.drawCircle(packetPos, 4.0, packetCore);
          }
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant TopologyPainter oldDelegate) {
    return true;
  }
}
