<?php
    /**
     * Present a map location as an image
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    use Railpage\Users\User;
    use Railpage\API;
    use Railpage\AppCore;
    use Railpage\Place;
    use Exception;
    use DateTime;
    use DateTimeZone;
    use DateInterval;
    use stdClass;
    
    /**
     * Present a map location as an image
     * @since Version 3.8.7
     */
    
    class MapImage extends AppCore {
        
        /**
         * Image title
         * @since Version 3.8.7
         * @var string $title
         */
        
        public $title;
        
        /**
         * Image provider
         * @since Version 3.8.7
         * @var string $provider
         */
        
        public $provider;
        
        /**
         * Geographic place where this photo was taken
         * @since Version 3.8.7
         * @var \Railpage\Place $Place
         */
        
        public $Place;
        
        /**
         * Object of image sizes and their source URLs
         * @since Version 3.8.7
         * @var \stdClass $sizes
         */
        
        public $sizes;
        
        /**
         * Object of image page URLs
         * @since Version 3.8.7
         * @var \stdClass $links
         */
        
        public $links;
        
        /**
         * URL to this image on Railpage
         * @since Version 3.8.7
         * @var string $url
         */
        
        public $url;
        
        /**
         * Image meta data
         * @since Version 3.8.7
         * @var array $meta
         */
        
        public $meta;
        
        /**
         * Memcached identifier key
         * @since Version 3.8.7
         * @var string $mckey
         */
        
        public $mckey;
        
        /**
         * JSON data string of this image
         * @since Version 3.8.7
         * @var string $json
         */
        
        public $json;
        
        /**
         * Constructor
         * @since Version 3.8.7
         * @param double $lat
         * @param double $lon
         */
        
        public function __construct($lat = NULL, $lon = NULL) {
            parent::__construct();
            
            $Config = AppCore::GetConfig(); 
            
            if ($lat != NULL && $lon != NULL) {
                $this->Place = new Place($lat, $lon);
                
                $urlstring = "http://maps.googleapis.com/maps/api/staticmap?key=%s&center=%s,%s&zoom=%d&size=%dx%d&maptype=roadmap&markers=color:red%%7C%s,%s";
                
                $this->sizes = array(
                    "thumb" => array(
                        "width" => 300,
                        "height" => 150,
                        "zoom" => 14
                    ),
                    "small" => array(
                        "width" => 500,
                        "height" => 281,
                        "zoom" => 14
                    ),
                    "largest" => array(
                        "width" => 800,
                        "height" => 600,
                        "zoom" => 12
                    )
                );
                
                foreach ($this->sizes as $size => $row) {
                    $this->sizes[$size]['source'] = sprintf($urlstring, $Config->Google->API_Key, $this->Place->lat, $this->Place->lon, $row['zoom'], $row['width'], $row['height'], $this->Place->lat, $this->Place->lon);
                }
            }
        }
        
        /**
         * Echo the "default" map image
         * @since Version 3.9.1
         * @return string
         */
        
        public function __toString() {
            return $this->sizes['largest']['source'];
        }
        
        /**
         * Static function to make using this class easier/lazier
         * @since Version 3.10.0
         * @param double $lat
         * @param double $lon
         * @return string
         */
        
        public static function Image($lat, $lon) {
            
            $MapImage = new MapImage($lat, $lon);
            return $MapImage->__toString(); 
            
        }
        
    }
    