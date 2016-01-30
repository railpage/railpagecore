<?php

/**
 * Forums API
 * @since Version 3.0.1
 * @version 3.9
 * @package Railpage
 * @author James Morgan, Michael Greenhill
 */
 
namespace Railpage\Forums;

use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\Module;
use Railpage\Url;
use Railpage\AppCore;
use Railpage\Registry;
use Exception;
use DateTime;
use ArrayObject;
use stdClass;
use Memcached;
use Doctrine\Common\Cache\MemcachedCache;

/**
 * phpBB post class
 * @since Version 3.0.1
 * @version 3.0.1
 * @author James Morgan
 */

class Post extends Forums {
    
    /**
     * Post ID
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $id
     */
    
    public $id;
    
    /**
     * Post author user id
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $uid
     */
    
    public $uid;
    
    /**
     * Post timestamp
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $timestamp
     */
    
    public $timestamp;
    
    /**
     * DateTime object of post date
     * @since Version 3.8.7
     * @var \DateTime $Date
     */
    
    public $Date;
    
    /**
     * Post IP
     * @since Version 3.0.1
     * @version 3.0.1
     * @var string $ip
     */
    
    public $ip;
    
    /**
     * Post BBCode enabled
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $flag_bbCode
     */
    
    public $flag_bbCode = 1;
    
    /**
     * Post HTML enabled
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $flag_html
     */
    
    public $flag_html = 0;
    
    /**
     * Post smilies enabled
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $flag_smilies
     */
    
    public $flag_smilies = 1;
    
    /**
     * Post signature enabled
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $flag_signature
     */
    
    public $flag_signature = 1;
    
    /**
     * Post last edit timestamp
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $edit_timestamp
     */
    
    public $edit_timestamp;
    
    /**
     * Post edit count
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $edit_count
     */
    
    public $edit_count = 0;
    
    /**
     * Post reported status
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $reported
     */
    
    public $reported = 0;
    
    /**
     * Post herring count
     * @since Version 3.0.1
     * @version 3.0.1
     * @var int $herring_count
     */
    
    public $herring_count = 0;
    
    /**
     * Post subject
     * @since Version 3.0.1
     * @version 3.0.1
     * @var string $subject
     */
    
    public $subject;
    
    /**
     * Post text
     * @since Version 3.0.1
     * @version 3.0.1
     * @var string $text
     */
    
    public $text;
    
    /**
     * Post rating
     * @since Version 3.2
     * @var int $rating
     */
    
    public $rating = 0;
    
    /** 
     * Latitude
     * @since Version 3.2
     * @var string $lat
     */
     
    public $lat;
    
    /**
     * Longitude
     * @since Version 3.2
     * @var string $lon
     */
    
    public $lon;
    
    /**
     * BBCode UID
     * @since Version 3.2
     * @var string $bbcode_uid
     */
    
    public $bbcode_uid;
    
    /**
     * Thread object
     * @since Version 3.0.1
     * @version 3.0.1
     * @var object $thread
     */
    
    public $thread;
    
    /**
     * Editor version
     * @since Version 3.5
     * @version 3.5
     * @var int $editor_version
     */
    
    public $editor_version;
    
    /**
     * Post URL slug
     * @since Version 3.8.7
     * @var string $url_slug
     */
    
    public $url_slug;
    
    /**
     * Is this post pinned in the thread? (Use for flagging a post as important
     * @since Version 3.9.1
     * @var boolean $pinned
     */
    
    public $pinned = false;
    
    /**
     * Author of this post
     * @since Version 3.8.7
     * @param \Railpage\Users\User $Author
     */
     
    public $Author;
    
    /**
     * Constructor
     * @since Version 3.0.1
     * @version 3.0.1
     * @param object $database
     * @param int $postid
     */
    
