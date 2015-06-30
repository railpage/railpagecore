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
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Url;
	use Railpage\ContentUtility;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Entry
	 */
	
	class Entry extends AppCore {
		
		/**
		 * The maximum length allowed for the title/short name
		 * @since Version 3.9.1
		 * @const int SHORT_MAX_CHARS
		 */
		
		const SHORT_MAX_CHARS = 32;
		
		/**
		 * Status: approved
		 * @since Version 3.9
		 * @const int STATUS_APPROVED
		 */
		
		const STATUS_APPROVED = 1;
		
		/**
		 * Status: unapproved / pending
		 * @since Version 3.9
		 * @const int STATUS_UNAPPROVED
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
		 * @param int|string $id
		 * @param string $type
		 */
		
		public function __construct($id = false, $type = false) {
			parent::__construct();
			
			$this->Module = new Module("glossary");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
			} else {
				$query = "SELECT id FROM glossary WHERE slug = ? AND type = ?";
				$this->id = $this->db->fetchOne($query, array($id, $type)); 
			}
			
			if ($this->id = filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("%s.entry=%d", $this->Module->namespace, $this->id);
				
				$this->populate();
			}
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function populate() {
				
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT * FROM glossary WHERE id = ?";
				
				$row = $this->db->fetchRow($query, $this->id);
				
				$this->Memcached->save($this->mckey, $row);
			}
			
			if (!isset($row) || !is_array($row)) {
				return;
			}
			
			$this->name = $row['short'];
			$this->text = $row['full'];
			$this->example = $row['example'];
			$this->Type = new Type($row['type']);
			$this->status = isset($row['status']) ? $row['status'] : self::STATUS_APPROVED;
			$this->slug = $row['slug'];
			
			if ($row['date'] == "0000-00-00 00:00:00") {
				$this->Date = new DateTime;
				$this->commit();
			} else {
				$this->Date = new DateTime($row['date']);
			}
			
			$this->setAuthor(UserFactory::CreateUser($row['author']));
			$this->makeURLs(); 
			$this->makeSlug(); 
			
		}
		
		/**
		 * Make a URL slug
		 * @since Version 3.9.1
		 * @package Railpage
		 * @author Michael Greenhill
		 * @return void
		 */
		
		private function makeSlug() {
			
			if (!empty($this->slug)) {
				return;
			}
			
			$proposal = ContentUtility::generateUrlSlug($this->name, 20);
			
			$query = "SELECT COUNT(id) FROM glossary WHERE slug = ?"; 
			$num = $this->db->fetchOne($query, $proposal); 
			
			if ($num) {
				$proposal .= $num; 
			}
			
			$this->slug = $proposal; 
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->commit(); 
			}
			
			return;
			
		}
		
		/**
		 * Make URLs
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function makeURLs() {
			
			$this->url = new Url(sprintf("%s?mode=entry&id=%d", $this->Module->url, $this->id));
			
			if (!empty($this->slug)) {
				$this->url = new Url(sprintf("%s/%s/%s", $this->Module->url, $this->Type->id, $this->slug)); 
			}
			
			$this->url->edit = sprintf("%s?mode=add&id=%d", $this->Module->url, $this->id);
			$this->url->publish = sprintf("%s?mode=entry.publish&id=%d", $this->Module->url, $this->id);
			$this->url->reject = sprintf("%s?mode=entry.reject&id=%d", $this->Module->url, $this->id);
			
		}
		
		/**
		 * Validate changes to this entry
		 * @since Version 3.8.7
		 * @throws \Exception if $this->name is empty
		 * @throws \Exception if $this->text is empty
		 * @throws \Exception if $this->Type is not an instance of \Railpage\Glossary\Type
		 * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
		 * @todo Entry duplication checking
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
			
			if (strlen($this->name) > self::SHORT_MAX_CHARS) {
				throw new Exception(sprintf("The title of this entry is too long: the maximum allowed is %d", self::SHORT_MAX_CHARS));
			}
			
			$this->makeSlug();
			
			/**
			 * Check if an entry by this title exists elsewhere
			 */
			
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				
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
				"author" => filter_var($this->Author->id, FILTER_VALIDATE_INT),
				"status" => $this->status,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				if (isset($this->mckey) && !empty($this->mckey)) {
					$this->Memcached->delete($this->mckey);
				}
				
				$this->db->update("glossary", $data, $where);
			} else {
				$this->db->insert("glossary", $data);
				$this->id = $this->db->lastInsertId();
				$this->mckey = sprintf("%s.entry=%d", $this->Module->namespace, $this->id);
			}
				
			$this->makeURLs(); 
			
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
		
		/**
		 * Get this as an associative array
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"name" => $this->name,
				"text" => $this->text,
				"example" => $this->example,
				"type" => $this->Type->getArray(),
				"url" => $this->url->getURLs()
			);
		}
	}
	