<?php
	/**
	 * Functions required for testing
	 */
	
	
	/**
	 * Create a URL slug
	 * @since Version 3.7.5
	 * @param string $string
	 * @return string
	 */
	
	if (!function_exists("create_slug")) {
		function create_slug($string) {
			$find = array(
				"(",
				")",
				"-"
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			$string = str_replace($find, $replace, $string);
				
			$slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($string)));
			return $slug;
		}
	}
	
	/**
	 * Better relative date
	 * From http://stackoverflow.com/questions/2690504/php-producing-relative-date-time-from-timestamps
	 * @since Version 3.7.5
	 * @param string|int $ts
	 * @return string
	 */
	
	if (!function_exists("time2str")) {
		function time2str($ts, $now = false) {
			if(!ctype_digit($ts))
				$ts = strtotime($ts);
			
			if ($now === false) {
				$now = time();
			}
			
			$diff = $now - $ts;
			if($diff == 0)
				return 'now';
			elseif($diff > 0)
			{
				$day_diff = floor($diff / 86400);
				if($day_diff == 0)
				{
					if($diff < 60) return 'just now';
					if($diff < 120) return '1 minute ago';
					if($diff < 3600) return floor($diff / 60) . ' minutes ago';
					if($diff < 7200) return '1 hour ago';
					if($diff < 86400) return floor($diff / 3600) . ' hours ago';
				}
				if($day_diff == 1) return 'Yesterday';
				if($day_diff < 7) return $day_diff . ' days ago';
				if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
				if($day_diff < 60) return 'last month';
				return date('F Y', $ts);
			}
			else
			{
				$diff = abs($diff);
				$day_diff = floor($diff / 86400);
				if($day_diff == 0)
				{
					if($diff < 120) return 'in a minute';
					if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
					if($diff < 7200) return 'in an hour';
					if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
				}
				if($day_diff == 1) return 'Tomorrow';
				if($day_diff < 4) return date('l', $ts);
				if($day_diff < 7 + (7 - date('w'))) return 'next week';
				if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
				if(date('n', $ts) == date('n') + 1) return 'next month';
				return date('F Y', $ts);
			}
		}
	}
	
	