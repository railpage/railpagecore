<?php
	/**
	 * Old Gallery1-migrated module
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Gallery;
	
	use Exception;
	use DateTime;
	use Railpage\Users\User;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\AppCore;
	
	/**
	 * Gallery
	 */
	
	class Gallery extends AppCore {
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			
			parent::__construct();
			
			$this->Module = new Module("Gallery");
			
		}
		
		/**
		 * List the albums available
		 * @since Version 3.8.7
		 * @yield \Railpage\Gallery\Album
		 */
		
		public function getAlbums() {
			$query = "SELECT id FROM gallery_mig_album WHERE parent_id = ? ORDER BY title";
			
			foreach ($this->db->fetchAll($query, '0') as $album) {
				yield new Album($album['id']);
			}
		}
		
		/**
		 * Find album for a given user
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $User
		 * @return \Railpage\Gallery\Album
		 */
		
		public function getUserAlbum(User $User) {
			$query = "SELECT id FROM gallery_mig_album WHERE parent_id = ? AND owner = ?";
			
			$id = $this->db->fetchOne($query, array(2805, $User->id));
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				return new Album($id);
			}
			
			return false;
		}
	}
?>