#!/usr/bin/env php5
<?php

$epoch = 0;

$size = 64;
$timestampSize = 42;
$hostSize = 12;
$sequenceSize = 10;
$cacheKey = 'uuid_seq';
$ttl = 0;

// select method
$cacheMethod = 'semaphores';
$cacheMethod = 'memcache';
$cacheMethod = 'apc';


while (true) {
    $timestampMillis = (int) ((microtime(true) - $epoch) * 1000);

    $hostId = getenv('ADSP_UUID_HOSTID');
    if ($hostId === false) {
        $hostId = rand(0, pow(2, $hostSize)-1);
        putenv("ADSP_UUID_HOSTID=$hostId");
    }

    // apc
    if ($cacheMethod == 'apc') {
        if (apc_exists($cacheKey)) {
            $sequence = apc_inc($cacheKey);
        } else {
            $sequence = 1;
            if(apc_store($cacheKey, $sequence, $ttl) === false) {
                print_r(apc_cache_info());
                throw new Exception("An error ocurred storing key to apc.");
            }
            var_dump(apc_fetch($cacheKey));
        }
    } elseif($cacheMethod == 'semaphores') { // shared memory
        $semaphoreId = 100;
        $semaphorePerms = 0600;
        $segmentId = 200;
        $segmentPerms = 0600;
        $segmentBytes = 16;
        $maxAccess = 1;
        $variableId = 1234;

        // when shm_attach() is called for the first time, PHP writes a header to the beginning of the shared memory. 
        $shmHeaderSize = (PHP_INT_SIZE * 4) + 8; 
        // when shm_put_var() is called, the variable is serialized and a small header is placed in front of it before it is written to shared memory. 
        $shmVarSize = (((strlen(serialize(PHP_INT_MAX))+ (4 * PHP_INT_SIZE)) /4 ) * 4 ) + 4; 
        $segmentBytes = $shmHeaderSize + $shmVarSize;

        $sem = sem_get($semaphoreId, $maxAccess, $semaphorePerms);
        if (sem_acquire($sem) === false)
            throw new Exception("An error ocurred acquiring semaphore.");

        $shm = shm_attach($segmentId, $segmentBytes, $segmentPerms);

        if (shm_has_var($shm, $variableId)) {
            $sequence = shm_get_var($shm, $variableId);
            if (shm_put_var($shm, $variableId, ++$sequence) === false)
                throw new Exception("An error ocurred incrementing shared memory variable.");
        } else {
            $sequence = 0;
            if (shm_put_var($shm, $variableId, $sequence) === false)
                throw new Exception("An error ocurred initializing shared memory variable.");
        }
        shm_detach($shm);
        sem_release($sem);

    } else { // memcache
        $cache = new Memcache();
        if ($cache->pconnect('localhost', 12345) === false)
            throw new Exception("An error ocurred connecting to cache.");

        $sequence = $cache->get($cacheKey);
        if ($sequence !== false) {
            $sequence = $cache->increment($cacheKey);
        } else {
            $sequence = 0;
            if($cache->set($cacheKey, $sequence, MEMCACHE_COMPRESSED & 0, $ttl) === false) {
                throw new Exception("An error ocurred storing key to memcache.");
            }
        }
    }

    // build bitfield
    $bitfield = 0 << ($size);
    $bitfield |= $timestampMillis << ($size - $timestampSize);
    $bitfield |= $hostId << ($size - $timestampSize - $hostSize);
    $bitfield |= $sequence << ($size - $timestampSize - $hostSize - $sequenceSize);

    //printf("%064b\n", $bitfield);
    //printf("%d\n", $bitfield);
    printf("%s %d %d\n", $hostId, $sequence, $bitfield);

    // sleep 1ms or less
    $sleepMicroseconds = 1 * 1000;
    usleep(rand(1, $sleepMicroseconds));
}
