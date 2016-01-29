<?php
    /** 
     * Create and manage official organisations
     * The purpose of an organisation is to identify a forum member as an official representative of an organisation or company
     * @since Version 3.2
     * @version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Organisations;
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Debug;
    use Railpage\ContentUtility;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    
    /**
     * Base organisation class
     * @since Version 3.2
     * @version 3.8.7
     * @author Michael Greenhill
     */
    
    class Base extends AppCore {
        
        /**
         * Constructor
         * @since Version 3.8.7
         */
        
        public function __construct() {
            parent::__construct(); 
            
            $this->Module = new Module("organisations");
            $this->namespace = $this->Module->namespace;
        }
        
        /**
         * Search for an organisation
         * @since Version 3.2
         * @param string $name
         * @return array
         */
        
        public function search($name) {
            $query = "SELECT organisation_id, organisation_name, organisation_desc FROM organisation WHERE organisation_name LIKE ? ORDER BY organisation_name DESC";
            
            $name = "%" . $name . "%";
            
            $result = $this->db->fetchAll($query, $name);
            $return = array(); 
            
            foreach ($result as $row) {
                $row['organisation_desc_short'] = substr($row['organisation_desc'], 0, 100)."...";
                $return[$row['organisation_id']] = $row; 
            }
            
            return $return;
        }
        
        /** 
         * List all organisations
         * @since Version 3.2
         * @version 3.2
         * @return array
         * @param string $starter
         */
        
        public function listorgs($starter = false) {
            if ($starter == "number") {
                $starter = "WHERE o.organisation_name RLIKE '^[^A-Z]'";
            } elseif (preg_match("@([a-zA-Z]+)@", $starter)) {
                $starter = "WHERE o.organisation_name LIKE '" . $starter . "%'";
            } else {
                $starter = "";
            }
            
            $query = "SELECT o.* FROM organisation AS o " . $starter  . " ORDER BY o.organisation_name ASC";
            
            $return = array();
            
            if ($result = $this->db->fetchAll($query)) {
                $return['stat'] = "ok";
                
                foreach ($result as $row) {
                    $row['organisation_url'] = "/orgs/" . $row['organisation_slug'];
                    $return['orgs'][$row['organisation_id']] = $row;
                }
            } else {
                $return['stat'] = "error";
            }
            
            return $return;
        }
        
        /**
         * Alias for listorgs()
         * @since Version 3.6
         */
        
        public function getOrganisations() {
            return $this->listorgs(); 
        }
        
        /**
         * Generate the URL slug
         * @since Version 3.7.5
         * @param int $id
         * @param string $name
         * @return string
         */
        
        public function createSlug($id = false, $name = false) {
            
            $timer = Debug::GetTimer(); 
            
            if (filter_var($id, FILTER_VALIDATE_INT) && !$name) {
                $name = $this->db->fetchOne("SELECT organisation_name FROM organisation WHERE organisation_id = ?", $id); 
            } elseif (filter_var($id, FILTER_VALIDATE_INT) && is_string($name)) {
                // Do nothing
            } elseif (isset($this->name) && !empty($this->name)) {
                $name = $this->name;
                $id = $this->id;
            } else {
                return false;
            }
            
            $proposal = ContentUtility::generateUrlSlug($name, 200);
            
            /**
             * Check that we haven't used this slug already
             */
            
            $result = $this->db->fetchAll("SELECT organisation_id FROM organisation WHERE organisation_slug = ? AND organisation_id != ?", array($proposal, $id)); 
            
            if (count($result)) {
                $proposal .= count($result);
            }
            
            if (isset($this->slug) || empty($this->slug)) {
                $this->slug = $proposal;
            }
            
            /**
             * Add this slug to the database
             */
            
            $data = array(
                "organisation_slug" => $proposal
            );
            
            $where = array(
                "organisation_id = ?" => $id
            );
            
            $rs = $this->db->update("organisation", $data, $where); 
            
            Debug::LogEvent(__METHOD__, $timer); 
            
            /**
             * Return it
             */
            
            return $proposal;
        }
        
        /**
         * Make a permalink
         * @since Version 3.7.5
         * @return string
         * @param int|string $entity
         */
        
        public function makePermaLink($entity = false) {
            if (!$entity) {
                return false;
            }
            
            if (filter_var($entity, FILTER_VALIDATE_INT)) {
                $slug = $this->db->fetchOne("SELECT organisation_slug FROM organisation WHERE organisation_id = ?", $entity); 
                
                if ($slug === false || empty($slug)) {
                    $slug = $this->createSlug($entity); 
                }
            } else {
                $slug = $entity;
            }
            
            $permalink = "/orgs/" . $slug; 
            
            return $permalink;
        }
    }
    