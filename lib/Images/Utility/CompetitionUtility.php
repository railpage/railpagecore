<?php

/**
 * Photo competition utility class
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Utility;

use Railpage\Images\Competition;
use Railpage\Images\Competitions;
use Railpage\Images\Image;
use Railpage\Images\Images;
use Railpage\ContentUtility;
use Railpage\Debug;
use Railpage\SiteMessages\SiteMessages;
use Railpage\SiteMessages\SiteMessage;
use Railpage\Notifications\Notification;
use Railpage\Notifications\Notifications;
use DateTime;
use Railpage\AppCore;
use Railpage\News\Article as NewsArticle;
use Railpage\News\Topic as NewsTopic;
use Railpage\News\News;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;

class CompetitionUtility {
    
    /**
     * Find the photo context from a specified array
     * @since Version 3.9.1
     * @param array $photos
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
    
    public static function getPhotoContext($photos, $imageObject) {
        
        /**
         * Loop through the array once until we find the provided image. Add photos to a temporary array, and then we slice it to return the last 4 entries to the array
         */
        
        $return = array(); 
        $split = 0;
        $tmp = array(); 
        
        foreach ($photos as $key => $data) {
            
            if ($data['image_id'] == $imageObject->id) {
                $split = $key;
                break;
            }
            
            $tmp[] = $data; 
        }
        
        $return = array_slice($tmp, -2);
        
        /**
         * Add the current photo to the array
         */
        
        $return[] = array_merge($photos[$split], array("current" => true));
        
        /**
         * Loop through the array starting at $split
         */
        
        $tmp = array(); 
        
        foreach ($photos as $key => $data) {
            if ($key >= $split + 1) {
                $tmp[] = $data;
            }
        }
        
        $return = array_merge($return, array_slice($tmp, 0, 2));
        
        return array_reverse($return);
        
    }
    
    /**
     * Create a news article announcing the commencement of submissions
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return void
     */
    
    public static function createNewsArticle_SubmissionsOpen(Competition $photoComp) {
        
        if (isset($photoComp->meta['news.submissions.open']) && $photoComp->meta['news.submissions.open'] == "created") {
            return;
        }
        
        if (!self::isSubmissionWindowOpen($photoComp)) {
            return;
        }
        
        $theme = $photoComp->theme;
        if (!preg_match('/[\p{P}]$/u', $theme)) {
            $theme .= ".";
        }
        
        /**
         * Curate the news article
         */
        
        $Topic = new NewsTopic(5); // Topic in the Railpage category
        $Article = new NewsArticle;
        
        $Article->title = "Submissions open for " . $photoComp->title . " photo comp";
        $Article->featured_image = "https://static.railpage.com.au/i/photocomphero.jpg";
        
        $Article->lead = sprintf("Submissions are now open for our monthly international photo competition. The theme for this competition is <em>%s</em>", $theme); 
        $Article->firstline = $Article->lead;
        $Article->paragraphs  = $Article->lead . "\n\n" . sprintf("Entries are open until %s. Please take a moment to read the competition rules before submitting your entry, and please note the photo must belong to you.\n\n", $photoComp->SubmissionsDateClose->format("F jS"));
        $Article->paragraphs .= sprintf("To enter the competition your photo must appear on Flickr or SmugMug, and you must have a valid Railpage user account. For further details, view the submissions thus far or to enter your own photo please head to the <a href='%s'>%s</a> competition page.", $photoComp->url->url, $photoComp->title); 
        
        $Article->setAuthor(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setStaff(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setTopic($Topic);
        
        $Article->commit();
        
        $photoComp->meta['news.submissions.open'] = "created";
        $photoComp->commit();
        
        return;
        
    }
    
    /**
     * Create a news article announcing the commencement of voting
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return void
     */
    
    public static function createNewsArticle_VotingOpen(Competition $photoComp) {
        
        if (isset($photoComp->meta['news.voting.open']) && $photoComp->meta['news.voting.open'] == "created") {
            return;
        }
        
        if (!self::isVotingWindowOpen($photoComp)) {
            return;
        }
        
        $theme = $photoComp->theme;
        if (!preg_match('/[\p{P}]$/u', $theme)) {
            $theme .= ".";
        }
        
        /**
         * Curate the news article
         */
        
        $Topic = new NewsTopic(5); // Topic in the Railpage category
        $Article = new NewsArticle;
        
        $Article->title = "Voting open for " . $photoComp->title . " photo comp";
        $Article->featured_image = "https://static.railpage.com.au/i/photocomphero.jpg";
        
        $Article->lead = sprintf("Voting is now open for our monthly international photo competition. The theme for this competition is <em><a href='%s'>%s</a></em>", $photoComp->url->url, $theme); 
        $Article->firstline = $Article->lead;
        $Article->paragraphs  = $Article->lead . "\n\n" . sprintf("Voting is open until %s.\n\n", $photoComp->VotingDateClose->format("F jS"));
        //$Article->paragraphs .= sprintf("To enter the competition your photo must appear on Flickr or SmugMug, and you must have a valid Railpage user account. For further details, view the submissions thus far or to enter your own photo please head to the <a href='%s'>%s</a> competition page.", $photoComp->url->url, $photoComp->title); 
        
        $Article->setAuthor(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setStaff(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setTopic($Topic);
        
        $Article->commit();
        
        $photoComp->meta['news.voting.open'] = "created";
        $photoComp->commit();
        
        return;
        
    }
    
    /**
     * Create a news article announcing the end of the competition
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return void
     */
    
    public static function createNewsArticle_Winner(Competition $photoComp) {
        
        /**
         * Get the winning photo
         */
        
        $Photo = $photoComp->getWinningPhoto();
        
        /**
         * Get all photos by vote count
         */
        
        $photos = $photoComp->getPhotosAsArrayByVotes(); 
        
        /**
         * Get the next competition
         */
        
        $Competitions = new Competitions;
        $NextComp = $Competitions->getNextCompetition($photoComp); 
        
        /**
         * Curate the news article
         */
        
        $Topic = new NewsTopic(5); // Topic in the Railpage category
        $Article = new NewsArticle;
        
        $Article->title = $photoComp->title . " photo comp";
        $Article->lead = sprintf("Congratulations to [url=%s]%s[/url] who has won the [url=%s]%s[/url] photo competition with %d votes.", $Photo->Author->url->url, $Photo->Author->username, $photoComp->url->url, $photoComp->title, count($photos[0]['votes'])); 
        $Article->firstline = $Article->lead;
        $Article->featured_image = $Photo->Image->sizes['medium']['source'];
        
        $Article->paragraphs  = $Article->lead . "\n\n" . sprintf("In second place was [url=%s]%s[/url] with %d votes, and in third place was [url=%s]%s[/url] with %d votes.\n\n", $photos[1]['author']['url']['url'], $photos[1]['author']['username'], count($photos[1]['votes']), $photos[2]['author']['url']['url'], $photos[2]['author']['username'], count($photos[2]['votes'])); 
        
        if (self::isSubmissionWindowOpen($NextComp) && !self::isVotingWindowOpen($NextComp)) {
            $Article->paragraphs .= sprintf("Submissions for our next competition, [url=%s]%s[/url], are open until %s.", $NextComp->url->url, $NextComp->title, $NextComp->SubmissionsDateClose->Format("F jS")); 
        } elseif (!self::isSubmissionWindowOpen($NextComp) && !self::isVotingWindowOpen($NextComp)) {
            $Article->paragraphs .= sprintf("Submissions for our next competition, [url=%s]%s[/url], are open from %s until %s.", $NextComp->url->url, $NextComp->title, $NextComp->SubmissionsDateOpen->Format("F jS"), $NextComp->SubmissionsDateClose->Format("F jS")); 
        }
        
        if ($NextComp->VotingDateOpen > new DateTime) {
            
            if (!preg_match('/[\p{P}]$/u', $NextComp->theme)) {
                $NextComp->theme .= ".";
            }
            
            $Article->paragraphs .= sprintf("Entry to the competition is open to all registered users. If you're not a registered user, you can [url=/registration]sign up now[/url]!\n\nThe next theme is: [i]%s[/i]\n\n", $NextComp->theme); 
            
        }
        
        $Article->setAuthor(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setStaff(UserFactory::CreateUser(User::SYSTEM_USER_ID))
                ->setTopic($Topic);
        
        $Article->commit();
        $Article->Approve(User::SYSTEM_USER_ID);
        
    }
    
    /**
     * Create a site message targeted to the competition winner
     * @since Version 3.9.1
     * @param \Railpage\Images\Competition $photoComp
     * @return void
     */
    
    public static function createSiteNotificationForWinner(Competition $photoComp) {
        
        $Photo = $photoComp->getWinningPhoto();
        
        if (!$SiteMessage = (new SiteMessages)->getMessageForObject($photoComp)) {
            $SiteMessage = new SiteMessage; 
            
            $SiteMessage->title = sprintf("Photo competition: %s", $photoComp->title); 
            $SiteMessage->text = sprintf("You won the %s photo competition! <a href='%s'>Set the subject of next month's competition</a>.", $photoComp->title, $photoComp->url->suggestsubject); 
            $SiteMessage->Object = $photoComp;
            $SiteMessage->targetUser($Photo->Author)->commit(); 
        }
        
    }
    
    /**
     * Check if the image submission window is open
     * @since Version 3.9.1
     * @param \Railpage\Images\Competition $photoComp
     * @return boolean
     */
    
    public static function isSubmissionWindowOpen(Competition $photoComp) {
        
        return self::compareWindows($photoComp, "Submissions");
        
    }
    
    /**
     * Check if the voting window is open
     * @since Version 3.9.1
     * @param \Railpage\Images\Competition $photoComp
     * @return boolean
     */
    
    public static function isVotingWindowOpen(Competition $photoComp) {
        
        return self::compareWindows($photoComp, "Voting");
        
    }
    
    /**
     * Compare voting / submission windows to current time
     * @since Version 3.9.1
     * @param \Railpage\Images\Competition $photoComp
     * @param string $window
     * @return boolean
     */
    
    private static function compareWindows(Competition $photoComp, $window) {
        
        $Now = new DateTime;
        
        $open = sprintf("%sDateOpen", $window);
        $close = sprintf("%sDateClose", $window);
        
        if (!($photoComp->$open instanceof DateTime && $photoComp->$open <= $Now) || 
            !($photoComp->$close instanceof DateTime && $photoComp->$close >= $Now)) {
                return false;
        }
        
        return true;
        
    }
    
    /**
     * Get contestants from a photo competition
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return array
     */
    
    public static function getCompetitionContestants(Competition $photoComp) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT u.user_id, u.username, u.user_email AS contact_email FROM image_competition_submissions AS s LEFT JOIN nuke_users AS u ON s.user_id = u.user_id WHERE s.competition_id = ? AND s.status != ?";
        $params = [ $photoComp->id, Competitions::PHOTO_REJECTED ];
        
        return $Database->fetchAll($query, $params); 
        
    }
    
    /**
     * Process the recipients of the notification email
     * @since Version 3.9.1
     * @param \Railpage\Notifications\Notification $notificationObject
     * @param \Railpage\Images\Competition $photoComp
     * @param array $notificationOptions
     * @return \Railpage\Notifications\Notification
     */
     
    public static function notificationDoRecipients(Notification $notificationObject, Competition $photoComp, $notificationOptions = null) {
        
        $contestants = (new Competitions)->getPreviousContestants(); 
        
        $replacements = array(); 
        $exclude = array(); 
        
        if ($notificationOptions['excludeCurrentContestants']) {
            foreach (self::getCompetitionContestants($photoComp) as $row) {
                $exclude[$row['user_id']] = $row;
            }
        }
        
        foreach ($contestants as $row) {
            if (!in_array($row['user_id'], array_keys($exclude))) {
                $notificationObject->AddRecipient($row['user_id'], $row['username'], $row['contact_email']);
                
                // Add to the decorator
                $replacements[$row['contact_email']] = array(
                    "[username]" => $row['username']
                );
            }
        }
        
        $notificationObject->meta['decoration'] = $replacements;
        
        return $notificationObject;

    }
    
    /**
     * Send an email notification for this competition
     * @since Version 3.9.1
     * @param \Railpage\Images\Competition $photoComp
     * @param array $notificationOptions
     * @return void
     * @todo Finish push notifications
     */
    
    public static function sendNotification(Competition $photoComp, $notificationOptions) {
        
        if (count((new Competitions)->getPreviousContestants()) == 0) {
            return false;
        }
        
        $flag = $notificationOptions['flag'];
        
        if (!is_array($notificationOptions)) {
            $notificationOptions = [];
        }
        
        // If we want to exclude contestants in this competition from this email, set this to true
        // This is to remind contestants from previous comps to submit a photo
        
        if (!isset($notificationOptions['excludeCurrentContestants'])) {
            $notificationOptions['excludeCurrentContestants'] = false;
        }
        
        /**
         * Check if the notification sent flag has been set
         */
        
        if (!isset($photoComp->meta[$flag]) || !filter_var($photoComp->meta[$flag]) || $photoComp->meta[$flag] === false) {
            
            /**
             * Create the notification
             */
            
            $Notification = new Notification;
            $Notification->subject = $notificationOptions['subject'];
            $Push = new Notification;
            $Push->subject = $Notification->subject;
            $Push->transport = Notifications::TRANSPORT_PUSH;
            
            /**
             * Add recipients and decoration
             */
            
            $Notification = self::notificationDoRecipients($Notification, $photoComp, $notificationOptions);
            $Push = self::notificationDoRecipients($Push, $photoComp, $notificationOptions);  
                            
            /**
             * Set our email body
             */
            
            if (function_exists("wpautop") && function_exists("format_post")) {
                $notificationOptions['body'] = wpautop(format_post($notificationOptions['body']));
            }
            
            /**
             * Assemble some template vars for our email
             */
            
            $Smarty = AppCore::getSmarty(); 
            
            $Smarty->Assign("email", array(
                "subject" => $notificationOptions['subject'],
                "body" => $notificationOptions['body']
            ));
            
            /**
             * Set the body, submit the notification to the dispatch queue
             */
            
            $Notification->body = $Smarty->Fetch($Smarty->ResolveTemplate("template.generic"));
            $Notification->commit();
            
            /**
             * Set the notification flag
             */
            
            $photoComp->meta[$flag] = true;
            $photoComp->commit(); 
            
        }

    }
    
    /**
     * Get the competition ID
     * @since Version 3.9.1
     * @return int
     * @param string $slug
     */
    
    public static function getIDFromSlug($slug) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        if (filter_var($slug, FILTER_VALIDATE_INT)) {
            return $slug; 
        }
        
        $query = "SELECT id FROM image_competition WHERE slug = ?";
        $tempid = $Database->fetchOne($query, $slug);
        
        if (filter_var($tempid, FILTER_VALIDATE_INT)) {
            return $tempid;
        }
        
        $query = "SELECT ID from image_competition WHERE title = ?";
        return $Database->fetchOne($query, $slug);
        
    }
    
    /**
     * Make an opengraph tag from the supplied object
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @param string $tag
     * @return string
     */
    
    public static function makeOpenGraphTag(Competition $photoComp, $tag) {
        
        switch ($tag) {
            case "description":
            case "desc":
                $string = trim("Theme: " . $photoComp->theme);
                
                if (preg_match("/([a-zA-Z0-9]+)/", substr($string, -1, 1))) {
                    $string .= "."; 
                }
                
                $string = trim($string);
                
                if (self::isSubmissionWindowOpen($photoComp)) {
                    $string .= " Submissions open until " . $photoComp->SubmissionsDateClose->format("F jS");
                }
                
                if (self::isVotingWindowOpen($photoComp)) {
                    $string .= " Voting open until " . $photoComp->VotingDateClose->format("F jS");
                }
                
                if (preg_match("/([a-zA-Z0-9]+)/", substr($string, -1, 1))) {
                    $string = trim($string) . "."; 
                }
                
                return $string;
                
                break;
                
        }
    }
    
    /**
     * Send out a notification to site admins to cast a deciding vote in the event of a tied competition
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return void
     */
    
    public static function NotifyTied(Competition $photoComp) {
        
        if (isset($photoComp->meta['notifytied']) && $photoComp->meta['notifytied'] >= strtotime("-1 day")) {
            return;
        }
        
        $photoComp->meta['notifytied'] = time(); 
        $photoComp->commit(); 
        
        $Smarty = AppCore::GetSmarty(); 
        
        // User who will cast the deciding vote
        $Decider = UserFactory::CreateUser(45); 
        
        // Create the push notification
        $Push = new Notification;
        $Push->transport = Notifications::TRANSPORT_PUSH; 
        $Push->subject = "Tied photo competition";
        $Push->body = sprintf("The %s photo competition is tied. Cast a deciding vote ASAP.", $photoComp->title); 
        $Push->setActionUrl($photoComp->url->tied)->addRecipient($Decider->id, $Decider->username, $Decider->username); 
        $Push->commit()->dispatch(); 
        
        // Create an email notification as a backup
        $Email = new Notification;
        $Email->subject = "Tied competition: " . $photoComp->title;
        $Email->addRecipient($Decider->id, $Decider->username, $Decider->username); 
        $tpl = $Smarty->ResolveTemplate("template.generic");
            
        $email = array(
            "subject" => $Email->subject,
            "subtitle" => "Photo competitions",
            "body" => sprintf("<p>The <a href='%s'>%s</a>photo competition is tied and requires a deciding vote. <a href='%s'>Cast it ASAP</a>.</p>", $photoComp->url->canonical, $photoComp->title, $photoComp->url->tied)
        );
        
        $Smarty->Assign("email", $email);
        
        $Email->body = $Smarty->fetch($tpl);
        
        $Email->commit(); 
        
        return;
        
    }
    
    /**
     * Get photos tied for first place
     * @since Version 3.10.0
     * @param \Railpage\Images\Competition $photoComp
     * @return array
     */
    
    public static function getTiedPhotos(Competition $photoComp) {
        
        $photos = $photoComp->getPhotosAsArrayByVotes(); 
        $votes = false;
        $tied = [];
        
        foreach ($photos as $photo) {
            if ($votes === false) {
                $votes = count($photo['votes']); 
                $tied[] = $photo;
                continue;
            }
            
            if (count($photo['votes']) === $votes) {
                $tied[] = $photo;
            }
            
            if (count($photo['votes']) < $votes) {
                continue;
            }
            
        }
        
        if (count($tied) < 2) {
            return [];
        }
           
        return $tied;
        
    }
}