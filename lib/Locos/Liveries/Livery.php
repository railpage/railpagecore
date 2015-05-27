<?php
	/** 
	 * Livery class
	 * @since Version 3.2
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Liveries;
	
	use Exception;
	use Railpage\Images\Images;
	
	/**
	 * Livery class
	 * @since Version 3.2
	 */
	
	class Livery extends Base {
		
		/**
		 * Livery ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Livery name
		 * @since Version 3.2
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Date introduced
		 * @since Version 3.2
		 * @var string $introduced
		 */
		
		public $introduced;
		
		/**
		 * Date phased out
		 * @since Version 3.2
		 * @var string $withdrawn
		 */
		
		public $withdrawn;
		
		/** 
		 * This livery was superseded by x
		 * @since Version 3.2
		 * @var int $superseded_by
		 */
		
		public $superseded_by;
		
		/** 
		 * This livery was superseded by x ID
		 * @since Version 3.2
		 * @var int $superseded_by_id
		 */
		
		public $superseded_by_id;
		
		/**
		 * This livery supersedes x
		 * @since Version 3.2
		 * @var int $supersedes
		 */
		
		public $supersedes;
		
		/**
		 * This livery supersedes x ID
		 * @since Version 3.2
		 * @var int $supersedes_id
		 */
		
		public $supersedes_id;
		
		/**
		 * Flickr photo ID
		 * @since Version 3.2
		 * @var string $photo
		 */
		
		public $photo_id;
		
		/**
		 * Region within a country this livery is primarily found in. 
		 * For inter-region (inter-state) liveries, leave blank
		 *
		 * @since Version 3.2
		 * @var string $region
		 */
		
		public $region;
		
		/**
		 * Country this livery is primarily found in
		 * For international liveries, leave blank
		 *
		 * @since Version 3.2
		 * @var string $country
		 */
		
		public $country;
		
		/**
		 * Do we recurse through child objects and populate them? Could be very very memory intensive
		 * @since Version 3.2
		 * @var boolean $recurse
		 */
		
		public $recurse;
		
		/**
		 * Flickr machine tag
		 * @since Version 3.8.7
		 * @var string $tag
		 */
		
		public $tag;
		
		/**
		 * Loco image object
		 * @since Version 3.8.7
		 * @var \Railpage\Images\Image $Image
		 */
		
		public $Image;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @param int $id
		 * @param boolean $recurse
		 */
		
		public function __construct($id, $recurse = true) {
			
			parent::__construct();
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_startx = microtime(true);
			}
			
			$this->namespace = "railpage.locos.liveries.livery";
			
			// Fetch any child objects
			$this->recurse = $recurse;
			
			if ($id) {
				$this->id = $id;
				
				$this->url = "/flickr?tag=railpage:livery=" . $this->id;
				
				$this->fetch();
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $debug_timer_startx, 5) . "s";
			}
		}
		
		/**
		 * Load this livery object
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch Livery object - no ID given"); 
				return false;
			}
			
			$this->mckey = sprintf("railpage:livery=%d", $this->id);
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT * FROM loco_livery WHERE livery_id = ?";
				$row = $this->db->fetchRow($query, $this->id); 
			}
			
			if (isset($row) && is_array($row)) {		
				$this->name 		= $row['livery'];
				$this->introduced	= $row['introduced'];
				$this->withdrawn	= $row['withdrawn'];
				$this->superseded_by_id	= ($row['superseded_by'] > 0) ? $row['superseded_by'] : NULL;
				$this->supersedes_id	= ($row['supersedes'] > 0) ? $row['supersedes'] : NULL;
				$this->photo_id			= $row['photo_id'];
				$this->region		= $row['region'];
				$this->country		= $row['country'];
				$this->tag = sprintf("railpage:livery=%d", $this->id);
				$this->url = "/flickr?tag=" . $this->tag;
				
				if (filter_var($this->photo_id, FILTER_VALIDATE_INT)) {
					
					#$rdkey = sprintf("railpage:image;provider=%s;photo_id=%s;v2", "flickr", $this->photo_id);
					
					#if (!$this->Image = $this->Redis->fetch($rdkey)) {
						$this->Image = (new Images)->findImage("flickr", $this->photo_id, Images::OPT_NOPLACE);
					#	$this->Redis->save($rdkey, $this->Image, strtotime("+24 hours"));
					#}
				}
				
				return true;
			} else {
				throw new Exception("Cannot fetch Livery object - no livery for ID ".$this->id." was found");
				return false;
			}
		}
		
		/**
		 * Return the livery superseded by this one
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Liveries\Livery
		 */
		
		public function getPreviousLivery() {
			if ($this->recurse && $this->supersedes_id > 0) {
				return new Livery($this->supersedes_id, false);
			}
			
			return null;
		}
		
		/**
		 * Return the livery that supersedes this one
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Liveries\Livery
		 */
		
		public function getNextLivery() {
			if ($this->recurse && $this->superseded_by_id > 0) {
				return new Livery($this->superseded_by_id, false);
			}
			
			return null;
		}
		
		/**
		 * Commit changes to this livery
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"livery" => $this->name,
				"introduced" => $this->introduced,
				"withdrawn" => $this->withdrawn,
				"superseded_by" => $this->superseded_by_id,
				"supersedes" => $this->supersedes_id,
				"photo_id" => $this->photo_id,
				"region" => $this->region,
				"country" => $this->country
			);
			
			if (empty($this->id)) {
				$this->db->insert("loco_livery", $data); 
				$this->id = $this->db->lastInsertId();
			} else {
				$where = array(
					"livery_id = ?" => $this->id
				);
				
				$this->db->update("loco_livery", $data, $where);
			}
			
			
			return true;
		}
		
		/**
		 * Validate this livery
		 * @return true
		 * @throws \Exception if $this->name is empty
		 * @throws \Exception if $this->country is empty
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate changes to this livery - livery name cannot be empty"); 
			}
			
			if (empty($this->country)) {
				throw new Exception("Cannot validate changes to this livery - country cannot be empty"); 
			}
			
			return true;
		}
	}
	