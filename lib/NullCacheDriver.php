<?php
	/**
	 * Null cache driver
	 * When a Doctrine cache provider cannot be reached use this class instead to return false for all operations
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage;
	
	/**
	 * Null cache driver
	 */
	
	class NullCacheDriver {
		
		public function fetch($id) {
			return false;
		}
		
		public function contains($id) {
			return false;
		}
		
		public function save($id, $data, $lifeTime = false) {
			return false;
		}
		
		public function delete($id) {
			return false;
		}
	}