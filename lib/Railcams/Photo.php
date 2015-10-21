<?php
	/**
	 * Railcam photo
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams;
	
	use Exception;
	use DateTime;
	use flickr_railpage;
	use Railpage\Url;
	use Railpage\Locos\Locomotive;
	use Railpage\Images\Images;
	use Railpage\AppCore;
	use Railpage\Debug;
	use PDO;
	use Zend_Db_Expr;
	
	/**
	 * Railcam photo
	 */
	
	class Photo extends Railcams {
		
		/**
		 * Photo provider
		 * @since Version 3.9
		 */
		
		private $Provider;
		
		/**
		 * Railcam which took this photo
		 * @var \Railpage\Railcams\Camera
		 * @since Version 3.9
		 */
		
		private $Camera;
		
		/**
		 * Photo ID
		 * @since Version 3.9
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Photo title
		 * @since Version 3.9
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Photo description
		 * @since Version 3.9
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Array of DateTime instances appliccable to this photo
		 * @since Version 3.9
		 * @var array $dates
		 */
		
		public $dates;
		
		/**
		 * Associative array of photo author details
		 * @since Version 3.9
		 * @var array $author
		 */
		
		public $author;
		
		/**
		 * Array of different photo sizes
		 * @since Version 3.9
		 * @var array $sizes
		 */
		
		public $sizes;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $id The ID of the photo from the provider
		 * @param object $Provider The provider of the image, using the \Railpage\Railcams\Provider interface
		 */
		
		public function __construct($id = false, $Provider = false) {
			
			parent::__construct(); 
			
			if ($Provider) {
				$this->setProvider($Provider);
			}
				
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				if (is_object($this->Provider)) {
					$this->load();
				}
			}
		}
		
		/**
		 * Set the provider of this photo
		 * @since Version 3.9
		 * @param object $Provider The provider of the image, using the \Railpage\Railcams\Provider interface
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function setProvider($Provider = false) {
			$implements = class_implements($Provider);
			
			if (in_array("Railpage\\Railcams\\ProviderInterface", $implements)) {
				$this->Provider = $Provider;
			} else {
				throw new Exception("The specified object " . get_class($Provider) . " does not implement \\Railpage\\Railcams\\ProviderInterface");
			}
			
			return $this;
		}
		
		/**
		 * Set the railcam which took this photo
		 * @since Version 3.9
		 * @param \Railpage\Railcams\Camera $Camera
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function setCamera(Camera $Camera) {
			$this->Camera = $Camera;
			
			return $this;
		}
		
		/**
		 * Load this photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function load() {
			$photodata = $this->Provider->getPhoto($this->id);
					
			$this->title = $photodata['title'];
			$this->description = $photodata['description'];
			$this->dates = $photodata['dates'];
			$this->author = $photodata['author'];
			$this->sizes = $photodata['sizes'];
			
			if ($this->Camera instanceof Camera) {
				$this->url = new Url(sprintf("%s%d", $this->Camera->url->photo, $this->id));
				$this->url->delete = sprintf("%s/deletephoto/%d", $this->Camera->url, $this->id);
			}
			
			/**
			 * Normalize some sizes
			 */
			
			if (count($this->sizes)) {
			
				if (!isset($this->sizes['thumb'])) {
					foreach ($this->sizes as $size) {
						if ($size['width'] >= 280 && $size['height'] >= 150) {
							$this->sizes['thumb'] = $size;
							break;
						}
					}
				}
				
				if (!isset($this->sizes['small'])) {
					foreach ($this->sizes as $size) {
						if ($size['width'] >= 500 && $size['height'] >= 281) {
							$this->sizes['small'] = $size;
							break;
						}
					}
				}
				
				$width = 0;
				
				foreach ($this->sizes as $size) {
					if ($size['width'] > $width) {
						$this->sizes['largest'] = $size;
					
						$width = $size['width'];
					}
				}
			
				foreach ($this->sizes as $size) {
					if ($size['width'] >= 1920) {
						$this->sizes['fullscreen'] = $size;
						break;
					}
				}
			
				foreach ($this->sizes as $size) {
					if ($size['width'] > 1024 && $size['width'] <= 1920) {
						$this->sizes['larger'] = $size;
						break;
					}
				}
			
				foreach ($this->sizes as $size) {
					if ($size['width'] == 1024) {
						$this->sizes['large'] = $size;
						break;
					}
				}
			
				foreach ($this->sizes as $size) {
					if ($size['width'] == 800) {
						$this->sizes['medium'] = $size;
						break;
					}
				}
			}

			
			return $this;
		}
		
		/**
		 * Get the name of this photo provider
		 * @since Version 3.9
		 * @return string
		 */
		
		public function getProviderName() {
			return $this->Provider->getProviderName(); 
		}
		
		/**
		 * Save changes to this photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function commit() {
			return $this->Provider->setPhoto($this);
		}
		
		/**
		 * Delete this photo
		 * @since Version 3.9.1
		 * @return boolean
		 */
		
		public function delete() {
			return $this->Provider->deletePhoto($this);
		}
		
		/**
		 * Get previous photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function previous() {
			$context = $this->Provider->getPhotoContext($this);
			
			if (isset($context['previous']) && isset($context['previous']['id']) && filter_var($context['previous']['id'], FILTER_VALIDATE_INT)) {
				$Photo = (new Photo($context['previous']['id']))->setProvider($this->Provider)->setCamera($this->Camera)->load(); 
				
				return $Photo;
			}
			
			return false;
		}
		
		/**
		 * Get the next photo 
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function next() {
			$context = $this->Provider->getPhotoContext($this);
			
			if (isset($context['next']) && isset($context['next']['id']) && filter_var($context['next']['id'], FILTER_VALIDATE_INT)) {
				$Photo = (new Photo($context['next']['id']))->setProvider($this->Provider)->setCamera($this->Camera)->load(); 
				
				return $Photo;
			}
			
			return false;
		}
		
		/**
		 * Get a associative array of this photo data in a standardised format
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"title" => $this->title,
				"description" => $this->description,
				"provider" => $this->getProviderName(),
				"url" => $this->url->getURLs(),
				"sizes" => Images::NormaliseSizes($this->sizes),
				"dates" => $this->dates
			);
		}
		
		/**
		 * Tag a locomotive in this photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function tagLoco(Locomotive $Loco) {
			
			if (!filter_var($Loco->id, FILTER_VALIDATE_INT)) {
				throw new Exception("An invalid instance of Railpage\\Locos\\Locomotive was supplied");
			}
			
			/**
			 * Lookup this sighting in Sphinx first
			 */
			
			$Config = AppCore::GetConfig(); 
			$SphinxPDO_New = new PDO("mysql:host=" . $Config->Sphinx->Host . ";port=9312"); 
			$lookup = $SphinxPDO_New->prepare("SELECT * FROM idx_sightings WHERE meta.source = :source AND meta.photo_id = :photo_id");
			$lookup->bindValue(":source", "railcam", PDO::PARAM_STR);
			$lookup->bindValue(":photo_id", intval($this->id), PDO::PARAM_INT); 
			$lookup->execute(); 
			
			$id = 0; 
			$loco_ids = [];
			$meta = []; 
			
			/**
			 * If it's in Sphinx then we need to adjust some insert values
			 */
			
			if ($lookup->rowCount() > 0) {
				$row = $lookup->fetchAll(PDO::FETCH_ASSOC); 
				$id = $row[0]['id']; 
				$loco_ids = json_decode($row[0]['loco_ids'], true);
				$meta = json_decode($row[0]['meta'], true);
			}
			
			if (!in_array($Loco->id, $loco_ids)) {
				$loco_ids[] = $Loco->id;
			}
			
			$meta['source'] = "railcam";
			$meta['railcam_id'] = intval($this->Camera->id);
			$meta['photo_id'] = intval($this->id);
			
			/**
			 * Prepare the insert
			 */
			
			$data = [ 
				"timezone" => $this->Camera->timezone,
				"date" => $this->dates['taken']->format("Y-m-d H:i:s"),
				"date_added" => new Zend_Db_Expr("NOW()"),
				"lat" => $this->Camera->lat,
				"lon" => $this->Camera->lon,
				"text" => $this->text,
				"user_id" => $this->User->id,
				"loco_ids" => json_encode($loco_ids),
				"meta" => json_encode($meta)
			];
			
			/**
			 * Guess the train code
			 */
			
			if (preg_match("/([0-9]{1})([a-zA-Z]{2})([0-9]{1})/", $this->title, $matches)) {
				$data['traincode'] = sprintf("%s%s%s", $matches[1], $matches[2], $matches[3]); 
			}
			
			#printArray($data); printArray($id); die;
			
			/**
			 * Insert / update
			 */
			
			if ($id > 0) {
				$where = [ "id = ?" => $id ];
				$this->db->update("sighting", $data, $where);
			} else {
				$this->db->insert("sighting", $data); 
			}
			
			return $this;
			
			$data = array(
				"id" => (int) str_replace(".", "", microtime(true)),
				"railcam_id" => (int) $this->Camera->id,
				"loco_id" => (int) $Loco->id,
				"date" => $this->dates['taken']->format(DateTime::ISO8601),
				"photo_id" => (int) $this->id
			);
			
			#printArray($data);die;
			
			$Sphinx = $this->getSphinx(); 
			
			$Insert = $Sphinx->insert()->into("idx_railcam_locos");
			$Insert->set($data);
			
			$Insert->execute();
			
			return $this;
		}
		
		/**
		 * Get locos tagged in this photo
		 * @since Version 3.9
		 * @return \Railpage\Locos\Locomotive
		 * @yield \Railpage\Locos\Locomotive
		 */
		
		public function yieldLocos() {
			
			$Sphinx = $this->getSphinx(); 
			
			$query = $Sphinx->select("*")
					->from("idx_railcam_locos")
					->where("photo_id", "=", (int) $this->id)
					->where("railcam_id", "=", (int) $this->Camera->id);
			
			$locos = $query->execute();
			
			foreach ($locos as $row) {
				yield new Locomotive($row['loco_id']);
			}
		}
	}
	