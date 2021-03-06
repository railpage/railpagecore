<?php

/**
 * Utility class for user functions
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users;

use stdClass;
use Exception;
use DateTime;
use DateTimeZone;
use Railpage\Debug;
use Railpage\AppCore;
use Railpage\BanControl\BanControl;
use Railpage\Module;
use Railpage\Url;
use Railpage\Forums\Thread;
use Railpage\Forums\Forum;
use Railpage\Forums\Forums;
use Railpage\Forums\Index;
use Railpage\ContentUtility;

use Railpage\Users\Timeline\Utility\Grammar;

class Timeline extends AppCore {
    
    /**
     * Extract a user's timeline 
     * @since Version 3.9.1
     * @param \DateTime|int $dateFrom
     * @param \DateTime|int $dateTo
     * @return array
     */
    
    public function GenerateTimeline($dateFrom, $dateTo) {
        
        $page = false;
        $items_per_page = false;
        
        if (!$this->User instanceof User) {
            throw new InvalidArgumentException("No user object has been provided (hint: " . __CLASS__ . "::setUser(\$User))"); 
        }
        
        if (filter_var($dateFrom, FILTER_VALIDATE_INT)) {
            $page = $dateFrom;
        }
        
        if (filter_var($dateTo, FILTER_VALIDATE_INT)) {
            $items_per_page = $dateTo;
        }
        
        /**
         * Filter out forums this user doesn't have access to
         */
        
        $forum_post_filter = $this->getFilteredForums();
        
        if ($page && $items_per_page) {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " ORDER BY timestamp DESC LIMIT ?, ?";
            $offset = ($page - 1) * $items_per_page; 
            
            $params = array(
                $this->User->id, 
                $offset, 
                $items_per_page
            );
        }
        
        if (!$page || !$items_per_page) {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " AND timestamp >= ? AND timestamp <= ? ORDER BY timestamp DESC";
            
            $params = array(
                $this->User->id, 
                $dateFrom->format("Y-m-d H:i:s"), 
                $dateTo->format("Y-m-d H:i:s")
            );
        }
        
        $timeline = array(
            "total" => 0
        ); 
        
        if ($result = $this->db->fetchAll($query, $params)) {
            if ($page && $items_per_page) {
                $timeline['page'] = $page;
                $timeline['perpage'] = $items_per_page;
            }
            
            if (!$page || !$items_per_page) {
                $timeline['start'] = $dateFrom->format("Y-m-d H:i:s");
                $timeline['end'] = $dateTo->format("Y-m-d H:i:s");
            }
            
            $timeline['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
            
            foreach ($result as $row) {
                $row['args'] = json_decode($row['args'], true);
                $row['timestamp'] = new DateTime($row['timestamp']); 
                
                $timeline['timeline'][$row['id']] = $row;
            }
        }
        
        /**
         * Process the timeline data
         */
        
        if (!isset($timeline['timeline'])) {
            return $timeline;
        }
        
        foreach ($timeline['timeline'] as $key => $row) {
            
            // Set their timezone
            $row['timestamp']->setTimezone(new DateTimeZone($this->User->timezone));
            
            if (stristr($row['title'], "loco") && empty($row['module'])) {
                $row['module'] = "locos";
            }
            
            /**
             * Check if the meta data array exists
             */
            
            if (!isset($row['meta'])) {
                $row['meta'] = array(
                    "id" => NULL,
                    "namespace" => NULL
                ); 
            }
            
            /**
             * Format our data for grammatical and sentence structural purposes
             */
            
            $row = $this->processGrammar($row); 
            
            /**
             * Alter the object if needed
             */
            
            $row = Timeline\Utility\General::formatObject($row);
            
            /**
             * Set the module namespace
             */
            
            
            $row['meta']['namespace'] = Timeline\Utility\General::getModuleNamespace($row);
            
            /**
             * Attempt to create a link to this object or action if none exists
             */
            
            $row['meta']['url'] = Timeline\Utility\Url::createUrl($row);
            
            /**
             * Attempt to create a meta object title for this object or action if none exists
             */
            
            $row = Timeline\Utility\ObjectTitle::generateTitle($row); 
            
            /**
             * Compact it all together and create a succinct message
             */
            
            $row['action'] = Timeline\Utility\General::compactEvents($row); 
            
            /**
             * Create the timestamp
             */
            
            $row['timestamp_nice'] = ContentUtility::relativeTime($row['timestamp']);
            
            /**
             * Determine the icon
             */
            
            $row['glyphicon'] = Timeline\Utility\General::getIcon($row); 
            
            $timeline['timeline'][$key] = $row;
        }
        
        return $timeline;
        
    }
    
    /**
     * Get an SQL query used to exclude forums from timeline lookup
     * @since Version 3.9.1
     * @param \Railpage\Users\User $User
     * @return string
     */
    
    private function getFilteredForums() {
        
        $timer = Debug::GetTimer(); 
        
        if (!isset($this->User->Guest) || !$this->User->Guest instanceof User) {
            return "";
        }
        
        $mckey = sprintf("forum.post.filter.user:%d", $this->User->Guest->id);
        
        if ($forum_post_filter = $this->Redis->fetch($mckey)) {
            return $forum_post_filter;
        }
        
        $Forums = new Forums;
        $Index = new Index;
        
        $acl = $Forums->setUser($this->User->Guest)->getACL();
        
        $allowed_forums = array(); 
        
        foreach ($Index->forums() as $row) {
            $Forum = new Forum($row['forum_id']);
            
            if ($Forum->setUser($this->User->Guest)->isAllowed(Forums::AUTH_READ)) {
                $allowed_forums[] = $Forum->id;
            }
        }
        
        $forum_filter = "AND p.forum_id IN (" . implode(",", $allowed_forums) . ")";
        
        if (count($allowed_forums) === 0) {
            $this->Redis->save($mckey, "", strtotime("+1 week"));
            return "";
        }
        
        $forum_post_filter = "AND id NOT IN (SELECT l.id AS log_id
            FROM log_general AS l 
            LEFT JOIN nuke_bbposts AS p ON p.post_id = l.value
            WHERE l.key = 'post_id' 
            " . $forum_filter . ")";
        
        $this->Redis->save($mckey, $forum_post_filter, strtotime("+1 week"));
        
        Debug::LogEvent(__METHOD__, $timer);
        
        return $forum_post_filter;
    }
    
    /**
     * Format the timeline data, one row at a time, for grammatical purposes
     * @since Version 3.9.1
     * @param array $row
     * @return array
     */
    
    private function processGrammar($row) {
        
        $row['event']['action'] = ""; 
        $row['event']['article'] = ""; 
        $row['event']['object'] = ""; 
        $row['event']['preposition'] = ""; 
        
        $row['title'] = str_ireplace(array("loco link created"), array("linked a locomotive"), $row['title']);
        
        $row = $this->processGrammarAction($row);
        $row = $this->processGrammarPreposition($row); 
        $row = $this->processGrammarArticle($row); 
        
        return $row;
    }
    
    /**
     * Process and format the action (removed/suggested/etc) of a timeline item
     * @since Version 3.9.1
     * @param array $row
     * @return array
     */
    
    private function processGrammarAction($row) {
        
        $timer = Debug::GetTimer(); 
        
        $row['event']['action'] = Grammar::getAction($row);
        $row['event']['object'] = Grammar::getObject($row);
        
        if ($row['title'] == "Loco link removed") {
            $row['event']['action'] = "removed";
            $row['event']['object'] = "linked locomotive";
            $row['event']['article'] = "a";
            $row['event']['preposition'] = "from";
        }
        
        Debug::LogEvent(__METHOD__, $timer);
        
        return $row;
        
    }
    
    /**
     * Process and format the preposition (to/from/of) of a timeline item
     * @since Version 3.9.1
     * @param array $row
     * @return array
     */
    
    private function processGrammarPreposition($row) {
        
        $timer = Debug::GetTimer(); 
        
        $row['event']['preposition'] = Grammar::getPrepositionTo($row);
        $row['event']['preposition'] = Grammar::getPrepositionFrom($row);
        $row['event']['preposition'] = Grammar::getPrepositionOf($row);
        $row['event']['preposition'] = Grammar::getPrepositionIn($row);
        
        Debug::LogEvent(__METHOD__, $timer);
        
        return $row;
        
    }
    
    /**
     * Process and format the article (the/a/an) of a timeline item
     * @since Version 3.9.1
     * @param array $row
     * @return array
     */
    
    private function processGrammarArticle($row) {
        
        $timer = Debug::GetTimer(); 
        
        $row['event']['article'] = Grammar::getArticle_OfIn($row); 
        $row['event']['article'] = Grammar::getArticle_AnA($row);
        
        
        if (preg_match("@(date)@Di", $row['event']['object'], $matches) && preg_match("@(edited)@Di", $row['event']['action'], $matches)) {
            $row['event']['preposition'] = "for";
        }
        
        $row = Grammar::getArticle_The($row); 
        
        
        Debug::LogEvent(__METHOD__, $timer);
        
        return $row;
    }
}

