<?php

/**
 * Flickr image provider for Images
 * @since Version 3.9
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

#use flickr_railpage;

/**
 * Flickr image provider
 */

class Flickr extends AppCore implements ProviderInterface {
    
    /**
     * Provider name
     * @since Version 3.9
     * @const PROVIDER_NAME
     */
    
    const PROVIDER_NAME = "Flickr";
    
    /**
     * API endpoint
     * @since Version 3.9.1
     * @const string API_ENDPOINT
     */
    
    const API_ENDPOINT = "https://api.flickr.com/services/rest/";
    
    /**
     * Permission: group moderator
     * @since Version 3.9.1
     * @const string ACL_GROUP_ADMIN
     */
    
    const ACL_GROUP_ADMIN = 200;
    
    /**
     * Flickr OAuth token
     * @since Version 3.9
     * @var string $oauth_token
     */
    
    private $oauth_token;
    
    /**
     * Flickr OAuth secret
     * @since Version 3.9
     * @var string $oauth_secret
     */
    
    private $oauth_secret;
    
    /**
     * OAuth consumer key
     * @since VErsion 3.9.1
     * @var string $oauth_consumer_key
     */
    
    private $oauth_consumer_key;
    
    /**
     * OAuth consumer secret
     * @since Version 3.9.1
     * @var string $oauth_consumer_secret
     */
    
    private $oauth_consumer_secret;
    
    /**
     * Object representing the connection to Flickr
     * @since Version 3.9
     * @var \flickr_railpage $cn
     */
    
    public $cn;
    
    /**
     * The photo data as extracted from Flickr
     * @since Version 3.9
     * @var array $photo
     */
    
    private $photo;
    
    /**
     * API response format
     * @since Version 3.9.1
     * @var string $format
     */
    
    public $format = "json";
    
    /**
     * Last error code returned from the provider's API
     * @since Version 3.9.1
     * @var int $errcode
     */
    
    private $errcode;
    
    /**
     * Last error message returned from the provider's API
     * @since Version 3.9.1
     * @var string $errmessage
     */
    
    private $errmessage;
    
    /**
     * GuzzleHTTP Client
     * @since Version 3.9.1
     * @var \GuzzleHttp\Client $Client
     */
    
    private $Client;
    
    /**
     * Constructor
     * @since Version 3.9
     * @param array $params
     */
    
