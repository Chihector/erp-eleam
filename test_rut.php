<?php
function checkRut($rut) {
    $rut = preg_replace('/[^0-9Kk]/', '', $rut);
    if (strlen($rut) < 8) return false;
    
    $dv = strtoupper(substr($rut, -1));
    $number = substr($rut, 0, -1);
    
    $i = 2;
    $sum = 0;
    foreach(array_reverse(str_split($number)) as $v) {
        if($i == 8) $i = 2;
        $sum += $v * $i;
        $i++;
    }
    
    $res = 11 - ($sum % 11);
    
    if($res == 11) $expectedDv = '0';
    elseif($res == 10) $expectedDv = 'K';
    else $expectedDv = (string)$res;
    
    return $dv === $expectedDv;
}

$validRuts = ['19.123.456-1', '19123456-1', '191234561', '1.234.567-K', '1234567k'];
foreach($validRuts as $r) {
    echo $r . " => " . (checkRut($r) ? "OK" : "FAIL") . "\n";
}
