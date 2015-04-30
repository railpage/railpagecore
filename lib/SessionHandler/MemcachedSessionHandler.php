<?php
	/**
	 * MemcachedSessionHandler session handler using SessionHandlerInterface
	 * Based on code from aequasi/memcached-bundle
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\SessionHandler;
	
	use SessionHandlerInterface;
	use Exception;
	use Memcached as Memcached;
	use Railpage\AppCore;
	
	/**
	 * MemcachedSessionHandler session handler
	 */
	
	class MemcachedSessionHandler implements SessionHandlerInterface {
		
		/**
		 * @var \Memcached $Memcached Memcached driver.
		 * @since Version 3.9.1
		 */
		
		private $Memcached;
		
		/**
		 * @var int $ttl Time to live in seconds
		 * @since Version 3.9.1
		 */
		
		private $ttl;
		
		/**
		 * @var string $prefix Key prefix for shared environments.
		 * @since Version 3.9.1
		 */
		
		private $prefix;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param \Memcached $Memcached
		 * @param array $options
		 */
		
		public function __construct(Memcached $Memcached, array $options = array()) {
			$this->Memcached = $Memcached;
			
			if ($diff = array_diff(array_keys($options), array('prefix', 'expiretime'))) {
				throw new \InvalidArgumentException(sprintf(
					'The following options are not supported "%s"',
					implode(', ', $diff)
				));
			}
			$this->ttl    = isset($options['expiretime']) ? (int)$options['expiretime'] : 86400;
			$this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s-';
		}
		
		public function open($savePath, $sessionName) {
			return true;
		}
		
		public function close() {
			return true;
		}
		
		public function read($sessionId) {
			return $this->Memcached->get($this->prefix . $sessionId) ? : '';
		}
		
		public function write($sessionId, $data) {
			return $this->Memcached->set($this->prefix . $sessionId, $data, time() + $this->ttl);
		}
		
		public function destroy($sessionId) {
			return $this->Memcached->delete($this->prefix . $sessionId);
		}
		
		public function gc($lifetime) {
			return true;
		}
	}
	
	