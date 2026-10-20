<?php
function zte_telnet($host, $user, $pass, $commands, $port=23) {
    $errno = 0;
    $errstr = '';
    $fp = fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) die("Connect failed: {$errstr}\n");
    stream_set_timeout($fp, 2);
    
    // Login
    wait_for($fp, ['Username:', 'login:', '>']);
    fwrite($fp, "{$user}\r\n");
    wait_for($fp, ['Password:']);
    fwrite($fp, "{$pass}\r\n");
    sleep(1);
    read_until_prompt($fp);
    
    fwrite($fp, "terminal length 0\r\n");
    usleep(500000);
    read_until_prompt($fp);
    
    $output = '';
    foreach ($commands as $cmd) {
        fwrite($fp, "{$cmd}\r\n");
        usleep(500000);
        $r = read_until_prompt($fp);
        echo "CMD: {$cmd}\n";
        echo "OUT: {$r}\n";
        echo "---\n";
        $output .= $r;
    }
    fclose($fp);
    return $output;
}

function wait_for($fp, $patterns) {
    $buf = '';
    $deadline = time() + 10;
    while (time() < $deadline) {
        $c = fread($fp, 4096);
        if ($c) $buf .= $c;
        foreach ($patterns as $p) {
            if (stripos($buf, $p) !== false) return $buf;
        }
        usleep(100000);
    }
    return $buf;
}

function read_until_prompt($fp, $timeout=15) {
    $buf = '';
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $chunk = @fread($fp, 4096);
        if ($chunk === false || $chunk === '') { usleep(100000); continue; }
        $buf .= $chunk;
        if (preg_match('/\w+[#>]\s*$/', $buf)) break;
    }
    return $buf;
}

// Test pon-onu-mng with different syntax on existing ONU #2
$cmds = [
    'configure terminal',
    'pon-onu-mng gpon-onu_1/1/1:2',
    '?',
    'exit',
    'exit',
];
zte_telnet('136.1.1.100', 'zte', 'zte', $cmds);
