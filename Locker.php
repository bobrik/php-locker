<?php

namespace Locker;

require_once('./Lock.php');
require_once('./Exceptions.php');


/**
 * Class to represent connection to locker server.
 *
 * @author Ian Babrou <ibobrik@gmail.com>
 * @class
 */
class Locker {

    /**
     * Reply size from locker server in bytes.
     */
	const REPLY_SIZE = 6;

    /**
     * Action constant to lock.
     */
	const ACTION_LOCK = 1;

    /**
     * Action constant to unlock.
     */
	const ACTION_UNLOCK = 0;

    /**
     * Locker server hostname.
     *
     * @var string
     */
	private $host;

    /**
     * Locker server port.
     *
     * @var int
     */
	private $port;

    /**
     * Socket resource with locker server connection.
     *
     * @var resource
     */
	private $connection;

    /**
     * Last sequence number.
     *
     * @var int
     */
	private $sequence = 0;

    /**
     * Array of currently registered locks.
     *
     * @var Lock[]
     */
	private $locks = array();

    /**
     * Locker server connection constructor.
     *
     * @param string $host Locker server host.
     * @param int $port Locker server port.
     */
	public function __construct($host, $port = 4545) {
		$this->host = $host;
		$this->port = $port;
	}

    /**
     * Return next lock sequence number.
     *
     * @return int
     */
	protected function getNextSequence() {
		return ++$this->sequence;
	}

    /**
     * Return socket resource with locker server connection.
     *
     * @return resource
     */
	protected function getConnection() {
		if (!$this->connection) {
			$this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			socket_connect($this->connection, $this->host, $this->port);
		}

		return $this->connection;
	}

    /**
     * Create and register lock with given name.
     *
     * @param string $name Name of the lock.
     * @return Lock
     */
	public function createLock($name) {
		$sequence = $this->getNextSequence();
		$Lock     = new Lock($this, $name, $sequence);

        $this->locks[$sequence] = $Lock;

		return $Lock;
	}

    /**
     * Reset all acquired locks and locker connection.
     */
	protected function reset() {
		$this->connection = null;
		
		foreach ($this->locks as $sequence => $Lock) {
			if (!$Lock->getSequence()) {
				$Lock->reset();
			}
		}
	}

    /**
     * Perform generic locker server request and return reply success state.
     *
     * @param string $name Name of the lock to request (only for lock request).
     * @param int $sequence Sequence number.
     * @param int $action Action type (see ACTION_* constants).
     * @param int $wait Lock wait time in milliseconds (only for lock request).
     * @param int $timeout Lock timeout in milliseconds (only for lock request).
     * @return bool
     * @throws LockerInvalidActionException
     * @throws LockerInvalidSequenceException
     * @throws LockerException
     */
	protected function request($name, $sequence, $action, $wait, $timeout) {
		$connection = $this->getConnection();

		$length = strlen($name);
		$buffer = pack('CVVVC', $length, $sequence, $wait, $timeout, $action).$name;
		if (socket_write($connection, $buffer) === false) {
			$code = socket_last_error($connection);
			
			$this->reset();

			throw new LockerException('Writing to locker failed with code ' . $code);
		}

		$data  = socket_read($this->connection, self::REPLY_SIZE, PHP_BINARY_READ);
		$reply = unpack('Vsequence/Caction/Cresult', $data);

		if ($reply['sequence'] != $sequence) {
			$this->reset();
			throw new LockerInvalidSequenceException('Requested lock with sequence ' . $sequence .
				                                     ', received reply with ' . $reply['sequence']);
		}

		if ($reply['action'] != $action) {
			$this->reset();
			throw new LockerInvalidActionException('Requested action = ' . $action . ')' .
									               ', received action = ' . $data['action']);
		}

		return !!$reply['result'];
	}

    /**
     * Acquire lock and return success state,
     * to be performed from Lock::acquire.
     *
     * @param string $name Name of the lock to acquire.
     * @param int $sequence Sequence number.
     * @param int $wait Lock wait time in milliseconds.
     * @param int $timeout Lock timeout in milliseconds.
     * @return bool
     */
	public function lock($name, $sequence, $wait, $timeout) {
		return $this->request($name, $sequence, self::ACTION_LOCK, $wait, $timeout);
	}

    /**
     * Release lock by its sequence number and return success state,
     * to performed from Lock::release.
     *
     * @param int $sequence
     * @return bool
     */
	public function unlock($sequence) {
		return $this->request("", $sequence, 0, 0, self::ACTION_UNLOCK, 0, 0);
	}

    /**
     * Unregister Lock from Locker,
     * performed from Lock destructor.
     *
     * @param Lock $Lock Lock to unregister.
     */
	public function unregister(Lock $Lock) {
		unset($this->locks[$Lock->getSequence()]);
	}

}



