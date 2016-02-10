<?php

/**
 * Chronicle entry provider interface
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Chronicle;

/**
 * Chronicle entry provider interface
 */

interface ProviderInterface {
    
    /**
     * Get events from a given date range
     * @since Version 3.9
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDates($dateFrom, $dateTo);
    
    /**
     * Load an event from this provider
     * @since Version 3.9
     * @param int $id
     * @return array
     */
    
    public function getEvent($id);
    
    /**
     * Get events from a given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForDate($dateObject);
    
    /**
     * Get events from the week surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForWeek($dateObject);
    
    /**
     * Get events from the month surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForMonth($dateObject);
    
    /**
     * Get events from the year surrounding the given date
     * @since Version 3.9
     * @param \DateTime $dateObject
     * @return \Railpage\Chronicle\Entry
     * @yield \Railpage\Chronicle\Entry
     */
    
    public function getEventsForYear($dateObject);
    
}
