<?php
	/**
	 * Railway gauge - the space between the rails
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Debug;
	use Railpage\Url;
	use Railpage\ContentUtility;
	use Exception;
	use DateTime;
	
	/**
	 * Gauge
	 */
	
	class Gauge extends AppCore {
		
		/**
		 * Gauge ID
		 * @since Version 3.9.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Gauge name
		 * @since Version 3.9.1
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Width in metric
		 * @since Version 3.9.1
		 * @var string $width_metric
		 */
		
		public $width_metric;
		
		/**
		 * Width in imperial
		 * @since Version 3.9.1
		 * @var string $width_imperial
		 */
		
		public $width_imperial;
		
		/**
		 * Memcached key
		 * @since Version 3.9.1
		 * @var string $mckey
		 */
		
		public $mckey;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			
			$timer = Debug::getTimer(); 
			
			parent::__construct(); 
			
			$this->Module = new Module("locos"); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
			} elseif (is_string($id) && !empty($id)) {
				$query = "SELECT gauge_id FROM loco_gauge WHERE slug = ?";
				$this->id = $this->db->fetchOne($query, $id); 
			}
			
			$this->populate(); 
			
			Debug::logEvent(__METHOD__, $timer); 
			
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function populate() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return;
			}
			
			$this->mckey = sprintf("railpage:locos.gauge_id=%d", $this->id);
			 
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT * FROM loco_gauge WHERE gauge_id = ?";
				
				$row = $this->db->fetchRow($query, $this->id); 
				$this->Memcached->save($this->mckey, $row, strtotime("+1 year")); 
			}
			
			$this->name = $row['gauge_name'];
			$this->width_metric = $row['gauge_metric'];
			$this->width_imperial = $row['gauge_imperial'];
			$this->slug = isset($row['slug']) && !empty($row['slug']) ? $row['slug'] : $this->createSlug(); 
		}
		
		/**
		 * Validate changes to this gauge
		 * Attempts to generate an imperial width if a metric width is present
		 *
		 * @since Version 3.9.1
		 * @throws \Exception if $this->name is empty
		 * @throws \Exception if $this->width_metric is empty
		 * @throws \Exception if $this->width_imperial is empty
		 */
		
		private function validate() {
			if (empty($this->name)) {
				throw new Exception("Name cannot be empty");
			}
			
			if (empty($this->width_metric)) {
				throw new Exception("Metric gauge width cannot be empty"); 
			}
			
			if (substr($this->width_metric, -2) != "mm") {
				$this->width_metric .= "mm";
			}
			
			if (empty($this->width_imperial) && !empty($this->width_metric)) {
				$width = Locos::convert_to_inches($this->width_metric); 
				
				if (isset($width['ft']) && filter_var($width['ft'], FILTER_VALIDATE_INT) && $width['ft'] != 0) {
					$this->width_imperial = sprintf("%d' ", $width['ft']); 
				}
				
				if (isset($width['in']) && filter_var($width['in'], FILTER_VALIDATE_INT) && $width['in'] != 0) {
					$this->width_imperial = sprintf("%s%d\"", $this->width_imperial, $width['in']); 
				}
				
				$this->width_imperial = trim($this->width_imperial); 
			}
				
			if (empty($this->width_imperial)) {
				throw new Exception("Could not create an imperial width for this gauge"); 
			}
			
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			return true;
		}
		
		/**
		 * Commit changes
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Gauge
		 */
		
		public function commit() {
			$this->validate(); 
			
			$this->Memcached->delete($this->mckey);
			
			$data = array(
				"gauge_name" => $this->name,
				"gauge_metric" => $this->width_metric,
				"gauge_imperial" => $this->width_imperial,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"gauge_id = ?" => $this->id
				);
				
				$this->db->update("loco_gauge", $data, $where); 
			} else {
				$this->db->insert("loco_gauge", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.9.1
		 */
		
		private function createSlug() {
			$proposal = ContentUtility::generateUrlSlug($this->width_metric);
			
			$result = $this->db->fetchAll("SELECT gauge_id FROM loco_gauge WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
			
			return $this->slug;
		}
		
		/**
		 * Return an associative array of this gauge
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"gauge_id" => $this->id,
				"gauge_name" => $this->name,
				"gauge_imperial" => $this->width_imperial,
				"gauge_metric" => $this->width_metric,
				"slug" => $this->slug,
				"text" => $this->__toString()
			);
		}
		
		/**
		 * Get the gauge as a formatted string
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			return sprintf("%s %s (%s)", $this->name, $this->width_imperial, $this->width_metric); 
		}
	}
	