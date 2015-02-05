<?php
	/**
	 * Railpage API - End user implementation
	 * @package Railpage_API
	 * @since Version 1.0
	 * @version 3.8.7
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage;
	
	use Exception;
	use DateTime;
	use GuzzleHttp\Client;
	
	/**
	 * Base Railpage API
	 * @since Version 3.2
	 */
	
	class API {
		
		/**
		 * API endpoint
		 * @since Version 1.0
		 * @var string $endpoint
		 */
		
		public $endpoint;
		
		/**
		 * API key
		 * @since Version 1.0
		 * @var string $api_key
		 */
		
		public $api_key;
		
		/**
		 * API secret
		 * @since Version 1.0
		 * @var string $api_secret
		 */
		
		public $api_secret;
		
		/**
		 * Data format
		 * @since Version 1.0
		 * @var string $format
		 */
		
		public $format;
		
		/**
		 * Response from the server
		 * @since Version 1.0
		 * @var string $response
		 */
		
		public $response;
		
		/**
		 * Signature method
		 * @since Version 1.0
		 * @var string $signature_method
		 */
		
		public $signature_method;
		
		/**
		 * HTTP delivery - GET or POST
		 * @since Version 1.0
		 * @var string $http_delivery
		 */
		
		public $http_delivery;
		
		/** 
		 * Constructor
		 * @since Version 1.0
		 * @version 1.0
		 * @param string $api_key
		 * @param string $api_secret
		 * @param string $api_format
		 * @param string $api_endpoint
		 */
		
		public function __construct($api_key = false, $api_secret = false, $api_format = "json", $api_endpoint = "https://arjan.railpage.com.au") {
			if (!$api_key) { 
				throw new Exception("Cannot instantiate " . __CLASS__ . " because no API key was provided");
			}
			
			if (!$api_secret) {
				throw new Exception("Cannot instantiate " . __CLASS__ . " because no API secret was provided");
			}
			
			// Set some default values
			$this->api_key = $api_key;
			$this->api_secret = $api_secret;
			$this->format 	= $api_format;
			$this->endpoint	= $api_endpoint;
			$this->signature_method = "HMAC-SHA1";
			$this->http_delivery = "POST";
			
			$this->GuzzleClient = new Client;
		}
		
		/**
		 * Send a request to the API
		 * @since Version 1.0
		 * @version 1.0
		 * @input string $method
		 * @input array $args
		 * @return boolean
		 * @todo Finish OAuth hooks
		 */
		
		public function send($method = false, $args) {
			if (!$method) {
				return false;
			}
		
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$args['method']		= $method;
			$args['format']		= $this->format;
			
			/*
			// Only add these if authentication is required, which for the most part here, it won't be.
			if (!empty($this->token) && !empty($this->token_secret)) {
				$args['oauth_consumer_key'] = $this->api_key;
				$args['oauth_nonce']		= nonce;
				$args['oauth_signature_method']	= $this->signature_method;
				$args['oauth_timestamp']	= time();
				$args['oauth_version']		= "1.0";
			
				$signature = $this->http_delivery."&".$this->endpoint."/?".urlencode(http_build_query($args));
				
				$signature_key = urlencode(utf8_encode($this->api_secret))."&".urlencode(utf8_encode($this->token_secret));
				printArray($signature_key); die;
			}
			
			// Send the API key. The API will generate the API secret and try to match them up
			if (!empty($this->api_key)) {
				$args['api_key'] = $this->api_key;
			}
			*/
			
			// Start sending the data to the API
			$curl = curl_init($this->endpoint);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			// Get the response
			$response = curl_exec($curl);
			
			if ($response === false) {
				$error = curl_error($curl); 
				
				$response = array("stat" => "error", "error" => "cURL error: ".$error); 
			}
			
			#printArray($this->endpoint."?".http_build_query($args));die;
			
			// Close the connection
			curl_close($curl);
			
			// Save the response
			$this->response = $response;
		
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "(" . $method . ") completed in " . number_format(microtime(true) - $debug_timer_start, 8) . "s";
			}
		}
		
		/**
		 * Generate a GET url for the request
		 * @since Version 1.0
		 * @return string
		 * @param string $method
		 * @param array $args
		 */
		
		public function request_url($method = false, $args) {
			if (!$method) {
				return false;
			}
			
			$args['method']		= $method;
			$args['format']		= $this->format;
			
			// Send the API key. The API will generate the API secret and try to match them up
			if (!empty($this->api_key)) {
				$args['api_key'] = $this->api_key;
			}
			
			return sprintf("%s/?%s", $this->endpoint, http_build_query($args));
		}
		
		
		/**
		 * Return the response in a useable format
		 * @since Version 1.0
		 * @version 1.0
		 * @return array
		 */
		
		public function format() {
			if (empty($this->response)) {
				return false;
			}
			
			if ($this->format == "json") {
				$response = json_decode($this->response, true);
				
				if ($response) {
					return $response; 
				} else {
					switch (json_last_error()) {
						case JSON_ERROR_NONE:
							$error = 'No errors';
						break;
						case JSON_ERROR_DEPTH:
							$error = 'Maximum stack depth exceeded';
						break;
						case JSON_ERROR_STATE_MISMATCH:
							$error = 'Underflow or the modes mismatch';
						break;
						case JSON_ERROR_CTRL_CHAR:
							$error = 'Unexpected control character found';
						break;
						case JSON_ERROR_SYNTAX:
							$error = 'Syntax error, malformed JSON';
						break;
						case JSON_ERROR_UTF8:
							$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
						default:
							$error = 'Unknown error';
						break;
					}
					
					return array("stat" => "error", "error" => $error);
				}
			} else {
				return false;
			}
		}
		
		/**
		 * New and updated GET method
		 * @since Version 3.6
		 * @param string $method
		 * @param array $args
		 * @return array
		 */
		
		public function Get($method = false, $args = false) {
		
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$request_url = $this->request_url($method, $args);
			
			$return = array(); 
			
			$response = $this->GuzzleClient->get($request_url);
			
			if (!$response->getStatusCode() == 200) {
				throw new Exception(sprintf("Failed to execute API call: HTTP error %s", $response->getStatusCode()));
			}
			
			$return['stat'] = "ok";
					
			$this->response = $response->getBody();
			
			$return = array_merge($return, $this->format()); 
		
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "(" . $method . "::" . implode(", ", $args) . ") completed in " . number_format(microtime(true) - $debug_timer_start, 8) . "s";
			}
			
			return $return;
		}
	}
?>