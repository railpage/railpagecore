<?php
    /**
     * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Users;
    
    use Railpage\Debug;
    use Railpage\AppCore;
    use Railpage\Url;
    use Railpage\Registry;
    use Exception;
    
    class Factory {
        
        /**
         * Return a user
         * @since Version 3.9.1
         * @return \Railpage\Users\User
         * @param int|string $id
         */
        
        public static function CreateUser($id = false) {
            
            $Redis = AppCore::getRedis();
            $Registry = Registry::getInstance(); 
            
            $regkey = sprintf(User::REGISTRY_KEY, $id); 
            
            try {
                $User = $Registry->get($regkey); 
            } catch (Exception $e) {
                if (!$User = $Redis->fetch(sprintf("railpage:users.user=%d", $id))) {
                    $User = new User($id); 
                    $Redis->save(sprintf("railpage:users.user=%d", $id), $User);
                }
                
                $Registry->set($regkey, $User); 
            }
            
            return $User;
            
        }
        
        /**
         * Create a user by their username
         * @since Version 3.9.1
         * @return \Railpage\Users\User
         * @param int|string $id
         */
        
        public static function CreateUserFromUsername($username = false) {
            
            $id = Utility\UserUtility::getUserId($username); 
                
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                throw new Exception("Could not find user ID from given username"); 
            }
            
            return self::CreateUser($id); 
            
        }
        
    }