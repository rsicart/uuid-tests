#!/usr/bin/php5
<?php

$tsMillis = intval(microtime(true)*1000);


echo $tsMillis . "\n";
$a = $tsMillis % 256;
echo $a . "\n";
echo dechex($a) . "\n";
echo dechex($tsMillis) . "\n";


$sb = $tsMillis & 0xFF;
echo dechex($sb) . "\n";
