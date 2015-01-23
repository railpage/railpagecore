<?php
	/**
	 * Transport for WA GTFS interface
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\TransPerth;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	use Railpage\GTFS\StandardProvider;
	
	/**
	 * TransPerth class
	 */
	
	class TransPerth extends StandardProvider {
		
		/**
		 * Timetable data source
		 * @var string $provider
		 */
		
		public $provider = "TransPerth";
		
		/**
		 * Timetable data source as a constant
		 * @const string PROVIDER_NAME
		 * @since Version 3.9
		 */
		
		const PROVIDER_NAME = "TransPerth";
		
		/**
		 * Database table prefix
		 * @since Version 3.9
		 * @const string DB_PREFIX
		 */
		
		const DB_PREFIX = "au_wa";
		
	}
?>