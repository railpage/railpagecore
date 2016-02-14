<?php

/** 
 * Photo of the week nomination/management
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Exception;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use DateInterval;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\PrivateMessages\Message;

class PhotoOfTheWeek extends AppCore {
    
    /**
     * Nominate a photo 
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @param \DateTime $dateWeek
     * @param \Railpage\Users\User $userObject
     * @return boolean
     */
    
    public function NominateImage(Image $imageObject, DateTime $dateWeek, User $userObject) {
        
        $query = "SELECT id FROM image_weekly WHERE datefrom = ?";
        
        if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
            $dateWeek->add(new DateInterval("P7D"));
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
            
            if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
                $dateWeek->add(new DateInterval("P7D"));
            }
        }
        
        if ($this->db->fetchOne($query, $dateWeek->format("Y-m-d"))) {
            throw new Exception("We already have an image nominated for the week starting " . $dateWeek->format("Y-m-d")); 
        }
        
        if ($this->db->fetchOne("SELECT id FROM image_weekly WHERE image_id = ?", $imageObject->id)) {
            throw new Exception("This photo has already been nominated for Photo of the Week"); 
        }
        
        $data = [
            "image_id" => $imageObject->id,
            "datefrom" => $dateWeek->format("Y-m-d"),
            "added_by" => $userObject->id
        ];
        
        $this->db->insert("image_weekly", $data); 
        
        try {
            if (isset($imageObject->author->User) && $imageObject->author->User instanceof User) {
                $Message = new Message;
                $Message->setAuthor($userObject);
                $Message->setRecipient($imageObject->author->User);
                $Message->subject = "Photo of the Week";
                $Message->body = sprintf("Your [url=%s]photo[/url] has been nominated for Photo of the Week. \n\nRegards,\n%s\n\nThis is an automated message.", $imageObject->url, $userObject->username);
                
                $Message->send();
            }
        } catch (Exception $e) {
            // throw it away
        }
        
        return true;
        
    }
    
    /**
     * Get the start of week from any given date
     * @since Version 3.9.1
     * @param string $week
     * @return \DateTime
     */
    
    public static function getStartOfWeek($week = false) {
        
        if (!$week) {
            $week = new DateTime;
        }
        
        if (filter_var($week, FILTER_VALIDATE_INT)) {
            $week = new DateTime("@" . $week);
        }
        
        if (!$week instanceof DateTime) {
            $week = new DateTime($week);
        }
        
        $ts = strtotime('sunday last week', $week->getTimestamp()); 
        
        $Date = new DateTime("@" . $ts);
        $Date->setTimezone(new DateTimeZone("Australia/Melbourne")); 
        
        return $Date;
    }
    
    /**
     * Get the image of the week
     * @since Version 3.9.1
     * @return array
     */
    
    public function getImageOfTheWeek($week = false) {
        
        $Date = self::getStartOfWeek($week);
        
        $query = "SELECT i.*, u.username, iw.added_by AS user_id FROM image_weekly AS iw
                    LEFT JOIN image AS i ON iw.image_id = i.id
                    LEFT JOIN nuke_users AS u ON u.user_id = iw.added_by
                    WHERE iw.datefrom = ? LIMIT 1";
        
        $result = $this->db->fetchRow($query, $Date->format("Y-m-d")); 
        
        if (!$result) {
            return false;
        }
        
        $result['meta'] = json_decode($result['meta'], true); 
        $result['meta']['sizes'] = Images::normaliseSizes($result['meta']['sizes']);
        $result['url'] = Utility\Url::CreateFromImageID($result['id'])->getURLs();
        
        return $result;
        
    }
    
    /** 
     * Determine if an image has been photo of the week, and return the date range if found
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
    
    public function isPhotoOfTheWeek(Image $imageObject) {
        
        $query = "SELECT iw.datefrom, u.user_id, u.username, iw.image_id 
            FROM image_weekly AS iw 
            LEFT JOIN nuke_users AS u ON iw.added_by = u.user_id
            WHERE iw.image_id = ?";
        
        $result = $this->db->fetchRow($query, $imageObject->id); 
        
        return $result;
        
    }
    
    /**
     * Get previous photos
     * @since Version 3.10.0
     * @return array
     * @param int $page
     * @param int $itemsPerPage
     */
    
    public function getPreviousPhotos($page = 1, $itemsPerPage = 25) {
        
        $Date = self::getStartOfWeek(new DateTime);
        
        $query = "SELECT SQL_CALC_FOUND_ROWS i.*, u.username, iw.added_by AS user_id FROM image_weekly AS iw
                    LEFT JOIN image AS i ON iw.image_id = i.id
                    LEFT JOIN nuke_users AS u ON u.user_id = iw.added_by
                    WHERE iw.datefrom < ? LIMIT ?, ?";
        
        $params = [ 
            $Date->format("Y-m-d"),
            ($page - 1) * $itemsPerPage, 
            $itemsPerPage
        ];
        
        $result = $this->db->fetchAll($query, $params); 
        $return = [
            "total" => 0,
            "page" => $page, 
            "items_per_page" => $itemsPerPage,
            "photos" => []
        ];
        
        $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
        
        foreach ($result as $row) {
            $row['meta'] = json_decode($row['meta'], true); 
            $row['meta']['sizes'] = Images::normaliseSizes($row['meta']['sizes']);
            $row['url'] = Utility\Url::CreateFromImageID($row['id'])->getURLs();
            
            $return['photos'][] = $row;
        }
        
        return $return;
        
    }
    
}