<?php
    /**
     * Image favourites/stars
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\Images\Utility\Url as ImageUrl;
    use Railpage\Images\Utility\Updater as ImageUpdater;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Users\Utility\UrlUtility as UserUrlUtility;
    use Railpage\Users\Utility\UserUtility;
    use Railpage\Users\User;
    
    class Favourites extends AppCore {
        
        /**
         * Get users who have favourited the supplied image
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @return array
         */
        
        public function getImageFavourites(Image $Image) {
            
            $query = "SELECT f.*, u.username 
                FROM image_favourites AS f 
                    LEFT JOIN nuke_users AS u ON f.user_id = u.user_id
                WHERE f.image_id = ? 
                ORDER BY f.date DESC";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $Image->id) as $row) {
                $row['url'] = UserUrlUtility::MakeURLs($row)->getURLs(); 
                $return[] = $row;
            }
            
            return $return;
            
        }
        
        /**
         * Get favourites for the supplied user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return array
         */
        
        public function getUserFavourites(User $User) {
            
            $query = "SELECT f.*, i.title, i.description, i.meta
                FROM image_favourites AS f 
                    LEFT JOIN image AS i ON f.image_id = i.image_id
                WHERE f.user_id = ? 
                ORDER BY f.date DESC";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $User->id) as $row) {
                $row['url'] = ImageUrlUtility::CreateFromImageID($row['image_id'])->getURLs(); 
                $return[] = $row;
            }
            
            return $return;
            
        }
        
        /**
         * Favourite a photo
         * @since Version 3.10.0
         * @return void
         */
        
        public function setUserFavourite() {
            
            /**
             * Because I'll no doubt get confused - frequently - at the order of parameters, 
             * let's just be a sneaky bastard and check each parameter
             */
            
            $User = false;
            $Image = false;
            
            foreach (func_get_args() as $arg) {
                if ($arg instanceof User) {
                    $User = $arg;
                    continue;
                }
                
                if ($arg instanceof Image) {
                    $Image = $arg;
                    continue;
                }
            }
            
            if ($User === false) {
                throw new InvalidArgumentException("No or invalid user object provided"); 
            }
            
            if (!filter_var($User->id, FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException("It looks like you're not logged in");
            }
            
            if ($Image === false) {
                throw new InvalidArgumentException("No or invalid image object provided"); 
            }
            
            /**
             * If the author is the supplied user, get out
             */
            
            if (!isset($Image->author->User) || !$Image->author->User instanceof User) {
                $Image = ImageUpdater::updateAuthor($Image);
            }
            
            if (($Image->author->User instanceof User && $Image->author->User->id == $User->id) || $Image->author->railpage_id == $User->id) {
                return;
            }
            
            /**
             * Insert into...on duplicate key update because feck it
             */
            
            $query = "INSERT INTO image_favourites (user_id, image_id, date) VALUES (%d, %d, NOW()) ON DUPLICATE KEY UPDATE date = VALUES(date)";
            $query = sprintf($query, $this->db->quote(intval($User->id)), $this->db->quote(intval($Image->id))); 
            
            $this->db->query($query); 
            
            return;
            
        }
            
        
        /**
         * Un-favourite a photo
         * @since Version 3.10.0
         * @return void
         */
        
        public function removeUserFavourite() {
            
            /**
             * Because I'll no doubt get confused - frequently - at the order of parameters, 
             * let's just be a sneaky bastard and check each parameter
             */
            
            $User = false;
            $Image = false;
            
            foreach (func_get_args() as $arg) {
                if ($arg instanceof User) {
                    $User = $arg;
                    continue;
                }
                
                if ($arg instanceof Image) {
                    $Image = $arg;
                    continue;
                }
            }
            
            if ($User === false) {
                throw new InvalidArgumentException("No or invalid user object provided"); 
            }
            
            if (!filter_var($User->id, FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException("It looks like you're not logged in");
            }
            
            if ($Image === false) {
                throw new InvalidArgumentException("No or invalid image object provided"); 
            }
            
            $where = [ 
                "image_id = ?" => $Image->id,
                "user_id = ?" => $User->id
            ];
            
            $this->db->delete("image_favourites", $where); 
            
            return;
            
        }
        
    }
    