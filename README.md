php-locker - php client for [locker](https://github.com/bobrik/locker) lock server
===========================

Allows to lock common resources across servers with sub-second precision in php.

## Installation

Check out [locker server](https://github.com/bobrik/locker) page for server installation instructions.

Just copy `Locker.php`, `Lock.php` and `Exceptions.php` anywhere in your project.

## Usage

Look at `example/example.php`:

```php
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
```

## API

* Requiring:

    ```php
    require_once('/path/to/your/project/Locker.php');
    ```

* New connection:

    ```php
    $Locker = new \Locker\Locker($host, $port = 4545);
    ```

* Lock creation:

    ```php
    $Lock = $Locker->createLock($name);
    ```

* Acquiring lock:

    ```php
    $Lock->acquire($wait, $timeout);
    ```

    * `$wait` - max time to wait for lock (in milliseconds).
    * `$timeout` - max work time before `release` call or auto-release by timeout (in milliseconds).

* Releasing lock:

    ```php
    $Lock->release($panic = false);
    ```

If `$panic = true` then `LockerLostLockException` will be fired if time between `acquire` and `release` more than `$timeout`.

For more information look at source files, phpdoc included.
