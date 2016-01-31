<?php
    /**
     * Access rights for people viewing the photo competition
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    use Exception;
    use InvalidArgumentException;
    use Railpage\Users\User;
    use Railpage\AppCore;
    
    /**
     * ACL
     */
    
    class Acl {
        
        /**
         * Users with absolute awesome rights over the entire comp system
         * @since Version 3.10.0
         * @var array $showRunners
         */
        
        public $showRunners = [ 45, 28, 13666 ];
        
        /**
         * Constructor
         * @since Version 3.10.0
         */
        
        /**
         * Check if a user is allowed to do something
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @param string $perm
         * @return boolean
         */
        
        public static function can(User $User, $perm) {
            
            $Acl = new Acl;
            
            if (in_array($User->id, $Acl->showRunners)) {
                return true;
            }
            
            return false; 
            
        }
        
    }