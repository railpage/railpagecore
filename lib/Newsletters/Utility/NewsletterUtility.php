<?php
    /**
     * Utility for Newsletter class
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Newsletters\Utility;
    
    use Railpage\Newsletters\Newsletters;
    use Railpage\Newsletters\Newsletter;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\ContentUtility;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    
    class NewsletterUtility {
        
        /**
         * Create UTM link parameters
         * Finds all links in the newsletter content and ensures that each link has a valid set of UTM parameters
         * @since Version 3.10.0
         * @param \Railpage\Newsletters\Newsletter $Newsletter
         * @return \Railpage\Newsletters\Newsletter
         */
         
        public static function CreateUTMParameters(Newsletter $Newsletter) {
            
            foreach ($Newsletter->content as $k => $row) {
                $url = $row['link'];
                
                $Newsletter->content[$k]['link'] = self::CreateUTMParametersForLink($Newsletter, $url);
                
            }
            
            return $Newsletter;
            
        }
        
        /**
         * Assign UTM parameters to an individual URL
         * @since Version 3.10.0
         * @param \Railpage\Newsletters\Newsletter $Newsletter
         * @param string $url
         * @return string
         */
        
        public static function CreateUTMParametersForLink(Newsletter $Newsletter, $url) {
            
            if (is_array($url)) {
                $url = $url['url'];
            }
            
            $utm = [
                "utm_source=newsletter",
                "utm_medium=email",
                sprintf("utm_campaign=%s", ContentUtility::generateUrlSlug($Newsletter->subject))
            ];
            
            $parts = parse_url($url); 
            
            if (!isset($parts['query'])) {
                $url .= "?" . implode("&", $utm);
                return $url;
            }
            
            $parts['query'] = array_merge(explode("&", $parts['query']), $utm); 
            
            return ContentUtility::unparse_url($parts);
            
        }
        
    }