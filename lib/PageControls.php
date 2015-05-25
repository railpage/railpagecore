<?php
	/** 
	 * PageControls menu class
	 * @since Version 3.8
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage; 
	
	define("PAGECONTROL_TYPE_CONTROL", 1); 
	define("PAGECONTROL_TYPE_BUTTON", 2);
	
	/**
	 * PageControls class
	 */
	
	class PageControls extends AppCore {
		
		/**
		 * Page controls
		 * @var array $controls
		 */
		
		public $controls = array();
		
		/**
		 * Page controls type
		 * Buttons or control bar
		 * @since Version 3.8
		 * @var int $type
		 */
		
		public $type;
		
		/**
		 * Constructor
		 * @param int $type
		 */
		
		public function __construct($type = PAGECONTROL_TYPE_CONTROL) {
			$this->type = $type; 
			
			try {
				parent::__construct(); 
			} catch (Exception $e) {
				throw new \Exception($e->getMessage()); 
			}
		}
		
		/**
		 * Return as a string
		 * @since Version 3.8.6
		 * @return string
		 */
		
		public function __toString() {
			return $this->generate();
		}
		
		/**
		 * Add a page control
		 * @param array $args
		 * @return boolean
		 */
		
		public function addControl($args = false) {
			if (is_array($args)) {
				if (isset($args['href']) && isset($args['text'])) {
					$this->controls[] = $args;
					
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		/**
		 * Remove a control
		 * @param string $url
		 * @param string $text
		 * @return boolean
		 */
		
		public function removeControl($url = false, $text = false) {
			if ($url) {
				foreach ($this->controls as $id => $args) {
					if (!$text && $url == $args['href']) {
						unset($this->controls[$id]);
						return true;
					} elseif ($text && $text == $args['text'] && $url == $args['href']) {
						unset($this->controls[$id]);
						return true;
					}
				}
			}
			
			return false;
		}
		
		/**
		 * Generate HTML
		 * @return string
		 */
		
		public function generate() {
			$string = '';
			
			global $handheld;
			
			foreach ($this->controls as $control) {
				$string .= "<a href=\"" . $control['href'] . "\"";
				
				if (isset($control['title'])) {
					$string .= " title=\"" . $control['title'] . "\"";
				}
				
				if (isset($control['rel'])) {
					$string .= " rel=\"" . $control['rel'] . "\"";
				}
				
				if (isset($control['id'])) {
					$string .= " id=\"" . $control['id'] . "\"";
				}
				
				if (isset($control['other']) && is_array($control['other'])) {
					foreach ($control['other'] as $attr => $val) {
						$string .= " " . $attr . "=\"" . addslashes($val) . "\"";
					}
				}
				
				if (isset($control['data']) && is_array($control['data'])) {
					foreach ($control['data'] as $attr => $val) {
						$string .= " data-" . $attr . "=\"" . addslashes($val) . "\"";
					}
				}
				
				/**
				 * Add the required classes to this control
				 */
				
				if (!isset($control['class'])) {
					$control['class'] = "";
				}
					
				switch ($this->type) {
				
					case PAGECONTROL_TYPE_BUTTON :
						
						$control['class'] .= " btn btn-small";
						
					break;
					
					default : 
						
						$control['class'] .= " control";
					
					break;
				}
				
				$string .= " class=\"" . $control['class'] . "\">";
				
				if (!$handheld && isset($control['class']) && !empty($control['class'])) {
					#$string .= "<span class='icon'></span>";
				}
				
				if (!$handheld && isset($control['glyphicon']) && !empty($control['glyphicon'])) {
					$string .= "<span class='glyphicon " . $control['glyphicon'] . "'></span>&nbsp;";
				}
				
				$string .= $control['text'] . "</a>";
			}
			
			/**
			 * Assemble the controls into a string
			 */
			
			if (!empty($string)) {
				
				switch ($this->type) {
					
					case PAGECONTROL_TYPE_BUTTON :
						
						return "<div style='padding: 6px;background:#fff;width:100%;' class='btn-group'>" . $string . "</div>";
						
					break;
					
					default : 
						
						return "<div id='page-controls'>" . $string . "</div>";
						
					break;
				}
				
			} else {
				return $string;
			}
		}
	}
	
	