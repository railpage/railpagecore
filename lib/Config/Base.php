<?php
	/**
	 * Site config
	 * @since Version 3.2
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Config; 
	
	use Railpage\AppCore;
	
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
		
		public function get($key = false) {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM config";
				
				if (!empty($key)) {
					$query .= " WHERE `key` = '".$this->db->real_escape_string($key)."'";
				} else {
					$query .= " ORDER BY name";
				}
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$return[$row['id']] = $row; 
					}
					
					return $return;
				} else {
					throw new \Exception($this->db->error."\n".$query); 
					return false;
				}
			} else {
				if ($key) {
					return $this->db->fetchOne("SELECT value FROM config WHERE `key` = ?", $key); 
				} else {
					$return = array(); 
					
					foreach ($this->db->fetchAll("SELECT * FROM config ORDER BY name") as $row) {
						$return[$row['id']] = $row; 
					}
					
					return $return;
				}
			}
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
		
		public function set($key = false, $value, $name) {
			if (!$key) {
				throw new \Exception("Cannot set config option - \$key not given"); 
				return false;
			}
			
			if (empty($value)) {
				throw new \Exception("Cannot set config option - \$value cannot be empty"); 
				return false;
			}
			
			if (empty($name)) {
				throw new \Exception("Cannot set config option - \$name cannot be empty"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				if ($this->get($key)) {
					// Update
					
				} else {
					// Insert
					
				}
			} else {
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
				} else {
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
		}
	}
	