    public function __construct($params = null) {
        
        parent::__construct(); 
        
        if (!is_array($params)) {
            $Config = AppCore::getConfig();
            
            $params = array(
                "api_key" => $Config->Flickr->APIKey,
                "api_secret" => $Config->Flickr->APISecret
            );
        }
        
        $opts = array(
            "oauth_token" => "oauth_token",
            "oauth_secret" => "oauth_secret",
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
        
        /*
        if (!is_null($this->oauth_consumer_key)) {
            $this->cn = new flickr_railpage($this->oauth_consumer_key);
            $this->cn->cache = false;
            
            if (!is_null($this->oauth_token) && !is_null($this->oauth_secret)) {
                $this->cn->oauth_token = $this->oauth_token;
                $this->cn->oauth_secret = $this->oauth_secret;
            }
        }
        */
        
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
     * @param boolean|null $force
     * @return array
     */
    
    public function getImage($id, $force = 0) {
        
        $mckey = sprintf("railpage:image.provider=%s;image=%d", self::PROVIDER_NAME, $id);
        
        $force = (bool) $force;
        
        if ($force) {
            $this->Memcached->delete($mckey); 
        }
        
        if ($force !== true && $this->photo = $this->Memcached->fetch($mckey)) {
            return $this->photo;
        }
        
        $return = array(); 
        
        if ($return = $this->execute("flickr.photos.getInfo", array("photo_id" => $id))) {
            $return['photo']['sizes'] = $this->execute("flickr.photos.getSizes", array("photo_id" => $id));
            $return['id'] = $id;
        }
        
        if (empty($return) || !$return) {
            throw new Exception(sprintf("Unable to fetch data from %s: %s (%d)", self::PROVIDER_NAME, $this->getErrorMessage(), $this->getErrorCode()));
        }
        
        /**
         * Transform Flickr's result into our standard data format
         */
        
        $this->photo = self::constructPhotoArray($return); 
        
        /*
         * Check if the author is on Railpage
         */
        
        $query = "SELECT user_id FROM nuke_users WHERE flickr_nsid = ?";
        
        if ($tmp_user_id = $this->db->fetchOne($query, $this->photo['author']['id'])) {
            $this->photo['author']['railpage_id'] = $tmp_user_id;
        }
        
        $this->Memcached->save($mckey, $this->photo, strtotime("+2 days"));
        
        return $this->photo;
        
    }
    
    /**
     * Construct the photo data array
     * @since Version 3.10.0
     * @param array $return
     * @return array
     */
    
    private static function constructPhotoArray($return) {
        
        $data = [
            "provider" => self::PROVIDER_NAME,
            "id" => $return['id'],
            "dates" => array(
                "taken" => new DateTime($return['photo']['dates']['taken']),
                "uploaded" => isset($return['photo']['dateuploaded']) ? new DateTime(sprintf("@%s", $return['photo']['dateuploaded'])) : new DateTime($return['photo']['dates']['taken']),
                "updated" => isset($return['photo']['dates']['lastupdate']) ? new DateTime(sprintf("@%s", $return['photo']['dates']['lastupdate'])) : new DateTime($return['photo']['dates']['taken'])
            ),
            "author" => array(
                "id" => $return['photo']['owner']['nsid'],
                "username" => $return['photo']['owner']['username'],
                "realname" => $return['photo']['owner']['realname'],
                "url" => new Url(sprintf("https://www.flickr.com/photos/%s", $return['photo']['owner']['nsid']))
            ),
            "title" => $return['photo']['title'],
            "description" => $return['photo']['description'],
            "tags" => $return['photo']['tags']['tag'],
            "sizes" => $return['photo']['sizes'],
            "urls" => $return['photo']['urls'],
        ];
        
        if (isset($return['photo']['location'])) {
            $data['location'] = $return['photo']['location'];
        }
        
        return $data;
        
    }
    
    /**
     * Save the changes to this photo
     * @since Version 3.9
     * @return self
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function setImage(Image $imageObject) {
        
        /** 
         * Flush Memcache
         */
        
        $mckey = sprintf("railpage:image.provider=%s;image=%d", self::PROVIDER_NAME, $imageObject->id);
        
        /**
         * Check if the title and/or description have changed
         */
        
        if ($imageObject->title != $this->photo['title'] || $imageObject->description != $this->photo['description']) {
            $result = $this->cn->photos_setMeta($imageObject->id, $imageObject->title, $imageObject->description);
            
            $this->photo['title'] = $imageObject->title;
            $this->photo['description'] = $imageObject->description;
            
            if (!$result) {
                throw new Exception(sprintf("Could not update photo. The error returned from %s is: (%d) %s", self::PROVIDER_NAME, $this->cn->getErrorCode(), $this->cn->getErrorMsg()));
            }
        }
        
        $this->Memcached->save($mckey, $this->photo, strtotime("+2 days"));
        
        return $this;
    }
    
    /**
     * Get a list of images
     * @since Version 3.9.1
     * @param int $page
     * @param int $itemsPerPage
     * @return array
     */
    
    public function getImages($page, $itemsPerPage) {
        
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
    
    public function getImageContext(Image $imageObject) {
        #$rs = $this->cn->photos_getContext($imageObject->id);
        $rs = $this->execute("flickr.photos.getContext", array("photo_id" => $imageObject->id));
        
        $return = array(
            "previous" => false,
            "next" => false
        );
        
        if (isset($rs['prevphoto']) && is_array($rs['prevphoto'])) {
            $return['previous'] = array(
                "id" => $rs['prevphoto']['id'],
                "title" => isset($rs['prevphoto']['title']) ? $rs['prevphoto']['title'] : "Untitled"
            );
        }
        
        if (isset($rs['nextphoto']) && is_array($rs['nextphoto'])) {
            $return['next'] = array(
                "id" => $rs['nextphoto']['id'],
                "title" => isset($rs['nextphoto']['title']) ? $rs['nextphoto']['title'] : "Untitled"
            );
        }
        
        return $return;
    }
    
    /**
     * Delete this photo
     * @since Version 3.9.1
     * @return boolean
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function deleteImage(Image $imageObject) {
        #return $this->cn->photos_delete($imageObject->id);
        
        if (is_null(filter_var($imageObject->photo_id, FILTER_SANITIZE_STRING))) {
            throw new InvalidArgumentException("The supplied instance of Railpage\\Images\\Image does not provide a valid photo ID");
        }
        
        $result = $this->execute("flickr.photos.delete", array("photo_id" => $imageObject->photo_id));
        
        if ($result['stat'] == "ok") {
            return true;
        }
        
        return false;
    }
    
    /**
     * Fetch a request from Flickr's API
     * @since Version 3.9.1
     * @param string $method
     * @param array $params
     * @return array
     */
    
    public function execute($method = null, $params = array()) {
        
        if (is_null(filter_var($method, FILTER_SANITIZE_STRING))) {
            throw new InvalidArgumentException("Flickr API call failed: no API method requested"); 
        }
        
        /**
         * Build our query string
         */
        
        $params['method'] = $method;
        $params['api_key'] = $this->oauth_consumer_key;
        $params['format'] = $this->format;
        
        if ($params['format'] === "json") {
            $params['nojsoncallback'] = "1";
        }
        
        $params = http_build_query($params);
        $url = sprintf("%s?%s", self::API_ENDPOINT, $params);
        
        /**
         * Oauth handling
         */
        
        $this->configureOAuth();
        
        /**
         * Fetch the API request
         */
        
        $response = $this->Client->get($url); 
        
        if ($response->getStatusCode() != 200) {
            throw new Exception(sprintf("An unexpected HTTP response code of %s was returned from %s", $response->getStatusCode(), self::PROVIDER_NAME));
        }
        
        $result = $response->json(); 
        
        if ($result['stat'] != "ok") {
            $this->errcode = $result['code'];
            $this->errmessage = $result['message'];
            
            return false;
        }
        
        $result = $this->normaliseContent($result);
        $result = $this->normaliseSizes($result);
        
        return $result;
        
    }
    
    /**
     * Make an OAuth URL if we have the required information
     * @since Version 3.9.1
     * @return void
     */
    
    private function configureOAuth() {
        
        #printArray($this->oauth_consumer_key); 
        #printArray($this->oauth_consumer_secret); 
        #printArray($this->oauth_secret);
        #printArray($this->oauth_token);die;
        
        if (!is_null($this->oauth_token) && !is_null($this->oauth_secret) && !is_null($this->oauth_consumer_key) && !is_null($this->oauth_consumer_secret)) {
            $oauth = new Oauth1(array(
                "consumer_key" => $this->oauth_consumer_key,
                "consumer_secret" => $this->oauth_consumer_secret,
                "token" => $this->oauth_token,
                "token_secret" => $this->oauth_secret
            ));
        
            $this->Client = new Client(array(
                'defaults' => array('auth' => 'oauth')
            ));
            
            $this->Client->getEmitter()->attach($oauth);
        }
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
     * Normalise the array content
     * @since Version 3.9.1
     * @return array
     * @param array $array
     */
    
    private function normaliseContent($array) {
        foreach ($array as $key => $val) {
            if (is_array($val) && count($val) === 1 && isset($val['_content'])) {
                $array[$key] = $val['_content'];
            } elseif (is_array($val)) {
                $array[$key] = $this->normaliseContent($val); 
            }
        }
        
        return $array;
    }
    
    /**
     * Normalise the image sizes
     * @since Version 3.9.1
     * @return array
     * @param array $result
     */
    
    private function normaliseSizes($result) {
        if (isset($result['sizes']['size']) && is_array($result['sizes']['size'])) {
            return $result['sizes']['size'];
        }
        
        return $result;
    }
    
    /**
     * Check if a user has permission to do...
     * @since Version 3.9.1
     * @param int $perm
     * @return boolean
     */
    
    public function can($perm) {
        if (!filter_var($perm, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        if (!$this->User instanceof User) {
            throw new InvalidArgumentException("Cannot lookup permissions for " . __CLASS__ . ": no valid user has been set"); 
        }
        
        switch ($perm) {
            case self::ACL_GROUP_ADMIN :
                return $this->isGroupAdmin(); 
                break;
                
        }
        
    }
    
    /**
     * Is this user an administrator of the Flickr group
     * @since Version 3.9.1
     * @return boolean
     */
    
    private function isGroupAdmin() {
        $Config = AppCore::getConfig(); 
        
        $params = [ 
            "group_id" => $Config->Flickr->GroupID, 
            "membertypes" => "3,4", 
            "per_page" => 100, 
            "page" => 1 
        ];
        
        $members = $this->execute("flickr.groups.members.getList", $params);
        
        if (!$members) {
            throw new Exception(sprintf("Could not fetch members of Flickr group %s: %s (%d)", $params[0], $this->getErrorMessage(), $this->getErrorCode())); 
        }
            
        foreach ($members['members']['member'] as $row) {
            if ($row['nsid'] == $this->User->flickr_nsid) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the EXIF data for this image
     * @since Version 3.10.0
     * @return array
     * @param int $photoId
     */
    
    public function getExif($photoId) {
        
        $data = [ "photo_id" => $photoId ];
        
        $raw = $this->execute("flickr.photos.getExif", $data);
        $raw = $raw['photo']['exif']; 
        
        $exif = [];
        
        foreach ($raw as $row) {
            $processed_val = self::processExif($row); 
            
            if ($processed_val) {
                $exif[key($processed_val)] = current($processed_val);
            }
        }
        
        ksort($exif);
        
        return $exif;
        
    }
    
    /**
     * Process a row of EXIF data
     * @since Version 3.10.0
     * @param array $row
     * @return array
     */
    
    private static function processExif($row) {
        
        $key = $row['label'];
        
        $column_lookup = [
            "Make" => "camera_make",
            "Model" => "camera_model",
            "Exposure Program" => "exposure_program",
        ];
        
        if (!isset($column_lookup[$key])) {
            $column_lookup[$key] = str_replace(" ", "_", strtolower($key));
        }
        
        switch ($key) {
            
            case "Software" :
            case "ISO Speed" :
            case "Aperture" :
            case "Exposure" :  
            case "Exposure Program" :
            case "White Balance" :
            case "Lens Model" :
            case "Lens Serial Number" : 
            case "Make" :
                return [ $column_lookup[$key] => $row['raw'] ];
                break;
            
            case "Model" :
                return [ $column_lookup[$key] => preg_replace("/(CANON|Canon|NIKON|NIKON CORPORATION) /", "", $row['raw']) ];
                break;
            
            case "Focal Length" :
                return [ $column_lookup[$key] => round(str_replace(" mm", "", $row['raw'])) ];
                break;
                
        }
        
    }
}
