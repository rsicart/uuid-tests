#!/usr/bin/env php5
<?php

// sizes
$timestampSize = 42;
$versionSize = 6;
$hostSize = 20;
$pidSize = 16;
$randomSize = 12;
$size = $timestampSize + $versionSize + $hostSize + $pidSize + $randomSize;

$sizeLeft = $timestampSize + $versionSize;
$sizeRight = $hostSize + $pidSize + $randomSize;

// setup
$version = 17;

$hostId = getenv('ADSP_UUID_HOSTID');
if ($hostId === false) {
    $hostId = rand(0, pow(2, $hostSize)-1);
    putenv("ADSP_UUID_HOSTID=$hostId");
}

$pid = getmypid();

$cacheKey = "seq_$pid";
$ttl = 0;

$ts = (microtime(true) * 1000);
$tsMax = $ts + 1000 * 60 * 60 * 10;

//print("$ts\n");
//print("$tsMax\n");
//print($tsMax-$ts);

while (true) {

    // apc
    /*
    if (apc_exists($cacheKey)) {
        $random = (apc_inc($cacheKey) % (pow(2, $randomSize)));
    } else {
        $random = 1;
        if(apc_store($cacheKey, $random, $ttl) === false) {
            print_r(apc_cache_info());
            throw new Exception("An error ocurred storing key to apc.");
        }
    }
    //var_dump(apc_fetch($cacheKey));
    //var_dump($random);
     */

    // sequence
    $random = (getenv('ADSP_SEQ') % pow(2, $randomSize)) | 0;
    putenv("ADSP_SEQ=" . ($random + 1));

    $timestampMillis = (int) (microtime(true) * 1000);

    //print("left\n");
    //
    // build bitfield left side
    $bitfieldLeft = 0;

    $bitfieldLeft |= $version << ($sizeLeft - $versionSize);
    //printf("%048b\n", $bitfieldLeft);

    $bitfieldLeft |= $timestampMillis;
    //printf("%048b\n", $bitfieldLeft);

    //print("right\n");

    // build bitfield right side
    $bitfieldRight = 0;

    $bitfieldRight |= $hostId << ($sizeRight - $hostSize);
    //printf("%048b\n", $bitfieldRight);

    $bitfieldRight |= $pid << ($sizeRight - $hostSize - $pidSize);
    //printf("%048b\n", $bitfieldRight);

    $bitfieldRight |= $random;
    //printf("%048b\n", $bitfieldRight);

    //print("all bin\n");
    //printf("%048b %048b\n", $bitfieldLeft, $bitfieldRight);
    //print("all hex\n");
    printf("%012x%012x\n", $bitfieldLeft, $bitfieldRight);

    // sleep 1ms or less
    $sleepMicroseconds = 1 * 1000;
    usleep(rand(1, $sleepMicroseconds));

    $ts += 1;
    if ($ts >= $tsMax)
        exit();
}

