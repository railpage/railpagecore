<?php
	/**
	 * Railcams class
	 * @since Version 3.4
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams; 
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use stdClass;
	use PDO;
	
	/**
	 * Railcams base class
	 */
	
	class Railcams extends AppCore {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			$this->Module = new Module("railcams");
			$this->namespace = $this->Module->namespace;
		}
		
		/**
		 * List all railcams
		 * @since Version 3.4
		 * @return array
		 */
		
		public function listAll() {
			$query = "SELECT * FROM railcams ORDER BY name";
			
			if ($result = $this->db->fetchAll($query)) {
				$return = array(); 
				
				foreach ($result as $row) {
					$return[$row['id']] = $row;
				}
				
				return $return;
			}
		}
		
		/**
		 * Get a Railcam ID from its permalink
		 * @since Version 3.4
		 * @param string $permalink 
		 * @return int|boolean
		 */
		
		public function getIDFromPermalink($permalink = false) {
			if (!$permalink) {
				throw new Exception("Cannot find the railcam ID from the given permalink - no permalink given!"); 
				return false;
			}
			
			$query = "SELECT id FROM railcams WHERE permalink = ?";
			
			if ($id = $this->db->fetchOne($query, $permalink)) {
				return $id;
			} else {
				throw new Exception("Cannot find the railcam ID from the given permalink - no results found"); 
				return false;
			}
		}
		
		/**
		 * Get a Railcam ID from its NSID
		 * @since Version 3.4
		 * @param string $nsid
		 * @return int|boolean
		 */
		
		public function getIDFromNSID($nsid = false) {
			if (!$nsid) {
				throw new Exception("Cannot find the railcam ID from the given Flickr NSID - no NSID given!"); 
				return false;
			}
			
			$query = "SELECT id FROM railcams WHERE nsid = ?";
			
			if ($id = $this->db->fetchOne($query, $nsid)) {
				return $id;
			} else {
				throw new Exception("Cannot find the railcam ID from the given Flickr NSID - no results found"); 
				return false;
			}
		}
		
		/**
		 * Get railcam types
		 * @since Version 3.8
		 * @return array
		 */
		
		public function getTypes() {
			$query = "SELECT * FROM railcams_type ORDER BY name ASC";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[] = $row;
			}
			
			return $return;
		}
		
		/**
		 * Get photos tagged with locomotives
		 * @since Version 3.9
		 * @return array
		 * @param boolean|\DateTime $DateFrom
		 * @param boolean|\DateTime $DateTo
		 */
		
		public function getTaggedPhotos($DateFrom = false, $DateTo = false) {
			
			$Config = AppCore::GetConfig(); 
			$SphinxPDO_New = new PDO("mysql:host=" . $Config->Sphinx->Host . ";port=9312"); 
			$lookup = $SphinxPDO_New->prepare("SELECT * FROM idx_sightings WHERE meta.source = :source ORDER BY date_unix DESC LIMIT 0, 25");
			$lookup->bindValue(":source", "railcam", PDO::PARAM_STR); 
			$lookup->execute(); 
			
			$result = $lookup->fetchAll(PDO::FETCH_ASSOC); 
			
			foreach ($result as $key => $val) {
				$result[$key]['loco_ids'] = json_decode($val['loco_ids'], true); 
				$result[$key]['meta'] = json_decode($val['meta'], true); 
			}
			
			return $result;
			
			
			$Sphinx = $this->getSphinx(); 
			
			$query = $Sphinx->select("*")
					->from("idx_railcam_locos")
					->orderBy("id", "DESC");
			
			if ($DateFrom instanceof DateTime) {
				$query->where("date", ">=", $DateFrom->format(DateTime::ISO8601));
			}
			
			if ($DateTo instanceof DateTime) {
				$query->where("date", "<=", $DateTo->format(DateTime::ISO8601));
			}
			
			$locos = $query->execute();
			
			return $locos;
		}
	}
	