<?php
    /** 
     * Create and manage official organisations
     * The purpose of an organisation is to identify a forum member as an official representative of an organisation or company
     * @since Version 3.2
     * @version 3.87
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Organisations;
    use Railpage\AppCore;
    use Exception;
    use DateTime;
    
    /**
     * Create an organisation object from its URL slug
     * @since Version 3.7.5
     */
    
    class OrganisationFromSlug extends Organisation {
        
        /**
         * Constructor
         * @param string $slug
         */
        
        public function __construct($slug = false) {
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            
            if ($slug) {
                $query = "SELECT organisation_id FROM organisation WHERE organisation_slug = ?";
                
                if ($id = $Database->fetchOne($query, $slug)) {
                    parent::__construct($id); 
                }
            }
        }
    }
    