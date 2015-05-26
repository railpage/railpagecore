<?php
	/**
	 * Skeleton RP config
	 * @since Version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	if (file_exists("/srv/railpage.com.au/www/public_html/config/config.railpage.php")) {
		require_once("/srv/railpage.com.au/www/public_html/config/config.railpage.php"); 
	} else {
		$RailpageConfig = array(
			"Yahoo" => array(
				"ApplicationID" => "wFe9XhLV34Gy7xHCL94iD2.KhfmKHo9WsTH1.7XmXwbTtRN9DQQbI8HOq3_Mdu7FXTA-",
				"ApplicationEntryPoint" => "http://www.railpage.com.au"
			),
			"Flickr" => array(
				"APIKey" => "e828abc6d87d4d99aa611177e13e1965"
			)
		);
		
		$RailpageConfig = json_decode(json_encode($RailpageConfig));
		
		$RailpageConfig->Memcached = new stdClass;
		$RailpageConfig->Memcached->Host = defined("RP_MEMCACHE_HOST") ? RP_MEMCACHE_HOST : "127.0.0.1";
		$RailpageConfig->Memcached->Port = defined("RP_MEMCACHE_PORT") ? RP_MEMCACHE_PORT : 11211;
	}