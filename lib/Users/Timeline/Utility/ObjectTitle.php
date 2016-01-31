<?php
    /**
     * Generate and format the title of the object listed in a timeline entry
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Users\Timeline\Utility;
    
    use Railpage\Forums\Post;
    use Railpage\Locos\Factory as LocosFactory;
    use Railpage\Locos\Locomotive;
    use Railpage\Locos\LocoClass;
    use Railpage\Locations\Location;
    use Railpage\Ideas\Idea;
    
    class ObjectTitle {
        
        /**
         * The timeline row we're processing
         * @since Version 3.9.1
         * @var array $row
         */
        
        private static $row;
        
        /**
         * Generate the object title
         * @since Version 3.9.1
         * @param array $row
         * @return array
         */
        
        public static function generateTitle($row) {
            
            self::$row = $row;
            
            switch (self::$row['key']) {
                
                /**
                 * Forum post
                 */
                
                case "post_id" : 
                    self::processForumPost(); 
                    break;
                
                /**
                 * Locomotive
                 */
                
                case "loco_id" : 
                    self::processLocomotive(); 
                    break;
                
                /**
                 * Locomotive class
                 */
                
                case "class_id" : 
                    self::processLocomotiveClass(); 
                    break;
                
                /**
                 * Location
                 */
                
                case "id" :
                    if (self::$row['module'] == "locations") {
                        self::processLocation(); 
                    }
                    break;
                
                /**
                 * Photo
                 */
                
                case "photo_id" : 
                    self::processFlickrPhoto(); 
                    break;
                
                /**
                 * Sighting
                 */
                
                case "sighting_id" : 
                    self::processSighting(); 
                    break;
                
                /**
                 * Idea
                 */
                
                case "idea_id" : 
                    self::processIdea(); 
                    break;
            }
            
            return self::$row;

        }
        
        /**
         * Process this row as a forum post
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processForumPost() {
            $Post = new Post(self::$row['value']);
            self::$row['meta']['object']['title'] = $Post->thread->title;
        }
        
        /**
         * Process this row as a locomotive
         * @since Version 3n.9.1
         * @return void
         */
        
        private static function processLocomotive() {
            $Loco = LocosFactory::CreateLocomotive(self::$row['value']); 
            
            if (!$Loco instanceof Locomotive) {
                return;
            }
            
            self::$row['meta']['namespace'] = $Loco->namespace;
            self::$row['meta']['id'] = $Loco->id;
            
            if (self::$row['event']['action'] == "added" && self::$row['event']['object'] == "loco") {
                self::$row['meta']['object']['title'] = $Loco->class->name;
            } else {
                self::$row['meta']['object']['title'] = $Loco->number;
                self::$row['meta']['object']['subtitle'] = $Loco->class->name;
            }
        }
        
        /**
         * Process this row as a locomotive class
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processLocomotiveClass() {
            $LocoClass = LocosFactory::CreateLocoClass(self::$row['value']); 
            
            if (!$LocoClass instanceof LocoClass) {
                return;
            }
            
            self::$row['meta']['object']['title'] = $LocoClass->name;
            
            self::$row['meta']['namespace'] = $LocoClass->namespace;
            self::$row['meta']['id'] = $LocoClass->id;
        }
        
        /**
         * Procss this row as a lineside photography location
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processLocation() {
            $Location = new Location(self::$row['value']);
            self::$row['meta']['object']['title'] = $Location->name;
            self::$row['meta']['url'] = $Location->url;
            unset(self::$row['event']['article']);
            unset(self::$row['event']['object']);
            unset(self::$row['event']['preposition']);
        }
        
        /**
         * Process this row as a Flickr photo
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processFlickrPhoto() {
            self::$row['meta']['object']['title'] = "photo";
            self::$row['meta']['url'] = "/flickr/" . self::$row['value'];
            
            if (self::$row['event']['action'] == "commented") {
                self::$row['event']['object'] = "";
                self::$row['event']['article'] = "on";
                self::$row['event']['preposition'] = "a";
            }
        }
        
        /**
         * Process this row as a locomotive sighting
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processSighting() {
            if (empty(self::$row['module']) || !isset(self::$row['module'])) {
                self::$row['module'] = "sightings";
            }
            
            self::$row['event']['preposition'] = "of";
            self::$row['event']['article'] = "a";
            
            if (count(self::$row['args']['locos']) === 1) {
                self::$row['meta']['object']['title'] = self::$row['args']['locos'][key(self::$row['args']['locos'])]['Locomotive'];
            } elseif (count(self::$row['args']['locos']) === 2) {
                self::$row['meta']['object']['title'] = self::$row['args']['locos'][key(self::$row['args']['locos'])]['Locomotive'];
                next(self::$row['args']['locos']);
                
                self::$row['meta']['object']['title'] .= " and " . self::$row['args']['locos'][key(self::$row['args']['locos'])]['Locomotive'];
            } else {
                $locos = array();
                foreach (self::$row['args']['locos'] as $loco) {
                    $locos[] = $loco['Locomotive'];
                }
                
                $last = array_pop($locos);
                
                self::$row['meta']['object']['title'] = implode(", ", $locos) . " and " . $last;
            }
        }
        
        /**
         * Process this row as an idea
         * @since Version 3.9.1
         * @return void
         */
        
        private static function processIdea() {
            $Idea = new Idea(self::$row['value']);
            self::$row['meta']['object']['title'] = $Idea->title;
            self::$row['meta']['url'] = $Idea->url;
            self::$row['glyphicon'] = "thumbs-up";
            self::$row['event']['object'] = "idea:";
            self::$row['event']['article'] = "an";
        }
    }