<?php

/**
 * 500px image provider for Images
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Provider;

use Railpage\Images\Images;
use Railpage\Images\Image;
use Railpage\Images\ProviderInterface;
use Railpage\Users\User;
use Railpage\AppCore;
use Railpage\Url;
use Exception;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

/**
 * Flickr image provider
 */

class FiveHundredPx extends AppCore implements ProviderInterface {
    
    /**
     * Provider name
     * @since Version 3.10.0
     * @const PROVIDER_NAME
     */
    
    const PROVIDER_NAME = "FiveHundredPx";
    
    /**
     * API endpoint
     * @since Version 3.10.0
     * @const string API_ENDPOINT
     */
    
    const API_ENDPOINT = "https://api.500px.com/";
    
    /**
     * Permission: group moderator
     * @since Version 3.10.0
     * @const string ACL_GROUP_ADMIN
     */
    
    const ACL_GROUP_ADMIN = 200;
    
    /**
     * Provider API version
     * @since Version 3.10.0
     * @const string PROVIDER_API_VERSION
     */
    
    const PROVIDER_API_VERSION = "v1";
    
    /**
     * Provider's timestamp format
     * @since Version 3.10.0
     * @const string PROVIDER_TIMESTAMP_FORMAT
     */
    
    const PROVIDER_TIMESTAMP_FORMAT = "Y-m-d\TH:i:sP";
    
    /**
     * OAuth consumer key
     * @since VErsion 3.10.0
     * @var string $oauth_consumer_key
     */
    
    private $oauth_consumer_key;
    
    /**
     * OAuth consumer secret
     * @since Version 3.10.0
     * @var string $oauth_consumer_secret
     */
    
    private $oauth_consumer_secret;
    
    /**
     * The photo data as extracted from Flickr
     * @since Version 3.10.0
     * @var array $photo
     */
    
    private $photo;
    
    /**
     * API response format
     * @since Version 3.10.0
     * @var string $format
     */
    
    public $format = "json";
    
    /**
     * Last error code returned from the provider's API
     * @since Version 3.10.0
     * @var int $errcode
     */
    
    private $errcode;
    
    /**
     * Last error message returned from the provider's API
     * @since Version 3.10.0
     * @var string $errmessage
     */
    
    private $errmessage;
    
    /**
     * GuzzleHTTP Client
     * @since Version 3.10.0
     * @var \GuzzleHttp\Client $Client
     */
    
    private $Client;
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @param array $params
     */
    
    public function __construct($params = false) {
        
        parent::__construct(); 
        
        #if ($params === false) {
            /*
            $Config = AppCore::getConfig();
            
            $params = array(
                "api_key" => $Config->FiveHundredPX->APIKey,
                "api_secret" => $Config->FiveHundredPX->APISecret
            );
            */
            
            // temporary hard code
            $params = [
                "oauth_consumer_key" => "X9hfaNi3y6uJsVjOEz6Ld4lQIY1T8AqpR4mYJvYr",
                "oauth_consumer_secret" => "48ElAgTOSn1lYon4Phs8A1zjXpmy7jCTU4xa0t2o",
                "javascript_sdk_key" => "33339f35f4f5aad73b6380ce54a56cf0e464e076",
                "callback_url" => "http://www.railpage.com.au/endpoint/500px"
            ];
        #}
        
        /*
        $opts = array(
            "oauth_consumer_key" => "api_key",
            "oauth_consumer_secret" => "api_secret"
        );
        
        foreach ($opts as $var => $val) {
            if (!isset($params[$val]) || is_null(filter_var($params[$val], FILTER_SANITIZE_STRING))) {
                $this->$var = NULL;
            } else {
                $this->$var = $params[$val];
            }
        }
        */
        
        #printArray($params); 
        
        foreach ($params as $key => $val) {
            $this->$key = $val; 
        }
        
        $this->Client = new Client;
        
    }
    
    
    /**
     * Set some options for this provider
     * @since Version 3.9.1
     * @param array $options
     * @return \Railpage\Images\Provider\Flickr
     */
    
    public function setOptions($options) {
        
        foreach ($options as $key => $val) {
            
            $this->$key = $val;
        }
        
        return $this;
    }
    
    /**
     * Get the photo from the provider
     * @since Version 3.9
     * @param int $id The ID of the photo from the provider
     * @param boolean $force
     * @return array
     */
    
    public function getImage($id, $force = false) {
        
        $params['image_size'] = "1,2,3,4,5,20,21,30,100,200,440,600,1080,1600,2048";
        
        return $this->execute("photos/" . $id, $params); 
        
    }
    
