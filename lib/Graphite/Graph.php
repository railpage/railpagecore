<?php
    /**
     * Get data from the Graphite backend and present it to the UI
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Graphite;
    
    use Railpage\Config;
    use Railpage\AppCore;
    use Exception;
    use DateTime;
    use Zend\Http\Client;

    class Graph extends AppCore {
        
        /**
         * Graph target (eg railpage.locos.class.11.view)
         * @since Version 3.8.7
         * @var string $target
         */
        
        protected $target;
        
        /**
         * Graph data format
         * @since Version 3.8.7
         * @var string $format
         */
        
        private $format;
        
        /**
         * Request URL
         * @since Version 3.8.7
         * @var string $url
         */
        
        private $url;
        
        /**
         * Graphite response
         * @since Version 3.8.7
         * @var array $response
         */
        
        private $resposne;
        
        /**
         * Railpage site configuration
         * @since Version 3.8.7
         * @var \stdClass $Config
         */
        
        protected $Config;
        
        /**
         * Constructor
         * @since Version 3.8.7
         * @param string $target
         */
        
        public function __construct($target = false) {
            parent::__construct(); 
            
            if (function_exists("getRailpageConfig")) {
                $this->Config = getRailpageConfig();
            }
            
            if ($target) {
                $this->target = $target;
            }
            
            $this->format = "json";
        }
        
        /**
         * Fetch data from Graphite and format it
         * @since Version 3.8.7
         * @return array
         */
        
        private function fetch() {
            $config = array(
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
            );
            
            $client = new Client($this->url, $config);
            $response = $client->send();
            
            $content = $response->getContent();
            
            return $this->url;
            return json_decode($content, true);
        }
        
        /**
         * Fetch latest data from Graphite for this target
         * @since Version 3.8.7
         * @return array
         */
        
        public function getData() {
            $this->url = sprintf("%s/render?target=%s.%s&format=%s", $this->Config->Graphite->Host, "stats", $this->target, $this->format);
            
            return $this->fetch();
        }
        
        /**
         * Get summary data
         * @since Version 3.8.7
         * @return array
         */
        
        public function getSummary() {
            $this->url = sprintf("%s/render?target=summarize(%s.%s,\"5min\")&format=%s", $this->Config->Graphite->Host, "stats", $this->target, $this->format);
            
            return $this->fetch();
        }
        
        /**
         * Get the URL for the summary image
         * @since Version 3.8.7
         * @return string
         */
        
        public function getSummaryImage($width = 500, $height = 300) {
            $this->url = sprintf("%s/render?target=summarize(%s.%s,\"5min\")&width=%d&height=%d", $this->Config->Graphite->Host, "stats", $this->target, $width, $height);
            
            return $this->url;
        }
    }
    