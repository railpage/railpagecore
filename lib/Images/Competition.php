<?php

/**
 * Photo competition!
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\Config\Base as Config;
use Railpage\SiteMessages\SiteMessages;
use Railpage\SiteMessages\SiteMessage;
use Railpage\AppCore;
use Railpage\Url;
use Railpage\Module;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Exception;
use DateTime;
use DateInterval;
use DatePeriod;
use stdClass;
use Railpage\ContentUtility;

use Railpage\Notifications\Notifications;
use Railpage\Notifications\Notification;

/**
 * Competition
 */

class Competition extends AppCore {
    
    /**
     * Registry key
     * @since Version 3.10.0
     * @const CACHE_KEY
     */
    
    const CACHE_KEY = "railpage:photo.comp=%d";
    
    /**
     * Competition ID
     * @since Version 3.9.1
     * @var int $id
     */
    
    public $id;
    
    /**
     * Competition title
     * @since Version 3.9.1
     * @var string $title
     */
     
    public $title;
    
    /**
     * The theme of this competition (eg at night, close up)
     * @since Version 3.9.1
     * @var string $theme
     */
    
    public $theme;
    
    /**
     * Competition description
     * @since Version 3.9.1
     * @var string $description
     */
    
    public $description;
    
    /**
     * Competition status
     * @since Version 3.9.1
     * @var int $status
     */
    
    public $status;
    
    /**
     * Meta config array (various options I can't be bothered splitting into separate vars
     * @since Version 3.9.1
     * @var array $meta
     */
     
    public $meta;
    
    /**
     * URL Slug
     * @since Version 3.9.1
     * @var string $slug
     */
    
    public $slug;
    
    /**
     * Submissions Date open
     * @since Version 3.9.1
     * @var \DateTime $SubmissionsDateOpen
     */
    
    public $SubmissionsDateOpen;
    
    /**
     * Submissions Date close
     * @since Version 3.9.1
     * @var \DateTime $SubmissionsDateClose
     */
    
    public $SubmissionsDateClose;
    
    /**
     * Date open
     * @since Version 3.9.1
     * @var \DateTime $VotingDateOpen
     */
    
    public $VotingDateOpen;
    
    /**
     * Date close
     * @since Version 3.9.1
     * @var \DateTime $VotingDateClose
     */
    
    public $VotingDateClose;
    
    /**
     * Author
     * @since Version 3.9.1
     * @var \Railpage\Users\User $Author
     */
    
    public $Author;
    
    /**
     * Constructor
     * @since Version 3.9.1
     * @param int|string $id
     */
    
