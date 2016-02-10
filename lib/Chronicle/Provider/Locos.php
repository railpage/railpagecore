<?php

/**
 * Locomotives chroncile event provider
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Chronicle\Provider;

use Exception;
use DateTime;

use Railpage\Chronicle\Chronicle;
use Railpage\Chronicle\ProviderInterface;

use Railpage\Locos\Locos as Locos_Module;

class Locos extends Chronicle implements ProviderInterface {
    
    /**
     * Provider name
     * @since Version 3.9
     * @const PROVIDER_NAME
     */
    
    const PROVIDER_NAME = "Locos";
    
    /**
     * Get events from a given date range
     * @since Version 3.9
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDates($dateFrom = null, $dateTo = null) {
        
        $events = array(); 
        
        $Locos = new Locos_Module;
        
        if (!$dateFrom instanceof DateTime) {
            throw new Exception("dateFrom is not an instance of \DateTime"); 
        }
        
        if (!$dateTo instanceof DateTime) {
            throw new Exception("dateTo is not an instance of \DateTime"); 
        }
        
        foreach ($Locos->yieldDatesWithinRange($dateFrom, $dateTo) as $Date) {
            $events[] = array(
                "provider" => self::PROVIDER_NAME,
                "id" => $Date->id,
                "title" => $Date->text,
                "date" => $Date->Date,
                "url" => $Date->url->getURLs()
            );
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
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDate($dateObject) {
        
    }
    
    /**
     * Get events from the week surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForWeek($dateObject) {
        
    }
    
    /**
     * Get events from the month surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForMonth($dateObject) {
        
    }
    
    /**
     * Get events from the year surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForYear($dateObject) {
        
    }
}
