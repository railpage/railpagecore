<?php
	/**
	 * Construct a submenu for a page
	 * @since Version 3.4
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage; 
	
	use Railpage\AppCore;
	use Exception;
	
	/**
	 * Submenu class
	 * @since Version 3.4
	 */
	
	class Submenu extends AppCore {
		
		/**
		 * Menu array
		 * @since Version 3.4
		 * @var array $menu
		 */
		
		public $menu;
		
		/**
		 * Constructor
		 * @since Version 3.4
		 */
		
		public function __construct() {
			// Placeholder - do nothing for now
			
			/**
			 * Record this in the debug log
			 */
			
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
		}
		
		/**
		 * Add a menu grouping
		 * @since Version 3.8.7
		 * @param string $title The title of this submenu category
		 * @param string $subtitle A subtitle to apply to this category
		 */
		
		public function AddGrouping($title = false, $subtitle = NULL) {
			if (!$title) {
				throw new Exception("Cannot add menu grouping - no title given"); 
				return false;
			}
			
			if (!isset($this->menu[$title])) {
				$this->menu[$title] = array(
					"title" => $title,
					"subtitle" => $subtitle,
					"menu" => array()
				);
			}
			
			#$this->section = $title;
			
			return $this;
		}
		
		/**
		 * Set subtitle for a grouping
		 * @since Version 3.8.7
		 * @param string $grouping The title of the grouping/category to change
		 * @param string $subtitle The subtitle to apply to ths grouping/category
		 */
		
		public function SetGroupingSubtitle($grouping = false, $subtitle = false) {
			if ($grouping && $subtitle) {
				if (isset($this->menu[$grouping])) {
					$this->menu[$grouping]['subtitle'] = $subtitle;
				}
			}
		}
		
		/**
		 * Add menu entry
		 * @since Version 3.4
		 * @param string $title The title of the menu item
		 * @param string $url A link to apply
		 * @param string $grouping An optional grouping/category this menu item belongs to
		 * @param array $meta An optional array of parameters to apply to this menu item
		 */
		
		public function Add($title = false, $url = false, $grouping = false, $meta = false) {
			if (!$title) {
				throw new Exception("Cannot add item to menu - no title given"); 
				return $this;
			}
			
			$i = count($this->menu); 
			
			if ($grouping || (isset($this->section) && !empty($this->section))) {
				
				if (isset($this->section) && !empty($this->section)) {
					$grouping = $this->section;
				}
				
				$this->AddGrouping($grouping);
				
				$i = count($this->menu[$grouping]['menu']); 
				
				// Check if this already exists
				foreach ($this->menu[$grouping]['menu'] as $k => $menu) {
					if ($menu['title'] == $title) {
						$i = $k;
						break;
					}
				}
				
				$this->menu[$grouping]['menu'][$i]['title'] = $title; 
				
				if ($url) {
					$this->menu[$grouping]['menu'][$i]['url'] = $url;
				}
				
				if ($meta) {
					$this->menu[$grouping]['menu'][$i]['meta'] = $meta;
				}
			} else {
				$this->menu[$i]['title'] = $title; 
				
				if ($url) {
					$this->menu[$i]['url'] = $url;
				}
				
				if ($meta) {
					$this->menu[$i]['meta'] = $meta;
				}
			}
			
			return $this;
		}
		
		/**
		 * Get the URL for a menu item
		 * @param string $title
		 * @return string
		 */
		
		public function GetURL($title = false) {
			if (!$title) {
				throw new Exception("Cannot return submenu URL - no title provided to look for");
				return false;
			}
			
			foreach ($this->menu as $key => $data) {
				if ($data['title'] == $title) {
					return $data['url'];
				}
			}
			
			return false;
		}
		
		/**
		 * Get menu as HTML
		 * @return string
		 */
		
		public function GetHTML() {
			global $smarty;
			
			#printArray($this->menu);die;
			
			$smarty->assign("submenu", $this->menu);
			return $smarty->fetch(RP_SITE_ROOT . DS . "content" . DS . "inc.submenu.tpl");
		}
		
		/**
		 * Set the section
		 * @since Version 3.8.7
		 * @param string $section
		 */
		
		public function Section($title = NULL) {
			if (isset($this->menu[$title])) {
				$this->section = $title;
			} else {
				$this->section = $title;
				$this->AddGrouping($title);
			}
			
			return $this;
		}
		
		/**
		 * Add a section but don't switch to it
		 * @since Version 3.8.7
		 * @param string $section
		 */
		
		public function AddSection($title = NULL) {
			return $this->AddGrouping($title);
		}
		
		/**
		 * Check if there's anything in this menu
		 * @since Version 3.9
		 * @return boolean
		 */
		
		public function HasItems() {
			foreach ($this->menu as $section) {
				if (isset($section['menu']) && count($section['menu'])) {
					return true;
				}
			}
			
			return false;
		}
	}
	