    public function __construct($id = null) {
        parent::__construct(); 
        
        $this->Module = new Module("images.competitions"); 
        $this->Module->namespace = sprintf("%s.competition", $this->Module->namespace); 
        
        $id = Utility\CompetitionUtility::getIDFromSlug($id); 
        
        if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
            $this->cachekey = sprintf("railpage:photo.comp=%d", $id);
            
            $this->id = $id;
            $this->load();
        }
    }
    
    /**
     * Populate this object
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     */
    
    private function load() {
        
        $query = "SELECT * FROM image_competition WHERE id = ?";
        
        $row = $this->db->fetchRow($query, $this->id);
        
        $lookup = [ "title", "description", "status", "slug", "theme" ];
        
        foreach ($lookup as $var) {
            $this->$var = $row[$var];
        }
        
        $this->meta = json_decode($row['meta'], true);
        
        $lookup = array(
            "voting_date_open" => "VotingDateOpen",
            "voting_date_close" => "VotingDateClose",
            "submissions_date_open" => "SubmissionsDateOpen",
            "submissions_date_close" => "SubmissionsDateClose"
        );
        
        foreach ($lookup as $db => $var) {
            if ($row[$db] != "0000-00-00 00:00:00") {
                $this->$var = new DateTime($row[$db]);
            }
        }
        
        if ($this->VotingDateClose->format("H:i:s") === "00:00:00") {
            $this->VotingDateClose = new DateTime($this->VotingDateClose->format("Y-m-d 23:59:59"));
        }
        
        if ($this->SubmissionsDateClose->format("H:i:s") === "00:00:00") {
            $this->SubmissionsDateClose = new DateTime($this->SubmissionsDateClose->format("Y-m-d 23:59:59"));
        }
        
        $this->setAuthor(UserFactory::CreateUser($row['author']));
        
        $this->url = Utility\Url::makeCompetitionUrls($this);
        
        $this->notifySubmissionsOpen();
        $this->notifyVotingOpen(); 
        
        Utility\CompNotify::notifyWinner($this); 
        
        Utility\CompetitionUtility::createNewsArticle_SubmissionsOpen($this);
        Utility\CompetitionUtility::createNewsArticle_VotingOpen($this);
        
        return $this;
    }
    
    /**
     * Validate changes to this competition
     * @since Version 3.9.1
     * @return boolean
     */
    
    private function validate() {
        if (empty($this->title)) {
            throw new Exception("Competition title cannot be empty");
        }
        
        if (empty($this->theme)) {
            throw new Exception("Competition theme cannot be empty");
        }
        
        if (empty($this->description)) {
            $this->description = ""; #throw new Exception("Competition description cannot be empty");
        }
        
        if (empty($this->status) || !filter_var($this->status, FILTER_VALIDATE_INT)) {
            $this->status = Competitions::STATUS_CLOSED;
        }
        
        if (empty($this->slug)) {
            $proposal = ContentUtility::generateUrlSlug($this->title); 
            
            $query = "SELECT id FROM image_competition WHERE slug = ?";
            $num = count($this->db->fetchAll($query, $proposal));
            
            if ($num > 0) {
                $proposal .= $num;
            }
            
            $this->slug = $proposal;
        }
        
        if (!$this->Author instanceof User || !filter_var($this->Author->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Author is not set (hint: setAuthor(User))");
        }
        
        $dates = [ "VotingDateOpen", "VotingDateClose", "SubmissionsDateOpen", "SubmissionsDateClose" ];
        
        foreach ($dates as $date) {
            if (!$this->$date instanceof DateTime) {
                throw new Exception(sprintf("%s::%s must be an instance of DateTime", __CLASS__, $date)); 
            }
        }
        
        if ($this->VotingDateOpen > $this->VotingDateClose) {
            throw new Exception("VotingDateOpen is greater than VotingDateClose");
        }
        
        if ($this->SubmissionsDateOpen > $this->SubmissionsDateClose) {
            throw new Exception("SubmissionsDateOpen is greater than SubmissionsDateClose");
        }
        
        if ($this->SubmissionsDateClose > $this->VotingDateOpen) {
            throw new Exception("SubmissionsDateClose is greater than VotingDateOpen");
        }
        
        if ($this->SubmissionsDateOpen <= new DateTime) {
            $this->status = Competitions::STATUS_OPEN;
        }
        
        if ($this->VotingDateClose <= new DateTime) {
            $this->status = Competitions::STATUS_CLOSED;
        }
        
        if ($this->VotingDateClose->format("H:i:s") === "00:00:00") {
            $this->VotingDateClose = new DateTime($this->VotingDateClose->format("Y-m-d 23:59:59"));
        }
        
        if ($this->SubmissionsDateClose->format("H:i:s") === "00:00:00") {
            $this->SubmissionsDateClose = new DateTime($this->SubmissionsDateClose->format("Y-m-d 23:59:59"));
        }
        
        return true;
    }
    
    /**
     * Commit changes to this competition
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     */
    
    public function commit() {
        
        $this->validate(); 
        
        $data = array(
            "title" => $this->title,
            "theme" => $this->theme,
            "description" => $this->description,
            "slug" => $this->slug,
            "status" => $this->status,
            "author" => $this->Author->id,
            "voting_date_open" => $this->VotingDateOpen instanceof DateTime ? $this->VotingDateOpen->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
            "voting_date_close" => $this->VotingDateClose instanceof DateTime ? $this->VotingDateClose->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
            "submissions_date_open" => $this->SubmissionsDateOpen instanceof DateTime ? $this->SubmissionsDateOpen->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
            "submissions_date_close" => $this->SubmissionsDateClose instanceof DateTime ? $this->SubmissionsDateClose->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
            "meta" => json_encode($this->meta)
        );
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->update("image_competition", $data, $where); 
        }
        
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->db->insert("image_competition", $data);
            $this->id = $this->db->lastInsertId(); 
        }
        
        /**
         * Clear the cache
         */
        
        $regkey = sprintf(self::CACHE_KEY, $this->id);
        $Redis = AppCore::GetRedis(); 
        $Memcached = AppCore::GetMemcached(); 
        $Redis->delete($regkey);
        $Memcached->delete($regkey);
        
        /**
         * Check our themes and see if we need to mark this theme as used
         */
        
        $themes = (new Competitions)->getSuggestedThemes(); 
        
        foreach ($themes as $key => $theme) {
            if (function_exists("format_topictitle")) {
                $theme['theme'] = format_topictitle($theme['theme']);
            }
            
            if ((!isset($theme['used']) || $theme['used'] === false) && $theme['theme'] === $this->theme) {
                $themes[$key]['used'] = true;
            }
        }
        
        $Config = new Config;
        $Config->set("image.competition.suggestedthemes", json_encode($themes), "Photo competition themes"); 
        
        $this->url = Utility\Url::makeCompetitionUrls($this);
        
        return $this;
    }
    
    /**
     * Get approved photos in this competition
     * @since Version 3.9.1
     * @return array
     */
    
    public function getPhotos() {
        $query = "SELECT s.* FROM image_competition_submissions AS s LEFT JOIN image AS i ON s.image_id = i.id WHERE s.competition_id = ? AND s.status = ? AND i.photo_id != 0 ORDER BY s.date_added DESC";
        $params = array(
            $this->id,
            Competitions::PHOTO_APPROVED
        );
        
        $photos = $this->db->fetchAll($query, $params); 
        
        foreach ($photos as $row) {
            yield $this->getPhoto($row);
        }
    }
    
    /**
     * Get a single photo from this competition
     * @since Version 3.9.1
     * @return stdClass
     * @param array|\Railpage\Images\Image $image
     */
    
    public function getPhoto($image) {
        
        if ($image instanceof Image) {
            $query = "SELECT * FROM image_competition_submissions WHERE competition_id = ? AND image_id = ? ORDER BY date_added DESC";
            $params = array(
                $this->id,
                $image->id
            );
            
            $image = $this->db->fetchRow($query, $params);
        }
        
        $Photo = new stdClass;
        $Photo->id = $image['id'];
        $Photo->Author = UserFactory::CreateUser($image['user_id']);
        $Photo->Image = ImageFactory::CreateImage(isset($image['image_id']) ? $image['image_id'] : $image['id']);
        $Photo->DateAdded = new DateTime($image['date_added']);
        $Photo->Meta = json_decode($image['meta'], true);
        $Photo->status = intval($image['status']); 
        
        $Photo->url = new Url(sprintf("%s/%d", $this->url->url, $Photo->Image->id));
        $Photo->url->vote = sprintf("%s/vote", $Photo->url);
        
        return $Photo;
        
    }
    
    /**
     * Check if this image was submitted by this user
     * @since Version 3.9.1
     * @param \Railpage\Users\User $userObject
     * @param \Railpage\Images\Image $imageObject
     * @return boolean
     */
    
    public function isImageOwnedBy(User $userObject, Image $imageObject) {
        $query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND user_id = ? AND image_id = ?";
        
        $params = array(
            $this->id,
            $userObject->id,
            $imageObject->id
        );
        
        $result = $this->db->fetchAll($query, $params); 
        
        if (count($result)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the number of votes made by this user
     * @since Version 3.9.1
     * @param \Railpage\Users\User $userObject
     * @return array
     */
    
    public function getNumVotesForUser(User $userObject) {
        if (!filter_var($userObject->id, FILTER_VALIDATE_INT)) {
            return array(
                "cast" => 0,
                "free" => 0
            );
        }
        
        $query = "SELECT id FROM image_competition_votes WHERE competition_id = ? AND user_id = ?";
        
        $params = array(
            $this->id,
            $userObject->id
        );
        
        $result = $this->db->fetchAll($query, $params); 
        
        $max_votes = isset($this->meta['maxvotes']) && filter_var($this->meta['maxvotes'], FILTER_VALIDATE_INT) ? $this->meta['maxvotes'] : Competitions::MAX_VOTES_PER_USER;
        
        $return = array(
            "cast" => count($result),
            "free" => $max_votes - count($result)
        );
        
        return $return;
    }
    
    /**
     * Get the number of votes for this photo
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return int
     */
    
    public function getNumVotesForImage(Image $imageObject) {
        $votes = 0;
        
        foreach ($this->getVotesForImage($imageObject) as $row) {
            $votes += $row['amount'];
        }
        
        return $votes;
    }
    
    /**
     * Get the votes cast for a given image in this competition
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
    
    public function getVotesForImage(Image $imageObject) {
        $query = "SELECT u.username, v.user_id, date, amount FROM image_competition_votes AS v LEFT JOIN nuke_users AS u ON u.user_id = v.user_id
                    WHERE v.competition_id = ? AND v.image_id = ?";
        
        return $this->db->fetchAll($query, array($this->id, $imageObject->id));
    }
    
    /**
     * Can a user vote in this competition?
     * @since Version 3.9.1
     * @return boolean
     * @param \Railpage\Users\User $userObject
     */
    
    public function canUserVote(User $userObject, $imageObject = null) {
        if (!filter_var($userObject->id, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        //$now = new DateTime;
        
        if (!Utility\CompetitionUtility::isVotingWindowOpen($this)) {
            return false;
        }
        
        $query = "SELECT id FROM image_competition_votes WHERE competition_id = ? AND user_id = ?";
        $params = array(
            $this->id,
            $userObject->id
        );
        
        if ($imageObject instanceof Image) {
            if ($this->isImageOwnedBy($userObject, $imageObject)) {
                return false;
            }
            
            $query .= " AND image_id = ?";
            $params[] = $imageObject->id;
        }
        
        $result = $this->db->fetchAll($query, $params);
        
        if ($imageObject instanceof Image && count($result) > 0) {
            return false;
        }
        
        if (isset($result[0]) && isset($result[0]['user_id']) && (int) $result[0]['user_id'] === (int) $userObject->id) {
            return false;
        }
        
        $max_votes = isset($this->meta['maxvotes']) && filter_var($this->meta['maxvotes'], FILTER_VALIDATE_INT) ? $this->meta['maxvotes'] : Competitions::MAX_VOTES_PER_USER;
        
        if (count($result) >= $max_votes) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Can a user submit a photo to this competition?
     * @since Version 3.9.1
     * @return boolean
     * @param \Railpage\Users\User $userObject
     */
    
    public function canUserSubmitPhoto(User $userObject) {
        if (!filter_var($userObject->id, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $now = new DateTime;
        
        if (!($this->SubmissionsDateOpen instanceof DateTime && $this->SubmissionsDateOpen <= $now) || 
            !($this->SubmissionsDateClose instanceof DateTime && $this->SubmissionsDateClose >= $now)) {
                return false;
        }
        
        $query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND user_id = ? AND status != ?";
        $where = array(
            $this->id,
            $userObject->id,
            Competitions::PHOTO_REJECTED
        );
        
        if ($this->db->fetchAll($query, $where)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Submit a photo to this competition
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @param \Railpage\Users\User $userObject
     * @param array $meta
     * @return int
     */
    
    public function submitPhoto(Image $imageObject, User $userObject, $meta = array()) {
        
        if (!filter_var($userObject->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Invalid user ID");
        }
        
        $data = array(
            "competition_id" => $this->id,
            "user_id" => $userObject->id,
            "image_id" => $imageObject->id,
            "meta" => json_encode($meta),
            "date_added" => date("Y-m-d H:i:s"),
            "status" => Competitions::PHOTO_UNAPPROVED
        );
        
        $this->db->insert("image_competition_submissions", $data);
        
        Utility\PushNotify::photoAwaitingApproval($this, $imageObject, $userObject); 
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Vote on a photo
     * @since Version 3.9.1
     * @param \Railpage\Users\User $userObject
     * @param \Railpage\Images\Image $imageObject
     * @return \Railpage\Images\Competition
     */
    
    public function submitVote(User $userObject, Image $imageObject) {
        
        if (!filter_var($userObject->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Invalid user ID");
        }
        
        $data = array(
            "competition_id" => $this->id,
            "user_id" => $userObject->id,
            "image_id" => $imageObject->id,
            "date" => date("Y-m-d H:i:s"),
            "amount" => 1
        );
        
        $this->db->insert("image_competition_votes", $data);
        
        return $this;
    }
    
    /**
     * Check if an image is in this competition
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return boolean
     */
    
    public function isImageInCompetition(Image $imageObject) {
        $query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND image_id = ?";
        $where = array(
            $this->id,
            $imageObject->id
        );
        
        if ($this->db->fetchAll($query, $where)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get winning photo
     * @since Version 3.9.1
     * @return array
     */
    
    public function getWinningPhoto() {
        
        if ($this->VotingDateClose >= new DateTime) {
            return false;
        }
        
        $photos = $this->getPhotosAsArrayByVotes(); 
        $num_votes = false;
        $tied = []; 
        
        if (count($photos) == 0) {
            return false;
        }
        
        foreach ($photos as $photo) {
            if ($num_votes === false) {
                $num_votes = count($photo['votes']); 
                $tied[] = $photo;
                continue;
            }
            
            if ($num_votes == count($photo['votes'])) {
                $tied[] = $photo; 
                continue;
            }
            
            if (count($photo['votes']) < $num_votes) {
                continue;
            }
        }
        
        if (count($tied) > 1) {
            Utility\CompetitionUtility::NotifyTied($this); 
            return false;
        }
        
        $tied[0]['image']['user_id'] = $tied[0]['author']['id'];
        
        $result = $tied[0]['image']; 
        
        if ($result['winner'] == "0") {
            $data = [ "winner" => 1 ];
            
            $where = [ "id = ?" => $result['id'] ];
            
            $this->db->update("image_competition_submissions", $data, $where); 
        }
        
        $photo = $this->getPhoto($result);
        
        return $photo;
        
    }
    
    /**
     * Get votes 
     * @since Version 3.9.1
     * @return array
     */
    
    public function getVotes() {
        $query = "SELECT v.*, u.username FROM image_competition_votes AS v LEFT JOIN nuke_users AS u ON u.user_id = v.user_id WHERE v.competition_id = ? ORDER BY v.date DESC";
        
        return $this->db->fetchAll($query, $this->id);
    }
    
    /**
     * Get submissions pending approval
     * @since Version 3.9.1
     * @return array
     */
    
    public function getPendingSubmissions() {
        $query = "SELECT s.* FROM image_competition_submissions AS s LEFT JOIN image AS i ON s.image_id = i.id WHERE s.competition_id = ? AND s.status = ? AND i.photo_id != 0 ORDER BY s.date_added DESC";

        $where = array(
            $this->id,
            Competitions::PHOTO_UNAPPROVED
        );
        
        foreach ($this->db->fetchAll($query, $where) as $row) {
            $Author = UserFactory::CreateUser($row['user_id']);
            $imageObject = new Image($row['image_id']);
            $Date = new DateTime($row['date_added']); 
            
            $return = new stdClass;
            $return->id = $row['id']; 
            $return->Author = $Author;
            $return->Image = $imageObject;
            $return->DateAdded = $Date;
            $return->Meta = json_decode($row['meta'], true);
            $return->url = new Url(sprintf("/gallery?mode=competition.image&comp_id=%d&image_id=%d", $this->id, $imageObject->id));
            $return->url->approve = sprintf("/gallery?mode=competition.photo.manage&comp_id=%d&image_id=%d&action=approve", $this->id, $imageObject->id);
            $return->url->reject = sprintf("/gallery?mode=competition.photo.manage&comp_id=%d&image_id=%d&action=reject", $this->id, $imageObject->id);
            
            yield $return;
        }
    }
    
    /**
     * Get the number of photos pending approval
     * @since Version 3.9.1
     * @return int
     */
    
    public function getNumPendingSubmissions() {
        $query = "SELECT * FROM image_competition_submissions WHERE competition_id = ? AND status = ? ORDER BY date_added DESC";
        $where = array(
            $this->id,
            Competitions::PHOTO_UNAPPROVED
        );
        
        return count($this->db->fetchAll($query, $where)); 
    }
    
    /**
     * Approve a queued submission
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function approveSubmission(Image $imageObject) {
        if (!filter_var($imageObject->id, FILTER_VALIDATE_INT)) {
            throw new Exception("The supplied image appears to be invalid...");
        }
        
        $data = array(
            "status" => Competitions::PHOTO_APPROVED
        );
        
        $where = array(
            "image_id = ?" => $imageObject->id,
            "competition_id = ?" => $this->id
        );
        
        /**
         * Update the database table
         */
        
        $this->db->update("image_competition_submissions", $data, $where);
        
        /**
         * Update the cached array of photos
         */
        
        $this->getPhotosAsArray(true);
        
        return $this;
    }
    
    /**
     * Reject a queued submission
     * @since Version 3.9.1
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function rejectSubmission(Image $imageObject) {
        if (!filter_var($imageObject->id, FILTER_VALIDATE_INT)) {
            throw new Exception("The supplied image appears to be invalid...");
        }
        
        $data = array(
            "status" => Competitions::PHOTO_REJECTED
        );
        
        $where = array(
            "image_id = ?" => $imageObject->id,
            "competition_id = ?" => $this->id
        );
        
        $this->db->update("image_competition_submissions", $data, $where);
    
        /**
         * Release all votes cast for this photo
         */
        
        $this->releaseVotesForImage($imageObject);
        
        /**
         * Update the cached array of photos
         */
        
        $this->getPhotosAsArray(true);
        
        return $this;
    }
    
    /**
     * Get this competition information as an associative array
     * @since Version 3.9.1
     * @return array
     */
    
    public function getArray() {
        //$now = new DateTime;
        
        $voting_open = Utility\CompetitionUtility::isVotingWindowOpen($this);
        $submissions_open = Utility\CompetitionUtility::isSubmissionWindowOpen($this);
        
        $return = array(
            "id" => $this->id,
            "title" => $this->title,
            "theme" => $this->theme,
            "description" => $this->description,
            "status" => array(
                "id" => $this->status,
                "name" => $this->status === Competitions::STATUS_OPEN ? "Open" : "Closed"
            ),
            "url" => isset($this->url) && $this->url instanceof Url ? $this->url->getURLs() : array(),
            "voting" => array(
                "status" => $voting_open,
                "open" => array(
                    "absolute" => $this->VotingDateOpen->format("Y-m-d H:i:s"),
                    "formatted" => $this->VotingDateOpen->format("F jS"),
                    "us" => $this->VotingDateOpen->format("m/d/Y")
                ),
                "close" => array(
                    "absolute" => $this->VotingDateClose->format("Y-m-d H:i:s"),
                    "formatted" => $this->VotingDateClose->format("F jS"),
                    "us" => $this->VotingDateClose->format("m/d/Y")
                )
            ),
            "submissions" => array(
                "status" => $submissions_open,
                "open" => array(
                    "absolute" => $this->SubmissionsDateOpen->format("Y-m-d H:i:s"),
                    "formatted" => $this->SubmissionsDateOpen->format("F jS"),
                    "us" => $this->SubmissionsDateOpen->format("m/d/Y")
                ),
                "close" => array(
                    "absolute" => $this->SubmissionsDateClose->format("Y-m-d H:i:s"),
                    "formatted" => $this->SubmissionsDateClose->format("F jS"),
                    "us" => $this->SubmissionsDateClose->format("m/d/Y")
                )
            ),
            "meta" => $this->meta
        );
        
        return $return;
    }
    
    /**
     * Get author of a submitted photo
     * @since Version 3.9.1
     * @return \Railpage\Users\User
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function getPhotoAuthor(Image $imageObject) {
        $query = "SELECT user_id FROM image_competition_submissions WHERE competition_id = ? AND image_id = ?";
        
        $params = array(
            $this->id,
            $imageObject->id
        );
        
        #printArray($params);die;
        
        $user_id = $this->db->fetchOne($query, $params);
        
        return UserFactory::CreateUser($user_id);
    }
    
    /**
     * Get photo context
     * @since Version 3.9.1
     * @return array
     * @param \Railpage\Images\Image $imageObject
     */
    
    public function getPhotoContext(Image $imageObject) {
        
        $query = "SELECT s.*, 0 AS current FROM image_competition_submissions AS s LEFT JOIN image AS i ON s.image_id = i.id WHERE s.competition_id = ? AND s.status = ? AND i.photo_id != 0 ORDER BY s.date_added ASC";

        $where = array(
            $this->id,
            Competitions::PHOTO_APPROVED
        );
        
        $photos = $this->db->fetchAll($query, $where);
        
        $return = Utility\CompetitionUtility::getPhotoContext($photos, $imageObject); 
        
        /**
         * Loop through the context and return a stdClass photo
         */
        
        foreach ($return as $data) {
            $Photo = $this->getPhoto($data); 
            $Photo->current = (bool) $data['current'];
            
            yield $Photo;
        }
        
    }
    
    /**
     * Get photos as an associative array
     * @since Version 3.9.1
     * @return array
     * @param boolean $force
     */
    
    public function getPhotosAsArray($force = null) {
        $key = sprintf("railpage:comp=%d;images.array", $this->id);
        
        $this->Memcached = AppCore::getMemcached();
        
        if ($force == null && $photos = $this->Memcached->fetch($key)) {
            return $photos;
        }
        
        $photos = array(); 
        
        foreach ($this->getPhotos() as $Submission) {
            $photos[] = array(
                "id" => $Submission->id,
                "url" => $Submission->url->getURLs(),
                "image" => $Submission->Image->getArray(),
                "author" => array(
                    "id" => $Submission->Author->id,
                    "username" => $Submission->Author->username,
                    "url" => $Submission->Author->url instanceof Url ? $Submission->Author->url->getURLs() : array("url" => $Submission->Author->url)
                ),
                "dateadded" => array(
                    "absolute" => $Submission->DateAdded->format("Y-m-d H:i:s"),
                    "relative" => function_exists("time2str") ? time2str($Submission->DateAdded->getTimestamp()) : null
                )
            );
        }
        
        $this->Memcached->save($key, $photos);
        
        return $photos;
    }
    
    /**
     * Get all the photos in this competition and sort them by vote counts
     * @since Version 3.10.0
     * #return array
     */
    
    public function getPhotosAsArrayByVotes() {
        
        $photos = $this->getPhotosAsArray(); 
        
        /**
         * Add the votes to each photo
         */
        
        $votes = array(); 
        $totalVotes = 0;
        
        foreach ($this->getVotes() as $vote) {
            $totalVotes++; 
            
            $votes[$vote['image_id']][] = $vote; 
        }
        
        if ($totalVotes == 0) {
            return $photos; 
        }
        
        foreach ($photos as $key => $photo) {
            $photos[$key]['votes'] = $votes[$photo['image']['id']]; 
            $photos[$key]['votes_percentage'] = round((count($photos[$key]['votes']) / $totalVotes) * 100);
        }
        
        /**
         * Sort the photos by votes
         */
        
        usort($photos, function ($a, $b) {
            if (count($a['votes']) == count($b['votes'])) {
                return 0;
            }
            
            return (count($a['votes']) > count($b['votes'])) ? -1 : 1;
        });
        
        return $photos; 

    }
    
    /**
     * Get site message
     * @since Version 3.9.1
     * @return \Railpage\SiteMessages\SiteMessage
     */
    
    public function getSiteMessage() {
        $Message = (new SiteMessages)->getMessageForObject($this); 
        
        if (!$Message instanceof SiteMessage) {
            $Message = new SiteMessage; 
            #$Message->
        }
    }
    
    /**
     * Get vote counts per day over the voting period
     * @since Version 3.9.1
     * @return array
     */
    
    public function getVoteCountsPerDay() {
        $query = "SELECT COUNT(id) AS votes, DATE(`date`) AS day FROM image_competition_votes WHERE competition_id = ? GROUP BY DATE(`date`)";
        $params = array($this->id); 
        $votes = array();
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $params) as $day) {
            $votes[$day['day']] = $day['votes'];
        }
        
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($this->VotingDateOpen, $interval, $this->VotingDateClose);
        
        foreach ($period as $Date) {
            $return[$Date->format("Y-m-d")] = isset($votes[$Date->format("Y-m-d")]) ? $votes[$Date->format("Y-m-d")] : 0;
        }
        
        return $return;
    }
    
    /**
     * Notify previous participants that this competition is open for submissions
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     * @todo Check recipient preferences for email notifications
     */
    
    public function notifySubmissionsOpen() {
        
        /**
         * Return if we're not within the submissions bounds
         */
        
        if (!Utility\CompetitionUtility::isSubmissionWindowOpen($this)) {
            return $this;
        }
        
        /**
         * If we've recently sent a reminder, exit. No sense in nagging
         */
        
        $datekey = sprintf("%sDate", __FUNCTION__);
        
        if (isset($this->meta[$datekey])) {
            if (strtotime($this->meta[$datekey]) >= strtotime("5 days ago")) {
                return $this;
            }
            
            $this->meta[__FUNCTION__] = false;
            
        }
        
        /**
         * Assemble our options to send to the mailer
         */
        
        $body = sprintf(
            "Hi [username],\n\nWe wanted to let you know that a new photo competition, <a href='%s'>%s</a>, is open for submissions until %s.\n\nYou've received this email because you've participated in a previous photo competition.\n\nThanks\nThe Railpage team.",
            $this->url->email, 
            $this->title, 
            $this->SubmissionsDateClose->format("F jS")
        );
        
        $notificationOptions = array(
            "flag" => __FUNCTION__, 
            "subject" => sprintf("Submissions open: %s", $this->title),
            "body" => $body,
            "excludeCurrentContestants" => true
        );
        
        /**
         * Dispatch
         */
        
        Utility\CompetitionUtility::sendNotification($this, $notificationOptions); 
        
        /** 
         * Update our reminder date sent
         */
        
        $this->meta[$datekey] = date(DateTime::ISO8601); 
        $this->commit(); 
        
        return $this;
        
    }
    
    /**
     * Notify participants that this competition is open for voting
     * @since Version 3.9.1
     * @return \Railpage\Images\Competition
     * @todo Check recipient preferences for email notifications
     */
    
    private function notifyVotingOpen() {
        
        /**
         * Return if we're not within the voting bounds
         */
        
        if (!Utility\CompetitionUtility::isVotingWindowOpen($this)) {
            return $this;
        }
            
        $body = sprintf(
            "Hi [username],\n\nWe wanted to let you know that the <a href='%s'>%s</a> photo competition is open for voting until %s.\n\nYou've received this email because you've participated in a previous photo competition.\n\nThanks\nThe Railpage team.",
            $this->url->email, 
            $this->title, 
            $this->VotingDateClose->format("F jS")
        );
        
        $notificationOptions = array(
            "flag" => __FUNCTION__, 
            "subject" => sprintf("Voting open: %s", $this->title),
            "body" => $body
        );
        
        Utility\CompetitionUtility::sendNotification($this, $notificationOptions); 
        
        return $this;
        
    }
    
    /**
     * Release votes cast for a given image
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return \Railpage\Images\Competition
     */
    
    private function releaseVotesForImage(Image $imageObject) {
        $where = array(
            "competition_id = ?" => $this->id,
            "image_id = ?" => $imageObject->id
        );
        
        $this->db->delete("image_competition_votes", $where);
        
        return $this;
    }
    
}
