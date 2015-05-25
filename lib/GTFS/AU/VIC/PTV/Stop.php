<?php
	/**
	 * PTV GTFS stop/place class
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\VIC\PTV;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\StopInterface;
	use Railpage\Place;
	use Railpage\GTFS\StandardStop;
	
	/**
	 * GTFS stop for the AU\PTV GTFS provider
	 * @since Version 3.8.7
	 */
	
	class Stop extends StandardStop {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param mixed $id
		 */
		
		public function __construct($id = false) {
			$this->Provider = new PTV; 
			
			parent::__construct($id);
		}
		
		/**
		 * Get the next departures for this stop
		 * @return array
		 * @param int $limit The number of next departures to show
		 */
		
		public function NextDepartures($limit = 10) {
			$train_params = array(
				"mode" => 0,
				"stop" => $this->id,
				"departures" => "by-destination",
				"limit" => $limit // mode 0 will ignore the limit, and return all trains this day
			);
			
			$vline_params = array(
				"mode" => 3,
				"stop" => $this->id,
				"departures" => "by-destination",
				"limit" => $limit
			);
			
			$departures = $this->formatDepartures($this->Provider->fetch(NULL, $train_params)) + $this->formatDepartures($this->Provider->fetch(NULL, $vline_params));
			ksort($departures);
			$departures = array_slice($departures, 0, $limit, true);
			
			return $departures;
		}
		
		/**
		 * Format the next departures list
		 * @param array $departures
		 * @return array
		 */
		
		public function formatDepartures($departures) {
			$return = array();
			
			if (isset($departures['values'])) {
				foreach ($departures['values'] as $row) {
					if (!in_array(trim($row['platform']['direction']['line']['line_name']), $this->Provider->ignore_routes)) {
						$Date = new DateTime($row['time_timetable_utc']);
						$Date->setTimezone(new DateTimeZone($this->Place->Region->timezone));
						$key = $Date->getTimestamp();
						
						$Now = new DateTime();
						$Now->setTimezone(new DateTimeZone($this->Place->Region->timezone));
						
						$item = array(
							"to" => array(
								"stop_id" => $row['run']['destination_id'],
								"stop_name" => $row['run']['destination_name']
							),
							"leaving" => array(
								"utc" => $row['time_timetable_utc'],
								"local" => $Date->format(DateTime::ISO8601),
								"local_nice" => $Date->format("F j, g:i a"),
								"relative" => time2str($Date->getTimestamp(), $Now->getTimestamp())
							),
							"route" => array(
								"name" => $row['platform']['direction']['line']['line_name'],
								"id" => $row['platform']['direction']['line']['line_id'],
								"number" => $row['platform']['direction']['line']['line_number'],
							),
							"direction" => array(
								"id" => $row['platform']['direction']['direction_id'],
								"name" => $row['platform']['direction']['direction_name']
							)
						);
						
						$return[$key] = $item;
					}
				}
			}
			
			return $return;
		}
	}
	