<?php
// Quick test optical-ddm command

$host = '172.16.16.4';
$username = 'admin';
$password = 'admin';

$sock = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$sock) die("Connection failed\n");

stream_set_timeout($sock, 10);

// Login fast
$buf = ''; $end = time() + 5;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (stripos($buf, 'login') !== false || stripos($buf, 'username') !== false) break; usleep(100000); }
fwrite($sock, "$username\r\n"); usleep(500000);
$buf = ''; $end = time() + 3;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (stripos($buf, 'password') !== false) break; usleep(100000); }
fwrite($sock, "$password\r\n"); usleep(500000);
$buf = ''; $end = time() + 3;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (strpos($buf, '>') !== false) break; usleep(100000); }
fwrite($sock, "enable\r\n"); usleep(500000);
$buf = ''; $end = time() + 2;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (strpos($buf, '#') !== false) break; usleep(100000); }

echo "Logged in.\n\n";

// Run command - per ONU
fwrite($sock, "show onu optical-ddm epon 0/1 3\r\n");
usleep(500000);

$out = '';
$end = time() + 15;
while (time() < $end) {
    $c = @fread($sock, 4096);
    if ($c) {
        $out .= $c;
        if (strpos($out, '--More--') !== false || strpos($out, '--- Enter Key') !== false) {
            fwrite($sock, " ");
            $out = preg_replace('/--More--|--- Enter Key.*----/', '', $out);
        }
        if (preg_match('/EPON#\s*$/', $out)) break;
    }
    usleep(100000);
}

fwrite($sock, "exit\r\n");
fclose($sock);

echo "=== Raw Output ===\n";
echo str_replace("\r", "", $out);
echo "\n";
