<?php

/**
 * Password utility class
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users\Utility;

use Exception;
use InvalidArgumentException;
use Railpage\AppCore;
use Railpage\Users\User;

/**
 * PasswordUtility
 */

class PasswordUtility {
    
    /**
     * Before validating a password check first that all the 
     * parameters are correct
     * @param string $username
     * @param string $password
     * @param \Railpage\Users\User $ThisUser
     */
    
    public static function validateParameters($username, $password, User $ThisUser) {
        
        /**
         * Check for a valid password
         */

        if (!$password || empty( $password )) {
            throw new Exception("Cannot validate password - no password was provided");
        }

        /**
         * Check for a supplied userame or if this object is populated
         */

        if (( !$username || empty( $username ) ) && ( !filter_var($ThisUser->id, FILTER_VALIDATE_INT) || $ThisUser->id < 1 )) {
            throw new Exception("Cannot validate password for user because we don't know which user this is");
        }

        /**
         * Check if a supplied username matches the username in this populated object
         */

        if ($username && !empty( $username ) && !empty( $ThisUser->username ) && $ThisUser->username != $username) {
            throw new Exception("The supplied username does not match the username given for this account. Something dodgy's going on...");
        }
        
    }
    
    /**
     * Actually validate the password
     * @since Version 3.10.0
     * @param string $password
     * @param string $storedPassword
     * @param string $storedBcryptedPassword
     * @return boolean
     */
    
    public static function validatePassword($password = null, $storedPassword = null, $storedBcryptedPassword = null) {
        
        if (is_null($password)) {
            throw new InvalidArgumentException("No password supplied"); 
        }
        
        if (is_null($storedPassword)) {
            throw new InvalidArgumentException("Encrypted password missing"); 
        }
        
        if (is_null($storedBcryptedPassword)) {
            throw new InvalidArgumentException("BCrypted password missing"); 
        }
        
        if (md5($password) === $storedPassword) {
            return true;
        }
        
        if (password_verify($password, $storedPassword)) {
            return true;
        }
        
        if (password_verify($password, $storedBcryptedPassword)) {
            return true;
        }
        
        return false;
        
    }
    
}