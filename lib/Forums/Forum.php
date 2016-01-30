<?php
    
    /**
     * Forums API
     * @since Version 3.0.1
     * @version 3.2
     * @package Railpage
     * @author James Morgan, Michael Greenhill
     */
     
    namespace Railpage\Forums;
    
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Zend_Acl;
    use Zend_Db_Expr;
    
    use DateTime;
    use Exception;
    use InvalidArgumentException;
    use stdClass;
    
    /** 
     * phpBB Forum class
     * @since Version 3.0.1
     * @version 3.0.1
     * @author James Morgan
     */
    
    class Forum extends Forums {
        
        /**
         * Forum status: unlocked
         * @since Version 3.9.1
         * @const int FORUM_UNLOCKED
         */
        
        const FORUM_UNLOCKED = 0; 
        
        /**
         * Forum status: locked
         * @since Version 3.9.1
         * @const int FORUM_LOCKED
         */
        
        const FORUM_LOCKED = 1;
        
        /**
         * Forum ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $id
         */
        
        public $id;
        
        /**
         * Category ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $catid
         */
        
        public $catid;
        
        /**
         * Forum name
         * @since Version 3.0.1
         * @version 3.0.1
         * @var string $name
         */
        
        public $name;
        
        /**
         * Forum description
         * @since Version 3.0.1
         * @version 3.0.1
         * @var string $description
         */
        
        public $description;
        
        /**
         * Forum status
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $status
         */
        
        public $status;
        
        /**
         * Forum order
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $order
         */
        
        public $order;
        
        /**
         * Number of posts in this forum
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $posts
         */
        
        public $posts;
        
        /**
         * Number of topics in this forum
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $topics
         */
        
        public $topics;
        
        /**
         * Last post ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $last_post
         */
        
        public $last_post;
        
        /**
         * Category object
         * @since Version 3.2
         * @version 3.2
         * @var object $category
         */
        
        public $category;
        
        /**
         * Parent forum
         * @since Version 3.9.1
         * @var \Railpage\Forums\Forum|null $Parent
         */
        
        public $Parent;
        
        /**
         * Constructor
         * @since Version 3.0.1
         * @version 3.0.1
         * @param int $forumid
         * @param object $database
         */
        
        function __construct($forumid = false, $getParent = true) {
            parent::__construct();
            
            if (RP_DEBUG) {
                global $site_debug;
                $debug_timer_start = microtime(true);
            }
            
            $this->Module = new Module("forums");
            
            if (filter_var($forumid, FILTER_VALIDATE_INT)) {
                $this->load($forumid, $getParent);
            } elseif ($shortname = filter_var($forumid, FILTER_SANITIZE_STRING)) {
                if (!is_null($shortname)) {
                    $this->load($shortname, $getParent);
                }
            }
            
            if (RP_DEBUG) {
                $site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
            }
        }
        
        /**
         * Load the forum
         * @since Version 3.9.1
         * @return \Railpage\Forums\Forum
         * @param int|string $id
         * @param boolean $getParent
         */
        
        public function load($id = false, $getParent = false) {
            if ($id === false) {
                throw new InvalidArgumentException("No valid forum ID or shortname was provided");
            }
            
            $this->url = new Url(sprintf("/f-f%d.htm", $id));
            
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                #$query = "SELECT * FROM nuke_bbforums f LEFT JOIN (nuke_bbtopics t, nuke_bbposts p, nuke_bbposts_text pt) ON (f.forum_last_post_id = p.post_id AND p.topic_id = t.topic_id AND pt.post_id = p.post_id) WHERE f.forum_id = ? LIMIT 1";
                
                $query = "SELECT f.*, p.post_time, p.poster_id, p.post_username, pt.post_subject, pt.post_text, pt.bbcode_uid,
                                t.topic_id, t.topic_title, t.topic_time,
                                f.forum_name, f.forum_desc
                            FROM nuke_bbforums AS f
                                LEFT JOIN nuke_bbposts AS p ON f.forum_last_post_id = p.post_id
                                LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
                                LEFT JOIN nuke_bbposts_text AS pt ON pt.post_id = p.post_id
                            WHERE f.forum_id = ?";
                
                $row = $this->db->fetchRow($query, $id);
            }
            
            if (isset($row) && is_array($row)) {
                $this->id           = $row['forum_id'];
                $this->catid        = $row["cat_id"];
                $this->name         = function_exists("html_entity_decode_utf8") ? html_entity_decode_utf8($row["forum_name"]) : $row['forum_name'];
                $this->description  = function_exists("html_entity_decode_utf8") ? html_entity_decode_utf8($row["forum_desc"]) : $row['forum_desc'];
                $this->status       = $row["forum_status"];
                $this->order        = $row["forum_order"];
                $this->posts        = $row["forum_posts"];
                $this->topics       = $row["forum_topics"];
                $this->last_post    = $row["forum_last_post_id"];
                
                $this->last_post_id         = $this->last_post;
                $this->last_post_time       = $row['post_time'];
                $this->last_post_user_id    = $row['poster_id'];
                $this->last_post_username   = $row['post_username'];
                $this->last_post_subject    = $row['post_subject'];
                $this->last_post_text       = $row['post_text'];
                $this->last_post_bbcodeuid  = $row['bbcode_uid'];
                
                $this->last_post_topic_id       = $row['topic_id'];
                $this->last_post_topic_title    = $row['topic_title'];
                $this->last_post_topic_time     = $row['topic_time'];
                
                $this->acl_resource = sprintf("railpage.forums.forum:%d", $this->id);
                
                if ($getParent) {
                    $this->category = ForumsFactory::CreateCategory($this->catid);
                }
                
                if (filter_var($row['forum_parent'], FILTER_VALIDATE_INT) && $row['forum_parent'] > 0) {
                    $this->Parent = new Forum($row['forum_parent']);
                }
            }

        }
        
        /**
         * Set the category for this forum
         * @since Version 3.9.1
         * @return \Railpage\Forums\Forum
         * @param \Railpage\Forms\Category $Category
         */
        
        public function setCategory(Category $Category) {
            
            $this->catid = $Category->id;
            $this->category = $Category;
            
            return $this;
        }
        
        /**
         * Validate changes to this forum
         * @since Version 3.9.1
         * @return boolean
         */
        
        private function validate() {
            
            if (!filter_var($this->catid, FILTER_VALIDATE_INT) && !$this->category instanceof Category) {
                throw new Exception("No valid forum category has been set (hint: Forum::setCategory");
            }
            
            /**
             * Sanitize
             */
            
            $vars = array(
                "name",
                "description"
            );
            
            foreach ($vars as $var) {
                $this->$var = filter_var($this->$var, FILTER_SANITIZE_STRING); 
            }
            
            if (empty($this->name)) {
                throw new Exception("No forum name has been set");
            }
            
            if (!filter_var($this->status, FILTER_VALIDATE_INT)) {
                $this->status = self::FORUM_UNLOCKED;
            }
            
            /**
             * Set some default ints
             */
            
            $vars = array(
                "order", 
                "posts",
                "topics", 
                "last_post",
            );
            
            foreach ($vars as $var) {
                if (!filter_var($this->$var, FILTER_VALIDATE_INT)) {
                    $this->$var = 0;
                }
            }
            
            return true;
        }
        
        /**
         * Commit changes to this forum
         * @since Version 3.9.1
         * @return \Railpage\Forums\Forum
         */
        
        public function commit() {
            
            $this->validate(); 
            
            $data = array(
                "cat_id" => $this->catid,
                "forum_name" => $this->name,
                "forum_desc" => $this->description,
                "forum_status" => $this->status,
                "forum_order" => $this->order,
                "forum_posts" => $this->posts,
                "forum_topics" => $this->topics,
                "forum_last_post_id" => $this->last_post,
                "forum_parent" => $this->Parent instanceof Forum ? $this->Parent->id : 0
            );
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = array(
                    "forum_id = ?" => $this->id
                );
                
                $this->db->update("nuke_bbforums", $data, $where); 
            } else {
                $this->db->insert("nuke_bbforums", $data); 
                $this->id = intval($this->db->lastInsertId());
            }
            
            return $this;
        }
        
        /**
         * Tell the forum that there's a new post
         * @since Version 3.0.1
         * @version 3.0.1
         * @param int $postID
         * @return boolean
         */
        
        function addPost($postID = false) {
            if (empty($postID) || !$postID) {
                throw new \Exception("No post ID specified for " . __CLASS__ . "::" . __FUNCTION__ . "()"); 
                return false;
            }
            
            /*
            if ($this->db instanceof \sql_db) {
                if ($postID != null) {
                    if ($this->db->query("UPDATE nuke_bbforums SET forum_posts=forum_posts+1, forum_last_post_id='".$this->db->real_escape_string($postID)."' WHERE forum_id = '".$this->id."'") === true) { 
                        return true; 
                    } else { 
                        return false; 
                    }
                } else {
                    trigger_error("PhpBB_forum: Class has no data to add post.", E_USER_NOTICE); 
                    return false;
                }
            } else {
            */
                $data = array(
                    "forum_posts" => new Zend_Db_Expr("forum_posts + 1"),
                    "forum_last_post_id" => $postID
                );
                
                $where = array(
                    "forum_id = ?" => $this->id
                );
                
                return $this->db->update("nuke_bbforums", $data, $where); 
            //}
        }
        
        
        /**
         * Tell the forum that there's a new thread
         * @since Version 3.0.1
         * @version 3.0.1
         * @param int $topicID
         * @return boolean
         */ 
        
        
        function addTopic() {
            $data = array(
                "forum_topics" => new Zend_Db_Expr("forum_topics + 1"),
            );
            
            $where = array(
                "forum_id = ?" => $this->id
            );
            
            return $this->db->update("nuke_bbforums", $data, $where); 
        }
        
        /**
         * Reload the forum data - eg when a new post or topic has been created
         * @since Version 3.0.1
         * @version 3.0.1
         */
        
        function refresh() {
            if ($this->db instanceof \sql_db) {
                $result = $this->db->query("SELECT * FROM nuke_bbforums WHERE forum_id = '".$this->db->real_escape_string($this->id)."' LIMIT 1");
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc;
                        
                    foreach ($row as $key => $val) {
                        $row[$key] = iconv('windows-1256', 'UTF-8', $val);
                    }
                    
                    $this->id           = $this->id;
                    $this->catid        = $row["cat_id"];
                    $this->name         = $row["forum_name"];
                    $this->description  = $row["forum_desc"];
                    $this->status       = $row["forum_status"];
                    $this->order        = $row["forum_order"];
                    $this->posts        = $row["forum_posts"];
                    $this->topics       = $row["forum_topics"];
                    $this->last_post    = $row["forum_last_post_id"];
                    $result->close();
                } else {
                    trigger_error("PhpBB_forum: Forum ID ".$this->id." does not exist.", E_USER_NOTICE);    
                }
            } else {
                $query = "SELECT * FROM nuke_bbforums WHERE forum_id = ? LIMIT 1";
                
                $row = $this->db->fetchRow($query, $this->id); 
                
                $this->catid        = $row["cat_id"];
                $this->name         = $row["forum_name"];
                $this->description  = $row["forum_desc"];
                $this->status       = $row["forum_status"];
                $this->order        = $row["forum_order"];
                $this->posts        = $row["forum_posts"];
                $this->topics       = $row["forum_topics"];
                $this->last_post    = $row["forum_last_post_id"];
                
                return true;
            }
        }
        
        /**
         * Permissions check
         * @since Version 3.2
         * @version 3.2
         * @param object $permissions
         * @return boolean
         */
         
        public function get_permission($permission_name = false, $permissions_object = false) {
            if ($permissions_object) {
                $this->permissions = $permissions_object;
            }
            
            if (!$permission_name || !$this->permissions || !$this->id) {
                return false;
            }
            
            $object = $this->permissions->forums[$this->id];
            
            switch ($permission_name) {
                case "view":
                    if ($object['auth_view'] == 0 || $object['auth_read'] == 0) {
                        return true;
                        
                    } elseif ($this->permissions->user->id) {
                        // Logged in
                        
                        if ($object['auth_mod'] > 0) {
                            // User is a moderator
                            return true;
                        }
                        
                        if ($this->permissions->user->level > 1) {
                            return true;
                        }
                    }
                break;
            }
            
            return false;
        }
        
        /**
         * Get topics from this forum
         * @since Version 3.2
         * @version 3.2
         * @return array
         * @param int $items_per_page
         * @param int $page_num
         * @param string $sort
         */
        
        public function topics($items_per_page = 25, $page_num = 1, $sort = "DESC") {
            if ($this->db instanceof \sql_db) {
                $query = "SELECT 
                                SQL_CALC_FOUND_ROWS 
                                t.*, 
                                ufirst.username AS first_post_username, 
                                ufirst.user_id AS first_post_user_id, 
                                ulast.username AS last_post_username, 
                                ulast.user_id AS last_post_user_id,
                                pfirst_text.post_text AS first_post_text,
                                pfirst_text.bbcode_uid AS first_post_bbcode_uid,
                                pfirst.post_time AS first_post_time,
                                plast_text.post_text AS last_post_text,
                                plast_text.bbcode_uid AS last_post_bbcode_uid,
                                plast.post_time AS last_post_time
                                
                            FROM nuke_bbtopics AS t
                            
                            LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
                            LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
                            LEFT JOIN nuke_bbposts_text AS pfirst_text ON pfirst.post_id = pfirst_text.post_id
                            LEFT JOIN nuke_bbposts_text AS plast_text ON plast.post_id = plast_text.post_id
                            LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
                            LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
                            
                            WHERE t.forum_id = ".$this->db->real_escape_string($this->id)." 
                            ORDER BY t.topic_type DESC, plast.post_time ".$this->db->real_escape_string($sort)." 
                            LIMIT ".$this->db->real_escape_string(($page_num - 1) * $items_per_page).", ".$this->db->real_escape_string($items_per_page);
                
                if ($rs = $this->db->query($query)) {
                    $total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
                    $total = $total->fetch_assoc(); 
                    
                    $topics = array();
                    $topics['total_topics'] = $total['total'];
                    $topics['total_pages'] = ceil($total['total'] / $items_per_page);
                    $topics['page_num'] = $page_num;
                    $topics['items_per_page'] = $items_per_page;
                    
                    while ($row = $rs->fetch_assoc()) {
                        $topics['topics'][$row['topic_id']] = $row;
                    }
                    
                    return $topics;
                }
                
                trigger_error("phpBB Forum : Unable to fetch topic list for forum id ".$this->id);
                trigger_error($this->db->error);
                trigger_error($query);
                
                return false;
            }
            
            $query = "SELECT 
                            SQL_CALC_FOUND_ROWS 
                            t.*, 
                            ufirst.username AS first_post_username, 
                            ufirst.user_id AS first_post_user_id, 
                            ulast.username AS last_post_username, 
                            ulast.user_id AS last_post_user_id,
                            pfirst_text.post_text AS first_post_text,
                            pfirst_text.bbcode_uid AS first_post_bbcode_uid,
                            pfirst.post_time AS first_post_time,
                            plast_text.post_text AS last_post_text,
                            plast_text.bbcode_uid AS last_post_bbcode_uid,
                            plast.post_time AS last_post_time
                            
                        FROM nuke_bbtopics AS t
                        
                        LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
                        LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
                        LEFT JOIN nuke_bbposts_text AS pfirst_text ON pfirst.post_id = pfirst_text.post_id
                        LEFT JOIN nuke_bbposts_text AS plast_text ON plast.post_id = plast_text.post_id
                        LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
                        LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
                        
                        WHERE t.forum_id = ?
                        ORDER BY t.topic_type DESC, plast.post_time " . $sort . "
                        LIMIT ?, ?";
            
            $params = array(
                $this->id,
                ($page_num - 1) * $items_per_page,
                $items_per_page
            );
            
            $result = $this->db->fetchAll($query, $params);
            
            $topics = array();
            $topics['total_topics'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
            $topics['total_pages'] = ceil($total['total'] / $items_per_page);
            $topics['page_num'] = $page_num;
            $topics['items_per_page'] = $items_per_page;
            
            foreach ($result as $row) {
                $topics['topics'][$row['topic_id']] = $row;
            }
            
            return $topics;
        }
        
        /**
         * Get threads within this forum
         * @since Version 3.8.7
         * @yield \Railpage\Forums\Thread
         * @param int $items_per_page
         * @param int $page
         */
        
        public function getThreads($items_per_page = 25, $page = 1) {
            $query = "SELECT topic_id FROM nuke_bbtopics WHERE forum_id = ? LIMIT ?, ?";
            
            foreach ($this->db->fetchAll($query, array($this->id, ($page - 1) * $items_per_page, $items_per_page)) as $row) {
                yield new Thread($row['topic_id']);
            }
        }
        
        /**
         * Check various forums permissions 
         * @since Version 3.8.7
         * @param string $permission
         * @return boolean
         */
        
        public function isAllowed($permission) {
            if (!$this->User instanceof User) {
                throw new Exception("Cannot check forum ACL because no valid user has been set (hint: setUser(\$User)");
            }
            
            $this->getACL();
            
            return $this->ZendACL->isAllowed("forums_viewer", $this->acl_resource, $permission);
        }
        
        /**
         * Refresh forum data
         * @since Version 3.9.1
         * @return \Railpage\Forums\Forum
         */
        
        public function refreshForumStats() {
            $query = "SELECT 
                (SELECT COUNT(post_id) AS forum_posts FROM nuke_bbposts WHERE forum_id = ?) AS forum_posts,
                (SELECT COUNT(topic_id) AS forum_topics FROM nuke_bbtopics WHERE forum_id = ?) AS forum_topics,
                (SELECT post_id AS forum_last_post_id FROM nuke_bbposts WHERE forum_id = ? ORDER BY post_time DESC LIMIT 1) AS forum_last_post_id";
            
            $where = array(
                $this->id,
                $this->id,
                $this->id
            );
            
            $stats = $this->db->fetchAll($query, $where);
            
            if (isset($stats[0])) {
                $data = $stats[0];
                
                $where = array(
                    "forum_id = ?" => $this->id
                );
                
                $this->db->update("nuke_bbforums", $data, $where);
            }
            
            return $this;
        }
    }
    