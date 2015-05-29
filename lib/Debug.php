<?php
	/**
	 * Railpage site debugging
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	
	class Debug {
		
		/**
		 * Our debug array
		 * @since Version 3.9.1
		 * @var array $log
		 */
		
		private static $log;
		
		/**
		 * Start a debug timer
		 * @since Version 3.9.1
		 * @return float
		 */
		
		public static function startTimer() {
			
			if (!defined("RP_DEBUG") || !RP_DEBUG) {
				return; 
			}
			
			return microtime(true);
		}
		
		/**
		 * Add an entry to the log 
		 * @since Version 3.9.1
		 * @param string $text
		 * @param int|boolean $timer
		 * @return void
		 */
		
		public static function logEvent($text, $timer = false) {
			
			if (!defined("RP_DEBUG") || !RP_DEBUG) {
				return; 
			}
			
			if (filter_var($timer, FILTER_VALIDATE_FLOAT)) {
				$text = sprintf("%s - completed in %ss", round(microtime(true) - $debug_timer_start, 5));
			}
			
			self::$log[] = $text;
			
			// Temporary
			global $site_debug; 
			$site_debug[] = $text;
			
			return;
		}
		
		/**
		 * Get the contents of this log
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function getLog() {
			return self::$log;
		}
	}