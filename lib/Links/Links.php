<?php
	/**
	 * Links management
	 * @package Railpage
	 * @since Version 3.0
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */ 
	 
	namespace Railpage\Links;
	
	use DateTime;
	use Exception;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Module;
	use Zend_Db_Expr;
	 
	/** 
	 * Links base class
	 * @since Version 3.0
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @todo Break this up into objects
	 */ 
	
	class Links extends Appcore {
		
		/**
		 * Get categories
		 */
		
		public function getCategories($parent = 0, $children = true) {
			$parent = intval($parent);
			
			$mckey = "railpage:links.categories.parent=" . $parent . ".children=" . (bool)$children; 
			
			if (!$return = $this->Memcached->fetch($mckey)) {
				#$query	= "SELECT * FROM nuke_links_categories ORDER BY parentid ASC, title ASC";
				$query = "SELECT c.*, p.slug AS parent_slug, CONCAT('/links', COALESCE(CONCAT('/', p.slug, '/', c.slug), CONCAT('/', c.slug))) AS url 
							FROM nuke_links_categories AS c 
							LEFT JOIN nuke_links_categories AS p ON p.cid = c.parentid AND c.parentid != 0 
							ORDER BY p.parentid ASC, c.title ASC";
				
				$params = array(); 
				
				if ($children == false) {
					$query .= " WHERE cid = ?";
					$params[] = $parent;
				} elseif ($parent) {
					$query .= " WHERE parentid = ? OR cid = ?";
					$params[] = $parent;
					$params[] = $parent;
				}
				
				$return = array(); 
				
				if ($children) {
					foreach ($this->db->fetchAll($query, $params) as $row) {
						if (empty($row['slug'])) {
							$row['slug'] = $this->createSlug($row['cid']); 
							$row['url'] = $this->makePermaLink($row['cid']); 
						}
						
						if ($row['parentid'] == 0) {
							$return[$row['cid']] = $row; 
						} else {
							$return[$row['parentid']]['children'][$row['cid']] = $row; 
						}
					}
				} else {
					$return = $this->db->fetchRow($query, $params);
					
					foreach ($return as $row) {
						if (empty($row['slug'])) {
							$row['slug'] = $this->createSlug(); 
							$row['url'] = $this->makePermaLink($row['cid']); 
						}
					}
				}
				
				$this->Memcached->save($mckey, $return, strtotime("+24 hours")); 
				
				return $return;
			}
		}
		
		
		/**
		 *
		 * Get links within a category
		 *
		 */
		
		public function getLinks($category_id = false, $sort = "title", $direction = "ASC") {
			if (!$this->db || !$category_id) {
				return false;
			}
			
			$mckey = "railpage:links.category_id=" . $category_id . ".sort=" . $sort . ".direction=" . $direction;
			
			if (!$return = $this->Memcached->fetch($mckey)) {
				$query	= "SELECT * FROM nuke_links_links WHERE link_approved = 1 AND cid = ? ORDER BY " . $sort . " " . $direction; 
				$params = array(
					$category_id
				);
				
				$return = array(); 
				
				foreach ($this->db->fetchAll($query, $params) as $row) {
					if (stripos($row['url'], "http") === false) {
						$row['url'] = "http://".$row['url']; 
					}
					
					$return[] = $row; 
				}
				
				$this->Memcached->save($mckey, $return, strtotime("+24 hours")); 
				
				return $return;
			}
		}
		
		
		/**
		 *
		 * Get an individual link
		 *
		 */
		 
		public function getLink($id = false) {
			
			$query	= "SELECT * FROM nuke_links_links WHERE lid = ?";
			$return = $this->db->fetchRow($query, $id); 
				
			if (stripos($return['url'], "http") === false) {
				$return['url'] = "http://".$return['url']; 
			}
			
			return $return;
			
		}
		
		/**
		 * Report a broken link
		 */
		 
		public function broken($id = false, $username = false) {
			if (!$id || !$username || !$this->db) {
				return false;
			}
			
			$link = $this->getLink($id); 
			
			$query = "SELECT * FROM nuke_links_modrequest WHERE lid = ?";
			
			if (!$this->db->fetchRow($query, $link['lid'])) {
				$data = array(
					"lid" => $link['lid'],
					"cid" => $link['cid'],
					"sid" => $link['sid'],
					"title" => $link['title'],
					"image" => $link['image'],
					"url" => $link['url'],
					"description" => $link['description'],
					"modifysubmitter" => $username,
					"brokenlink" => 1
				);
				
				return $this->db->insert("nuke_links_modrequest", $data); 
			}
		}
		
		/**
		 * Get newest links
		 */
		 
		public function getNewest($category_id = false, $limit = 20, $start = 0) {
			
			$params = array(); 
			$query = "SELECT l.*, c.title as category_title, c.cdescription as category_description FROM nuke_links_links l, nuke_links_categories c WHERE l.cid = c.cid AND l.link_approved = 1";
			
			if ($category_id) {
				$query .= " AND l.cid = ?";
				$params[] = $category_id;
			}
			
			$query .= " ORDER BY date DESC LIMIT ?, ?";
			$params[] = $start;
			$params[] = $limit;
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $params) as $row) {
				if (stripos($row['url'], "http") === false) {
					$row['url'] = "http://".$row['url']; 
				}
				
				$Category = new Category($row['cid']); 
				
				if ($Category->parent instanceof Category) {
					$row['category_title'] = $Category->parent->name . "\\" . $row['category_title']; 
				}
				
				$return[] = $row; 
			}
			
			return $return;
			
		}
		
		/**
		 * Get pending links
		 */
		 
		public function getPending() {
			$query = "SELECT * FROM nuke_links_links WHERE link_approved = 0";
			
			return $this->db->fetchAll($query);
		}
		
		
		/**
		 * Reject a pending link
		 */
		 
		public function reject($id = false) {
			$where = array(
				"lid = ?" => $id
			);
			
			$this->db->delete("nuke_links_links", $where); 
			
			return $this;
		}
		
		/**
		 * Approve a pending link
		 * @since Version 3.0.1
		 * @version 3.9
		 * @param int $id
		 * @return boolean
		 */
		
		public function approve($id = false) { 
			$data = array(
				"link_approved" => "1"
			);
			
			$where = array(
				"lid = ?" => $id
			);
			
			$this->db->update("nuke_links_links", $data, $where);
			
			return $this;
		}
		
		/**
		 * Generate the URL slug
		 * @since Version 3.7.5
		 * @param int $category_id
		 * @return string
		 */
		
		public function createSlug($category_id = false) {
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
				
			// Assume ZendDB
			$find = array(
				"(",
				")",
				"-"
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			if ($category_id) {
				$title = $this->db->fetchOne("SELECT title FROM nuke_links_categories WHERE cid = ?", $category_id); 
			} elseif (isset($this->title) && !empty($this->title)) {
				$title = $this->title;
				$category_id = $this->id;
			} else {
				return false;
			}
			
			$name = str_replace($find, $replace, $title);
			$proposal = create_slug($name);
			
			/**
			 * Trim it if the slug is too long
			 */
			
			if (strlen($proposal) >= 256) {
				$proposal = substr($poposal, 0, 200); 
			}
			
			/**
			 * Check that we haven't used this slug already
			 */
			
			$result = $this->db->fetchAll("SELECT cid FROM nuke_links_categories WHERE slug = ? AND cid != ?", array($proposal, $category_id)); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			if (isset($this->slug)) {
				$this->slug = $proposal;
			}
			
			/**
			 * Add this slug to the database
			 */
			
			$data = array(
				"slug" => $proposal
			);
			
			$where = array(
				"cid = ?" => $category_id
			);
			
			$rs = $this->db->update("nuke_links_categories", $data, $where); 
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Zend_DB: FAILED create url slug for link category ID " . $category_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				} else {
					$site_debug[] = "Zend_DB: SUCCESS create url slug for link category ID " . $category_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				}
			}
			
			/**
			 * Return it
			 */
			
			return $proposal;
		}
		
		/**
		 * Make a permalink
		 * @since Version 3.7.5
		 * @return string
		 */
		
		public function makePermaLink($entity = false) {
			if (!$entity) {
				return false;
			}
			
			if (filter_var($entity, FILTER_VALIDATE_INT)) {
				$row = $this->db->fetchRow("SELECT slug, parentid FROM nuke_links_categories WHERE cid = ?", $entity); 
				
				if ($row === false || empty($row['slug'])) {
					$row['slug'] = $this->createSlug($entity); 
				}
				
				if (intval($row['parentid']) > 0) {
					$slug = $this->db->fetchOne("SELECT slug FROM nuke_links_categories WHERE cid = ?", $row['parentid']) . "/" . $row['slug']; 
				} else {
					$slug = $row['slug']; 
				}
			} else {
				$slug = $entity;
			}
			
			$permalink = "/links/" . $slug; 
			
			return $permalink;
		}
	}
	