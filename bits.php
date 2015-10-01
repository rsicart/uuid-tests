#!/usr/bin/env php5
<?php

$epoch = 0;
// uncomment to use custom epoch
//$epoch = strtotime('2010-01-01 00:00:00');


$timestampMillis = (int) ((microtime(true) - $epoch) * 1000);
$datacenterId = 1;
$hostId = 1;
//$pid = getmypid();
$pid = 1;

$size = 64;
$timestampSize = 42;
$datacenterSize = 3;
$hostSize = 4;
$pidSize = 15;

// build bitfield
$bitfield = 0 << ($size);
$bitfield |= $timestampMillis << ($size - $timestampSize);
$bitfield |= $datacenterId << ($size - $timestampSize - $datacenterSize);
$bitfield |= $hostId << ($size - $timestampSize - $datacenterSize - $hostSize);
$bitfield |= $pid << ($size - $timestampSize - $datacenterSize - $hostSize - $pidSize);

printf("%064b\n", $bitfield);
printf("%d\n", $bitfield);