    public function __construct($postid = false) {
        global $site_debug;
        $post_timer_start = microtime(true);
        
        parent::__construct();
        
        $this->Module = new Module("forums");
        
        $this->timestamp = time();
        $this->mckey = sprintf("railpage:forums;post=%d", $postid);
        
        $this->Memcached = AppCore::getMemcached();  
        
        if (!$row = $this->Redis->fetch($this->mckey)) {
            if (filter_var($postid, FILTER_VALIDATE_INT)) {
                if (RP_DEBUG) {
                    global $site_debug;
                    $debug_timer_start = microtime(true);
                }
                
                if ($this->db instanceof \sql_db) {
                    $query = "SELECT p.*, t.*, u.username, u.user_avatar FROM nuke_bbposts p, nuke_bbposts_text t, nuke_users AS u WHERE u.user_id = p.poster_id AND p.post_id = '".$this->db->real_escape_string($postid)."' AND t.post_id = p.post_id LIMIT 1";
                
                    $result = $this->db->query($query);
                    
                    if ($result && $result->num_rows == 1) {
                        $row = $result->fetch_assoc();
                    }
                } else {
                    $query = "SELECT p.*, t.*, u.username, u.user_avatar FROM nuke_bbposts p, nuke_bbposts_text t, nuke_users AS u WHERE u.user_id = p.poster_id AND p.post_id = ? AND t.post_id = p.post_id LIMIT 1";
                    
                    $row = $this->db->fetchRow($query, $postid);
                    $this->Redis->save($this->mckey, $row, strtotime("+12 hours"));
                
                    if (RP_DEBUG) {
                        if ($row === false) {
                            $site_debug[] = "Zend_DB: FAILED select post ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
                        } else {
                            $site_debug[] = "Zend_DB: SUCCESS select post ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
                        }
                    } 
                }
            } elseif (is_string($postid)) {
                $query = "SELECT p.*, t.*, u.username, u.user_avatar FROM nuke_bbposts p, nuke_bbposts_text t, nuke_users AS u WHERE u.user_id = p.poster_id AND t.url_slug = ? AND t.post_id = p.post_id LIMIT 1";
                
                $row = $this->db->fetchRow($query, $postid);
                $this->Redis->save($this->mckey, $row, strtotime("+12 hours"));
            }
        }
            
        if (isset($row) && is_array($row)) {
            $this->id = $row['post_id'];
            $this->thread = new Thread($row['topic_id']);
            $this->uid = $row['poster_id'];
            $this->username = $row['username'];
            $this->user_avatar = $row['user_avatar'];
            $this->timestamp = $row['post_time'];
            $this->ip = $row['poster_ip'];
            $this->flag_bbCode = $row['enable_bbcode'];
            $this->flag_html = $row['enable_html'];
            $this->flag_smilies = $row['enable_smilies'];
            $this->flag_signature = $row['enable_sig'];
            $this->edit_timestamp = $row['post_edit_time'];
            $this->edit_count = $row['post_edit_count'];
            $this->reported = $row['post_reported'];
            $this->herring_count = $row['post_edit_count'];
            $this->bbcodeuid = $row['bbcode_uid'];
            $this->subject = $row['post_subject'];
            $this->text = stripslashes($row['post_text']);
            $this->old_text = stripslashes($row['post_text']);
            $this->rating = $row['post_rating'];
            $this->bbcode_uid = $row['bbcode_uid'];
            $this->editor_version = $row['editor_version']; 
            $this->url_slug = $row['url_slug'];
            $this->pinned = isset($row['pinned']) ? (bool) $row['pinned'] : false;
            
            if (empty($this->url_slug)) {
                $this->createSlug();
                $this->commit();
            }
            
            $this->lat = $row['lat']; 
            $this->lon = $row['lon'];
            
            $this->Date = new DateTime;
            $this->Date->setTimestamp($row['post_time']);
            $this->Author = UserFactory::CreateUser($row['poster_id']);
            
            $this->makeLinks(); 
        }
        
        if (RP_DEBUG) {
            $site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $post_timer_start, 5) . "s";
        }
    }
    
    /**
     * Post validator
     *
     * Checks that the post is OK before committing it to the database
     * @since Version 3.0.1
     * @version 3.0.1
     * @return boolean
     * @todo Post validation
     */
    
    public function validate() {
        //TODO Validation
        
        if (is_null($this->bbcode_uid)) {
            $this->bbcode_uid = "sausages";
        }
        
        if (is_null($this->lat)) {
            $this->lat = 0;
        }
        
        if (is_null($this->lon)) {
            $this->lon = 0;
        }
        
        if (is_null($this->edit_timestamp)) {
            $this->edit_timestamp = 0;
        }
        
        if (is_null($this->edit_count)) {
            $this->edit_count = 0;
        }
        
        if (empty($this->uid) && $this->Author instanceof User) {
            $this->uid = $this->Author->id;
        }
        
        if (empty($this->uid)) {
            throw new Exception("No post author specified");
        }
        
        if (empty($this->ip)) {
            $this->ip = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL); #$_SERVER['REMOTE_ADDR'];
        }
        
        if (empty($this->url_slug)) {
            $this->createSlug();
        }
        
        if (empty($this->url_slug)) {
            $this->url_slug = "";
        }
        
        if (!filter_var($this->timestamp)) {
            $this->timestamp = time(); 
        }
        
        if (empty($this->text)) {
            throw new Exception("No post text was submitted"); 
        }
        
        
        return true;
    }
    
    /**
     * Commit this post to the database
     *
     * If $this->id is not specified, it will try to create a new post
     * @since Version 3.0.1
     * @version 3.3
     * @return boolean
     */
    
    public function commit() {
        if (empty($this->bbcode_uid)) {
            $this->bbcode_uid = crc32($this->text);
        }
        
        // Set the editor version
        $this->editor_version = defined("EDITOR_VERSION") ? EDITOR_VERSION : 2; 
        
        /** 
         * Validate the post
         */
        
        $this->validate(); 
        
        /** 
         * Start the timer if we're in debug mode
         */
        
        if (RP_DEBUG) {
            global $site_debug;
            $debug_timer_start = microtime(true);
        }
        
        /**
         * Process @mentions
         */
         
        if (function_exists("process_mentions")) {
            $this->text = process_mentions($this->text);
        }
        
        /**
         * Update this information in Redis
         */
        
        $this->Redis->save(sprintf("railpage:forums.post=%d", $this->id), $this);
        $this->Redis->delete(sprintf("railpage:forums.post=%d;processed_message", $this->id));
        
        /**
         * If this is an existing post, insert it into the edit table before we proceed
         */
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            global $User;
            
            #$CurrentPost = new Post($this->id);
            
            if ($this->old_text != $this->text) {
                $dataArray = array(); 
                $dataArray['post_id']       = $this->id;
                $dataArray['thread_id']     = $this->thread->id;
                $dataArray['poster_id']     = $this->uid;
                $dataArray['edit_time']     = time(); 
                $dataArray['edit_body']     = $this->old_text;
                $dataArray['bbcode_uid']    = $this->bbcode_uid;
                $dataArray['editor_id']     = $User->id;
                
                if ($this->db->insert("nuke_bbposts_edit", $dataArray)) {
                    $changes = array(
                        "Forum" => $this->thread->forum->name,
                        "Forum ID" => $this->thread->forum->id,
                        "Thread" => $this->thread->title,
                        "Thread ID" => $this->thread->id,
                        "Author user ID" => $this->uid
                    );
                    
                    try {
                        $Event = new \Railpage\SiteEvent; 
                        $Event->user_id = $User->id; 
                        $Event->title = "Forum post edited";
                        $Event->module_name = "forums";
                        $Event->args = $changes; 
                        $Event->key = "post_id";
                        $Event->value = $this->id;
                        
                        $Event->commit();
                    } catch (Exception $e) {
                        $Error->save($e); 
                    }
                }
            }
        }
        
        unset($CurrentPost); unset($dataArray); unset($query);
        
        /**
         * Insert or update the post
         */
        
        $data = array(
            "topic_id" => $this->thread->id,
            "forum_id" => $this->thread->forum->id,
            "poster_id" => $this->uid,
            "post_time" => $this->timestamp,
            "poster_ip" => $this->ip,
            "enable_bbcode" => $this->flag_bbCode,
            "enable_html" => $this->flag_html,
            "enable_smilies" => $this->flag_smilies,
            "enable_sig" => $this->flag_signature,
            "post_rating" => $this->rating,
            "post_reported" => $this->reported,
            "post_herring_count" => $this->herring_count,
            "lat" => $this->lat,
            "lon" => $this->lon,
            "pinned" => intval($this->pinned)
        );
        
        $text = array(
            "bbcode_uid" => $this->bbcode_uid,
            "post_subject" => $this->subject,
            "post_text" => $this->text,
            "editor_version" => $this->editor_version,
            "url_slug" => $this->url_slug
        );
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $data['post_edit_count'] = $this->edit_count++;
            
            $where = array(
                "post_id = ?" => $this->id
            );
            
            $this->db->update("nuke_bbposts", $data, $where);
            $this->db->update("nuke_bbposts_text", $text, $where);
            $verb = "Update";
        } else {
            $this->db->insert("nuke_bbposts", $data); 
            $this->id = $this->db->lastInsertId(); 
            
            $text['post_id'] = $this->id;
            $this->db->insert("nuke_bbposts_text", $text);
            $verb = "Insert";
            
            $this->thread->reDrawStats();
        }
            
        if (RP_DEBUG) {
            $site_debug[] = "Zend_DB: " . $verb . " Forum post ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
        }
        
        $this->makeLinks();
        
        if (!$this->Author instanceof User) {
            $this->loadAuthor();
        }
        
        return true;
    }
    
    /**
     * Load the author
     * @since Version 3.8.7
     * @return $this
     */
    
    public function loadAuthor() {
        $this->Author = UserFactory::CreateUser($this->uid);
        
        return $this;
    }
    
    /**
     * Delete this post into Thread Storage
     * @since Version 3.8.7
     * @param \Railpage\Users\User $Staff Required for logging of deletion
     * @return $this
     */
    
    public function delete(User $Staff) {
        if ($this->thread->id == "9448") {
            return $this;
        }
        
        $string = sprintf("From topic [url=%s]%s[/url] (#%d), forum [url=%s]%s[/url] (#%d), post #%d, deleted by [url=%s]%s[/url] (uid %d)\n\n", 
            $this->thread->url, $this->thread->title, $this->thread->id,
            $this->thread->forum->url, $this->thread->forum->name, $this->thread->forum->id,
            $this->id,
            $Staff->url, $Staff->username, $Staff->id
        );
        
        $this->text = $string.$this->text;
        
        $OldThread = $this->thread;
        
        $this->thread = new Thread("9448");
        
        if ($this->commit()) {
            $OldThread->reDrawStats();
            
            if (!$this->Author instanceof User) {
                $this->loadAuthor();
                $this->Author->chaff(20);
            }
        }
        
        return $this;
    }
    
    /**
     * Find edits of this post 
     * @since Version 3.8.7
     * @return \ArrayObject
     */
    
    public function getEdits() {
        $query = "SELECT editor_id, edit_time, edit_body, bbcode_uid FROM nuke_bbposts_edit WHERE post_id = ? ORDER BY edit_time DESC";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $DateTime = new DateTime;
            $DateTime->setTimestamp($row['edit_time']);
            $return = new stdClass;
            
            $return->uid = $row['editor_id'];
            $return->text = $row['edit_body'];
            $return->Date = $DateTime;
            $return->bbcode_udi = $row['bbcode_uid'];
            
            yield $return;
        }
        
        return;
    }
    
    /**
     * Get the number of edits of this post
     * @since Version 3.8.7
     * @return int
     */
    
    public function getNumEdits() {
        $query = "SELECT editor_id, edit_time, edit_body, bbcode_uid FROM nuke_bbposts_edit WHERE post_id = ? ORDER BY edit_time DESC";
        
        $result = $this->db->fetchAll($query, $this->id);
        
        if (is_array($result)) {
            return count($result);
        } else {
            return 0;
        }
    }
    
    /**
     * Set the author of this post
     * @since Version 3.8.7
     * @param \Railpage\Users\User $User
     * @return $this
     */
    
    public function setAuthor(User $User) {
        if (!$this->Author instanceof User) {
            $this->Author = $User;
        }
        
        return $this;
    }
    
    /**
     * Set the thread that this post belongs in
     * @since Version 3.8.7
     * @param \Railpage\Forums\Thread $Thread
     * @return $this
     */
    
    public function setThread(Thread $Thread) {
        if (!$this->thread instanceof Thread || !filter_var($this->thread->id, FILTER_VALIDATE_INT)) {
            $this->thread = $Thread;
        }
        
        return $this;
    }
    
    /**
     * Get the thread that this post belomgs in
     * @since Version 3.8.7
     * @return \Railpage\Forums\Thread
     */
    
    public function getThread() {
        if (!$this->thread instanceof Thread) {
            throw new Exception("Cannot find the thread that this post belongs to");
        }
        
        return $this->thread;
    }
    
    /**
     * Create a URL slug
     * @since Version 3.8.7
     */
    
    private function createSlug() {
        
        $proposal = !empty($this->subject) ? substr(create_slug($this->subject), 0, 60) : $this->id;
        
        $result = $this->db->fetchAll("SELECT post_id FROM nuke_bbposts_text WHERE url_slug = ?", $proposal); 
        
        if (count($result)) {
            $proposal .= count($result);
        }
        
        $this->url_slug = $proposal;
        
    }
    
    /**
     * Make links to this post
     * @since Version 3.8.7
     * @return \Railpage\Forums\Post
     */
    
    public function makeLinks() {
        $this->url = new Url(sprintf("/f-p%d.htm#%d", $this->id, $this->id));
        $this->url->single = sprintf("/forums?mode=post.single&id=%d", $this->id);
        $this->url->report = sprintf("/f-report-%d.htm", $this->id);
        $this->url->reply = sprintf("/f-po-quote-%d.htm", $this->id);
        $this->url->delete = sprintf("/f-po-delete-%d.htm", $this->id);
        $this->url->edit = sprintf("/f-po-editpost-%d.htm", $this->id);
        $this->url->replypm = sprintf("/messages/new/from/%d/", $this->id);
        $this->url->iplookup = sprintf("/moderators?mode=ip.lookup&ip=%s", $this->ip);
        $this->url->herring = sprintf("/f-herring-p-%d.htm", $this->id);
        
        if ($this->thread->forum->id == 71) {
            $this->url->developers = sprintf("/%s/d/%s/%s", "developers", $this->thread->url_slug, $this->url_slug);
        }
        
        return $this;
    }
    
    /**
     * Add a reputation marker to this post
     * @since Version 3.9
     * @param int $reputation_type_id
     * @return int
     */
    
    public function addReputationMarker($reputation_type_id = false) {
        if (!filter_var($reputation_type_id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot add a reputation marker to this post because the supplied type ID (\$reputation_type_id) is not valid");
        }
        
        if (!$this->User instanceof User) {
            throw new Exception("Cannot add a reputation marker to this post because no valid user object has been specified");
        }
        
        /**
         * Check if this user has already rated this post
         */
        
        $query = "SELECT id FROM nuke_bbposts_reputation WHERE post_id = ? AND user_id = ?";
        
        $id = $this->db->fetchOne($query, array($this->id, $this->User->id));
        
        /**
         * Add the rating
         */
        
        $data = array(
            "post_id" => $this->id,
            "type" => $reputation_type_id,
            "user_id" => $this->User->id
        );
        
        $markers = $this->getReputationMarkers(); 
        
        foreach ($markers as $marker) {
            if ($marker['id'] == $reputation_type_id) {
                $reputation_type_name = $marker['name'];
                break;
            }
        }
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $where = array(
                "id = ?" => $id
            );
            
            $this->db->update("nuke_bbposts_reputation", $data, $where); 
        } else {
            $this->db->insert("nuke_bbposts_reputation", $data); 
            $id = $this->db->lastInsertId(); 
        }
        
        /** 
         * Add a note to this user
         */
        
        try {
            $this->User->addNote(sprintf("Rated <a href='%s'>Post ID %d</a> as %s", $this->url->url, $this->id, $reputation_type_name));
        } catch (Exception $e) {
            // Throw it away
        }
        
        $markers = $this->getReputationMarkers(true); 
        
        return $id;
    }
    
    /**
     * Get reputation markers for this post
     * @since Version 3.9
     * @return array
     * @param boolean $force Force an update, removing whatever is cached in Memcache
     */
    
    public function getReputationMarkers($force = false) {
        $mckey = sprintf("railpage:forums.post;id=%d;getreputationmarkers", $this->id);
        
        $Registry = Registry::getInstance();
        
        $Memcached = new Memcached;
        $Memcached->addServer($Config->Memcached->Host, 11211);
        
        #$cacheDriver = new MemcachedCache;
        #$cacheDriver->setMemcached($Memcached);
        
        #$this->Memcached = $cacheDriver;
        
        if ($force || !$types = $Memcached->get($mckey)) {
            $query = "SELECT r.*, u.username FROM nuke_bbposts_reputation AS r LEFT JOIN nuke_users AS u ON r.user_id = u.user_id WHERE r.post_id = ?";
            
            $types = $this->getReputationTypes(); 
            
            foreach ($this->db->fetchAll($query, $this->id) as $row) {
                $types[$row['type']]['votes'][] = $row;
                $types[$row['type']]['count'] = count($types[$row['type']]['votes']);
            }
            
            $Memcached->set($mckey, $types, 0);
        }
        
        return $types;
    }
}
