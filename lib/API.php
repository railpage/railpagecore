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
use InvalidArgumentException;
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
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $apiFormat
     * @param string $apiEndpoint
     */
    
    public function __construct($apiKey = null, $apiSecret = null, $apiFormat = "json", $apiEndpoint = "https://api.railpage.com.au") {
        if (is_null($apiKey)) { 
            throw new Exception("Cannot instantiate " . __CLASS__ . " because no API key was provided");
        }
        
        if (is_null($api_secret)) {
            throw new Exception("Cannot instantiate " . __CLASS__ . " because no API secret was provided");
        }
        
        // Set some default values
        $this->api_key = $apiKey;
        $this->api_secret = $apiSecret;
        $this->format   = $apiFormat;
        $this->endpoint = $apiEndpoint;
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
    
    public function send($method = null, $args = null) {
        if (is_null($method)) {
            throw new InvalidArgumentException("No API method specified"); 
        }
    
        if (RP_DEBUG) {
            global $site_debug;
            $debug_timer_start = microtime(true);
        }
        
        $args['method']     = $method;
        $args['format']     = $this->format;
        
        /*
        // Only add these if authentication is required, which for the most part here, it won't be.
        if (!empty($this->token) && !empty($this->token_secret)) {
            $args['oauth_consumer_key'] = $this->api_key;
            $args['oauth_nonce']        = nonce;
            $args['oauth_signature_method'] = $this->signature_method;
            $args['oauth_timestamp']    = time();
            $args['oauth_version']      = "1.0";
        
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
    
    public function request_url($method = null, $args = null) {
        if (is_null($method)) {
            throw new InvalidArgumentException("No API method specified");
        }
        
        $args['method']     = $method;
        $args['format']     = $this->format;
        
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
            }
            
            return $this->jsonError(json_last_error());
        }
        
        return false;
    }
    
    /**
     * Return our API response in the case of a JSON error
     * Re-factored because, apparently, long functions are "complex" and "too hard to read" and "waaaaaahhh"
     * @since Version 3.9.1
     * @return array
     */
    
    private function jsonError($lasterror = NULL) {
        if (is_null($lasterror)) {
            $lasterror = json_last_error();
        }
        
        return array("stat" => "error", "error" => json_last_error_msg());
    }
    
    /**
     * New and updated GET method
     * @since Version 3.6
     * @param string $method
     * @param array $args
     * @return array
     */
    
    public function Get($method = null, $args = null) {
    
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
