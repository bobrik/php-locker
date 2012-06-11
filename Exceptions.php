<?php

class LockerException extends \RuntimeException {}

class LockerInvalidSequenceException extends LockerException {}

class LockerInvalidActionException extends LockerException {}

class LockerUnlockWithoutLockException extends LockerException {}

class LockerLostLockException extends LockerException {}

class LockerWaitTimeoutException extends LockerException {}

class LockerLockReuseException extends LockerException {}
