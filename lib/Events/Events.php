<?php

/**
 * Events management class
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Events;

use Railpage\AppCore;
use Railpage\Module;
use DateTime;
use DateTimeZone;
use Railpage\Organisations\Organisation;

/**
 * Events
 * 
 * The Events master class
 * @since Version 3.8.7
 */

class Events extends AppCore {
    
    /**
     * Status: approved
     * @const STATUS_APPROVED
     * @since Version 3.9
     */
    
    const STATUS_APPROVED = 1;
    
    /**
     * Status: unapproved
     * @const STATUS_UNAPPROVED
     * @since Version 3.9
     */
    
    const STATUS_UNAPPROVED = 0;
    
    /**
     * Constructor
     * @since Version 3.8.7
     */
    
    public function __construct() {
        parent::__construct(); 
        
        $this->Module = new Module("events");
        $this->namespace = $this->Module->namespace;
    }
    
    /**
     * Find an event based on name
     * @param string $name The name of the event to search for
     * @return array
     */
    
    public function findEvent($name = NULL) {
        if (!is_string($name) || is_null($name)) {
            return false;
        }
        
        return $this->db->fetchAll("SELECT * FROM event WHERE title = ?", $name);
    }
    
    /**
     * Get events for a given DateTime object
     * @param \DateTime $dateObject An optional DateTime instance to search for. Will default to today if not provided
     * @return array
     */
    
    public function getEventsForDate(DateTime $dateObject = NULL) {
        if (!$dateObject instanceof DateTime) {
            $dateObject = new DateTime;
        }
        
        $args = array(
            $dateObject->format("Y-m-d"), 
            self::STATUS_APPROVED, 
            EventDate::STATUS_RUNNING
        );
        
        return $this->db->fetchAll("SELECT ed.*, e.title, CONCAT('/events/', e.slug) AS url FROM event_dates AS ed LEFT JOIN event AS e ON e.id = ed.event_id WHERE ed.date = ? AND e.status = ? AND ed.status = ?", $args);
    }
    
    /**
     * Get upcoming events
     * @param int $itemsPerPage The number of events to return
     * @param int $page The "page" number of events
     * @return array
     */
    
    public function getUpcomingEvents($itemsPerPage = 25, $page = 1) {
        
        $Now = new DateTime;
        
        $args = array(
            $Now->format("Y-m-d"),
            self::STATUS_APPROVED, 
            EventDate::STATUS_RUNNING,
            ($page - 1) * $itemsPerPage, 
            $itemsPerPage
        ); 
        
        return $this->db->fetchAll("SELECT ed.* FROM event_dates AS ed LEFT JOIN event AS e ON e.id = ed.event_id WHERE ed.date >= ? AND e.status = ? AND ed.status = ? ORDER BY ed.date LIMIT ?, ?", $args);
    
    }
    
    /**
     * Get event categories
     * @since Version 3.8.7
     * @return array
     */
    
    public function getCategories() {
        return $this->db->fetchAll("SELECT * FROM event_categories ORDER BY title");
    }
    
    /**
     * Get upcoming events for an organisation
     * @since Version 3.8.7
     * @return array
     * @param \Railpage\Organisations\Organisation $orgObject
     */
    
    public function getUpcomingEventsForOrganisation(Organisation $orgObject) {
        if (!filter_var($orgObject->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot fetch upcoming events because the specified organisation is invalid or doesn't exist");
        }
        
        $query = "SELECT ed.id, ed.event_id, ed.date FROM event_dates AS ed LEFT JOIN event AS e ON e.id = ed.event_id WHERE ed.date >= ? AND e.organisation_id = ? AND e.status IN (" . EventDate::STATUS_RUNNING . ")";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, array(date("Y-m-d"), $orgObject->id)) as $row) {
            $Event = new Event($row['event_id']);
            $return[$row['date']][] = array(
                "id" => $Event->id,
                "name" => $Event->title,
                "event_date" => $row['id']
            );
        }
        
        return $return;
    }
    
    /**
     * Yield events pending approval
     * @since Version 3.9
     * @yield new \Railpage\Events\Event
     */
    
    public function yieldPendingEvents() {
        $query = "SELECT id FROM event WHERE status = ?";
        
        foreach ($this->db->fetchAll($query, self::STATUS_UNAPPROVED) as $row) {
            yield new Event($row['id']);
        }
    }
    
    /**
     * Yield event dates pending approval
     * @since Version 3.9
     * @yield new \Railpage\Events\EventDate
     */
    
    public function yieldPendingEventDates() {
        $query = "SELECT id FROM event_dates WHERE status = ?";
        
        foreach ($this->db->fetchAll($query, self::STATUS_UNAPPROVED) as $row) {
            yield new EventDate($row['id']);
        }
    }
}
