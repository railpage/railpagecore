<?php
    /**
     * Cache templates using Memcache
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     * 
     * Code lifted from http://www.smarty.net/docs/en/caching.custom.tpl
     *
     * See Railpage bug/issue http://redmine.railpage.org/redmine/issues/141
     */
    
    namespace Railpage;
    use Smarty_CacheResource_KeyValueStore;
    use Memcache;
    
    if (!defined("RP_MEMCACHE_HOST")) {
        define("RP_MEMCACHE_HOST", "cache.railpage.com.au");
    }
    
    if (!defined("RP_MEMCACHE_PORT")) {
        define("RP_MEMCACHE_PORT", 11211);
    }
    
    /**
     * Memcache CacheResource
     *
     * CacheResource Implementation based on the KeyValueStore API to use
     * memcache as the storage resource for Smarty's output caching.
     *
     * Note that memcache has a limitation of 256 characters per cache-key.
     * To avoid complications all cache-keys are translated to a sha1 hash.
     *
     * @package CacheResource-examples
     * @author Rodney Rehm
     */
    class Template_Cache_Memcache extends Smarty_CacheResource_KeyValueStore {
        /**
         * memcache instance
         * @var Memcache
         */
        protected $Memcached = null;
        
        public function __construct()
        {
            $this->memcache = new Memcache();
            $this->memcache->addServer(RP_MEMCACHE_HOST, RP_MEMCACHE_PORT);
            #$this->Memcached = AppCore::getMemcached(); 
        }
        
        /**
         * Read values for a set of keys from cache
         *
         * @param array $keys list of keys to fetch
         * @return array list of values with the given keys used as indexes
         * @return boolean true on success, false on failure
         */
        protected function read(array $keys)
        {
            #echo "reading template from cache";
            #printArray($keys);
            $_keys = $lookup = array();
            foreach ($keys as $k) {
                $_k = sha1($k);
                $_keys[] = $_k;
                $lookup[$_k] = $k;
            }
            $_res = array();
            $res = $this->memcache->get($_keys);
            foreach ($res as $k => $v) {
                $_res[$lookup[$k]] = $v;
            }
            return $_res;
        }
        
        /**
         * Save values for a set of keys to cache
         *
         * @param array $keys list of values to save
         * @param int $expire expiration time
         * @return boolean true on success, false on failure
         */
        protected function write(array $keys, $expire=null)
        {
            #echo "writing template to cache";
            #printArray($keys);
            foreach ($keys as $k => $v) {
                $k = sha1($k);
                $rs = $this->memcache->set($k, $v, 0, $expire);
            }
            return true;
        }
    
        /**
         * Remove values from cache
         *
         * @param array $keys list of keys to delete
         * @return boolean true on success, false on failure
         */
        protected function delete(array $keys)
        {
            #echo "deleting template from cache";
            #printArray($keys);
            foreach ($keys as $k) {
                $k = sha1($k);
                $this->memcache->delete($k);
            }
            return true;
        }
    
        /**
         * Remove *all* values from cache
         *
         * @return boolean true on success, false on failure
         */
        protected function purge()
        {
            return false; #return $this->Memcached->flush();
        }
    }
    