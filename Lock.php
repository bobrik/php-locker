<?php

namespace Locker;

/**
 * Class to represent lock.
 *
 * @author Ian Babrou <ibobrik@gmail.com>
 * @class
 */
class Lock {

    /**
     * Locker which this lock belongs to.
     *
     * @var Locker
     */
    private $Locker;

    /**
     * Resource name to lock.
     *
     * @var string
     */
    private $name;

    /**
     * Sequence number from Locker.
     *
     * @var int
     */
    private $sequence;

    /**
     * Flag of being locked.
     *
     * @var bool
     */
    private $acquired = false;

    /**
     * Constructor, must be only called from Locker::createLock.
     *
     * @param Locker $Locker Locker which this lock belongs to.
     * @param string $name Resource name to lock.
     * @param int $sequence Sequence number from Locker.
     */
    public function __construct(Locker $Locker, $name, $sequence) {
        $this->Locker   = $Locker;
        $this->name     = $name;
        $this->sequence = $sequence;
    }

    /**
     * Acquire lock with wait timeout and work timeout.
     *
     * @param int $wait Time to wait for lock in milliseconds.
     * @param int $timeout Time to acquire lock for in milliseconds.
     * @throws LockerLockReuseException
     * @throws LockerWaitTimeoutException
     */
    public function acquire($wait, $timeout) {
        if (!$this->sequence) {
            throw new LockerLockReuseException('Trying to reuse lock ' . $this->name);
        }

        $result = $this->Locker->lock($this->name, $this->sequence, $wait, $timeout);
        if (!$result) {
            throw new LockerWaitTimeoutException('Wait timeout exceed for lock ' . $this->name);
        }

        $this->acquired = true;
    }

    /**
     * Release lock. Released lock should not be reused again.
     *
     * @param bool $panic Flag to throw exception if lock has been lost.
     * @throws LockerLostLockException
     * @throws LockerUnlockWithoutLockException
     */
    public function release($panic = false) {
        if (!$this->isAcquired()) {
            throw new LockerUnlockWithoutLockException('Trying to unlock lock ' . $this->name .
                ' without lock');
        }

        $result = $this->Locker->unlock($this->sequence);
        $this->reset();

        if ($panic && !$result) {
            throw new LockerLostLockException('Lost lock ' . $this->name);
        }
    }

    /**
     * Destructor to release lock on object destruction
     * and unregister it from Locker.
     */
    public function __destruct() {
        if ($this->isAcquired()) {
            $this->release();
        } else {
            if ($this->getSequence()) {
                $this->reset();
            }
        }
    }

    /**
     * Reset lock to make it invalid if locker connection
     * is broken or after release.
     */
    public function reset() {
        if ($this->sequence) {
            $this->Locker->unregister($this);
        }

        $this->sequence = false;
        $this->acquired = false;
    }

    /**
     * Determine is lock acquired or not.
     *
     * @return bool
     */
    public function isAcquired() {
        return $this->acquired;
    }

    /**
     * Return sequence number from Locker.
     *
     * @return int
     */
    public function getSequence() {
        return $this->sequence;
    }

}