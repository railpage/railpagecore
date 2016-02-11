<?php

/**
 * Suggestions for side ideas and improvements, ala Wordpress.org/ideas
 * @since Version 3.8.7
 * @author Michael Greenhill
 * @package Railpage
 */

namespace Railpage\Ideas;

use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\AppCore;
use Railpage\Module;
use Railpage\SiteEvent;
use Railpage\Forums\Thread;
use Railpage\Url;
use Railpage\ContentUtility;
use Exception;
use DateTime;

/**
 * Idea class
 */

class Idea extends AppCore {
    
    /**
     * Idea ID
     * @var int $id
     */
    
    public $id;
    
    /**
     * Idea title
     * @var string $title
     */
    
    public $title;
    
    /**
     * Idea URL slug
     * @var string $slug
     */
    
    public $slug;
    
    /**
     * Idea URL
     * @var string $url
     */
    
    public $url;
    
    /**
     * Description
     * @var string $description
     */
    
    public $description;
    
    /**
     * Number of votes for this idea
     * @var array $votes
     */
    
    private $votes = array();
    
    /**
     * Status of this idea
     * @var int $status
     */
    
    public $status = 1;
    
    /**
     * Forum discussion thread ID
     * @since Version 3.9.1
     * @var int $forum_thread_id
     */
    
    private $forum_thread_id = 0;
    
    /**
     * Redmine issue ID
     * @since Version 3.9.1
     * @var int $redmine_id
     */
    
    public $redmine_id = 0;
    
    /**
     * Array of meta data
     * @since Version 3.10.0
     * @var array $meta
     */
    
    public $meta = [];
    
    /**
     * Author of this idea
     * @var \Railpage\Users\User $Author
     */
    
    public $Author;
    
    /**
     * Creation date
     * @var \DateTime $Date
     */
    
    public $Date;
    
    /**
     * Idea category
     * @var \Railpage\Ideas\Category $Category
     */
    
    public $Category;
    
    /**
     * Constructor
     * @param int $id
     */
    
