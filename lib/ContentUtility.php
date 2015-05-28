<?php
	/**
	 * A series of text formatting utilities to increase code decoupling
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\Url;
	use Exception;
	use InvalidArgumentException;
	
	class ContentUtility {
		
		/**
		 * Format URL slugs for consistency
		 * @since Version 3.9.1
		 * @param string $text
		 * @param int $maxlength
		 * @return string
		 */
		
		static public function generateUrlSlug($text, $maxlength = 200) {
			$find = array(
				"(",
				")",
				"-",
				"?",
				"!",
				"#",
				"$",
				"%",
				"^",
				"&",
				"*",
				"+",
				"=",
				"'",
				"\""
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			$text = str_replace($find, $replace, strtolower(trim($text)));
				
			$text = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($text)));
			$text = substr($text, 0, $maxlength); 
			
			if (substr($text, -1) === "-") {
				$text = substr($text, 0, -1);
			}
			
			return $text;
		}
	}