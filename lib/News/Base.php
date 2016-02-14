<?php
    /**
     * News classes
     * @since Version 3.0.1
     * @version 3.0.1
     * @author Michael Greenhill
     * @package Railpage
     * @copyright Copyright (c) 2012 Michael Greenhill
     */
     
    namespace Railpage\News;
    
    use Railpage\AppCore;
    use Railpage\Module;
    use Exception;
    use DateTime;
    use Railpage\ContentUtility;
    use Railpage\Debug;
    
    /**
     * Base news class
     * @since Version 3.0.1
     * @version 3.0.1
     * @author Michael Greenhill
     * @copyright Copyright (c) 2012 Michael Greenhill
     */
    
    class Base extends AppCore {
        
        /**
         * User handle
         * @version 3.0
         * @since Version 3.0
         * @var object $user
         */
        
        public $user = false;
        
        /**
         * Constructor
         * @since Version 3.8.7
         */
        
        public function __construct() {
            parent::__construct(); 
            
            $this->Module = new Module("news");
            $this->namespace = $this->Module->namespace;
        }
        
        /**
         * Get the latest news items
         * @version 3.7.5
         * @since Version 3.0
         * @return mixed
         * @param int $number
         * @param int $offset
         */
         
        public function latest($number = 5, $offset = 0) {
            $return = false;
            $mckey = "railpage:news.latest.count=" . $number .".offset=" . $offset;
            $mcexp = strtotime("+5 minutes"); // Store for five minutes
            
            $Sphinx = $this->getSphinx();
            
            $query = $Sphinx->select("*")
                    ->from("idx_news_article")
                    ->orderBy("story_time_unix", "DESC")
                    ->where("story_active", "=", 1)
                    ->limit($offset, $number);
                    
            $matches = $query->execute(); 
            
            /**
             * Attempt to fetch from Sphinx first
             */
            
            if (is_array($matches) && count($matches)) {
                
                foreach ($matches as $id => $row) {
                    $row['time_relative'] = time2str($row['story_time_unix']);
                    $row['time'] = time2str($row['story_time']);
                    
                    // Match the first sentence
                    $line = explode("\n", str_replace("\r\n", "\n", !empty($row['story_lead']) ? $row['story_lead'] : $row['story_blurb']));
                    $row['firstline']   = strip_tags($line[0]);
                    
                    $row['hometext'] = wpautop(process_bbcode($row['story_blurb']));
                    $row['bodytext'] = wpautop(process_bbcode($row['story_body']));
                    $row['title'] = format_topictitle($row['story_title']);
                    $row['featured_image'] = $row['story_image'];
                    
                    if (empty($row['slug'])) {
                        $row['slug'] = $this->createSlug($row['story_id']); 
                    }
                    
                    $row['url'] = $this->makePermaLink($row['story_slug']); 
                    $matches[$id] = $row;
                }
                
                return $matches;
                
            }
            
            /**
             * Fall back to database query
             */
            
            if (!$data = $this->Memcached->fetch($mckey)) {
            
                $timer = Debug::GetTimer(); 
            
                $query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id, u.user_id, u.username, u.user_avatar 
                        FROM nuke_stories AS s
                        LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
                        LEFT JOIN nuke_users AS u ON s.informant = u.username
                        WHERE s.title != \"\"
                        AND s.approved = ?
                        ORDER BY s.time DESC
                        LIMIT ?, ?"; 
                
                if ($result = $this->db_readonly->fetchAll($query, array("1", $offset, $number))) {
                    $return = array(); 
                    
                    foreach ($result as $row) {
                        if (function_exists("relative_date")) {
                            $row['time_relative'] = relative_date(strtotime($row['time']));
                        } else {
                            $row['time_relative'] = $row['time'];
                        }
                        
                        // Match the first sentence
                        $line = explode("\n", str_replace("\r\n", "\n", $row['hometext']));
                        $row['firstline']   = strip_tags($line[0]);
                        
                        $row['hometext']    = format_post($row['hometext']);
                        $row['hometext']    = wpautop($row['hometext']);
                        
                        if (empty($row['slug'])) {
                            $row['slug'] = $this->createSlug($row['sid']); 
                        }
                        
                        $row['url'] = $this->makePermaLink($row['slug']); 
                        
                        $return[] = $row; 
                    }
                    
                    $this->Memcached->save($mckey, $return, $mcexp); 
                    
                    Debug::LogEvent(__METHOD__, $timer); 
                }
                    
                return $return;
            }
        }
        
        /**
         * Get pending stories
         * @version 3.0
         * @since Version 3.0
         * @return mixed
         */
         
        public function getPending() {
            
            #$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, u.username FROM nuke_stories AS s, nuke_topics AS t, nuke_users AS u WHERE s.user_id = u.user_id AND s.topic = t.topicid";
            $query = "SELECT s.*, t.topicname, t.topictext, u.username, 'newqueue' AS queue
                FROM nuke_stories AS s
                LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
                LEFT JOIN nuke_users AS u ON s.user_id = u.user_id
                WHERE s.approved = 0
                AND s.queued = 0
                ORDER BY s.time DESC";
            
            $return = array();
            
            foreach ($this->db_readonly->fetchAll($query) as $row) {
                if ($row['title'] == "") {
                    $row['title'] = "No subject";
                }
                
                $return[] = $row; 
            }
            
            /**
             * Get stories from the older queue
             */
            
            $query = "SELECT q.qid AS sid, q.uid AS user_id, u.username, q.subject AS title, q.story AS hometext, q.storyext AS bodytext, q.timestamp AS time, q.source, t.topicname, t.topictext, q.topic, 'oldqueue' AS queue
                        FROM nuke_queue AS q
                        LEFT JOIN nuke_topics AS t ON q.topic = t.topicid
                        LEFT JOIN nuke_users AS u ON q.uid = u.user_id
                        ORDER BY q.timestamp DESC";
            
            
            foreach ($this->db_readonly->fetchAll($query) as $row) {
                if ($row['title'] == "") {
                    $row['title'] = "No subject";
                }
                
                $return[] = $row; 
            }   
            
            return $return;
        }
        
        /**
         * Most read articles this week
         * @version 3.0
         * @since Version 3.2
         * @return mixed
         * @param int $limit
         */
        
        public function mostReadThisWeek($limit = 5) {
            $return = false;
            
            $params = array(); 
            
            if (isset($this->id) && filter_var($this->id, FILTER_VALIDATE_INT)) {
                $topic_sql = "AND s.topic = ?";
                $params[] = $this->id; 
            } else {
                $topic_sql = NULL;
            }
            
            $query = "SELECT s.*, t.topictext, t.topicname FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid " . $topic_sql . " AND s.weeklycounter > 0 ORDER BY s.weeklycounter DESC LIMIT 0, ?";
            $params[] = $limit;
            
            if ($result = $this->db_readonly->fetchAll($query, $params)) {
                $return = array(); 
                
                foreach ($result as $row) {
                    if (function_exists("relative_date")) {
                        $row['time_relative'] = relative_date(strtotime($row['time']));
                    } else {
                        $row['time_relative'] = $row['time'];
                    }
                    
                    // Match the first sentence
                    $line = explode("\n", str_replace("\r\n", "\n", !empty($row['lead']) ? $row['lead'] : $row['hometext']));
                    $row['firstline']   = trim(strip_tags($line[0]));
                    
                    $row['story_lead'] = !empty($row['lead']) ? $row['lead'] : $row['hometext'];
                    $row['story_body'] = !empty($row['paragraphs']) ? $row['paragraphs'] : $row['bodytext'];
                    
                    $row['hometext'] = wpautop(process_bbcode(!empty($row['lead']) ? $row['lead'] : $row['hometext']));
                    $row['bodytext'] = wpautop(process_bbcode(!empty($row['paragraphs']) ? $row['paragraphs'] : $row['bodytext']));
                    $row['title'] = format_topictitle($row['title']);
                    
                    if (empty($row['slug'])) {
                        $row['slug'] = $this->createSlug($row['sid']); 
                    }
                    
                    $row['url'] = $this->makePermaLink($row['slug']); 
                    
                    $return[] = $row; 
                }
            }
                
            return $return;
        }
        
        /**
         * List all topics
         * @version 3.0
         * @since Version 3.0
         * @param int $id
         * @return mixed
         */
        
        public function topics($id = false) {
            
            $params = array(); 
            $return = array(); 
            
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $query = "SELECT * FROM nuke_topics WHERE topicid = ? ORDER BY topictext";
                $params[] = $id;
            } else {
                $query = "SELECT * FROM nuke_topics ORDER BY topictext";
            }
            
            foreach ($this->db_readonly->fetchAll($query, $params) as $row) {
                $return[] = $row; 
            }
            
            return $return;
            
        }
        
        /**
         * Complying with naming conventions
         * @param int $id
         */
         
        public function getTopics($id = false) {
            return $this->topics($id); 
        }
        
        /**
         * Generate the URL slug for this news article
         * @since Version 3.7.5
         * @param int $story_id
         * @return string
         */
        
        public function createSlug($story_id = false) {
            if (RP_DEBUG) {
                global $site_debug;
                $debug_timer_start = microtime(true);
            }
                
            // Assume ZendDB
            $find = array(
                "(",
                ")",
                "-",
                "?",
                "!",
                "#",
                "$",
                "%",
                "^",
                "&",
                "*",
                "+",
                "="
            );
            
            $replace = array(); 
            
            foreach ($find as $item) {
                $replace[] = "";
            }
            
            if ($story_id) {
                $title = $this->db->fetchOne("SELECT title FROM nuke_stories WHERE sid = ?", $story_id); 
            } elseif (isset($this->title) && !empty($this->title)) {
                $title = $this->title;
                $story_id = $this->id;
            } else {
                return false;
            }
            
            $name = str_replace($find, $replace, $title);
            $proposal = ContentUtility::generateUrlSlug($name);
            
            /**
             * Trim it if the slug is too long
             */
            
            if (strlen($proposal) >= 256) {
                $proposal = substr($poposal, 0, 200); 
            }
            
            /**
             * Check that we haven't used this slug already
             */
            
            $result = $this->db_readonly->fetchAll("SELECT sid FROM nuke_stories WHERE slug = ? AND sid != ?", array($proposal, $story_id)); 
            
            if (count($result)) {
                $proposal .= count($result);
            }
            
            if (isset($this->slug)) {
                $this->slug = $proposal;
            }
            
            /**
             * Add this slug to the database
             */
            
            $data = array(
                "slug" => $proposal
            );
            
            $where = array(
                "sid = ?" => $story_id
            );
            
            $rs = $this->db->update("nuke_stories", $data, $where); 
            
            if (RP_DEBUG) {
                if ($rs === false) {
                    $site_debug[] = "Zend_DB: FAILED create url slug for story ID " . $story_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
                } else {
                    $site_debug[] = "Zend_DB: SUCCESS create url slug for story ID " . $story_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
                }
            }
            
            /**
             * Return it
             */
            
            return $proposal;
        }
        
        /**
         * Make a permalink
         * @since Version 3.7.5
         * @return string
         * @param string|int $entity
         */
        
        public function makePermaLink($entity = false) {
            if (!$entity) {
                return false;
            }
            
            if (filter_var($entity, FILTER_VALIDATE_INT)) {
                $slug = $this->db_readonly->fetchOne("SELECT slug FROM nuke_stories WHERE sid = ?", $entity); 
                
                if ($slug === false || empty($slug)) {
                    $slug = $this->createSlug($entity); 
                }
            } else {
                $slug = $entity;
            }
            
            $permalink = "/news/s/" . $slug; 
            
            return $permalink;
        }
    }
    