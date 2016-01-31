<?php
    /**
     * Modules management
     * @since Version 3.2
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Modules;
    use Railpage\Module;
    use Railpage\AppCore;
    use Exception;
    use DateTime;
    
    /**
     * Base modules class
     * @since Version 3.2
     * @author Michael Greenhill
     */
    
    class Modules extends AppCore {
        
        /**
         * Get modules
         * @since Version 3.2
         * @return array
         * @param string $type
         */
        
        public function getModules($type = "all") {
            if (empty($type)) {
                $type = "all";
            }
            
            $query = "SELECT mid AS module_id, title AS module_url, custom_title AS module_name, active AS module_active, view AS module_private
                        FROM nuke_modules
                        ORDER BY title";
                        
            if ($type == "inactive") {
                $query .= " WHERE active = 0";
            } elseif ($type == "private") {
                $query .= " WHERE view = 1";
            } elseif ($type == "public") {
                $query .= " WHERE view = 0";
            }
            
            foreach ($this->db->fetchAll($query) as $row) {
                if (empty($row['module_name'])) {
                    $row['module_name'] = $row['module_url']; 
                }
                
                $modules[$row['module_id']] = $row; 
            }
            
            return $modules;
        }
        
        /**
         * Delete a module
         * @since Version 3.5
         * @param int $module_id
         * @return $this
         */
        
        public function deleteModule($module_id = false) {
            if (!$module_id) {
                throw new Exception("Cannot delete module - no module ID given"); 
                return false;
            }
            
            $query = "DELETE FROM nuke_modules WHERE mid = ?";
            
            $where = array(
                "mid = ?" => $module_id
            );
            
            $this->db->delete("nuke_modules", $where);
            
            return $this;
        }
        
        /**
         * Add a new module
         * @since Version 3.8.7
         * @param string $name
         * @param boolean $active
         * @param boolean $view
         * @return $this
         */
        
        public function addModule($name = false, $active = true, $public = true) {
            if ($name === false || empty($name)) {
                throw new Exception("Cannot add module because no module name was specified");
            }
            
            if (!is_dir(dirname(__DIR__) . DS . $name)) {
                throw new Exception(sprintf("The specified module '%s' does not exist", $name));
            }
            
            $data = array(
                "title" => $name, 
                "custom_title" => $name,
                "active" => $active,
                "view" => $public,
                "inmenu" => 1,
                "mcid" => 1
            );
            
            $this->db->insert("nuke_modules", $data);
            
            return $this;
        }
    }
    