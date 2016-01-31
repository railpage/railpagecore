<?php
    /**
     * Glossary of railway terms, acronyms and codes
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
     * Glossary
     */
    
    class Glossary extends AppCore {
        
        /**
         * Constructor
         */
        
        public function __construct() {
            parent::__construct();
            
            /**
             * Record this in the debug log
             */
            
            if (function_exists("debug_recordInstance")) {
                debug_recordInstance(__CLASS__);
            }
            
            /**
             * Load the Module object
             */
            
            $this->Module = new Module("glossary");
        }
        
        /**
         * Get a list of new glossary entries
         * @since Version 3.8.7
         * @yield \Railpage\Glossary\Entry
         * @param int $num Number of glossary entries to return
         */
        
        public function getNewEntries($num = 10) {
            
            $query = "SELECT id FROM glossary WHERE status = ? ORDER BY date DESC LIMIT 0, ?";
            
            foreach ($this->db->fetchAll($query, array(Entry::STATUS_APPROVED, $num)) as $row) {
                yield new Entry($row['id']);
            }
            
        }
        
        /**
         * Get a list of pending glossary entries
         * @since Version 3.8.7
         * @yield \Railpage\Glossary\Entry
         * @param int $num Number of glossary entries to return
         */
        
        public function getPendingEntries($num = 100) {
            
            $query = "SELECT id FROM glossary WHERE status = ? ORDER BY date DESC LIMIT 0, ?";
            
            foreach ($this->db->fetchAll($query, array(Entry::STATUS_UNAPPROVED, $num)) as $row) {
                yield new Entry($row['id']);
            }
            
        }
        
        /**
         * Lookup something in the glossary
         * @since Version 3.9.1
         * @return \Railpage\Glossary\Entry
         */
        
        public function lookupText($text) {
            
            $cachekey = sprintf("railpage:glossary.lookup.text=%s", md5($text)); 
            
            if ($id = $this->Redis->fetch($cachekey)) {
                $Sphinx = AppCore::getSphinx(); 
                
                $query = $Sphinx->select("*")
                        ->from("idx_glossary")
                        ->match("short", $text);
                
                $matches = $query->execute();
                
                if (count($matches) === 0 && strpos($text, "-") !== false) {
                    $query = $Sphinx->select("*")
                            ->from("idx_glossary")
                            ->match("short", str_replace("-", "", $text));
                    
                    $matches = $query->execute();
                    
                }
            
                if (count($matches) === 1) {
                    $id = $matches[0]['entry_id']; 
                    $this->Redis->save($cachekey, $id, strtotime("+1 year")); 
                }
            }
            
            if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) {
                return new Entry($id); 
            }
            
            return;
        }
    }
    