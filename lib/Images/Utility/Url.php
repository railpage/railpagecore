<?php
    /**
     * Image URL builder
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images\Utility;
    
    use Railpage\Url as RealUrl;
    use Railpage\Images\Competition;
    
    class Url {
        
        /**
         * Create URL object from an image ID
         * @since Version 3.9.1
         * @param int $image_id
         * @return \Railpage\Url
         */
        
        public static function CreateFromImageID($image_id) {
            
            $Url = new RealUrl(sprintf("/photos/%d", $image_id));
            $Url->favourite = sprintf("%s?mode=image.favourite", $Url->url);
            
            if ($Url->canonical == "http://" . $Url->url) {
                $Url->canonical = sprintf("http://railpage.com.au%s", $Url->url);
            }
            
            return $Url;
            
        }
        
        /**
         * Create competition URLs
         * @since Version 3.10.0
         * @param \Railpage\Images\Competition $competitionObject
         * @return \Railpage\Url
         */
        
        public static function makeCompetitionUrls(Competition $competitionObject) {
            
            $Url = new RealUrl(sprintf("/gallery/comp/%s", $competitionObject->slug));
            $Url->submitphoto = sprintf("%s/submit", $Url->url);
            $Url->edit = sprintf("/gallery?mode=competitions.new&id=%d", $competitionObject->id);
            $Url->pending = sprintf("/gallery?mode=competition.pendingphotos&id=%d", $competitionObject->id);
            $Url->suggestsubject = sprintf("/gallery?mode=competition.nextsubject&id=%d", $competitionObject->id);
            $Url->tied = sprintf("/gallery?mode=competition.tied&id=%d", $competitionObject->id);
            
            /**
             * Get the UTM email campaign link
             */
            
            $joiner = strpos($Url->canonical, "?") !== false ? "&" : "?";
            
            $parts = array(
                "utm_medium" => "email",
                "utm_source" => "Newsletter",
                "utm_campaign" => str_replace(" ", "+", $competitionObject->title)
            );
            
            $url = $Url->canonical . $joiner . http_build_query($parts);
            
            $Url->email = $url;
            
            return $Url;

        }
    }