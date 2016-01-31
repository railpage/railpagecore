<?php
    /**
     * Bug reporting code
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Bugs;
    
    use Exception;
    use DateTime;
    use Railpage\AppCore;
    use Railpage\Module;
    use Zend\Http\Client;
    
    /**
     * Bugs
     */
    
    class Bugs extends AppCore {
        
        /**
         * Redmine URL base
         */
        
        const REDMINE_URL = "http://redmine.railpage.org/redmine";
        
        /**
         * Redmine project ID
         */
        
        const REDMINE_PROJECT_ID = "rp3";
        
        /**
         * Redmine API key
         */
        
        const REDMINE_API_KEY = "1dd06ee2f781194880c01540d8b30f975607f06d";
        
        /**
         * Fetch data from the bug tracker
         * @since Version 3.8.7
         * @param string $url
         * @return array
         */
        
        public function fetch($url) {
            $config = array(
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
            );
            
            $client = new Client($url, $config);
            $client->setHeaders(array(
                "X-Redmine-API-Key" => self::REDMINE_API_KEY
            ));
            
            $response = $client->send();
            
            $response = $response->getContent();
            
            if (strlen($response) > 1) {
                $response = json_decode($response, true);
                
                return $response;
            } else {
                throw new Exception("Could not fetch current issues from the bug tracker");
            }
        }
        
        /**
         * Fetch bugs from Railpage
         * @since Version 3.8.7
         * @yield \Railpage\Bugs\Bug
         */
        
        public function getIssues() {
            
            $url = self::REDMINE_URL . "/issues.json?project_id=" . self::REDMINE_PROJECT_ID;
            
            $response = $this->fetch($url);
            
            foreach ($response['issues'] as $row) {
                echo $row['id'];
                yield new Bug($row['id']);
            }
        }
    }
    