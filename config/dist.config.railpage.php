<?php
	/**
	 * Railpage config
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	if (!defined("RP_WRITE_CONFIG")) {
		define("RP_WRITE_CONFIG", false); // Stop it from updating all. the. fucking. time
	}
	
	/**
	 * Update the config JSON object
	 * @since Version 3.8.7
	 */
	
	function setRailpageConfig($config = NULL) {
		if (!is_object($config)) {
			throw new Exception("Cannot set Railpage config to JSON file - parameter \$config is invalid");
		}
		
		$config = json_encode($config, JSON_PRETTY_PRINT);
		
		if (RP_WRITE_CONFIG && json_encode(getRailpageConfig(), JSON_PRETTY_PRINT) != $config) {
			
			if (!file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . "config.railpage.json", $config)) {
				throw new Exception("Cannot write to " . __DIR__ . DIRECTORY_SEPARATOR . "config.railpage.json");
			}
		}
		
		return true;
	}
	
	/**
	 * Get the config JSON object
	 * @since Version 3.8.7
	 * @return \stdClass
	 */
	
	function getRailpageConfig() {
		return json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "config.railpage.json"));
	}
	
	
	/**
	 * Set some general RP configuration options
	 * New configuration method as of 30/10/2013
	 */
	
	$RailpageConfig = new stdClass; 
	$RailpageConfig->SessionEngine = "php"; // php | memcached | apc
	$RailpageConfig->SiteName = "Railpage";
	$RailpageConfig->AvatarMaxWidth = 128;
	$RailpageConfig->AvatarMaxHeight = 128;
	$RailpageConfig->AvatarMaxSize = 131027; // 128kb
	$RailpageConfig->DefaultTheme = "jiffy_simple";
	$RailpageConfig->HTTPHost = defined("RP_HOST") ? RP_HOST : $_SERVER['HTTP_HOST'];
	$RailpageConfig->HTTPStaticHost = "static.railpage.com.au";
	$RailpageConfig->TwitterName = "@railpage";
	$RailpageConfig->OpenGraphMapWidth = 640;
	$RailpageConfig->OpenGraphMapHeight = 400;
	$RailpageConfig->AdHeader = "<p class='help-block rp-ad-header'><a href='/blog/advertising'>Sponsored advertisement</a></p>";
	$RailpageConfig->test = "1234";
	
	// API
	$RailpageConfig->API = new stdClass;
	$RailpageConfig->API->Key = "getyourown";
	$RailpageConfig->API->Secret = "apisecretz0r";
	$RailpageConfig->API->Endpoint = "https://arjan.railpage.com.au";
	
	// Language
	$RailpageConfig->Language = new stdClass;
	$RailpageConfig->Language->ISO6391 = "en";
	$RailpageConfig->Language->ISO6392 = "eng";
	
	// Website
	$RailpageConfig->Web = new stdClass; 
	$RailpageConfig->Web->SubDirectory = "";
	$RailpageConfig->Web->URL = "//" . $RailpageConfig->HTTPHost . $RailpageConfig->Web->SubDirectory;
	
	// Google APIs
	$RailpageConfig->Google = new stdClass; 
	$RailpageConfig->Google->API_Key = "goaway"; // https://code.google.com/apis/console/b/0/?noredirect&pli=1#project:241978833607:access
	$RailpageConfig->Google->Maps = new stdClass(); 
	$RailpageConfig->Google->Maps->Styles = array(
		array(
			"featureType" => "transit.line",
			"stylers" => array(
				array(
					"color" => "#010155"
				),
				array(
					"visibility" => "on",
				),
				array(
					"weight" => 3
				)
			)
		)
	);
	$RailpageConfig->Google->Maps->Icons = array(
		"location" => "//" . $RailpageConfig->HTTPStaticHost . "/i/icons/maps/location.png",
		"train" => "//" . $RailpageConfig->HTTPStaticHost . "/i/icons/maps/steamtrain.png",
		"photo" => "//" . $RailpageConfig->HTTPStaticHost . "/i/icons/maps/photo.png",
		"station" => "//" . $RailpageConfig->HTTPStaticHost . "/i/icons/maps/train.png",
	);
	$RailpageConfig->Google->OAuth = new stdClass;
	$RailpageConfig->Google->OAuth->ClientID = "BADPANDA";
	$RailpageConfig->Google->OAuth->ClientSecret = "z";
	$RailpageConfig->Google->OAuth->RedirectURI = sprintf("https://%s/oauth2callback", $RailpageConfig->HTTPHost);
	
	// Ban control
	$RailpageConfig->Ban = new stdClass; 
	$RailpageConfig->Ban->DefaultBanDuration = "+1 month";
	
	// Forums
	$RailpageConfig->Forums = new stdClass; 
	$RailpageConfig->Forums->ShowBSQuota = false; // Do we want to show per-user BS Quotas or not? Hint: this is negatively impacting page generation times
	$RailpageConfig->Forums->HerringCutoff = 20; // Hide forum posts with a herring number equal to (or greater than)...
	
	// Railcams
	$RailpageConfig->Railcams = new stdClass; 
	$RailpageConfig->Railcams->SendPhotoToFlickrPool = false; // Send photos to the RP Flickr pool when they've been tagged. Hint: this can flood the pool with sub-par photos.
	
	// Facebook
	$RailpageConfig->Facebook = new stdClass; 
	$RailpageConfig->Facebook->AppID = "329687420408130";
	
	// Yahoo APIs
	$RailpageConfig->Yahoo = new stdClass; 
	$RailpageConfig->Yahoo->ApplicationID = "wFe9XhLV34Gy7xHCL94iD2.KhfmKHo9WsTH1.7XmXwbTtRN9DQQbI8HOq3_Mdu7FXTA-";
	$RailpageConfig->Yahoo->ApplicationEntryPoint = "http://www.railpage.com.au";
	
	/**
	 * Flickr config
	 */
	
	$RailpageConfig->Flickr = new stdClass;
	$RailpageConfig->Flickr->GroupID = "297283@N25";
	$RailpageConfig->Flickr->APIKey = "xxxx";
	$RailpageConfig->Flickr->APISecret = "xxxx";
	
	// StopForumSpam.com API
	$RailpageConfig->SFS = array(
		"http://www.railpage.com.au" => "xxxx",
		"http://dev.railpage.com.au" => "xxxx"
	);
	
	// Downloads module / Uploads
	$RailpageConfig->Uploads = new stdClass;
	$RailpageConfig->Uploads->MaxSize = 209715200; // 200mb
	$RailpageConfig->Uploads->Directory = RP_SITE_ROOT . DS . "uploads" . DS;
	
	/**
	 * Graphite
	 */
	
	$RailpageConfig->Graphite = new stdClass;
	$RailpageConfig->Graphite->Host = "http://cesium.railpage.org:8080";
	
	/**
	 * Memcached
	 */
	
	$RailpageConfig->Memcached = new stdClass;
	$RailpageConfig->Memcached->Host = defined("RP_MEMCACHE_HOST") ? RP_MEMCACHE_HOST : "xxxx";
	$RailpageConfig->Memcached->Port = defined("RP_MEMCACHE_PORT") ? RP_MEMCACHE_PORT : 11211;
	
	/**
	 * Immersive UI elements
	 */
	
	$RailpageConfig->ImmersiveUI = new stdClass;
	$RailpageConfig->ImmersiveUI->Images = array(
		"14536993799",
		"14621479787",
		"14431016961",
		"14395323816",
		"14164503791",
		"13263994803",
		"11597513165",
		"14883030316",
		"14799959132",
		"14408962502",
		"14170226771",
		"13145205015",
		"11899231834",
		"11261655326",
		"13986900730",
		"10545626954",
		"9670691968",
		"7367751026",
		"7182512859",
		"10772624864",
		"3976426484",
		"3975828337",
		"7276345920",
		"14829926708",
		"11883968313",
		"11597881044",
		"11598321386",
		"10688178364",
		"8976514976",
		"8634349032",
		"8404271293",
		"8053030374",
		"8053022697",
		"8053032152",
		"7277957720",
		"7276614410",
		"15335136037",
		"15140812280",
		"15000301397",
		"13892987907",
		"15825596335",
		"16119543656",
		"16143491421",
		"15802854690",
		"15908351946",
		"15008986384",
		"16098876901",
	);
	
	/**
	 * GTFS
	 */
	
	$RailpageConfig->GTFS = new stdClass;
	$RailpageConfig->GTFS->PTV = new stdClass;
	$RailpageConfig->GTFS->PTV->api_key = "xxxxx";
	$RailpageConfig->GTFS->PTV->api_username = "1000079";
	$RailpageConfig->GTFS->PTV->api_password = NULL;
	$RailpageConfig->GTFS->PTV->db_host = "xxxx";
	$RailpageConfig->GTFS->PTV->db_name = "gtfs";
	$RailpageConfig->GTFS->PTV->db_user = "xxxx";
	$RailpageConfig->GTFS->PTV->db_pass = "xxxx";
	
	/**
	 * SMTP settings
	 */
	
	$RailpageConfig->SMTP = new stdClass;
	$RailpageConfig->SMTP->host = "smtp.gmail.com";
	$RailpageConfig->SMTP->port = 587;
	$RailpageConfig->SMTP->TLS = true;
	$RailpageConfig->SMTP->auth = true;
	$RailpageConfig->SMTP->username = "xxxxx";
	$RailpageConfig->SMTP->password = "xxxxx";
	
	/**
	 * Sphinx
	 */
	
	$RailpageConfig->Sphinx = new stdClass;
	$RailpageConfig->Sphinx->Host = "radium.railpage.org";
	$RailpageConfig->Sphinx->Port = 9312;
	
	/**
	 * reCAPTCHA
	 */
	
	$RailpageConfig->Captcha = new stdClass;
	$RailpageConfig->Captcha->SiteKey = "xxxx";
	$RailpageConfig->Captcha->SecretKey = "xxxx";
	
	setRailpageConfig($RailpageConfig);
?>