<?php
	/**
	 * Site statistics interface
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Statistics;
	
	/**
	 * Stats interface
	 * @since Version 3.8.7
	 */
	
	interface StatsInterface {
		
		/**
		 * Get a statistic
		 * @param int $stat_id
		 * @return array
		 */
		
		public function getStatistic($stat_id); 
		
		
	}
	