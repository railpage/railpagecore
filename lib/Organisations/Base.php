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
	use Exception;
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
			if ($this->db instanceof \sql_db) {
				$query = "SELECT organisation_id, organisation_name, organisation_desc FROM organisation WHERE organisation_name LIKE '%".$this->db->real_escape_string($name)."%' ORDER BY organisation_name DESC";
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$row['organisation_desc_short'] = substr($row['organisation_desc'], 0, 100)."...";
						$return[$row['organisation_id']] = $row; 
					} 
					
					return $return; 
				} else {
					throw new \Exception($this->db->error."\n".$query); 
				}
			} else {
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
			
			if ($this->db instanceof \sql_db) {
				$return = array();
				
				if ($rs = $this->db->query($query)) {
					$return['stat'] = "ok";
					while ($row = $rs->fetch_assoc()) {
						$row['organisation_url'] = "/orgs/" . $row['organisation_slug'];
						$return['orgs'][$row['organisation_id']] = $row;
					}
				} else {
					$return['stat'] = "error";
					$return['error'] = $this->db->error;
				}
				
				return $return;
			} else {
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
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
				
			// Assume ZendDB
			$find = array(
				"(",
				")",
				"-"
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
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
			
			$name = str_replace($find, $replace, $name);
			$proposal = create_slug($name);
			
			/**
			 * Trim it if the slug is too long
			 */
			
			if (strlen($proposal) >= 256) {
				$proposal = substr($poposal, 0, 200); 
			}
			
			/**
			 * Check that we haven't used this slug already
			 */
			
			$result = $this->db->fetchAll("SELECT organisation_id FROM organisation WHERE organisation_slug = ? AND organisation_id != ?", array($proposal, $id)); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			if (isset($this->slug)) {
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
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Zend_DB: FAILED create url slug for organisation ID " . $id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				} else {
					$site_debug[] = "Zend_DB: SUCCESS create url slug for organisation ID " . $id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				}
			}
			
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
?>