<?php
    /**
     * RedisSessionHandler session handler using SessionHandlerInterface
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\SessionHandler;
    
    use SessionHandlerInterface;
    use Exception;
    use Redis;
    use Railpage\AppCore;
    
    /**
     * RedisSessionHandler session handler
     */
    
    class RedisSessionHandler implements SessionHandlerInterface {
        
        /**
         * @var \Redis $Redis Redis driver.
         * @since Version 3.10.0
         */
        
        private $Redis;
        
        /**
         * @var int $ttl Time to live in seconds
         * @since Version 3.10.0
         */
        
        private $ttl;
        
        /**
         * @var string $prefix Key prefix for shared environments.
         * @since Version 3.10.0
         */
        
        private $prefix;
        
        /**
         * Constructor
         * @since Version 3.10.0
         * @param \Redis $Redis
         * @param array $options
         */
        
        public function __construct(Redis $Redis, array $options = array()) {
            $this->Redis = $Redis;
            
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
            return $this->Redis->get($this->prefix . $sessionId) ? : '';
        }
        
        public function write($sessionId, $data) {
            return $this->Redis->set($this->prefix . $sessionId, $data, time() + $this->ttl);
        }
        
        public function destroy($sessionId) {
            return $this->Redis->delete($this->prefix . $sessionId);
        }
        
        public function gc($lifetime) {
            return true;
        }
    }
    
    