    /**
     * Save the changes to this photo
     * @since Version 3.9
     * @return self
     * @param \Railpage\Images\Image $Image
     */
    
    public function setImage(Image $Image) {
        
    }
    
    /**
     * Get a list of photos
     * @since Version 3.9
     * @param int $page
     * @param int $items_per_page
     * @return array
     */
    
    public function getImages($page, $items_per_page) {
        
    }
    
    /**
     * Return the name of this provider
     * @since Version 3.9
     * @return string
     */
    
    public function getProviderName() {
        return self::PROVIDER_NAME;
    }
    
    /**
     * Return the context of the supplied photo
     * @since Version 3.9
     * @return array
     */
    
    public function getImageContext(Image $Image) {
        
    }
    
    /**
     * Delete this photo
     * @since Version 3.9.1
     * @return boolean
     * @param \Railpage\Images\Image $Image
     */
    
    public function deleteImage(Image $Image) {
        
    }
    
    /**
     * Fetch a request from Flickr's API
     * @since Version 3.9.1
     * @param string $method
     * @param array $params
     * @return array
     */
    
    public function execute($method = false, $params = array()) {
        
        if (strpos("consumer_key=" . $this->oauth_consumer_key, $method) === false && !isset($params['oauth_consumer_key'])) {
            $params['consumer_key'] = $this->oauth_consumer_key; 
        }
        
        $url = sprintf("%s%s/%s", self::API_ENDPOINT, self::PROVIDER_API_VERSION, $method); 
        
        if (count($params)) {
            $params = http_build_query($params);
            $url = sprintf("%s?%s", $url, $params);
        }
        
        $response = $this->Client->get($url); 
        
        if ($response->getStatusCode() != 200) {
            throw new Exception(sprintf("An unexpected HTTP response code of %s was returned from %s", $response->getStatusCode(), self::PROVIDER_NAME));
        }
        
        $result = $response->json(); 
        
        $result = $this->normaliseResult($result); 
        
        return $result;
        
    }
    
    /**
     * Normalise the result set
     * @since Version 3.10.0
     * @param array $result
     * @return array
     */
    
    private function normaliseResult($result) {
        
        $result = $result['photo'];
        
        $result['title'] = $result['name'];
        $result['sizes'] = $result['images'];
        
        foreach ($result['sizes'] as $key => $size) {
            $result['sizes'][$key] = $this->normaliseSize($size);
        }
        
        $result['author'] = $result['user'];
        $result['location'] = [ 
            "latitude" => $result['latitude'],
            "longitude" => $result['longitude']
        ];
        
        $result['dates'] = [
            "taken" => DateTime::CreateFromFormat(self::PROVIDER_TIMESTAMP_FORMAT, $result['taken_at']),
            "uploaded" => DateTime::CreateFromFormat(self::PROVIDER_TIMESTAMP_FORMAT, $result['created_at'])
        ];
        
        $result['urls']['url'][0]['_content'] = sprintf("https://500px.com/photo/%s", $result['id']); 
        
        return $result;
        
    }
    
    /**
     * Normalise the image size
     * @since Version 3.10.0
     * @param array $size
     * @return array
     */
    
    private function normaliseSize($size) {
        
        $size['source'] = $size['https_url']; 
        
        $key = sprintf("%s:image.size;image=%s", self::PROVIDER_NAME, $size['source']); 
        
        if (!$result = $this->Memcached->fetch($key)) {
            
            $result = getimagesize($size['source']); 
            $this->Memcached->save($key, $result); 
            
        }
        
        $size['width'] = $result[0];
        $size['height'] = $result[1]; 
        
        return $size;
        
    }
    
    /**
     * Make an OAuth URL if we have the required information
     * @since Version 3.9.1
     * @return void
     */
    
    private function configureOAuth() {
        
    }
    
    /**
     * Get error code
     * @since Version 3.9.1
     * @return int
     */
    
    public function getErrorCode() {
        return $this->errcode;
    }
    
    /**
     * Get the last error message
     * @since Version 3.9.1
     * @return string
     */
    
    public function getErrorMessage() {
        return $this->errmessage;
    }
    
    /**
     * Get the EXIF data for this image
     * @since Version 3.10.0
     * @return array
     * @param int $photo_id
     */
    
    public function getExif($photo_id) {
        
        $data = $this->getImage($photo_id); 
        
        // finish this 
        
    }
    
}
