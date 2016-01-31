<?php
    /**
     * Glossary type - term, acronym, etc
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Glossary;
    
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Url;
    use Exception;
    use DateTime;
    use stdClass;
    
    /**
     * Type
     */
    
    class Type extends AppCore {
        
        /**
         * ID
         * @var string $id;
         */
        
        public $id;
        
        /**
         * Type name
         * @var string $name
         */
        
        public $name;
        
        /**
         * URL
         * @var string $url
         */
        
        public $url;
        
        /**
         * Constructor
         * @since Version 3.8.7
         * @param string $type
         */
        
        public function __construct($type = false) {
            parent::__construct();
            
            $this->Module = new Module("glossary");
            
            if ($type) {
                $this->url = new Url(sprintf("%s?mode=type&type=%s", $this->Module->url, $type));
                $this->id = $type;
                
                switch ($type) {
                    case "code" : 
                    case "acronym" :
                    case "station" : 
                        $this->name = ucfirst($type . "s");
                        break;
                    
                    case "slang" : 
                    case "general" : 
                        $this->name = ucfirst($type);
                        break;
                        
                    case "term" : 
                        $this->name = "Terminology";
                        break;
                        
                    default :
                        $this->name = "General";
                        break;
                }
            }
        }
        
        /**
         * Get glossary entries of this type
         * @since Version 3.8.7
         * @yield \Railpage\Glossary\Entry
         */
        
        public function getEntries() {
            $query = "SELECT id FROM glossary WHERE type = ? AND status = ? ORDER BY short";
            
            foreach ($this->db->fetchAll($query, array($this->id, Entry::STATUS_APPROVED)) as $row) {
                $id = $row['id'];
                
                yield new Entry($id);
            }
        }
        
        /**
         * Get this an an associative array
         * @since Version 3.9.1
         * @return array
         */
        
        public function getArray() {
            return array(
                "id" => $this->id,
                "name" => $this->name,
                "url" => $this->url->getURLs()
            );
        }
    }
    