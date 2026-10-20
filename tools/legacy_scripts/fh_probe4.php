<?php
echo "=== xhr.js ===\n";
echo file_get_contents('/tmp/fh_xhr.js');
echo "\n\n=== aes.js (fhencrypt area) ===\n";
$aes = file_get_contents('/tmp/fh_aes.js');
foreach (preg_split('/\r?\n/', $aes) as $i=>$l) {
    if (preg_match('/fhencrypt|fhdecrypt|getKey|key|iv|aes|mode|ecb|cbc/i', $l)) {
        echo sprintf("%4d: %s\n", $i+1, trim($l));
    }
}
echo "\n\n=== util_functions.js (search getDataByAjax, sessionid, get_operator_test) ===\n";
$uf = file_get_contents('/tmp/fh_util_functions.js');
foreach (preg_split('/\r?\n/', $uf) as $i=>$l) {
    if (preg_match('/getDataByAjax|sessionid|sessionidstr|get_operator_test|do_login|fhencrypt|getRand|encrypt/i', $l)) {
        echo sprintf("%4d: %s\n", $i+1, trim($l));
    }
}
