<?php
$id = '000AC2-HG6145F-FHTT9B302530';
$url = "http://172.10.10.254:7557/devices/?query=" . rawurlencode(json_encode(['_id' => $id]));
$arr = json_decode(file_get_contents($url), true);
$dev = $arr[0];

function walk($node, $prefix, &$out){
    if (!is_array($node)) return;
    foreach ($node as $k => $v) {
        if (in_array($k, ['_object','_writable','_timestamp','_value'], true)) continue;
        $path = $prefix === '' ? $k : ($prefix . '.' . $k);
        if (is_array($v) && array_key_exists('_value', $v)) {
            $val = $v['_value'];
            if (is_bool($val)) $val = $val?'true':'false';
            $out[] = $path . ' = ' . (is_scalar($val) ? (string)$val : json_encode($val));
        }
        if (is_array($v)) walk($v, $path, $out);
    }
}

$out = [];
walk($dev, '', $out);

// Filter X_FH_, X_CT-, ServiceList, VLAN, ConnectionMode, Mode
$pat = '/(X_FH_|X_CT-|X_CMCC|X_CU_|VLAN|VlanID|VID|ServiceList|ServiceMode|ConnectionMode|\.Mode|WANConnectionDevice\.[0-9]+\.WAN(IP|PPP)Connection\.[0-9]+\.(Name|Enable|ConnectionType|NATEnabled|Username|Password))/i';
$hits = array_filter($out, fn($l)=>preg_match($pat, $l));
foreach ($hits as $l) echo $l . "\n";

echo "\n=== Top-level X_ keys count ===\n";
$xkeys = [];
foreach ($out as $l){ if (preg_match('/(X_[A-Z]+_[A-Za-z0-9_]+)/', $l, $m)) $xkeys[$m[1]] = ($xkeys[$m[1]] ?? 0)+1; }
arsort($xkeys); foreach (array_slice($xkeys,0,40,true) as $k=>$c) echo "$k = $c\n";
