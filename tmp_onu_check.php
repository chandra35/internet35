<?php
// Quick check ONU 16 dan 19
$pdo = new PDO('mysql:host=127.0.0.1;dbname=internet35', 'internet35', 'billing35db');

// Cek ONU data
$stmt = $pdo->query("SELECT o.id, o.name, o.sn, o.olt_id, o.pon_port, o.pppoe_username, o.status FROM onus o WHERE o.id IN (16,19) ORDER BY o.id");
$onus = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== ONU DATA ===\n";
foreach ($onus as $o) {
    echo "ID={$o['id']} name={$o['name']} sn={$o['sn']} olt_id={$o['olt_id']} pon={$o['pon_port']} pppoe={$o['pppoe_username']} status={$o['status']}\n";
}

// Cek wan_configs
$stmt2 = $pdo->query("SELECT w.* FROM wan_configs w WHERE w.onu_id IN (16,19) ORDER BY w.onu_id");
$wans = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== WAN CONFIGS ===\n";
foreach ($wans as $w) {
    echo "onu_id={$w['onu_id']} vlan={$w['vlan_id']} pppoe_user={$w['pppoe_user']} wan_type={$w['wan_type']} path={$w['path']}\n";
}

// Cek layanan
$stmt3 = $pdo->query("SELECT l.id, l.onu_id, l.pppoe_username, l.status, l.paket_id FROM layanans l WHERE l.onu_id IN (16,19) ORDER BY l.onu_id");
$layanans = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== LAYANANS ===\n";
foreach ($layanans as $l) {
    echo "layanan_id={$l['id']} onu_id={$l['onu_id']} pppoe={$l['pppoe_username']} status={$l['status']} paket={$l['paket_id']}\n";
}
