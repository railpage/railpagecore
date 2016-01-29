<?php
    /**
     * Show events in a given month
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Events;
    
    use Railpage\AppCore;
    use Railpage\Url;
    use Railpage\Module;
    use DateTime;
    use DateTimeZone;
    use Railpage\Organisations\Organisation;
    
    /**
     * Month
     */
    
    class Month extends AppCore {
        
        /**
         * Month
         * @since Version 3.9.1
         * @var int $month
         */
        
        public $month; 
        
        /**
         * Month name
         * @since Version 3.9.1
         * @var string $name
         */
        
        public $name;
        
        /**
         * Year
         * @since Version 3.9.1
         * @var int $year
         */
        
        public $year;
        
        /**
         * Constructor
         * @since Version 3.9.1
         * @param int $year
         * @param int $month
         */
        
        public function __construct($year = false, $month = false) {
            parent::__construct();
            
            $this->month = $month;
            $this->year = $year;
            
            // What is the first day of the month in question?
            $firstDayOfMonth = mktime(0, 0, 0, $this->month, 1, $this->year);
            
            // How many days does this month contain?
            $numberDays = date('t', $firstDayOfMonth);
            
            // Retrieve some information about the first day of the month in question
            $dateComponents = getdate($firstDayOfMonth);
            
            // What is the name of the month in question?
            $this->name = $dateComponents['month'];
            
            $this->url = new Url(sprintf("/events?year=%d&month=%d", $this->year, $this->month));
        }
        
        /**
         * Get the previous month
         * @since Version 3.9.1
         * @return \Railpage\Events\Month
         */
        
        public function prev() {
            $prevmonth = $this->month - 1; 
            $prevyear = $this->year;
            
            if ($prevmonth == -1) {
                $prevmonth = 1;
                $prevyear = $this->year - 1;
            }
            
            return new Month($prevyear, $prevmonth);
        }
        
        /**
         * Get the previous month (alias)
         * @since Version 3.9.1
         * @return \Railpage\Events\Month
         */
         
        public function previous() {
            return $this->prev();
        }
        
        /**
         * Get the next month
         * @since Version 3.9.1
         * @return \Railpage\Events\Month
         */
        
        public function next() {
            $nextmonth = $this->month + 1;
            $nextyear = $this->year; 
            
            if ($nextmonth == 13) {
                $nextmonth = 1;
                $nextyear = $this->year + 1;
            }
            
            return new Month($nextyear, $nextmonth);
        }
        
        /**
         * Generate the calendar table
         * Borrowed from https://css-tricks.com/snippets/php/build-a-calendar-table/
         *
         * @since Version 3.9.1
         * @return string
         */
        
        public function generateCalendar() {
            
            /**
             * Load the Events class for later on
             */
            
            $Events = new Events;
            
            // Create array containing abbreviations of days of week.
            $daysOfWeek = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            
            // What is the first day of the month in question?
            $firstDayOfMonth = mktime(0, 0, 0, $this->month, 1, $this->year);
            
            // How many days does this month contain?
            $numberDays = date('t', $firstDayOfMonth);
            
            // Retrieve some information about the first day of the month in question
            $dateComponents = getdate($firstDayOfMonth);
            
            // What is the index value (0-6) of the first day of the month in question
            $dayOfWeek = $dateComponents['wday'];
            
            // Create the table tag opener and day headers
            $calendar = "<table class='calendar'>";
            //$calendar .= "<caption>" . $this->name . " " . $this->year . "</caption>";
            $calendar .= "<thead><tr>";
            
            // Create the calendar headers
            foreach ($daysOfWeek as $day) {
                $calendar .= "<th class='header'>" . $day . "</th>";
            }
            
            // Create the rest of the calendar
            // Initiate the day counter, starting with the 1st.
            
            $currentDay = 1;
            
            $calendar .= "</tr></thead><tbody><tr>";
            
            // The variable $dayOfWeek is used to
            // ensure that the calendar
            // display consists of exactly 7 columns.
            
            if ($dayOfWeek > 0) { 
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    $calendar .= "<td class='notday'>&nbsp;</td>";
                }
            }
            
            $month = str_pad($this->month, 2, "0", STR_PAD_LEFT);
            
            while ($currentDay <= $numberDays) {
                // Seventh column (Saturday) reached. Start a new row.
                
                if ($dayOfWeek == 7) {
                    $dayOfWeek = 0;
                    $calendar .= "</tr><tr>";
                }
                
                $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
                
                $date = sprintf("%s-%s-%s", $this->year, $month, $currentDayRel);
                
                $calendar .= sprintf("<td class='isday %s' valign='top' rel='%s'>", $date == date("Y-m-d") ? "today" : "", $date);
                $calendar .= sprintf("<span class='daynum'>%d</span>", $currentDay);
                
                /**
                 * Get events on this date
                 */
                
                foreach ($Events->getEventsForDate(new DateTime($date)) as $row) {
                    $calendar .= sprintf("<a class='event-link' href='%s'><time datetime='%s'>%s</time></a>", $row['url'], (new DateTime($row['date'] . " " . $row['start']))->format(DATE_ISO8601), $row['title']);
                }
                
                
                $calendar .= "</td>";
                
                // Increment counters
                
                $currentDay++;
                $dayOfWeek++;
            }
            
            
            
            // Complete the row of the last week in month, if necessary
            
            if ($dayOfWeek != 7) { 
                $remainingDays = 7 - $dayOfWeek;
                
                for ($i = 0; $i < $remainingDays; $i++) {
                    $calendar .= "<td class='notday'>&nbsp;</td>";
                }
            }
            
            $calendar .= "</tr></tbody>";
            $calendar .= "</table>";
            
            return $calendar;
        }
    }