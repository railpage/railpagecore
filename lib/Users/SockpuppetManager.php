<?php
    /**
     * Look up sockpuppets 
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Users;
    
    use Exception;
    use DateTime;
    use InvalidArgumentException;
    use Railpage\Debug;
    use Railpage\AppCore;
    use Railpage\Url;
    use Tga\SimHash\SimHash;
    use Tga\SimHash\Extractor\SimpleTextExtractor;
    use Tga\SimHash\Comparator\GaussianComparator;
    
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/SimHash.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Fingerprint.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Comparator/ComparatorInterface.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Comparator/GaussianComparator.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Extractor/ExtractorInterface.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Extractor/SimpleTextExtractor.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Extractor/HtmlExtractor.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Tokenizer/TokenizerInterface.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Tokenizer/String128Tokenizer.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Tokenizer/String32Tokenizer.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Tokenizer/String64Tokenizer.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Vectorizer/VectorizerInterface.php");
    require_once(RP_SITE_ROOT . "/vendor/tga/simhash-php/lib/Tga/SimHash/Vectorizer/DefaultVectorizer.php");
    
    class SockpuppetManager extends AppCore {
        
        /**
         * The user we're basing the comparisons on
         * @since Version 3.10.0
         * @var \Railpage\Users\User $ReferenceUser
         */
        
        private $ReferenceUser;
        
        /**
         * An array of users we want to compare against
         * @since Version 3.10.0
         * @var array $suspects
         */
        
        private $suspects = array(); 
        
        /**
         * Set the reference user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return \Railpage\Users\SockpuppetManager
         */
        
        public function setReferenceUser(User $User) {
            
            $this->ReferenceUser = $User;
            
            return $this;
            
        }
        
        /**
         * Add a suspect to our lookup array
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return \Railpage\Users\SockpuppetManager
         */
        
        public function addSuspect(User $User) {
            
            $this->suspects[$User->id] = $User; 
            
            return $this;
            
        }
        
        /**
         * GoCompare!
         * @since Version 3.10.0
         * @return \Railpage\Users\SockpuppetManager
         */
        
        public function compare() {
            
            /**
             * Load our reference data first
             */
            
            $ref = array(
                "ips" => json_encode($this->ReferenceUser->getIPs(new DateTime("6 months ago")))
            );
            
            /**
             * Start our SimHash stuff
             */
            
            $SimHash = new SimHash;
            $Extractor = new SimpleTextExtractor; 
            $Comparator = new GaussianComparator(3); 
            
            foreach ($ref as $key => $lookup) {
                $this->hashes[$key]['reference'] = $SimHash->hash($Extractor->extract($lookup), SimHash::SIMHASH_64);
            }
            
            foreach ($this->suspects as $Suspect) {
                
                $suspectData = array(
                    "ips" => json_encode($Suspect->getIPs(new DateTime("6 months ago")))
                );
            
                foreach ($suspectData as $key => $lookup) {
                    $this->hashes[$key]['suspect'] = $SimHash->hash($Extractor->extract($lookup), SimHash::SIMHASH_64);
                }
                
                foreach ($this->hashes as $key => $users) {
                    $this->results[$Suspect->id][$key] = $Comparator->compare($users['reference'], $users['suspect']);
                }
                
            }
            
        }
        
        /**
         * Get the results of this scan
         * @since Version 3.10.0
         * @return array
         */
        
        public function getResults() {
            
            printArray($this->results);
            
        }
        
    }