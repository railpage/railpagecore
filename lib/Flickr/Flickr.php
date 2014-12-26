<?php
	/**
	 * Railpage's Flickr API connector
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage\Flickr;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Http\Client\Adapter\Curl;
	
	require_once(RP_SITE_ROOT . DS . "vendor" . DS . "autoload.php");
	require_once(RP_SITE_ROOT . DS . "vendor" . DS . "zendframework" . DS . "ZendService_Flickr" . DS . "library" . DS . "ZendService" . DS . "Flickr" . DS . "Flickr.php");
	require_once(RP_SITE_ROOT . DS . "vendor" . DS . "zendframework" . DS . "ZendService_Flickr" . DS . "library" . DS . "ZendService" . DS . "Flickr" . DS . "ResultSet.php");
	require_once(RP_SITE_ROOT . DS . "vendor" . DS . "zendframework" . DS . "ZendService_Flickr" . DS . "library" . DS . "ZendService" . DS . "Flickr" . DS . "Result.php");
	require_once(RP_SITE_ROOT . DS . "vendor" . DS . "zendframework" . DS . "ZendService_Flickr" . DS . "library" . DS . "ZendService" . DS . "Flickr" . DS . "Image.php");
	
	use ZendService\Flickr\Flickr as ZendFlickr;
	use ZendService\Flickr\ResultSet as ZendFlickrResultSet;
	use ZendService\Flickr\Result as ZendFlickrResult;
	use ZendService\Flickr\Image as ZendFlickrImage;
	
	/**
	 * Flickr class
	 *
	 * Devel-quality class extending the functionality of \ZendService\ZendFlickr
	 * @since Version 3.8.7
	 */
	
	class Flickr extends ZendFlickr {
		
		/**
		 * Constructor
		 * @param string $api_key Flickr's API key
		 * @param \Zend\Http\Client $adapter An instanceof \Zend\Http\Client to provide to ZendFlickr with settings for Flickr's new SSL requirement for their API
		 */
		
		public function __construct($api_key = NULL, Client $HttpClient = NULL) {
			if ($HttpClient == NULL) {
				$HttpClient = new Client;
				$adapter = new Curl;
				$adapter->setOptions(array(
					"curloptions" => array(
						CURLOPT_SSL_VERIFYPEER => false // Verify peer OR verify host + provide CA file
					)
				));
				
				$HttpClient->setAdapter($adapter);
			}
			
			parent::__construct($api_key, $HttpClient);
		}
	}
?>