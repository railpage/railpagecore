<?php
	/**
	 * Asset
	 *
	 * Assets are non-Flickr web resources (pages, images, videos, whatever) that can be associated against a locomotive, loco class, location - anything. As of v3.8.7 only locos and loco classes are supported. 
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Assets; 
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Exception;
	
	/**
	 * Asset class
	 *
	 * Assets are non-Flickr web resources (pages, images, videos, whatever) that can be associated against a locomotive, loco class, location - anything. As of v3.8.7 only locos and loco classes are supported. 
	 * @todo Extend usage of this class beyond the Locos module
	 */
	
	class Asset extends AppCore {
		
		/**
		 * Asset ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Asset hash
		 * @since Version 3.8.7
		 * @var string $hash The unique identifier of this asset, for this asset has been linked to multiple places on Railpage
		 */
		
		public $hash;
		
		/**
		 * Asset namespace
		 * @since Version 3.8.7
		 * @var string $namespace The namespace this asset applies to 
		 */
		
		public $namespace;
		
		/**
		 * Asset namespace key
		 * @since Version 3.8.7
		 * @var int $namespace_key The namespace key (eg loco_id) that this asset applies to
		 */
		
		public $namespace_key;
		
		/**
		 * Asset type ID
		 * @since Version 3.8.7
		 * @var int $type_id Asset type - photo, video, etc
		 */
		
		public $type_id;
		
		/**
		 * Asset meta data
		 * @since Version 3.8.7
		 * @var array $meta Metadata for this asset, eg image, thumbnail, icon, etc
		 */
		
		public $meta;
		
		/**
		 * Asset date added
		 * @since Version 3.8.7
		 * @var \DateTime $date The date that this asset was added to the database
		 */
		
		public $Date;
		
		/**
		 * Asset user ID
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $User The user that added this asset
		 */
		
		public $User;
		
		/**
		 * Asset links
		 * @since Version 3.8.7
		 * @var array $instances An array of instances of this asset - eg when it's been used on a locomotive and a locomotive class
		 */
		
		public $instances;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				if ($row = $this->db->fetchRow("SELECT * FROM asset WHERE id = ?", $this->id)) {
					$this->hash = $row['hash'];
					$this->type_id = $row['type_id']; 
					$this->meta = json_decode($row['meta'], true);
					
					if (function_exists("get_domain")) {
						$this->meta['domain'] = get_domain($this->meta['url']);
					}
					
					$this->url = "/assets?id=" . $this->id;
					
					foreach ($this->meta as $key => $val) {
						if (is_string($val)) {
							$this->meta[$key] = trim($val); 
						}
						
						if (is_array($val)) {
							foreach ($val as $k => $v) {
								if (is_string($v)) {
									$this->meta[$key][$k] = trim($v);
								}
							}
						}
					}
					
					/**
					 * Update the unique hash if we need to
					 */
					
					if (empty($this->hash)) {
						$data = array(
							"hash" => md5($this->meta['url'])
						);
						
						$where = array(
							"id = ?" => $this->id
						);
						
						$this->db->update("asset", $data, $where);
					}
					
					/**
					 * Get uses/instances from the database
					 */
					
					foreach ($this->db->fetchAll("SELECT * FROM asset_link WHERE asset_id = ? ORDER BY date DESC", $this->id) as $row) {
						$this->instances[$row['asset_link_id']] = $row;
						
						$this->Date = new \DateTime($row['date']);
						$this->User = new \Railpage\Users\User($row['user_id']);
					}
				}
			}
		}
		
		/**
		 * Validate changes to this asset
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws \Exception if $this->namespace is empty
		 * @throws \Exception if $this->namespace_key is empty
		 * @throws \Exception if $this->User is not an instance of \Railpage\Users\User
		 */
		
		private function validate() {
			if (empty($this->namespace)) {
				throw new Exception("Cannot validate changes to this asset - $this->namespace cannot be empty");
			}
			
			if (empty($this->namespace_key)) {
				throw new Exception("Cannot validate changes to this asset - $this->namespace key cannot be empty");
			}
			
			if (!$this->User instanceof User) {
				throw new Exception("Cannot validate changes to this asset - $this->User must be an instanceof of \Railpage\Users\User");
			}
			
			return true;
		}
		
		/**
		 * Save changes to this asset
		 *
		 * Creates a new asset or saves changes to an existing one as required
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate();
			
			$this->hash = md5($this->meta['url']); 
			
			$data = array(
				"hash" => $this->hash,
				"type_id" => $this->type_id,
				"meta" => json_encode($this->meta)
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				return $this->db->update("asset", $data, $where);
			} else {
				
				/**
				 * Check if the hash already exists in the database. If it does, we're just adding a new link to an existing asset
				 */
				 
				$asset_id = $this->db->fetchOne("SELECT id FROM asset WHERE hash = ?", md5($this->meta['url'])); 
				
				if (filter_var($asset_id) && $asset_id > 0) {
					// Asset exists - just populate the ID of this object
					$this->id = $asset_id;
				} else {
					// Can't find the asset, so go ahead and create one
					$this->db->insert("asset", $data);
					$this->id = $this->db->lastInsertId(); 
				}
					
				/**
				 * Insert the link
				 */
				
				$data = array(
					"namespace" => $this->namespace,
					"namespace_key" => $this->namespace_key,
					"asset_id" => $this->id,
					"user_id" => $this->User->id
				);
				
				$this->db->insert("asset_link", $data);
			}
			
			return true; // throws an exception if any SQl queries fail
		}
	}
?>