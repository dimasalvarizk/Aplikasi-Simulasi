<?php
require 'config/database.php';
$db = getDB();

echo "Database driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;

echo "=== GAMIFIKASI TABLE ===" . PHP_EOL;
$stmt = $db->query("SELECT g.*, u.username, u.nama, u.last_login FROM gamifikasi g JOIN users u ON u.id = g.user_id");
foreach ($stmt as $row) {
    echo "UserID: {$row['user_id']} | Username: {$row['username']} | XP: {$row['total_xp']} | Lvl: {$row['level']} | Streak: {$row['streak_days']} | LastAct: {$row['last_activity']} | LastLogin: {$row['last_login']}" . PHP_EOL;
}
