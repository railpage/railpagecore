<?php
	/**
	 * Miscellaneous colours and shit formatter
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage\Formatting;
	
	use Railpage\ContentUtility;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Url;
	use phpQuery;
	use DateTime;
	use Exception;
	use InvalidArgumentException;
	use Error;
	use DOMElement;
	
	/**
	 * Multimedia formatter
	 */
	
	class ColourUtility {
		
		/**
		 * Generate a HEX colour value from a given string
		 * @since Version 3.10.0
		 * @param string $string
		 * @return string
		 */
		
		public static function String2Hex($string) {
			
			return "#" . strrev(substr(dechex(crc32($string)), -6));
			
		}
		
		
	}
