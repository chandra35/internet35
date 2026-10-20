<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
$fp = @fsockopen($olt->ip_address, 23, $errno, $errstr, 10);
if (!$fp) die('fail');
stream_set_timeout($fp, 5);

$all = '';
$deadline = time() + 10;
while (time() < $deadline) {
    $c = @fread($fp, 4096);
    if ($c) $all .= $c;
    if (stripos($all, 'Username:') !== false) break;
    usleep(100000);
}
fwrite($fp, $olt->telnet_username . "\r\n");
$all = '';
$deadline = time() + 10;
while (time() < $deadline) {
    $c = @fread($fp, 4096);
    if ($c) $all .= $c;
    if (stripos($all, 'Password:') !== false) break;
    usleep(100000);
}
fwrite($fp, $olt->telnet_password . "\r\n");
sleep(1);

function rp($fp, $t = 15) {
    $b = ''; $d = time() + $t;
    while (time() < $d) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $b .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $b)) break;
    }
    return $b;
}

rp($fp);
foreach (['enable', 'terminal length 0', 'configure terminal'] as $cmd) {
    fwrite($fp, $cmd . "\r\n");
    usleep(300000);
    rp($fp);
}

// Enter pon-onu-mng context for specific ONU, then show running
fwrite($fp, "pon-onu-mng gpon-onu_1/1/4:2\r\n");
usleep(500000);
$r = rp($fp);

// In pon-onu-mng context, "show running-config" should show only THIS ONU
fwrite($fp, "show running-config\r\n");
usleep(500000);

// Read with a larger timeout and look for the end marker
$buf = '';
$deadline = time() + 20;
while (time() < $deadline) {
    $c = @fread($fp, 8192);
    if ($c !== false && $c !== '') {
        $buf .= $c;
    }
    // Stop when we see the closing "!" and then a prompt
    if (preg_match('/!\s*\r?\n[\w(][^\r\n]*[#>]\s*$/', $buf)) break;
    // Also stop on just prompt after end
    if (preg_match('/^end\s*\r?\n[\w(][^\r\n]*[#>]\s*$/m', $buf)) break;
    if (preg_match('/[\w)][#>]\s*$/', $buf) && strlen($buf) > 200) break;
    usleep(150000);
}

echo "=== SmartOLT 1/1/4:2 PON-ONU-MNG ===\n";
echo trim($buf) . "\n";

fwrite($fp, "exit\r\n");
usleep(300000);
rp($fp);
fclose($fp);
