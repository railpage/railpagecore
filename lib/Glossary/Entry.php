<?php
	/**
	 * Glossary item / entry
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Glossary;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Url;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Entry
	 */
	
	class Entry extends AppCore {
		
		/**
		 * Status: approved
		 * @since Version 3.9
		 * @const STATUS_APPROVED
		 */
		
		const STATUS_APPROVED = 1;
		
		/**
		 * Status: unapproved / pending
		 * @since Version 3.9
		 * @const STATUS_UNAPPROVED
		 */
		
		const STATUS_UNAPPROVED = 0;
		
		/**
		 * Glossary entry ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Glossary entry short name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Glossary entry long name
		 * @since Version 3.8.7
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * An example use of this glossary item
		 * @since Version 3.8.7
		 * @var string $example
		 */
		
		public $example;
		
		/**
		 * Status (approved / pending)
		 * @since Version 3.9
		 * @var int $status;
		 */
		
		public $status;
		
		/**
		 * Glossary entry type
		 * @since Version 3.8.7
		 * @var \Railpage\Glossary\Type $Type
		 */
		
		public $Type;
		
		/**
		 * Date added to database
		 * @since Version 3.8.7
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Author
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $Author
		 */
		
		public $Author;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct();
			
			$this->Module = new Module("glossary");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->mckey = sprintf("%s.entry=%d", $this->Module->namespace, $this->id);
				
				deleteMemcacheObject($this->mckey);
				
				if ($row = getMemcacheObject($this->mckey)) {
					
				} else {
					$query = "SELECT type, short, full, example, date, author, status FROM glossary WHERE id = ?";
					
					$row = $this->db->fetchRow($query, $this->id);
					
					setMemcacheObject($this->mckey, $row);
				}
				
				if (isset($row) && is_array($row)) {
					$this->name = $row['short'];
					$this->text = $row['full'];
					$this->example = $row['example'];
					$this->Type = new Type($row['type']);
					$this->status = isset($row['status']) ? $row['status'] : self::STATUS_APPROVED;
					
					if ($row['date'] == "0000-00-00 00:00:00") {
						$this->Date = new DateTime;
						$this->commit();
					} else {
						$this->Date = new DateTime($row['date']);
					}
					
					$this->Author = new User($row['author']);
					
					$this->url = new Url(sprintf("%s?mode=entry&id=%d", $this->Module->url, $this->id));
					$this->url->edit = sprintf("%s?mode=add&id=%d", $this->Module->url, $this->id);
					$this->url->publish = sprintf("%s?mode=entry.publish&id=%d", $this->Module->url, $this->id);
					$this->url->reject = sprintf("%s?mode=entry.reject&id=%d", $this->Module->url, $this->id);
				}
			}
		}
		
		/**
		 * Validate changes to this entry
		 * @since Version 3.8.7
		 * @throws \Exception if $this->name is empty
		 * @throws \Exception if $this->text is empty
		 * @throws \Exception if $this->Type is not an instance of \Railpage\Glossary\Type
		 * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
		 */
		
		private function validate() {
			if (empty($this->name)) {
				throw new Exception("Entry name cannot be empty");
			}
			
			if (empty($this->text)) {
				throw new Exception("Entry text cannot be empty");
			}
			
			if (!$this->Type instanceof Type) {
				throw new Exception("Entry type is invalid");
			}
			
			if (is_null($this->example)) {
				$this->example = "";
			}
			
			if (!$this->Date instanceof DateTime) {
				$this->Date = new DateTime;
			}
			
			if (!$this->Author instanceof User) {
				throw new Exception("No author given for glossary entry");
			}
			
			if (empty($this->status) || !filter_var($this->status, FILTER_VALIDATE_INT)) {
				$this->status = self::STATUS_UNAPPROVED;
			}
			
			return true;
		}
		
		/**
		 * Set the author of this glossary entry
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function setAuthor(User $User) {
			$this->Author = $User;
			
			return $this;
		}
		
		/**
		 * Commit changes to this entry
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"type" => $this->Type->id,
				"short" => $this->name,
				"full" => $this->text,
				"example" => $this->example,
				"date" => $this->Date->format("Y-m-d H:i:s"),
				"author" => $this->Author->id,
				"status" => $this->status
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				deleteMemcacheObject($this->mckey);
				
				$this->db->update("glossary", $data, $where);
			} else {
				$this->db->insert("glossary", $data);
				$this->id = $this->db->lastInsertId();
				$this->url = sprintf("%s?mode=entry.view&id=%d", $this->Module->url, $this->id);
			}
			
			return $this;
		}
		
		/**
		 * Publish / approve this glossary entry
		 * @since Version 3.9
		 * @return $this
		 */
		
		public function approve() {
			$this->status = self::STATUS_APPROVED;
			$this->commit(); 
			
			return $this;
		}
		
		/**
		 * Reject this glossary entry
		 * @since Version 3.9
		 * @return boolean
		 */
		
		public function reject() {
			$where = array(
				"id = ?" => $this->id
			);
			
			$this->db->delete("glossary", $where);
			
			return true;
		}
	}