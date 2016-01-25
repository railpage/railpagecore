<?php
    /**
     * News classes
     * @since     Version 3.0.1
     * @version   3.8.7
     * @author    Michael Greenhill
     * @package   Railpage
     * @copyright Copyright (c) 2012 Michael Greenhill
     */

    namespace Railpage\News;

    use DateTime;
    use DateTimeZone;
    use DateInterval;
    use Exception;
    use Railpage\Users\User;
    use Railpage\Users\Utility\AvatarUtility;
    use Railpage\Users\Utility\UrlUtility;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Url;
    use Railpage\fwlink;
    use Railpage\AppCore;
    use Railpage\ContentUtility;
    use Railpage\Debug;

    /**
     * News article display and management
     * @author    Michael Greenhill
     * @version   3.0.1
     * @since     Version 3.8.7
     * @package   Railpage
     * @copyright Copyright (c) 2012 Michael Greenhill
     */
    class Article extends Base {
        
        /**
         * Cache key to store and fetch the formatted lead text from the cache
         * @since Version 3.10.0
         * @const string CACHE_KEY_FORMAT_LEAD
         */
        
        const CACHE_KEY_FORMAT_LEAD = "railpage:format.news.lead=%d";
        
        /**
         * Cache key to store and fetch the formatted paragraph text from the cache
         * @since Version 3.10.0
         * @const string CACHE_KEY_FORMAT_PARAGRAPHS
         */
        
        const CACHE_KEY_FORMAT_PARAGRAPHS = "railpage:format.news.paragraphs=%d";

        /**
         * Status: Approved
         * @since Version 3.9
         * @const int STATUS_APPROVED
         */

        const STATUS_APPROVED = 1;

        /**
         * Status: Unapproved
         * @since Version 3.9
         * @const int STATUS_UNAPPROVED
         */

        const STATUS_UNAPPROVED = 0;

        /**
         * Maximum title length
         * since Version 3.10.0
         * @const int MAX_TITLE_LENGTH
         */

        const MAX_TITLE_LENGTH = 58;

        /**
         * Registry / cache key format
         * since Version 3.10.0
         * @const string REGISTRY_KEY
         */

        const REGISTRY_KEY = "railpage:news.article=%s";

        /**
         * Story ID
         * @since   Version 3.0.1
         * @version 3.0.1
         * @var int $id
         */

        public $id;

        /**
         * Story title
         * @since Version 3.3
         * @var string $title
         */

        public $title;

        /**
         * First line of the story
         * @since Version 3.9
         * @var string $firstline
         */

        public $firstline;

        /**
         * Article summary
         * Will eventually replace separate blurb & body with whole post (content) and summary (lead)
         * @since Version 3.9.1
         * @var string $lead
         */

        public $lead;

        /**
         * Story blurb
         * @since Version 3.3
         * @var string $blurb
         */

        public $blurb;

        /**
         * Story blurb - stripped of BBCode
         * @since Version 3.8
         * @var string $blurb_clean
         */

        public $blurb_clean;

        /**
         * The main body of the article
         * Will eventually replace separate blurb & body with whole post (paragraphs) and summary (lead)
         * @since Version 3.9.1
         * @var string $paragraphs
         */

        public $paragraphs;

        /**
         * Story body
         * @since Version 3.3
         * @var string $body
         */

        public $body;

        /**
         * Story body - stripped of BBCode
         * @since Version 3.8
         * @var string $body_clean
         */

        public $body_clean;

        /**
         * Hits this week
         * @since Version 3.3
         * @var int $hits
         */

        public $hits;

        /**
         * Author user ID
         * @since Version 3.3
         * @var int $user_id
         */

        public $user_id;

        /**
         * Author username
         * @since Version 3.3
         * @var string $username
         */

        public $username;

        /**
         * Staff member user ID
         * @since Version 3.3
         * @var int $staff_user_id
         */

        public $staff_user_id;

        /**
         * Staff member username
         * @since Version 3.3
         * @var string $staff_username
         */

        public $staff_username;

        /**
         * Forum thread ID
         * @since Version 3.3
         * @var int $topic_id
         */

        public $topic_id;

        /**
         * Source URL
         * @since Version 3.3
         * @var string $source
         */

        public $source;

        /**
         * Date added
         * @since Version 3.3
         * @var object $date
         */

        public $date;

        /**
         * Approved
         * @since Version 3.3
         * @var boolean $approved
         */

        public $approved;

        /**
         * Topic object
         * @since Version 3.3
         * @var object $Topic
         */

        public $Topic;

        /**
         * If $pending set to true, we need to retrieve the story from the pending table
         * @since   Version 3.0.1
         * @version 3.0.1
         * @var boolean $pending
         */

        public $pending = false;

        /**
         * Latitude of point of interest
         * @since Version 3.5
         * @var float $lat
         */

        public $lat;

        /**
         * Longitude of point of interest
         * @since Version 3.5
         * @var float $lon
         */

        public $lon;

        /**
         * Flag - has this article been sent to Facebook
         * @since Version 3.6
         * @var boolean $sent_to_fb
         */

        public $sent_to_fb;

        /**
         * URL Slug
         * @since Version 3.7.5
         * @var string $slug
         */

        public $slug;

        /**
         * URL relative to site root
         * @since Version 3.7.5
         * @var string $url
         */

        public $url;

        /**
         * Featured image URL
         * @since Version 3.8
         * @var string $featured_image
         */

        public $featured_image = false;

        /**
         * Fwlink (shortcut) object
         * @since Version 3.8.6
         * @var object $fwlink
         */

        public $fwlink;
        
        /**
         * A unique ID for this article, derived from the original title 
         * @since Version 3.10.0
         * @var string $unique_id
         */
        
        public $unique_id; 
        
        /**
         * Is this article queued for drip feed publishing?
         * @since Version 3.10.0
         * @var boolean $queued
         */
         
        public $queued = false;

        /**
         * Constructor
         * @since   Version 3.0.1
         * @version 3.3
         *
         * @param int|bool $id
         * @param boolean  $pending True/false flag to indicate if this article is in the (new deprecated) pending
         *                          article table
         */

        public function __construct($id = false, $pending = false) {

            $this->pending = $pending;

            // Set the parent constructor
            parent::__construct();

            if ($id) {
                $this->id = intval($id);

                $this->fetch();
            }
        }

        /**
         * Return news article text
         * @since   Version 3.0.1
         * @version 3.4
         *
         * @param int|bool $id
         *
         * @return mixed
         * @throws \Exception if it cannot find the article ID
         */

        public function fetch($id = false) {
            if ($id) {
                $this->id = $id;
            }

            if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
                throw new Exception("Cannot fetch news article - no ID given");
            }

            if (RP_DEBUG) {
                global $site_debug;
                $debug_timer_start = microtime(true);
            }

            $return = false;

            $this->mckey = __METHOD__ . "-" . $this->id;
            $mcexp = strtotime("+1 hour");

            if (!$return = $this->Memcached->fetch($this->mckey)) {

                $query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, t.topicid
							FROM nuke_stories AS s 
							LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
							WHERE s.sid = ?";

                $return = $this->db_readonly->fetchRow($query, $this->id);
                $this->Memcached->save($this->mckey, $return);
            }

            if (isset( $return ) && is_array($return) && !empty( $return )) {

                $this->title = $return['title'];
                $this->blurb = $return['hometext'];
                $this->body = $return['bodytext'];
                $this->date = new DateTime($return['time']);
                $this->hits = isset( $return['weeklycounter'] ) ? intval($return['weeklycounter']) : 0;
                $this->Topic = new Topic($return['topic']);
                $this->user_id = isset( $return['user_id'] ) ? intval($return['user_id']) : 0;
                $this->staff_user_id = isset( $return['staff_id'] ) ? intval($return['staff_id']) : 0;
                $this->topic_id = isset( $return['ForumThreadID'] ) ? intval($return['ForumThreadID']) : 0;
                $this->source = $return['source'];
                $this->approved = isset( $return['approved'] ) ? (bool)$return['approved'] : false;
                $this->sent_to_fb = isset( $return['sent_to_fb'] ) ? (bool)$return['sent_to_fb'] : false;
                $this->featured_image = isset( $return['featured_image'] ) ? $return['featured_image'] : false;
                $this->lead = $return['lead'];
                $this->paragraphs = $return['paragraphs'];
                $this->source = $return['source'];
                $this->unique_id = isset($return['unique_id']) ? $return['unique_id'] : md5($this->title);
                $this->queued = isset($return['queued']) ? $return['queued'] : false;

                // Match the first sentence
                $line = explode("\n", $this->getLead());
                $this->firstline = preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));

                if (isset( $return['geo_lat'] ) && !empty( $return['geo_lat'] ) && isset( $return['geo_lon'] ) && !empty( $return['geo_lon'] )) {
                    $this->lat = $return['geo_lat'];
                    $this->lon = $return['geo_lon'];
                }

                /**
                 * URLs
                 */

                if (empty( $return['slug'] )) {
                    $return['slug'] = $this->createSlug();
                }

                $this->slug = $return['slug'];
                $this->makeURLs();

                /**
                 * Instantiate the author
                 */

                $this->setAuthor(UserFactory::CreateUser($this->user_id));
                $this->username = $this->Author->username;

                /**
                 * Instantiate the staff object
                 */

                if (!filter_var($this->staff_user_id, FILTER_VALIDATE_INT) || $this->staff_user_id == 0) {
                    $this->staff_user_id = 72587;
                }

                $this->setStaff(UserFactory::CreateUser($this->staff_user_id));
                $this->staff_username = $this->Staff->username;

                /**
                 * Alter the URL
                 */

                if (empty( $this->getParagraphs() ) && !empty( $this->source )) {
                    $this->url->url = $this->source;
                    $this->url->canonical = $this->source;
                }

                /**
                 * Set a cover photo
                 */

                $this->guessCoverPhoto();

            } else {
                throw new Exception(sprintf("Cannot find news article #%d", $this->id));
            }

            if (RP_DEBUG) {
                $site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $debug_timer_start, 5) . "s";
            }

            return $return;
        }

        /**
         * Make the URLs for this object
         * @since Version 3.10.0
         * @return void
         */

        public function makeURLs() {

            $this->url = new Url($this->makePermaLink($this->slug));
            $this->url->source = $this->source;
            $this->url->reject = sprintf("/news/pending?task=reject&id=%d", $this->id);
            $this->url->publish = sprintf("/news/pending?task=approve&id=%d", $this->id);
            $this->url->approve = sprintf("/news/pending?task=approve&id=%d", $this->id);
            $this->url->queue = sprintf("/news/pending?task=queue&id=%d", $this->id);
            $this->url->edit = sprintf("/news?mode=article.edit&id=%d", $this->id);
            $this->fwlink = $this->url->short;

            /**
             * Alter the URL
             */

            if (empty( $this->getParagraphs() ) && !empty( $this->source )) {
                $this->url->url = $this->source;
                $this->url->canonical = $this->source;
            }

        }

        /**
         * Someone has viewed an article, update the view count
         * @version 3.0
         * @since   Version 3.0
         * @return bool
         */

        public function viewed() {
            $data = array(
                "weeklycounter" => new \Zend_Db_Expr('weeklycounter + 1')
            );

            $where = array(
                "sid = ?" => $this->id
            );

            $this->db->update("nuke_stories", $data, $where);

            return true;
        }

        /**
         * Reject pending news item
         * @version 3.0
         * @since   Version 3.0
         * @return mixed
         */

        public function reject() {

            /**
             * Insert into Sphinx's rejected articles table
             */

            $data = array(
                "id"    => (int)str_replace(".", "", microtime(true)),
                "title" => $this->title
            );

            $Sphinx = $this->getSphinx();

            $Insert = $Sphinx->insert()->into("idx_news_articles_rejected");
            $Insert->set($data);
            $Insert->execute();

            if ($this->pending) {
                $where = array( "qid = ?" => $this->id );

                $this->db->delete("nuke_queue", $where);
            } else {
                $where = array( "sid = ?" => $this->id );

                $this->db->delete("nuke_stories", $where);
            }

            return true;
        }

        /**
         * Approve a story
         * @version 3.0
         * @since   Version 3.0
         *
         * @param int|\Railpage\Users\User $user_id
         *
         * @return mixed
         */

        public function approve($user_id) {
            if (!$this->id || !$this->db) {
                return false;
            }

            if ($user_id instanceof User) {
                $user_id = $user_id->id;
            }

            if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
                $user_id = false;
            }

            if ($this->pending) {
                $query = "SELECT u.username AS informant, p.geo_lat, p.geo_lon, p.subject AS title, p.story AS hometext, p.storyext AS bodytext, p.topic, p.source FROM nuke_queue p, nuke_users u WHERE u.user_id = p.uid AND p.qid = ?";

                if ($data = $this->db->fetchRow($query, $this->id)) {
                    $data['approved'] = 1;
                    $data['aid'] = $user_id;
                    $data['time'] = new \Zend_Db_Expr('NOW()');

                    $this->db->insert("nuke_stories", $data);

                    $id = $this->db->lastInsertId();

                    $this->db->delete("nuke_queue", array( "qid = ?" => $this->id ));

                    $this->id = $id;
                }
            } else {
                $this->approved = 1;
                $this->staff_user_id = $user_id;
                $this->setStaff(UserFactory::CreateUser($this->staff_user_id));
                $this->date = new DateTime;

                $this->commit();
            }

            /**
             * Flush the Memcache store for the news topic
             */

            if ($this->Topic instanceof Topic) {
                deleteMemcacheObject($this->Topic->mckey);
            }

            return true;
        }

        /**
         * Commit changes to a story
         * @since Version 3.4
         * @return boolean
         */

        public function commit() {

            $this->validate();

            // Format the article blurb
            try {
                if (!empty( $this->blurb )) {
                    $this->blurb = prepare_submit($this->blurb);
                }

                if (!empty( $this->lead )) {
                    $this->lead = prepare_submit($this->lead);
                }
            } catch (Exception $e) {
                Debug::SaveError($e);
            }

            // Format the article body
            try {
                if (!empty( $this->body )) {
                    $this->body = prepare_submit($this->body);
                }

                if (!empty( $this->paragraphs )) {
                    $this->paragraphs = prepare_submit($this->paragraphs);
                }
            } catch (Exception $e) {
                Debug::SaveError($e);
            }

            if ($this->Topic instanceof \Railpage\Forums\Thread) {
                $this->topic_id = $this->Topic->id;
            }

            $dataArray = array();

            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $this->Memcached->delete($this->mckey);
                $this->Memcached->delete(sprintf("json:railpage.news.article=%d", $this->id));

                $this->Redis->delete(sprintf("railpage:news.article=%s", $this->id));
                $this->Redis->delete(sprintf("railpage:news.article=%s", $this->slug));
                
                $this->Memcached->delete(sprintf(self::CACHE_KEY_FORMAT_LEAD, $this->id)); 
                $this->Memcached->delete(sprintf(self::CACHE_KEY_FORMAT_PARAGRAPHS, $this->id)); 
            }

            $dataArray['approved'] = $this->approved;
            $dataArray['title'] = $this->title;
            $dataArray['hometext'] = is_object($this->blurb) ? $this->blurb->__toString() : $this->blurb;
            $dataArray['bodytext'] = is_object($this->body) ? $this->body->__toString() : $this->body;
            $dataArray['lead'] = $this->lead;
            $dataArray['paragraphs'] = $this->paragraphs;
            $dataArray['ForumThreadID'] = $this->topic_id;
            $dataArray['source'] = $this->source;
            $dataArray['user_id'] = $this->Author instanceof User ? $this->Author->id : $this->user_id;
            $dataArray['staff_id'] = empty( $this->staff_user_id ) ? 0 : $this->staff_user_id;
            $dataArray['geo_lat'] = empty( $this->lat ) ? 0 : $this->lat;
            $dataArray['geo_lon'] = empty( $this->lon ) ? 0 : $this->lon;
            $dataArray['sent_to_fb'] = (bool)$this->sent_to_fb;
            $dataArray['time'] = $this->date->format("Y-m-d H:i:s");
            $dataArray['slug'] = empty($this->slug) ? $this->createSlug() : $this->slug;
            $dataArray['topic'] = $this->Topic->id;
            $dataArray['unique_id'] = $this->unique_id;
            $dataArray['queued'] = $this->queued;

            if ($this->featured_image !== false) {
                $dataArray['featured_image'] = $this->featured_image;
            }

            if (!empty( $this->username ) || $this->Author instanceof User) {
                $dataArray['informant'] = $this->Author instanceof User ? $this->Author->username : $this->username;
            }
			
			foreach ($dataArray as $key => $val) {
				$dataArray[$key] = trim($val); 
			}

            /**
             * Save changes
             */

            if (!empty( $this->id ) && $this->id > 0) {
                $where = array(
                    "sid = ?" => $this->id
                );

                $this->db->update("nuke_stories", $dataArray, $where);
            } else {
                $this->db->insert("nuke_stories", $dataArray);
                $this->id = $this->db->lastInsertId();
            }

            /**
             * Update Memcached
             */

            $this->makeJSON();

            /**
             * Update our URLs
             */

            $this->makeURLs();

            return true;
        }

        /**
         * Validate changes to an article before committing changes
         * @since Version 3.4
         * @return boolean
         * @throws \Exception if the article title is empty
         * @throws \Exception if the article blurb and lead are empty
         * @throws \Exception if the body and paragraphs AND source are empty
         * @throws \Exception if the article title is too long
         */

        public function validate() {
            if (empty( $this->title )) {
                throw new Exception("Validation failed: title is empty");
            }

            if (empty( $this->blurb ) && empty( $this->lead )) {
                throw new Exception("Validation failed: blurb is empty");
            }

            if (empty( $this->body ) && empty( $this->source ) && empty( $this->paragraphs )) {
                throw new Exception("Validation failed: body is empty");
            }

            if (is_null($this->blurb) || !empty( $this->lead )) {
                $this->blurb = "";
            }

            if (is_null($this->body) || !empty( $this->paragraphs )) {
                $this->body = "";
            }

            if (is_null($this->paragraphs)) {
                $this->paragraphs = "";
            }

            if (is_null($this->lead)) {
                $this->lead = "";
            }

            if (!isset( $this->Author ) || !$this->Author instanceof User) {
                $this->Author = UserFactory::CreateUser($this->user_id);
            }

            if (!$this->date instanceof DateTime) {
                $this->date = new DateTime;
            }

            if (!filter_var($this->approved)) {
                $this->approved = self::STATUS_UNAPPROVED;
            }

            if (is_null($this->source)) {
                $this->source = "";
            }
			
			if (empty($this->unique_id)) {
				$this->unique_id = md5($this->title); 
			}
            
            if (!is_bool($this->queued)) {
                $this->queued = false;
            }

            /**
             * Try to get the featured image from OpenGraph tags
             */

            if ($this->source && !$this->featured_image) {
                require_once( "vendor" . DS . "scottmac" . DS . "opengraph" . DS . "OpenGraph.php" );
                $graph = \OpenGraph::fetch($this->source);

                #printArray($graph->keys());
                #printArray($graph->schema);

                foreach ($graph as $key => $value) {
                    if ($key == "image" && strlen($value) > 0) {
                        $this->featured_image = $value;
                    }
                }
            }

            return true;
        }

        /**
         * Generate JSON object for this article
         * @since Version 3.8.7
         * @return string
         * @throws \Exception if the article does not have a valid ID (ie, is a skeleton)
         */

        public function makeJSON() {

            if ($this->date instanceof DateTime) {
                $timezone = $this->date->getTimezone();
            } else {
                $timezone = new DateTimeZone("Australia/Melbourne");
            }

            if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
                throw new Exception("Cannot make a JSON object for the requested news article beacause no valid article ID was found. Something's wrong....");
            }

            if (empty( $this->getParagraphs() ) && !empty( $this->source )) {
                if ($this->url instanceof Url) {
                    $this->url->url = $this->source;
                } else {
                    $this->url = $this->source;
                }
            }

            $response = array(
                "namespace" => $this->Module->namespace,
                "module"    => $this->Module->name,
                "article"   => array(
                    "id"       => $this->id,
                    "title"    => $this->title,
                    "hits"     => $this->hits,
                    "blurb"    => $this->getLead(),
                    "body"     => $this->getParagraphs(),
                    "image"    => $this->featured_image,
                    "approved" => $this->approved,
                    "queued"   => $this->queued,
                    "source"   => $this->source,

                    "url"      => $this->url instanceof Url ? $this->url->getURLs() : array( "url" => sprintf("/news/article-%d", $this->id) ),

                    "topic"    => $this->Topic->getArray(),

                    "thread"   => array(
                        "id"  => $this->topic_id,
                        "url" => array(
                            "view" => filter_var($this->topic_id, FILTER_VALIDATE_INT) ? sprintf("/f-t%d.htm", $this->topic_id) : ""
                        )
                    ),

                    "date"     => array(
                        "absolute" => $this->date->format("Y-m-d H:i:s"),
                        "timezone" => $timezone->getName(),
                        "unixtime" => $this->date->getTimestamp()
                    ),

                    "author"   => array(
                        "id"       => $this->Author->id,
                        "username" => $this->Author->username,
                        "url"      => array(
                            "view" => $this->Author->url instanceof Url ? $this->Author->url->url : $this->Author->url
                        )
                    ),

                    "staff"    => array(
                        "id"       => $this->Staff->id,
                        "username" => $this->Staff->username,
                        "url"      => array(
                            "view" => $this->Staff->url instanceof Url ? $this->Staff->url->url : $this->Staff->url
                        )
                    ),
                )
            );

            if (!isset( $response['article']['url']['edit'] )) {
                $response['article']['url']['edit'] = sprintf("/news?mode=article.edit&id=%d", $this->id);
            }

            $response = json_encode($response);

            $this->Memcached->save(sprintf("json:railpage.news.article=%d", $this->id), $response);

            return $response;
        }

        /**
         * Get JSON object
         * @since Version 3.8.7
         * @return string
         */

        public function getJSON() {
            if ($json = $this->Memcached->fetch(sprintf("json:railpage.news.article=%d", $this->id))) {
                return $json;
            } else {
                return $this->makeJSON();
            }
        }

        /**
         * Get an array of this object
         * @since Version 3.9.1
         * @return array
         */

        public function getArray() {
            //return json_decode($this->getJSON(), true);

            return json_decode($this->makeJSON(), true);
        }

        /**
         * Set the article topic
         * @since Version 3.9
         *
         * @param \Railpage\News\Topic $Topic
         *
         * @return \Railpage\News\Article
         */

        public function setTopic(Topic $Topic) {
            $this->Topic = $Topic;

            return $this;
        }

        /**
         * Find related news articles
         * @since Version 3.9
         * @return array
         *
         * @param int $num The maximum number of results to return
         */

        public function getRelatedArticles($num = 5) {
			
			$SphinxQL = $this->getSphinx(); 
            
            $title = preg_replace("/[^[:alnum:][:space:]]/u", '', $this->title);
            $title = trim($title);
            $title = str_replace(" ", "|", $title);
			
			$query = $SphinxQL->select("*")
							  ->from("idx_news_article")
							  ->match(array("story_title", "story_paragraphs"), $title, true)
							  ->where("story_id", "!=", $this->id)
							  ->where("story_time_unix", "BETWEEN", array(strtotime("1 year ago", time()), time()))
							  ->limit($num)
							  ->option("ranker", "proximity_BM25");
			
			$matches = $query->execute(); 
			
			if (count($matches)) {
				return $matches;
			}
			
			return array(); 
			

            $Sphinx = AppCore::getSphinxAPI();

            $Sphinx->setFilter("topic_id", array( $this->Topic->id ));
            $Sphinx->setFilter("story_id", array( $this->id ), true);
            $Sphinx->setFilterRange("story_time_unix", strtotime("1 year ago"), time());
            $Sphinx->setLimits(0, $num);

            $results = $Sphinx->query($Sphinx->escapeString($this->title), "idx_news_article");

            return isset( $results['matches'] ) ? $results['matches'] : array();
        }

        /**
         * Check for duplicated news articles before posting
         * @since Version 3.9.1
         * @return boolean
         */

        public function isDuplicate() {

            $Sphinx = $this->getSphinx();

            /**
             * Look through our approved news articles for a possible duplication
             */

            $olddate = clone ( $this->date );

            $query = $Sphinx->select("*")
                ->from("idx_news_article")
                ->orderBy("story_time_unix", "DESC")
                ->where("story_time_unix", ">=", $olddate->sub(new DateInterval("P7D"))->getTimestamp())
                ->match("story_title", $this->title);

            $matches = $query->execute();

            /**
             * Look through our rejected titles to see if we've already rejected this
             */

            $query = $Sphinx->select("*")
                ->from("idx_news_articles_rejected")
                ->match("title", $this->title);

            $rejected = $query->execute();

            /**
             * If no matches are found we'll add in the article
             */

            if (count($matches) || count($rejected)) {
                //return true;
            }

            /**
             * Fall back to a database query
             */

            $olddate = clone ( $this->date );

            $where = array(
                strtolower($this->title),
                md5(strtolower($this->title)),
                $this->unique_id
            );

            $query = "SELECT sid FROM nuke_stories WHERE (LOWER(title) = ? OR MD5(LOWER(title)) = ? OR unique_id = ?)";

            /**
             * @blame PBR for not providing dates on their stories
             */

            if ($olddate->format("Y-m-d") != (new DateTime)->format("Y-m-d")) {
                $query .= " AND time >= ?";
                $where[] = $olddate->sub(new DateInterval("P90D"))->format("Y-m-d H:i:s");
            }

            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $query .= " AND sid != ?";
                $where[] = $this->id;
            }

            $result = $this->db->fetchAll($query, $where);

            if (count($result)) {
                return true;
            }

            return false;
        }

        /**
         * Get the lead of this article
         * @since Version 3.9.1
         * @return string
         */

        public function getLead() {
            $lead = empty( $this->blurb ) || is_null($this->blurb) ? $this->lead : $this->blurb;

            return is_object($lead) ? $lead->__toString() : $lead;
        }

        /**
         * Get the paragraphs (body) of this article
         * @since Version 3.9.1
         * @return string
         */

        public function getParagraphs() {
            if (empty( $this->body ) && empty( $this->paragraphs )) {
                $paragraphs = $this->getLead();
            } else {
                $paragraphs = empty( $this->body ) || is_null($this->body) ? $this->paragraphs : $this->blurb . "\n\n" . $this->body;
            }

            return is_object($paragraphs) ? $paragraphs->__toString() : $paragraphs;
        }

        /**
         * Guess the cover photo for this article
         * @since Version 3.9.1
         * @return \Railpage\News\Article
         */

        public function guessCoverPhoto() {
            if ($this->featured_image == "http://railindustryworker.com.au/assets/logo-artc.gif" || empty( $this->featured_image ) && stripos($this->title, "artc") !== false) {
                $this->featured_image = "https://static.railpage.com.au/i/logos/artc.jpg";
            }

            if ($this->Topic->id == 4 && stripos($this->title, "Gheringhap Sightings") !== false) {
                $this->featured_image = "http://ghaploop.railpage.org.au/House%20%20Train%20(2).jpg"; #"https://farm3.staticflickr.com/2657/3978862684_b0acc234d4_z.jpg";
            }

            return $this;
        }

        /**
         * Get the source of this article
         * @since Version 3.9.1
         * @return array
         */

        public function getSource() {
            return array(
                "domain" => parse_url($this->source, PHP_URL_HOST),
                "source" => $this->source
            );
        }

        /**
         * Get actions on this article
         * @since Version 3.10.0
         * @return array
         */

        public function getChangelog() {

            $query = "SELECT u.username, u.user_id, u.user_avatar, s.time AS timestamp, 'Article created' AS title, '' AS args
						FROM nuke_stories AS s
						LEFT JOIN nuke_users AS u ON u.user_id = s.user_id
						WHERE s.sid = ?
					UNION 
					SELECT u.username, l.user_id, u.user_avatar, l.timestamp, l.title, l.args 
						FROM log_staff AS l 
						LEFT JOIN nuke_users AS u ON u.user_id = l.user_id
						WHERE `key` = 'article_id' 
						AND key_val = ? 
					UNION 
					SELECT u.username, l.user_id, u.user_avatar, l.timestamp, l.title, l.args 
						FROM log_general AS l 
						LEFT JOIN nuke_users AS u ON u.user_id = l.user_id
						WHERE `key` = 'article_id' 
						AND value = ? 
					ORDER BY UNIX_TIMESTAMP(`timestamp`) DESC";

            $params = [
                $this->id,
                $this->id,
                $this->id
            ];

            $return = $this->db->fetchAll($query, $params);

            foreach ($return as $key => $val) {

                $return[$key]['user'] = array(
                    "user_id"  => $val['user_id'],
                    "username" => $val['username'],
                    "avatar"   => array(
                        "small"  => AvatarUtility::Format($val['user_avatar'], 50, 50),
                        "medium" => AvatarUtility::Format($val['user_avatar'], 80, 80),
                        "large"  => AvatarUtility::Format($val['user_avatar'], 128, 128),
                    ),
                    "url"      => UrlUtility::MakeURLs($val)->getURLs()
                );

                $return[$key]['args'] = json_decode($val['args'], true);
            }

            return $return;

        }
    }