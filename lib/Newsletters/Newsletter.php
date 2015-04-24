<?php
	/**
	 * Newsletter
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Newsletters;
	
	use Exception;
	use DateTime;
	use Railpage\Url;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Notifications\Notification;
	use Railpage\Notifications\Transport\Email;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	
	/**
	 * Newsletter
	 */
	
	class Newsletter extends AppCore {
		
		/**
		 * Status: draft (unsent)
		 * @since Version 3.9.1
		 * @const int STATUS_DRAFT
		 */
		
		const STATUS_DRAFT = 0;
		
		/**
		 * Status: sent
		 * @since Version 3.9.1
		 * @const int STATUS_SENT
		 */
		
		const STATUS_SENT = 1;
		
		/**
		 * ID
		 * @since Version 3.9.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Status
		 * @since Version 3.9.1
		 * @var int $status
		 */
		
		public $status;
		
		/**
		 * Subject
		 * @since Version 3.9.1
		 * @var string $subject
		 */
		
		public $subject;
		
		/**
		 * Template
		 * @since Version 3.9.1
		 * @var string $template
		 */
		
		public $template;
		
		/**
		 * Newsletter text, links, images, etc
		 * @since Version 3.9.1
		 * @var array $content
		 */
		
		public $content;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->load(); 
			}
		}
		
		/**
		 * Load this newsletter from our database
		 * @since Version 3.9.1
		 * @return \Railpage\Newsletters\Newsletter
		 */
		
		private function load() {
			$query = "SELECT * FROM newsletter WHERE id = ?";
			$result = $this->db->fetchRow($query, $this->id); 
			
			$this->subject = $result['subject'];
			$this->status = $result['status'];
			$this->template = (new Newsletters)->getTemplate($result['template_id']);
			$this->content = json_decode($result['content'], true);
			
			$this->makeURLs();
			
			return $this;
		}
		
		/**
		 * Validate this newsletter
		 * @since Version 3.9.1
		 * @return boolean
		 */
		
		private function validate() {
			if (empty($this->subject)) {
				throw new Exception("Newsletter subject cannot be empty");
			}
			
			if (!is_array($this->template)) {
				throw new Exception("Invalid template specified");
			}
			
			if (!is_array($this->content) || count($this->content) === 0) {
				throw new Exception("No newsletter content provided");
			}
			
			if (!filter_var($this->status)) {
				$this->status = self::STATUS_DRAFT;
			}
			
			return true;
		}
		
		/**
		 * Commit this to the database
		 * @since Version 3.9.1
		 * @return \Railpage\Newsletters\Newsletter
		 */
		
		public function commit() {
			$this->validate();
			
			$data = array(
				"subject" => $this->subject,
				"status" => $this->status,
				"template_id" => $this->template['id'],
				"content" => json_encode($this->content)
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("newsletter", $data, $where);
			} else {
				$this->db->insert("newsletter", $data); 
				$this->id = $this->db->lastInsertId();
			}
			
			$this->makeURLs(); 
			
			return $this;
		}
		
		/**
		 * Set the hero image
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Image $Image
		 * @return \Railpage\Newsletters\Newsletter
		 */
		
		public function setHeroImage(Image $Image) {
			$this->content['hero'] = $Image->getArray(); 
			
			return $this;
		}
		
		/**
		 * Get the hero image
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Image
		 */
		
		public function getHeroImage() {
			if (isset($this->content['hero']) && isset($this->content['hero']['id'])) {
				return new Image($this->content['hero']['id']); 
			}
			
			return false;
		}
		
		/**
		 * Get an array of this data
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"status" => array(
					"id" => $this->status,
					"text" => ($this->status == self::STATUS_SENT) ? "Sent" : "Draft"
				),
				"template" => $this->template,
				"subject" => $this->subject,
				"hero" => $this->getHeroImage()->getArray(),
				"content" => $this->content,
				"url" => $this->url->getUrls()
			);
		}
		
		/**
		 * Make our URLs for this object
		 * @since Version 3.9.1
		 * @return \Railpage\Newsletters\Newsletter
		 */
		
		private function makeURLs() {
			$this->url = new Url();
			$this->url->edit = sprintf("/administrators?mode=newsletters.edit&id=%d", $this->id);
			$this->url->preview = sprintf("/administrators?mode=newsletters.preview&id=%d", $this->id);
			$this->url->sendtest = sprintf("/administrators?mode=newsletters.sendtest&id=%d", $this->id);
			
			return $this;
		}
	}
	