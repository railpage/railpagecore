<?php
	/**
	 * A series of text formatting utilities to increase code decoupling
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\Url;
	use DateTime;
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
		
		/**
		 * Take a DateTime instance, or unix timestamp, and convert it to a relative time (eg x minutes ago)
		 * @since Version 3.9.1
		 * @return string
		 * @param \DateTime|int $timestamp
		 * @param \DateTime|int $now
		 * @param string $format
		 */
		
		static public function relativeTime($timestamp, $now = false, $format = false) {
			
			if ($timestamp instanceof DateTime) {
				$timestamp = $timestamp->getTimestamp(); 
			}
			
			if ($now instanceof DateTime) {
				$now = $now->getTimestamp(); 
			}
			
			if (!filter_var($now, FILTER_VALIDATE_INT)) {
				$now = time();
			}
			
			$diff = $now - $timestamp;
		
			if ($diff < 60) {
				return sprintf($diff > 1 ? '%s seconds ago' : 'a second ago', $diff);
			}
		
			$diff = floor($diff / 60);
		
			if ($diff < 60) {
				return sprintf($diff > 1 ? '%s minutes ago' : 'one minute ago', $diff);
			}
		
			$diff = floor($diff / 60);
		
			if ($diff < 24) {
				return sprintf($diff > 1 ? '%s hours ago' : 'an hour ago', $diff);
			}
		
			$diff = floor($diff / 24);
		
			if ($diff < 7) {
				return sprintf($diff > 1 ? '%s days ago' : 'yesterday', $diff);
			}
		
			if ($diff < 30) {
				$diff = floor($diff / 7);
				return sprintf($diff > 1 ? '%s weeks ago' : 'one week ago', $diff);
			}
		
			$diff = floor($diff / 30);
		
			if ($diff < 12) {
				return sprintf($diff > 1 ? '%s months ago' : 'last month', $diff);
			}
		
			$diff = date('Y', $now) - date('Y', $date);
		
			return sprintf($diff > 1 ? '%s years ago' : 'last year', $diff);
			
			/*
			if ($timestamp instanceof DateTime) {
				$timestamp = $timestamp->getTimestamp(); 
			}
			
			if (!ctype_digit($timestamp)) {
				$timestamp = strtotime($timestamp);
			}
			
			if ($now === false) {
				$now = time();
			}
			
			$diff = $now - $timestamp;
			
			if ($diff === 0) {
				return 'now';
			}
			
			if ($diff > 0) {
				$day_diff = floor($diff / 86400);
				if ($day_diff == 0) {
					if ($diff < 60) return 'just now';
					if ($diff < 120) return 'a moment ago';
					if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
					if ($diff < 7200) return '1 hour ago';
					if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
				}
				
				if ($format) {
					return date($format, $timestamp);
				} 
				
				if ($day_diff == 1) return 'Yesterday';
				if ($day_diff < 7) return $day_diff . ' days ago';
				if ($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
				if ($day_diff < 60) return 'last month';
				return date('F Y', $timestamp);
			}
			
			$diff = abs($diff);
			$day_diff = floor($diff / 86400);
			
			if ($day_diff == 0) {
				if ($diff < 120) return 'in a minute';
				if ($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
				if ($diff < 7200) return 'in an hour';
				if ($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
			}
			
			if ($day_diff == 1) return 'Tomorrow';
			if ($day_diff < 4) return date('l', $timestamp);
			if ($day_diff < 7 + (7 - date('w'))) return 'next week';
			if (ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
			if (date('n', $timestamp) == date('n') + 1) return 'next month';
			return date('F Y', $timestamp);
			*/
		}
	}