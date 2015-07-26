<?php
	/**
	 * Update image details
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Utility;
	
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use Railpage\Url;
	use Railpage\Debug;
	use Railpage\Users\Utility\UserUtility;
	use Railpage\Users\Factory as UsersFactory;
	use Railpage\Images\Image;
	use Railpage\Images\ImageFactory;
	use Railpage\AppCore;
	
	class Updater {
		
		/**
		 * Update authors
		 * @since Version 3.9.1
		 * @return void
		 */
		
		public static function updateAuthors() {
			
			$userlookup = array(); 
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT id, meta FROM image WHERE user_id = 0 ORDER BY id DESC";
			
			foreach ($Database->fetchAll($query) as $row) {
				
				$data = [ ];
				$where = [ "id = ?" => $row['id'] ]; 
				
				
				$row['meta'] = json_decode($row['meta'], true); 
				$nsid = $row['meta']['author']['id'];
				
				if (!isset($row['meta']['author']['railpage_id'])) {
					
					if (isset($userlookup[$nsid])) {
						$row['meta']['author']['railpage_id'] = $userlookup[$nsid];
						$data['meta'] = json_encode($row['meta']);
					} elseif ($id = UserUtility::findFromFlickrNSID($row['meta']['author']['id'])) {
						$userlookup[$nsid] = $id;
						$row['meta']['author']['railpage_id'] = $userlookup[$nsid];
						$data['meta'] = json_encode($row['meta']);
					}
					
					if (!isset($row['meta']['author']['railpage_id'])) {
						continue;
					}
					
				}
				
				$data['user_id'] = $row['meta']['author']['railpage_id'];
				
				Debug::LogCLI("Updating author for image ID " . $row['id']);
				
				$Database->update("image", $data, $where); 
				
				continue;
				
				print_r($row['meta']['author']); die;
				
				if (isset($this->author->railpage_id)) {
					$this->author->User = UserFactory::CreateUser($this->author->railpage_id);
					
					if ($this->author->User instanceof User && $this->author->User->id != $row['user_id']) {
						$this->commit(); 
					}
				}
			}
			
		}
		
		/**
		 * Update date taken
		 * @since Version 3.9.1
		 * @return void
		 */
		
		public static function updateCaptureDate() {
			
			$userlookup = array(); 
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT id, meta FROM image WHERE captured IS NULL ORDER BY id DESC LIMIT 0, 10000";
			
			foreach ($Database->fetchAll($query) as $row) {
				
				$DateCaptured = false;
				
				$row['meta'] = json_decode($row['meta'], true);
				
				if (isset($row['meta']['data']['dates']['taken'])) {
					$DateCaptured = new DateTime($row['meta']['data']['dates']['taken']);
				}
				
				if ($DateCaptured) {
					$data = [ "captured" => $DateCaptured->format("Y-m-d H:i:s") ];
					$where = [ "id = ?" => $row['id'] ];
					
					$Database->update("image", $data, $where);
				}
				
			}
			
		}

	}