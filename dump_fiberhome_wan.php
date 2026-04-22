<?php
$id = '000AC2-HG6145F-FHTT9B302530';
$enc = rawurlencode($id);
$url = "http://172.10.10.254:7557/devices/?query=" . rawurlencode(json_encode(['_id' => $id]));
$json = file_get_contents($url);
$arr = json_decode($json, true);
if (!$arr || !isset($arr[0])) { echo "device not found\n"; exit; }
$dev = $arr[0];

// Walk and collect all parameter paths under WANConnectionDevice
function walk($node, $prefix, &$out, $maxDepth=20, $depth=0){
    if ($depth > $maxDepth) return;
    if (!is_array($node)) return;
    foreach ($node as $k => $v) {
        if (in_array($k, ['_object','_writable','_timestamp'], true)) continue;
        if ($k === '_value') continue;
        $path = $prefix === '' ? $k : ($prefix . '.' . $k);
        if (is_array($v) && array_key_exists('_value', $v)) {
            $val = $v['_value'];
            if (is_bool($val)) $val = $val?'true':'false';
            $val = is_scalar($val) ? (string)$val : json_encode($val);
            $out[] = $path . ' = ' . $val;
        }
        if (is_array($v)) walk($v, $path, $out, $maxDepth, $depth+1);
    }
}

$out = [];
walk($dev['InternetGatewayDevice']['WANDevice'] ?? [], 'InternetGatewayDevice.WANDevice', $out);

// Filter for VLAN/PPP-related
$vlan = array_filter($out, fn($l)=>stripos($l,'vlan')!==false || stripos($l,'PPP')!==false || stripos($l,'Username')!==false || stripos($l,'WANConnection')!==false || stripos($l,'X_')!==false);

echo "=== WAN VLAN/PPP/X_ params ===\n";
foreach ($vlan as $l) echo $l . "\n";

echo "\n=== ALL WANConnectionDevice paths (just keys, top 200) ===\n";
$keys = array_filter($out, fn($l)=>stripos($l,'WANConnectionDevice')!==false);
foreach (array_slice($keys, 0, 200) as $l) echo $l . "\n";
