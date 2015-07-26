<?php
	/**
	 * Forums factory
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Forums;
	
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Registry;
	use Exception;
	use InvalidArgumentException;
	
	class ForumsFactory {
		
		/**
		 * The registry
		 * @since Version 3.9.1
		 * @var \Railpage\Registry $Registry
		 */
		
		public static $Registry;
		
		/**
		 * Get stuff from the registry
		 * @since Version 3.9.1
		 * @param string $key
		 * @return mixed
		 */
		
		private static function load($key) {
			
			if (!self::$Registry instanceof Registry) {
				self::$Registry = Registry::getInstance();
			}
			
			try {
				return self::$Registry->get($key);
			} catch (Exception $e) {
				return false;
			}
			
		}
		
		/**
		 * Get an new instance of a forum category
		 * @since Version 3.9.1
		 * @return \Railpage\Forums\Category
		 * @param int $cat_id
		 */
		
		public static function CreateCategory($cat_id) {
			
			$key = sprintf("railpage:forums.category=%d", $cat_id); 
			
			if (!$Category = self::load($key)) {
				$Category = new Category($cat_id); 
				self::$Registry->set($key, $Category); 
			}
			
			return $Category;
			
		}
		
		/**
		 * Get an new instance of a forum
		 * @since Version 3.9.1
		 * @return \Railpage\Forums\Forum
		 * @param int $forum_id
		 */
		
		public static function CreateForum($forum_id) {
			
			$key = sprintf("railpage:forums.forum=%d", $forum_id); 
			
			if (!$Forum = self::load($key)) {
				$Forum = new Forum($forum_id); 
				self::$Registry->set($key, $Forum); 
			}
			
			return $Forum;
			
		}
		
		/**
		 * Get an new instance of the forum index
		 * @since Version 3.9.1
		 * @return \Railpage\Forums\Index
		 */
		
		public static function CreateIndex() {
			
			$key = sprintf("railpage:forums.index"); 
			
			if (!$Index = self::load($key)) {
				$Index = new Index($cat_id); 
				self::$Registry->set($key, $Index); 
			}
			
			return $Index;
			
		}
		
		/**
		 * Get an new instance of a forum post
		 * @since Version 3.9.1
		 * @return \Railpage\Forums\Post
		 * @param int $post_id
		 */
		
		public static function CreatePost($post_id) {
			
			$key = sprintf("railpage:forums.post=%d", $post_id); 
			
			if (!$Index = self::load($key)) {
				$Post = new Post($post_id); 
				self::$Registry->set($key, $Index); 
			}
			
			return $Post;
			
		}
		
	}