<?php
$names = ['Pon-Nni1','Pon-Nni2','Pon-Nni3','Pon-Nni4','G1','G2','G3','G4'];

echo "Testing regex: /pon.*?(\\d+)\$/i\n\n";

foreach($names as $n) { 
    if(preg_match('/pon.*?(\d+)$/i', $n, $m)) { 
        echo "$n -> PON port {$m[1]}\n"; 
    } else { 
        echo "$n -> skip (not PON)\n"; 
    } 
}
