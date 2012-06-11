<?php

require_once('Locker.php');

$Locker = new \Locker\Locker("127.0.0.1", 4545);

// Lock creation
$LockOne = $Locker->createLock('example');

// getting lock
$LockOne->acquire(200, 10000);
// doing very important stuff
echo 'Waiting for 5 seconds..'."\n";
sleep(5);
// releasing lock
$LockOne->release();