    public function __construct($id = null) {
        
        parent::__construct();
        
        $this->Module = new Module("ideas");
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->populate("id", $id); 
        } elseif (is_string($id) && strlen($id) > 1) {
            $this->populate("slug", $id); 
        }
    }
    
    /**
     * Populate this object
     * @since Version 3.9.1
     * @param string $column
     * @param string|int $value
     * @return void
     */
    
    private function populate($column, $value) {
        
        $query = sprintf("SELECT * FROM idea_ideas WHERE %s = ?", $column);
        
        if (!$row = $this->db->fetchRow($query, $value)) {
            return;
        }
        
        $this->title = $row['title'];
        $this->id = $row['id'];
        $this->slug = $row['slug'];
        $this->description = $row['description'];
        $this->Date = new DateTime($row['date']);
        $this->Category = new Category($row['category_id']);
        $this->status = $row['status'];
        $this->forum_thread_id = $row['forum_thread_id'];
        $this->redmine_id = $row['redmine_id'];
        $this->meta = json_decode($row['meta'], true); 
        
        if (!is_array($this->meta)) {
            $this->meta = []; 
        }
        
        $this->setAuthor(UserFactory::CreateUser($row['author']));
        $this->fetchVotes();
        $this->makeURLs(); 
        
    }
    
    /**
     * Make URLs for this idea
     * @since Version 3.9.1
     * @return \Railpage\Ideas\Idea
     */
    
    private function makeURLs() {
        
        $this->url = new Url(sprintf("%s/%s", $this->Category->url, $this->slug));
        
        $status = [
            "implemented" => Ideas::STATUS_IMPLEMENTED,
            "declined" => Ideas::STATUS_NO,
            "inprogress" => Ideas::STATUS_INPROGRESS,
            "active" => Ideas::STATUS_ACTIVE,
            "underconsideration" => Ideas::STATUS_UNDERCONSIDERATION,
            "active" => Ideas::STATUS_ACTIVE,
            "duplicate" => Ideas::STATUS_DUPLICATE,
        ];
        
        foreach ($status as $key => $val) {
            $this->url->$key = sprintf("%s?id=%d&mode=idea.setstatus&status_id=%d", $this->Module->url, $this->id, $val); 
        }
        
        $this->url->vote = sprintf("%s?mode=idea.vote&id=%d", $this->Module->url, $this->id);
        $this->url->creatediscussion = sprintf("%s?mode=idea.discuss&id=%d", $this->Module->url, $this->id);
        $this->url->edit = sprintf("%s?mode=idea.add&id=%d", $this->Module->url, $this->id);
        
        if (filter_var($this->redmine_id, FILTER_VALIDATE_INT)) {
            $this->url->redmine = sprintf("http://redmine.railpage.org/redmine/issues/%d", $this->redmine_id); 
        }
        
    }
    
    /**
     * Fire off some exceptions
     * @since Version 3.9.1
     * @return void
     */
    
    private function checkExceptions() {
        
        if (empty($this->title)) {
            throw new Exception("Title of the idea cannot be empty");
        }
        
        if (strlen($this->title) >= 64) {
            throw new Exception("The title for this idea is too long");
        }
        
        if (empty($this->description)) {
            throw new Exception("Description for the idea cannot be empty");
        }
        
        if (!$this->Author instanceof User) {
            throw new Exception("There must be a valid author specified for this idea");
        }
        
        if (!$this->Category instanceof Category) {
            throw new Exception("Each idea must belong to a valid category");
        }
        
        return;
        
    }
    
    /**
     * Set some default values
     * @since Version 3.9.1
     * @return void
     */
    
    private function setDefaults() {
        
        if (!$this->Date instanceof DateTime) {
            $this->Date = new DateTime;
        }
        
        if (empty($this->votes)) {
            $this->votes = 0;
        }
        
        if (empty($this->slug)) {
            $this->createSlug();
        }
        
        if (!filter_var($this->status, FILTER_VALIDATE_INT)) {
            $this->status = Ideas::STATUS_ACTIVE;
        }
        
        if (!filter_var($this->forum_thread_id, FILTER_VALIDATE_INT)) {
            $this->forum_thread_id = 0;
        }
        
        if (!filter_var($this->redmine_id, FILTER_VALIDATE_INT)) {
            $this->redmine_id = 0;
        }
        
        return;
        
    }
    
    /**
     * Validate changes to this idea
     * @since Version 3.8.7
     * @return boolean
     * @throws \Exception if $this->title is empty
     * @throws \Exception if $this->description is empty
     * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
     * @throws \Exception if $this->Category is not an instance of \Railpage\Ideas\Category
     */
    
    private function validate() {
        
        $this->checkExceptions(); 
        $this->setDefaults(); 
                    
        return true;
        
    }
    
    /**
     * Prepare the submit data
     * @since Version 3.9.1
     * @return array
     */
    
    private function prepareSubmitData() {
        
        $data = array(
            "title" => $this->title,
            "description" => $this->description,
            "slug" => $this->slug,
            "votes" => is_array($this->votes) ? count($this->votes) : $this->votes,
            "author" => $this->Author->id,
            "category_id" => $this->Category->id,
            "date" => $this->Date->format("Y-m-d H:i:s"),
            "status" => $this->status,
            "forum_thread_id" => $this->forum_thread_id,
            "redmine_id" => $this->redmine_id,
            "meta" => json_encode($this->meta),
        );
        
        return $data;
        
    }
    
    /**
     * Create a URL slug
     * @since Version 3.8.7
     */
    
    private function createSlug() {
        
        $proposal = ContentUtility::generateUrlSlug($this->title, 28);
        
        $result = $this->db->fetchAll("SELECT id FROM idea_ideas WHERE slug = ?", $proposal); 
        
        if (count($result)) {
            $proposal .= count($result);
        }
        
        $this->slug = $proposal;
        
    }
    
    /**
     * Commit changes to this idea
     * @since Version 3.8.7
     * @return $this
     */
    
    public function commit() {
        
        $this->validate();
        $data = $this->prepareSubmitData(); 
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->update("idea_ideas", $data, $where);
        
            $this->makeURLs(); 
            
            return $this;
        }
        
        $this->db->insert("idea_ideas", $data);
        $this->id = $this->db->lastInsertId();
    
        $this->Author->wheat(10);
        
        $this->logEvent(); 
        
        $this->makeURLs(); 
        
        return $this;
        
    }
    
    /**
     * Log an event
     * @since Version 3.9.1
     * @return void
     */
    
    private function logEvent() {
        
        try {
            $Event = new SiteEvent;
            $Event->title = "Suggested an idea";
            $Event->user_id = $this->Author->id;
            $Event->module_name = strtolower($this->Module->name);
            $Event->key = "idea_id";
            $Event->value = $this->id;
            
            $Event->commit();
        } catch (Exception $e) {
            //die($e->getMessage());
        }
        
        return;

    }
    
    /**
     * Update the votes for this idea
     * @since Version 3.8.7
     * @return $this
     */
    
    public function fetchVotes() {
        
        $query = "SELECT * FROM idea_votes WHERE idea_id = ? ORDER BY date DESC";
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $this->votes[] = array(
                "user_id" => $row['user_id'],
                "date" => new DateTime($row['date']),
                "id" => $row['id']
            );
        }
        
    }
    
    /**
     * Get the number of votes for this idea
     * @since Version 3.8.7
     * @return int
     */
    
    public function getVotes() {
        
        return count($this->votes);
        
    }
    
    /**
     * Get the voters for this idea
     * @since Version 3.8.7
     * @return array
     */
    
    public function getVoters() {
        
        return $this->votes;
        
    }
    
    /**
     * Check if this user can vote for this idea or not
     * @since Version 3.8.7
     * @param \Railpage\Users\User $userObject
     * @return boolean
     */
    
    public function canVote(User $userObject) {
        
        if ($this->status == Ideas::STATUS_DELETED || $this->status == Ideas::STATUS_NO || $this->status == Ideas::STATUS_IMPLEMENTED) {
            return false;
        }
        
        if ($userObject->id === 0 || $userObject->guest === true) {
            return false;
        }
        
        if ($userObject->id == $this->Author->id) {
            return false;
        }
        
        foreach ($this->votes as $vote) {
            if ($vote['user_id'] == $userObject->id) {
                return false;
            }
        }
        
        return true;
        
    }
    
    /**
     * Add a vote for this idea
     * @param \Railpage\Users\User $userObject
     * @return $this
     */
    
    public function vote(User $userObject) {
        
        if (!$this->canVote($userObject)) {
            throw new Exception("We couldn't add your vote to this idea. You must be logged in and not already voted for this idea");
        }
        
        $Date = new DateTime;
        
        $data = array(
            "idea_id" => $this->id,
            "user_id" => $userObject->id,
            "date" => $Date->format("Y-m-d H:i:s")
        );
        
        $this->db->insert("idea_votes", $data);
        
        $this->fetchVotes();
        
        $userObject->wheat();
        
        return $this;
        
    }
    
    /**
     * Get a standardised array of this data
     * @since Version 3.9.1
     * @return array
     */
    
    public function getArray() {
        $idea = array(
            "id" => $this->id,
            "title" => $this->title,
            "description" => function_exists("format_post") ? format_post($this->description) : $this->description,
            "status" => Ideas::getStatusDescription($this->status),
            "url" => $this->url->getURLs(),
            "votes" => array(
                "num" => $this->getVotes(),
                "text" => $this->getVotes() == 1 ? "1 vote" : sprintf("%d votes", $this->getVotes())
            ),
            "date" => array(
                "absolute" => $this->User instanceof User ? $this->Date->format($this->User->date_format) : $this->Date->format("F j, Y, g:i a"),
                "relative" => time2str($this->Date->getTimestamp())
            ),
            "author" => array(
                "id" => $this->Author->id,
                "username" => $this->Author->username,
                "url" => $this->Author->url,
                "avatar" => array(
                    "small" => function_exists("format_avatar") ? format_avatar($this->Author->avatar, 40) : $this->Author->avatar,
                    "large" => function_exists("format_avatar") ? format_avatar($this->Author->avatar, 120) : $this->Author->avatar
                )
            ),
            "category" => array(
                "id" => $this->Category->id,
                "name" => $this->Category->name,
                "url" => $this->Category->url
            ),
            "voters" => array()
        );
        
        return $idea;
    }
    
    /**
     * Set the discussion thread for this idea
     * @since Version 3.9.1
     * @param \Railpage\Forums\Thread $forumThread
     * @return \Railpage\Ideas\Idea
     */
    
    public function setForumThread(Thread $forumThread) {
        $this->forum_thread_id = $forumThread->id;
        $this->commit(); 
        
        $forumThread->putObject($this);
        
        return $this;
    }
    
    /**
     * Get the discussion thread for this idea
     * @since Version 3.9.1
     * @return \Railpage\Forums\Thread
     */
    
    public function getForumThread() {
        if (filter_var($this->forum_thread_id, FILTER_VALIDATE_INT) && $this->forum_thread_id > 0) {
            return new Thread($this->forum_thread_id);
        }
    }
}
