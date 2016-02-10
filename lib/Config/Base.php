<?php

/**
 * Site config
 * @since Version 3.2
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Config; 

use Railpage\AppCore;
use Railpage\Debug;
use Exception;
use InvalidArgumentException;
use DateTime;

/**
 * Config class
 * @since Version 3.2
 * @author Michael Greenhill
 */

class Base extends AppCore {
    
    /**
     * Return site config
     * @since Version 3.2
     * @param string $key
     * @return array
     */
    
    public function get($key = null) {
        
        if ($key != null) {
            $cachekey = sprintf("railpage:config:%s", $key); 
            
            if (!$value = $this->Memcached->fetch($cachekey)) {
                $value = $this->db->fetchOne("SELECT value FROM config WHERE `key` = ?", $key); 
                $this->Memcached->save($cachekey, $value, strtotime("+1 month")); 
            }
            
            return $value;
        }
        
        $return = array(); 
        
        foreach ($this->db->fetchAll("SELECT * FROM config ORDER BY name") as $row) {
            $return[$row['id']] = $row; 
        }
        
        return $return;
        
    }
    
    /**
     * Get a phpBB config item
     * @since Version 3.10.0
     * @param string $key
     * @return mixed
     */
     
    public static function getPhpBB($key = null) {
        
        $Memcached = AppCore::GetMemcached(); 
        
        $cachekey = sprintf("railpage:config_phpbb:%s", $key); 
    
        if ($rs = $Memcached->fetch($cachekey)) {
            return $rs; 
        }
        
        $Database = AppCore::GetDatabase(); 
        
        $query = "SELECT config_value FROM nuke_bbconfig WHERE config_name = 'allow_html_tags'"; 
        
        $rs = $Database->fetchOne($query); 
        $Memcached->save($cachekey, $rs, strtotime("+1 month")); 
        
        return $rs;
        
    }
    
    /**
     * Set config key
     * @since Version 3.7.5
     * @param string $key
     * @param string $value
     * @param string $name
     * @throws \Exception if $key is not given
     * @throws \Exception if $value is not given
     * @throws \Exception if $name is not given
     * @return boolean
     */
    
    public function set($key = null, $value, $name) {
        
        if ($key == null) {
            throw new Exception("Cannot set config option - \$key not given"); 
        }
        
        if (empty($value)) {
            throw new Exception("Cannot set config option - \$value cannot be empty"); 
        }
        
        if (empty($name)) {
            throw new Exception("Cannot set config option - \$name cannot be empty"); 
        }
        
        $cachekey = sprintf("railpage:config:%s", $key); 
        $this->Memcached->save($cachekey, $value, strtotime("+1 month")); 
        
        if ($this->get($key)) {
            // Update
            $data = array(
                "value" => $value,
                "name" => $name
            );
            
            $where = array(
                "`key` = ?" => $key
            );
            
            return $this->db->update("config", $data, $where);
        }
        
        // Insert
        $data = array(
            "date" => time(),
            "key" => $key,
            "value" => $value,
            "name" => $name
        );
        
        return $this->db->insert("config", $data);
        
    }
}
