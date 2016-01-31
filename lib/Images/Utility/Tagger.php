<?php
    /**
     * Image tag utility class
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images\Utility;
    
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Events\Events;
    use Railpage\Events\Factory as EventsFactory;
    use Railpage\Events\Event; 
    use Railpage\Events\EventDate;
    use Railpage\Images\Image;
    
    class Tagger {
        
        /**
         * Suggest events to tag
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @return array
         */
        
        public static function SuggestEvents(Image $Image) {
            
            if (!$Image->DateCaptured instanceof DateTime) {
                return;
            }
            
            $Database = (new AppCore)->getDatabaseConnection();
            
            $query = "SELECT COUNT(*) AS num FROM image_link WHERE namespace = ? AND image_id = ?";
            $params = [
                (new Event)->namespace,
                $Image->id
            ];
            
            if ($Database->fetchOne($query, $params) > 0) {
                return;
            }
            
            $Events = new Events;
            $list = $Events->getEventsForDate($Image->DateCaptured);
            
            foreach ($list as $k => $row) {
                $Event = new Event($row['event_id']); printArray($Event->namespace);die;
                $list[$k]['url'] = sprintf("/services?method=railpage.image.tag&image_id=%d&object=%s&object_id=%d", $Image->id, "\\Railpage\\Events\\Event", $row['event_id']);
            }
            
            return $list;
            
        }
        
    }