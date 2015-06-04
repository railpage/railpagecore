<?php
	/**
	 * Date / event object for a locomotive
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use DateTime;
	use Exception;
	use Railpage\Url;
	
	/**
	 * Date class
	 */
	
	class Date extends Locos {
		
		/**
		 * Date ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Date
		 * @var DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Date type
		 * @var string $action
		 */
		
		public $action;
		
		/**
		 * Date type id
		 * @var int $action_id
		 */
		
		public $action_id;
		
		/**
		 * Descriptive text
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Rich descriptive text
		 * @var string $rich_text
		 */
		
		public $rich_text;
		
		/**
		 * Metadata
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Locomotive object
		 * @var Locomotive $Loco
		 */
		
		public $Loco; 
		
		/**
		 * Constructor
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->populate(); 
			}
			
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function populate() {
			
			$update = false;
			
			$row = $this->db->fetchRow("SELECT d.*, dt.* FROM loco_unit_date AS d INNER JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id WHERE d.date_id = ?", $this->id); 
			
			if ($row === false) {
				return;
			}
			
			$this->text = $row['text'];
			$this->rich_text = $row['text'];
			$this->meta = json_decode($row['meta'], true);
			$this->action = $row['loco_date_text'];
			$this->action_id = $row['loco_date_id'];
			
			if ($row['timestamp'] == "0000-00-00") {
				$this->Date = new DateTime();
				$this->Date->setTimestamp($row['date']); 
				
				$update = true;
			} else {
				$this->Date = new DateTime($row['timestamp']); 
			}
			
			/**
			 * Create the rich text entry
			 */
			
			if (count($this->meta)) {
				
				foreach ($this->meta as $key => $data) {
					$this->rich_text .= "\n<strong>" . ucfirst($key) . ": </strong>";
					
					switch ($key) {
						
						case "livery" : 
							
							#$this->rich_text .= "[url=/flickr?tag=railpage:livery=" . $data['id'] . "]" . $data['name'] . "[/url]";
							$this->rich_text .= "<a data-livery-id=\"" . $data['id'] . "\" data-livery-name=\"" . $data['name'] . "\" href='#' class='rp-modal-livery'>" . $data['name'] . "</a>";
						
						break;
						
						case "owner" : 
							
							$Operator = new Operator($data['id']);
							
							$this->rich_text .= "[url=" . $Operator->url_owner . "]" . $Operator->name . "[/url]";
						
						break;
						
						case "operator" :
							
							$Operator = new Operator($data['id']);
							
							$this->rich_text .= "[url=" . $Operator->url_operator . "]" . $Operator->name . "[/url]";
							
						break;
						
						case "position" : 
						
							if (!isset($data['title']) || empty($data['title'])) {
								$data['title'] = "Location";
							}
							
							$this->rich_text .= "<a data-lat=\"" . $data['lat'] . "\" data-lon=\"" . $data['lon'] . "\" data-zoom=\"" . $data['zoom'] . "\" data-title=\"" . $data['title'] . "\" data-toggle='modal' href='#' class='rp-modal-map'>Click to view</a>";
							
						break;
					}
				}
			}
			
			$this->Loco = new Locomotive($row['loco_unit_id']);
			
			$this->url = new Url($this->Loco->url);
			
			/**
			 * Update this object if required
			 */
			
			if ($update) {
				$this->commit(); 
			}
		}
		
		
		/**
		 * Validate changes to this object
		 * @return boolean
		 * @throws \Exception if $this->date is not an instance of \DateTime
		 * @throws \Exception if $this->action_id is empty or not an integer
		 * @throws \Exception if $this->Loco is not an instance of \Railpage\Locos\Locomotive
		 */
		
		public function validate() {
			if (!$this->Date instanceof DateTime) {
				throw new Exception("\$this->Date is not an instance of DateTime");
			}
			
			if (!filter_var($this->action_id)) {
				throw new Exception("\$this->action_id cannot be empty");
			}
			
			if (!$this->Loco instanceof Locomotive) {
				throw new Exception("\$this->Loco is not an instance of Railpage\Locos\Locomotive");
			}
			
			if (!empty($this->meta)) {
				foreach ($this->meta as $k => $v) {
					if (is_array($v)) {
						foreach ($v as $l1k => $l1v) {
							if (is_array($l1v)) {
								foreach ($l1v as $l2k => $l2v) {
									if (empty($this->meta[$k][$l2k])) {
										unset($this->meta[$k][$l2k]);
									}
								}
							}
							
							if (empty($this->meta[$k][$l1k])) {
								unset($this->meta[$k][$l1k]);
							}
						}
					}
					
					if (empty($this->meta[$k])) {
						unset($this->meta[$k]); 
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this locomotive
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Date
		 */
		
		public function commit() {
			$this->validate();
			
			$data = array(
				"loco_unit_id" => $this->Loco->id,
				"loco_date_id" => $this->action_id,
				"date" => $this->Date->getTimestamp(),
				"timestamp" => $this->Date->format("Y-m-d"),
				"text" => $this->text,
				"meta" => json_encode($this->meta)
			);
			
			if (filter_var($this->id)) {
				$where = array(
					"date_id = ?" => $this->id
				);
				
				$this->db->update("loco_unit_date", $data, $where); 
			} else {
				$this->db->insert("loco_unit_date", $data); 
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
	}
	