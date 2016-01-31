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
    
    /**
     * CacheResource
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
    
    class Template_Cache_Memcached extends Smarty_CacheResource_KeyValueStore {
        
        /**
         * Cache instance
         * @var Cache
         */
        protected $Cache = null;
        
        public function __construct()
        {
            $this->Cache = AppCore::getMemcached(); 
            $Smarty = AppCore::GetSmarty();
            $Smarty->caching_type = "memcached";
            
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
            $res = $this->Cache->fetch($_keys);
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
                $rs = $this->Cache->save($k, $v, 0, $expire);
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
                $this->Cache->fetch($k);
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
    