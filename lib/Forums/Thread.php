<?php
    
    /**
     * Forums API
     * @since Version 3.0.1
     * @version 3.8.7
     * @package Railpage
     * @author James Morgan, Michael Greenhill
     */
     
    namespace Railpage\Forums;
    
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\News\Article;
    use Railpage\Images\Image;
    use Railpage\Images\ImageFactory;
    use DateTime;
    use DateTimeZone;
    use Exception;
    use stdClass;

    /**
     * phpBB thread class
     * @since Version 3.0.1
     * @version 3.0.1
     * @author James Morgan
     */
    
    class Thread extends Forums {
        
        /**
         * Thread ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $id
         */
        
        public $id;
        
        /**
         * Thread title
         * @since Version 3.0.1
         * @version 3.0.1
         * @var string $title
         */
        
        public $title;
        
        /**
         * Thread OP user ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $starteruid
         */
        
        public $starteruid;
        
        /**
         * Thread creation date
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $starttime
         */
        
        public $starttime;
        
        /**
         * Number of thread views
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $view
         */
        
        public $views = 0;
        
        /**
         * Number of thread replies
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $replies
         */
        
        public $replies = 0;
        
        /**
         * Number of posts in this thread
         * @since Version 3.8.7
         * @version 3.8.1
         * @var int $posts
         */
        
        public $posts = 0;
        
        /**
         * Thread status
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $status
         */
        
        public $status = 0;
        
        //Voting not implemented.
        
        /**
         * Thread type
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $type
         */
        
        public $type = 0;
        
        /**
         * Instance of Phpbb_post for first post ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var object $firstpost
         */
        
        public $firstpost;
        
        /**
         * Instance of Phpbb_post for last post ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var object $lastpost
         */
        
        public $lastpost;
        
        /**
         * Poll data
         * @since Version 3.2
         * @version 3.2
         * @var array $polldata
         */
        
        public $polldata;
        
        /**
         * Are there any highlighted posts?
         * @since Version 3.2
         * @var boolean $highlighted
         */
        
        public $highlighted = false;
        
        /**
         * Post IDs of highlighte dposts
         * @since Version 3.7.5
         * @var array $highlights
         */
        
        public $highlights = array();
        
        /**
         * Forum ID
         * @since Version 3.0.1
         * @version 3.0.1
         * @var int $forum
         */
        
        public $forum;
        
        /**
         * URL slug
         * @since Version 3.8.7
         * @var string $slug
         */
        
        public $url_slug;
        
        /**
         * Date the thread was started
         * @since Version 3.8.7
         * @var \DateTime $DateStarted
         */
        
        public $DateStarted;
        
        /**
         * Instance of \Railpage\Users\User who is currently viewing this thread
         * @since Version 3.9.1
         * @var \Railpage\Users\User $Viewer
         */
        
        public $Viewer;
        
        /**
         * Meta data for this thread
         * @since Version 3.9.1
         * @var array $meta
         */
        
        public $meta;
        
        /**
         * Constructor
         * @since Version 3.0.1
         * @version 3.0.1
         * @param int $threadid
         * @param object $database
         */
        
        function __construct($threadid = false) {
            
            if (RP_DEBUG) {
                global $site_debug;
                $debug_timer_start = microtime(true);
            }
            
            $this->starttime = time();
            $this->Module = new Module("forums");
            
            parent::__construct(); 
            
            if (filter_var($threadid, FILTER_VALIDATE_INT)) {
                $query = "SELECT *, (SELECT COUNT(post_id) FROM nuke_bbposts WHERE topic_id = ?) AS num_posts FROM nuke_bbtopics WHERE topic_id = ? LIMIT 1";
                // MGH 9/09/2014 - COUNT(post_id) added to properly calculate number of posts/replies
                
                $row = $this->db->fetchRow($query, array($threadid, $threadid));
            } elseif (is_string($threadid)) {
                $query = "SELECT * FROM nuke_bbtopics WHERE url_slug = ? LIMIT 1";
                $row = $this->db->fetchRow($query, $threadid);
                
                $row['num_posts'] = $this->db->fetchOne("SELECT COUNT(post_id) FROM nuke_bbposts WHERE topic_id = ?", $row['topic_id']);
                // MGH 9/09/2014 - COUNT(post_id) added to properly calculate number of posts/replies
            }
                
            if (isset($row) && is_array($row)) {    
                $this->id       = $row['topic_id'];
                $this->title    = function_exists("format_topictitle") ? format_topictitle($row['topic_title']) : $row['topic_title'];
                $this->forum    = new Forum($row['forum_id']);
                $this->url      = new Url(sprintf("/f-t%d.htm", $this->id));
                
                $this->Forum =& $this->forum;
                
                $this->starteruid   = $row['topic_poster'];
                $this->starttime    = $row['topic_time'];
                $this->views        = $row['topic_views'];
                $this->replies      = $row['num_posts'] - 1;
                $this->posts        = $row['num_posts'];
                $this->status       = $row['topic_status'];
                $this->firstpost    = $row['topic_first_post_id'];
                $this->lastpost     = $row['topic_last_post_id'];
                $this->type         = $row['topic_type'];
                $this->url_slug     = $row['url_slug'];
                $this->meta = isset($row['topic_meta']) ? json_decode($row['topic_meta'], true) : array(); 
                
                if (empty($this->url_slug)) {
                    $this->createSlug();
                    $this->commit();
                }
                
                $this->DateStarted = new DateTime(sprintf("@%s", $row['topic_time']));
                $this->DateStarted->setTimezone(new DateTimeZone("Australia/Melbourne"));
                
                if ($this->forum->id == 71) {
                    $this->url->developers = sprintf("/%s/d/%s", "developers", $this->url_slug);
                }
                
                /**
                 * Get highlighted posts within this thread
                 */
                
                /*
                if (!$highlights = getMemcacheObject(sprintf("railpage.highlighted.thread=%d", $this->id))) {
                    $query = "SELECT post_id FROM nuke_bbposts WHERE topic_id = ? AND post_rating > 0";
                    
                    $highlights = $this->db->fetchAll($query, $this->id);
                    
                    if (!is_array($highlights)) {
                        $highlights = array();
                    }
                    
                    @setMemcacheObject(sprintf("railpage.highlighted.thread=%d", $this->id), $highlights);
                }
                    
                foreach ($highlights as $row) {
                    $this->highlights[] = $row['post_id']; 
                }
                
                if (count($this->highlights)) { 
                    $this->highlighted = true; 
                }
                */
                
                /**
                 * Get poll data from this thread
                 * So totally deprecated. Let's just comment out this code and see what breaks... 6/09/2014 MGH
                 */
                
                /**
                if ($this->db instanceof \sql_db) {
                    // Poll data
                    $query = "SELECT v.vote_id, v.vote_text, v.vote_start, v.vote_length, vr.vote_option_id, vr.vote_option_text, vr.vote_result, vt.vote_user_id
                                FROM nuke_bbvote_desc AS v 
                                LEFT JOIN nuke_bbvote_results AS vr ON vr.vote_id = v.vote_id
                                LEFT JOIN nuke_bbvote_voters AS vt ON vt.vote_id = v.vote_id
                                WHERE v.topic_id = ".$this->id;
                    
                    if ($rs = $this->db->query($query)) {
                        if ($rs->num_rows > 0) {
                            // We have a poll!
                            
                            $polldata = array(); 
                            
                            while ($row = $rs->fetch_assoc()) {
                                $polldata['id']         = $row['vote_id'];
                                $polldata['question']   = $row['vote_text'];
                                $polldata['start']      = $row['vote_start']; 
                                $polldata['finish']     = $row['vote_start'] + $row['vote_length'];
                                
                                if (is_array($polldata['voters'])) {
                                    if (!in_array($row['vote_user_id'], $polldata['voters'])) {
                                        $polldata['voters'][] = $row['vote_user_id'];
                                    }
                                }
                                
                                $polldata['options'][$row['vote_option_id']]['option']  = $row['vote_option_text'];
                                $polldata['options'][$row['vote_option_id']]['votes']   = $row['vote_result'];
                            }
                            
                            $this->polldata = $polldata;
                        }
                    } else {
                        throw new Exception($this->db->error);
                    }
                } else {
                    // Complete this later
                }
                */
            }
            
            if (RP_DEBUG) {
                $site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
            }
        }
        
        /**
         * Validate the changes to the thread
         * @since Version 3.0.1
         * @version 3.8.1
         * @todo Post validation
         * @return boolean
         */
        
        function validate() {
            if (empty($this->title)) {
                throw new Exception("Thread title cannot be empty");
            }
            
            if (empty($this->starteruid)) {
                throw new Exception("The author of this post is not known");
            }
            
            if (is_null($this->type)) {
                $this->type = 0;
            }
            
            if (empty($this->firstpost)) {
                $this->firstpost = 0;
            }
            
            if (empty($this->lastpost)) {
                $this->lastpost = 0;
            }
            
            if (empty($this->starttime)) {
                $this->starttime = time();
            }
            
            if (empty($this->views)) {
                $this->views = 0;
            }
            
            if (empty($this->replies)) {
                $this->replies = 0;
            }
            
            if (empty($this->url_slug)) {
                $this->createSlug();
            }
            
            return true;
        }
        
        /**
         * Commit the thread
         *
         * If the class instance knows of an existing thread, it will update it - otherwise it will create a new thread
         * @since Version 3.0.1
         * @version 3.8.1
         * @return boolean
         */
        
        function commit() {
            $this->validate(); 
            
            $data = array(
                "forum_id" => $this->forum->id,
                "topic_title" => $this->title,
                "topic_poster" => $this->starteruid,
                "topic_time" => $this->starttime,
                "topic_views" => $this->views,
                "topic_replies" => $this->replies,
                "topic_status" => $this->status,
                "topic_first_post_id" => $this->firstpost,
                "topic_last_post_id" => $this->lastpost,
                "topic_type" => $this->type,
                "url_slug" => $this->url_slug,
                "topic_meta" => json_encode($this->meta)
            );
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = array(
                    "topic_id = ?" => $this->id
                );
                
                $this->db->update("nuke_bbtopics", $data, $where); 
            } else {
                $this->db->insert("nuke_bbtopics", $data); 
                $this->id = $this->db->lastInsertId(); 
                
                $this->url = new Url(sprintf("/f-t%d.htm", $this->id));
            }
            
            return true;
        }
        
        /**
         * Get posts in this thread
         * @since Version 3.2
         * @version 3.2
         * @return array
         * @param int $items_per_page
         * @param int $page_num
         * @param string $sort
         */
        
        public function posts($items_per_page = 25, $page_num = 1, $sort = "ASC", $highlights = false) {
            if ($highlights) {
                $highlight_sql = " AND p.post_rating > 0 ";
            } else {
                $highlight_sql = "";
            }
            
            if ($this->db instanceof \sql_db) {
                $query = "SELECT 
                                SQL_CALC_FOUND_ROWS
                                u.username, 
                                u.user_id, 
                                u.user_lastvisit,
                                u.user_posts,
                                u.user_warnlevel,
                                u.user_rank,
                                u.user_sig,
                                u.user_avatar,
                                u.user_avatar_width,
                                u.user_avatar_height,
                                u.uWheat,
                                u.uChaff,
                                u.user_from,
                                u.user_level,
                                pt.bbcode_uid,
                                pt.post_text,
                                r.rank_title AS special_rank,
                                p.* 
                            FROM nuke_bbposts p
                            
                            LEFT JOIN nuke_bbposts_text AS pt ON pt.post_id = p.post_id
                            LEFT JOIN nuke_users AS u ON p.poster_id = u.user_id
                            LEFT JOIN nuke_bbranks AS r ON r.rank_id = u.user_rank
                            
                            WHERE p.topic_id = ".$this->db->real_escape_string($this->id)." 
                            ".$highlight_sql."
                            ORDER BY p.post_time ".$sort." 
                            LIMIT ".$this->db->real_escape_string(($page_num - 1) * $items_per_page).", ".$this->db->real_escape_string($items_per_page);
                
                if ($rs = $this->db->query($query)) {
                    $total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
                    $total = $total->fetch_assoc(); 
                    
                    $topics = array();
                    $topics['total_posts'] = $total['total'];
                    $topics['total_pages'] = ceil($total['total'] / $items_per_page);
                    $topics['page_num'] = $page_num;
                    $topics['items_per_page'] = $items_per_page;
                    
                    while ($row = $rs->fetch_assoc()) {
                        
                        $row['post_text'] = stripslashes($row['post_text']);
                        $topics['posts'][$row['post_id']] = $row;
                    }
                    
                    return $topics;
                } else {
                    trigger_error("phpBB Thread : Unable to fetch posts topic id ".$this->id);
                    trigger_error($this->db->error);
                    trigger_error($query);
                    
                    return false;
                }
            } else {
                $query = "SELECT 
                                SQL_CALC_FOUND_ROWS
                                u.username, 
                                u.user_id, 
                                u.user_lastvisit,
                                u.user_posts,
                                u.user_warnlevel,
                                u.user_rank,
                                u.user_sig,
                                u.user_avatar,
                                u.user_avatar_width,
                                u.user_avatar_height,
                                u.uWheat,
                                u.uChaff,
                                u.user_from,
                                u.user_level,
                                pt.bbcode_uid,
                                pt.post_text,
                                r.rank_title AS special_rank,
                                p.* 
                            FROM nuke_bbposts p
                            
                            LEFT JOIN nuke_bbposts_text AS pt ON pt.post_id = p.post_id
                            LEFT JOIN nuke_users AS u ON p.poster_id = u.user_id
                            LEFT JOIN nuke_bbranks AS r ON r.rank_id = u.user_rank
                            
                            WHERE p.topic_id = ? 
                            ".$highlight_sql."
                            ORDER BY p.post_time ? 
                            LIMIT ?, ?";
                
                $params = array(
                    $this->id,
                    $sort,
                    ($page_num - 1) * $items_per_page,
                    $items_per_page
                );
                
                $result = $this->db->fetchAll($query); 
                
                $topics = array();
                $topics['total_posts'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total");
                $topics['total_pages'] = ceil($total['total'] / $items_per_page);
                $topics['page_num'] = $page_num;
                $topics['items_per_page'] = $items_per_page;
                
                foreach ($result as $row) {
                    $row['post_text'] = stripslashes($row['post_text']);
                    $topics['posts'][$row['post_id']] = $row;
                }
                
                return $topics;
            }
        }
        
        /**
         * Mark this topic as viewed
         * @since Version 3.2
         * @version 3.8.7
         * @author Michael Greenhill
         * @param int $user_id
         * @return \Railpage\Forums\Thread
         */
        
        public function viewed($user_id = false) {
            if (!filter_var($this->id)) {
                throw new Exception("Can't mark this thread as viewed because no thread ID exists");
            }
            
            #if (isset($this->Viewer) && $this->Viewer instanceof User && !$user_id) {
            #   $user_id = $this->Viewer->id;
            #}
            
            if (filter_var($user_id, FILTER_VALIDATE_INT)) {
                $this->Viewer = UserFactory::CreateUser($user_id);
            }
            
            if ($this->Viewer instanceof User) {
                Utility\ForumsUtility::updateUserThreadView($this, $this->Viewer);
            }
            
            return;
            
            if (filter_var($user_id, FILTER_VALIDATE_INT) && $user_id > 0) {
                $query = "CALL update_viewed_thread(?, ?)";
                $params = array(
                    $this->id,
                    $user_id
                );
                
                $result = $this->db->query($query, $params); 
                $result->fetchAll();
                $result->closeCursor();
            }
            
            return $this;
        }
        
        /**
         * Submit a vote for a forum poll
         * @since Version 3.2
         * @version 3.2
         * @param int $user_id
         * @param int $option_id
         * @return boolean
         */
        
        public function vote($user_id = false, $option_id = false) {
            if (!$user_id || !$option_id || !$this->id || empty($this->polldata)) {
                return false;
            }
            
            if ($this->db instanceof \sql_db) {
                $query = "UPDATE nuke_bbvote_results SET vote_result = vote_result + 1 WHERE vote_id = ".$this->polldata['id']." AND vote_option_id = ".$this->db->real_escape_string($option_id);
                
                if ($this->db->query($query)) {
                    // Hooray - now to record this user's vote
                    $dataArray = array(); 
                    $dataArray['vote_id'] = $this->polldata['id']; 
                    $dataArray['vote_user_id'] = $user_id; 
                    $dataArray['vote_user_ip'] = encode_ip($_SERVER['REMOTE_ADDR']); 
                    
                    $query = $this->db->buildQuery($dataArray, "nuke_bbvote_voters"); 
                    
                    if ($this->db->query($query)) {
                        return true;
                    } else {
                        throw new Exception($this->db->error); 
                        return false;
                    }
                } else {
                    throw new Exception($this->db->error); 
                    return false;
                }
            } else {
                // Ehhh 
            }
        }
        
        /**
         * Redraw the stats for this thread (total posts, last post, etc)
         * @since Version 3.8.7
         * @return \Railpage\Forums\Thread
         */
        
        public function reDrawStats() {
            $query = "SELECT (
                            SELECT count(post_id) AS count FROM nuke_bbposts WHERE topic_id = ?
                        ) AS post_count, (
                            SELECT post_id FROM nuke_bbposts WHERE topic_id = ? ORDER BY post_time DESC LIMIT 1
                        ) AS newest_post_id, (
                            SELECT post_id FROM nuke_bbposts WHERE topic_id = ? ORDER BY post_time ASC LIMIT 1
                        ) AS first_post_id";
            
            $where = array(
                $this->id,
                $this->id,
                $this->id
            );
            
            $result = $this->db->fetchRow($query, $where);
            $this->replies = $result['post_count'] - 1;
            $this->posts = $result['post_count'];
            $this->firstpost = $result['first_post_id'];
            $this->lastpost = $result['newest_post_id'];
            
            $this->commit();
            
            return $this;
        }
        
        /**
         * Get posts within this thread
         * @since Version 3.8.7
         * @param int $items_per_page
         * @param int $page
         * @yield \Railpage\Forums\Post
         * @return \Railpage\Forums\Post
         */
         
        public function getPosts($items_per_page = 25, $page = 1) {
            $query = "SELECT post_id FROM nuke_bbposts WHERE topic_id = ? LIMIT ?, ?";
            
            foreach ($this->db->fetchAll($query, array($this->id, ($page - 1) * $items_per_page, $items_per_page)) as $row) {
                yield new Post($row['post_id']);
            }
        }
        
        /**
         * Get the first post of this thread
         * @since Version 3.8.7
         * @return \Railpage\Forums\Post
         */
        
        public function getFirstPost() {
            if (filter_var($this->firstpost, FILTER_VALIDATE_INT)) {
                return new Post($this->firstpost);
            } else {
                $Post = new Post;
                $Post->thread = $this;
                
                return $Post;
            }
        }
        
        /**
         * Set the forum that this thread belongs to
         * @since Version 3.8.7
         * @param \Railpage\Forums\Forum $Forum
         * @return \Railpage\Forums\Thread
         */
        
        public function setForum(Forum $Forum) {
            if (!$this->forum instanceof Forum || !filter_var($this->forum->id, FILTER_VALIDATE_INT)) {
                $this->forum = $Forum;
            }
            
            return $this;
        }
        
        /**
         * Set the thread author
         * @since Version 3.9.1
         * @param \Railpage\Users\User $User
         * @return \Railpage\Forums\Thread
         */
        
        public function setAuthor(User $User) {
            $this->starteruid = $User->id; 
            
            return $this;
        }
        
        /**
         * Create a URL slug
         * @since Version 3.8.7
         */
        
        private function createSlug() {
            
            $proposal = substr(create_slug($this->title), 0, 60);
            
            $result = $this->db->fetchAll("SELECT topic_id FROM nuke_bbtopics WHERE url_slug = ?", $proposal); 
            
            if (count($result)) {
                $proposal .= count($result);
            }
            
            $this->url_slug = $proposal;
            
        }
        
        /**
         * Set the viewer of this thread
         * @since Version 3.8.7
         * @param \Railpage\Users\User $User
         * @return \Railpage\Forums\Thread
         * @throws \Exception if $User is not an instance of \Railpage\Users\User
         */
        
        public function setViewer(User $User) {
            if (filter_var($User->id, FILTER_VALIDATE_INT)) {
                $this->Viewer = $User;
            }
            
            return $this;
        }
        
        /**
         * Get watchers of this thread
         * @since Version 3.9
         * @return array
         */
        
        public function getWatchers() {
            $query = "SELECT w.user_id FROM nuke_bbtopics_watch AS w LEFT JOIN nuke_users AS u ON u.user_id = w.user_id WHERE w.topic_id = ? ORDER BY u.username";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $this->id) as $row) {
                $ThisUser = UserFactory::CreateUser($row['user_id']);
                
                $row = array(
                    "id" => $ThisUser->id,
                    "username" => $ThisUser->username,
                    "url" => $ThisUser->url->getUrls()
                );
                
                $return[] = $row;
            }
            
            return $return;
        }
        
        /**
         * Get the news article associated with this forum thread
         * @since Version 3.9
         * @return \Railpage\News\Article
         */
        
        public function getNewsArticle() {
            
            /**
             * Return false if this thread isn't in the news forum
             */
            
            if ($this->forum->id != 63) {
                return false;
            }
            
            /**
             * Find the article
             */
            
            $query = "SELECT sid AS article_id FROM nuke_stories WHERE ForumThreadId = ?";
            $article_id = $this->db->fetchOne($query, $this->id);
            
            if (!$article_id) {
                return false;
            }
            
            return new Article($article_id);
        }
        
        /**
         * Link an object to this thread
         * @since Version 3.9.1
         * @param object $Object
         * @return \Railpage\Forums\Thread
         */
        
        public function putObject($Object) {
            $class = get_class($Object); 
            $id = $Object->id;
            $name = isset($Object->name) ? $Object->name : $Object->title;
            
            $this->meta['linkedobjects'][$class] = array(
                "id" => $id,
                "name" => $name
            );
            
            $this->commit(); 
            
            return $this;
        }
        
        /**
         * Get objects linked to this thread
         * @since Verison 3.9.1
         * @return array
         */
        
        public function getObjects() {
            return $this->meta['linkedobjects'];
        }
        
        /**
         * Get pinned posts
         * @since Version 3.9.1
         * @return array
         */
        
        public function getPinnedPosts() {
            $query = "SELECT post_id FROM nuke_bbposts WHERE topic_id = ? AND pinned = 1 ORDER BY post_time ASC";
            
            $pinned = array(); 
            
            foreach ($this->db->fetchAll($query, $this->id) as $row) {
                $pinned[] = $row['post_id'];
            }
            
            return $pinned;
        }
        
        /**
         * Is this thread stale?
         * @since Version 3.9.1
         * @return boolean
         */
        
        public function isThreadStale() {
            if (!filter_var($this->lastpost, FILTER_VALIDATE_INT)) {
                return false;
            }
            
            $stale = new DateTime("6 months ago");
            
            $Post = ForumsFactory::CreatePost($this->lastpost); 
            
            return $Post->Date < $stale; 
        }
        
        /**
         * Remove the cover photo attached to this thread
         * @since Version 3.10.0
         * @return \Railpage\Forums\Thread
         */
        
        public function removeCoverPhoto() {
            
            unset($this->meta['coverphoto']); 
            
            return $this;
            
        }
        
        /**
         * Attach a cover photo to this thread
         * @since Version 3.10.0
         * @return \Railpage\Forums\Thread
         */
        
        public function setCoverPhoto(Image $Image) {
            
            $this->meta['coverphoto'] = $Image->id; 
            $this->commit(); 
            
            return $this;
            
        }
        
        /**
         * Get the cover photo attached to this thread
         * @since Version 3.10.0
         * @return \Railpage\Images\Image
         */
         
        public function getCoverPhoto() {
            
            if (isset($this->meta['coverphoto']) && filter_var($this->meta['coverphoto'], FILTER_VALIDATE_INT)) {
                $Image = ImageFactory::CreateImage($this->meta['coverphoto']); 
                
                return $Image;
            }
            
            return false;
            
        }
    }
    