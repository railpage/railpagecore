<?php

/**
 * Railcams chroncile event provider
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Chronicle\Provider;

use Exception;
use DateTime;
use DateTimeZone;

use Railpage\Chronicle\Chronicle;
use Railpage\Chronicle\ProviderInterface;

use Railpage\Railcams\Camera;
use Railpage\Railcams\Railcams as Module_Railcams;

use Railpage\Locos\Locomotive;

class Railcams extends Chronicle implements ProviderInterface {
    
    /**
     * Provider name
     * @since Version 3.9
     * @const PROVIDER_NAME
     */
    
    const PROVIDER_NAME = "Railcams";
    
    /**
     * Get events from a given date range
     * @since Version 3.9
     * @param \DateTime $From
     * @param \DateTime $To
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDates($From = false, $To = false) {
        
        $events = array(); 
        
        $Railcams = new Module_Railcams;
        
        foreach ($Railcams->getTaggedPhotos() as $photo) {
            $Camera = new Camera($photo['railcam_id']);
            $Photo = $Camera->getPhoto($photo['photo_id']);
            
            if ($From instanceof DateTime && $Photo->dates['taken'] >= $From &&
                $To instanceof DateTime && $Photo->dates['taken'] <= $To) {
                    
                $Loco = new Locomotive($photo['loco_id']);
                    
                $events[] = array(
                    "provider" => self::PROVIDER_NAME,
                    "id" => $photo['id'],
                    "title" => sprintf("Railcam sighting: %s at %s", $Loco->number, $Camera->name),
                    "date" => new DateTime(sprintf("@%s", substr($photo['id'], 0, 10))),
                    "url" => $Photo->url->getURLs()
                );
            }
        }
        
        return $events;
        
    }
    
    /**
     * Load an event from this provider
     * @since Version 3.9
     * @param int $id
     * @return array
     */
    
    public function getEvent($id) {
        
    }
    
    /**
     * Get events from a given date
     * @since Version 3.9
     * @param \DateTime $Date
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDate($Date) {
        
    }
    
    /**
     * Get events from the week surrounding the given date
     * @since Version 3.9
     * @param \DateTime $Date
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForWeek($Date) {
        
    }
    
    /**
     * Get events from the month surrounding the given date
     * @since Version 3.9
     * @param \DateTime $Date
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForMonth($Date) {
        
    }
    
    /**
     * Get events from the year surrounding the given date
     * @since Version 3.9
     * @param \DateTime $Date
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForYear($Date) {
        
    }
}
