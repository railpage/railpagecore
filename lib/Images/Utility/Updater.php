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
	use Railpage\Debug;
	use Railpage\Users\Utility\UserUtility;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Images\Image;
	use Railpage\Images\ImageFactory;
	use Railpage\AppCore;
	use Zend_Db_Expr;
	
	class Updater {
		
		/**
		 * Update authors
		 * @since Version 3.9.1
		 * @return void
		 */
		
		public static function updateAuthors() {
			
			$userlookup = array(); 
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT i.id, i.meta, COALESCE(g.owner, 0) AS owner
				FROM image AS i 
					LEFT JOIN gallery_mig_image AS g ON g.id = i.photo_id AND i.provider = 'rpoldgallery'
				WHERE i.user_id = 0 ORDER BY i.id DESC";
			
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
				
				if ($row['provider'] == "rpoldgallery" && $row['owner'] != 0) {
					$data['user_id'] = $row['owner'];
					
					if (is_array($data['meta'])) {
						$data['meta'] = json_decode($data['meta'], true);
					}
					
					$data['meta']['author']['railpage_id'];
					$data['meta'] = json_encode($row['meta']);
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
		 * Update the author for a specific image
		 * @since Version 3.10.0
		 * @param \Railpage\Images\Image $Image
		 * @return \Railpage\Images\Image
		 */
		
		public static function updateAuthor(Image $Image) {
			
			if ($id = UserUtility::findFromFlickrNSID($Image->author->id)) {
				$Image->author->railpage_id = $id;
				$Image->author->User = UserFactory::CreateUser($Image->author->railpage_id);
				$Image->commit(); 
			}
			
			return $Image;
			
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
		
		/**
		 * Fetch the latest information on an album from the relevant provider
		 * @since Version 3.10.0
		 * @param array $album
		 * @return void
		 */
	
		public static function ScrapeAlbum($album) {
			
			Debug::LogCLI("Scraping album ID " . $album['album_id'] . " from provider " . $album['provider']); 
			
			set_time_limit(30);
			
			$Database = AppCore::GetDatabase(); 
			$Provider = ImageUtility::CreateImageProvider($album['provider']); 
			
			// Assume Flickr for now, we can update the internal code later
			$params = [ "photoset_id" => $album['album_id'] ]; 
			$albumdata = $Provider->execute("flickr.photosets.getInfo", $params); 
			
			// Insert this shit into the database
			$data = [
				"scraped" => new Zend_Db_Expr("NOW()"),
				"meta" => json_encode($albumdata['photoset'])
			];
			
			$where = [ "id = ?" => $album['id'] ];
			$Database->update("image_scrape_album", $data, $where); 
			
			// Fetch the photos
			$params['user_id'] = $albumdata['photoset']['owner'];
			$photos = $Provider->execute("flickr.photosets.getPhotos", $params); 
			
			foreach ($photos['photoset']['photo'] as $photo) {
				
				Debug::LogCLI("Scraping photo ID " . $photo['id']); 
				
				set_time_limit(10);
				
				$Image = ImageFactory::CreateImage($photo['id'], $album['provider']);
				
				Debug::LogCLI("Sleeping for 2 seconds..."); 
				
				sleep(2); 
				
			}
			
		}

	}