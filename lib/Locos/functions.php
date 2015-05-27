<?php
	/**
	 * Loco module functions
	 * @since Version 3.2
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	/**
	 * Format the gauge string to make it look nicer
	 * @since Version 3.2
	 * @param string $gauge
	 * @return string
	 */
	
	function format_gauge($gauge = false) {
		if (!$gauge) {
			return NULL;
		}
		
		preg_match("/(?<=\()(.+)(?=\))/is", $gauge, $matches); 
		
		$keys = array_keys($matches);
		$lastkey = array_pop($keys); 
		// Throws a PHP Strict error if you try to combine the above into array_pop(array_keys());
		unset($keys); 
		
		if (isset($matches[$lastkey])) {
			$str = trim(str_replace("(".$matches[$lastkey].")", "", $gauge));
			$str = $str."<span style='display:block;margin-top:-8px;margin-bottom:-4px;' class='gensmall'>".$matches[$lastkey]."</span>";
		} else {
			$str = $gauge; 
		}
		
		return $str;
	}
	