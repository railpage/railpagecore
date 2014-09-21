<?php
	/**
	 * TFNSW GTFS stop/place class
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\TFNSW;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\StopInterface;
	use Railpage\Place;
	
	/**
	 * GTFS stop for the AU\TFNSW GTFS provider
	 * @since Version 3.8.7
	 */
	
	class Stop implements StopInterface {
		
		/**
		 * Stop ID
		 * @var mixed $id
		 */
		
		public $id;
		
		/**
		 * Stop name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Stop code
		 * @var string $code
		 */
		
		public $code;
		
		/**
		 * Latitude
		 * @var double $lat
		 */
		
		public $lat;
		
		/**
		 * Longitude
		 * @var double $lon
		 */
		
		public $lon;
		
		/**
		 * Wheelchair boarding
		 * @var boolean $wheelchair_boarding
		 */
		
		public $wheelchair_boarding;
		
		/**
		 * Place object
		 * @var \Railpage\Place $Place
		 */
		
		public $Place;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param mixed $id
		 */
		
		public function __construct($id = false) {
			$this->Provider = new TFNSW; 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$result = $this->Provider->adapter->query("SELECT * FROM au_syd_stops WHERE stop_id = '" . $id . "' AND location_type = 1", Adapter::QUERY_MODE_EXECUTE); 
				
				if ($result) {
					foreach ($result as $row) {
						$row = $row->getArrayCopy();
						
						$this->id = $row['stop_id'];
						$this->name = $row['stop_name'];
						$this->code = $row['stop_code'];
						$this->lat = $row['stop_lat'];
						$this->lon = $row['stop_lon'];
						$this->wheelchair_boarding = (bool) $row['wheelchair_boarding'];
						
						$this->Place = new Place($this->lat, $this->lon);
					}
				}
			}
		}
		
		/**
		 * Get the next departures for this stop
		 * @return array
		 */
		
		public function NextDepartures() {
			
		}
	}
?>