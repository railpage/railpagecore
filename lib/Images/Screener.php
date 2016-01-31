<?php
    /**
     * Review images submitted to the pool
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    use DateTime;
    use Exception;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Users\Utility\AvatarUtility;
    
    class Screener extends AppCore {
        
        /**
         * Get unreviewed images
         * @since Version 3.10.0
         * @param int $page
         * @param int $items_per_page
         * @return array
         */
        
        public function getUnreviewedImages($page = 1, $items_per_page = 25) {
            
            if (!$this->User instanceof User) {
                throw new Exception("Cannot find unreviewed images - no user has been set");
            }
            
            $query = "SELECT SQL_CALC_FOUND_ROWS image.id, image.title, image.user_id 
                        FROM image 
                        WHERE image.id NOT IN ( 
                            SELECT f.image_id 
                            FROM image_flags AS f
                            WHERE f.image_id = image.id 
                        ) 
                        AND image.id NOT IN (
                            SELECT s.image_id
                            FROM image_flags_skip AS s
                            WHERE s.user_id = ?
                        )
                        AND image.hidden = 0 
                        ORDER BY image.id DESC 
                        LIMIT ?, ?";
            
            $params = [ 
                $this->User->id,
                ($page - 1) * $items_per_page, 
                $items_per_page 
            ];
            
            $return = array(
                "page" => $page, 
                "items_per_page" => $items_per_page, 
                "total" => 0,
                "images" => array()
            );
            
            $result = $this->db->fetchAll($query, $params); 
            $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS()");
            
            $return['images'] = $result;
            
            return $return;
            
        }
        
        /**
         * Review an image
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @param \Railpage\Users\User $User
         * @param boolean $publish
         * @param boolean $pick Does the screener think this is an exceptional photograph?
         * @return \Railpage\Images\Screener
         */
        
        public function reviewImage(Image $Image, User $User, $publish = false, $pick = false) {
            
            $publish = (bool) intval($publish);
            $pick = (bool) intval($pick); 
            
            // If the screener hinks this is a worthy photo, why shouldn't we publish it?
            if ($pick) {
                $publish = true;
            }
            
            $query = sprintf("INSERT INTO image_flags (
                                image_id, published, screened, screened_by, screened_on, screened_pick, rejected
                            ) VALUES (
                                %d, %d, %d, %d, NOW(), %d, %d
                            ) ON DUPLICATE KEY UPDATE 
                                published = VALUES(published), screened_by = VALUES(screened_by), 
                                screened_on = NOW(), screened_pick = VALUES(screened_pick), rejected = VALUES(rejected)",
                            $this->db->quote(intval($Image->id)), $publish, 1, $this->db->quote(intval($User->id)), $pick, !$publish);
            
            $this->db->query($query); 
            
            /**
             * Delete it from the review skip table
             */
            
            $where = [ 
                "image_id = ?" => $Image->id
            ];
            
            $this->db->delete("image_flags_skip", $where);
            
            if ($publish) {
                $Image = Utility\Updater::updateAuthor($Image);
            }
            
            return $this;
            
        }
        
        /**
         * Skip reviewing this image
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @param \Railpage\Users\User $User
         * @return \Railpage\Images\Screener
         */
        
        public function skipImage(Image $Image, User $User) {
            
            $query = sprintf("INSERT INTO image_flags_skip ( 
                                `image_id`, `user_id`, `date`
                            ) VALUES (
                                %d, %d, NOW()
                            ) ON DUPLICATE KEY UPDATE
                                `date` = NOW()",
                            $this->db->quote(intval($Image->id)), $this->db->quote(intval($User->id)));
            
            $this->db->query($query); 
            
            return $this;
            
        }
        
        /**
         * Get a list of users who have skipped reviewing this photo
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @return array
         */
        
        public function getSkippedBy(Image $Image) {
            
            $query = "SELECT u.username, u.user_id FROM nuke_users AS u LEFT JOIN image_flags_skip AS p ON p.user_id = u.user_id WHERE p.image_id = ?";
            
            $rs = $this->db->fetchAll($query, $Image->id); 
            return $rs;
            
        }
        
        /**
         * Undo the last screening by this user
         * @since Version 3.10.0
         * @return \Railpage\Images\Screener
         */
        
        public function undo() {
            
            if (!$this->User instanceof User) {
                throw new Exception("No valid user has been set");
            }
            
            $id = $this->db->fetchOne("SELECT image_id FROM image_flags WHERE screened_by = ? ORDER BY screened_on DESC LIMIT 1", $this->User->id);
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                return $this;
            }
            
            $where = [ "image_id = ?" => $id ];
            $this->db->delete("image_flags", $where);
            
            return $this;
            
        }
        
        /**
         * Delete an image 
         * @since Version 3.10.0
         * @param int|string $id
         * @param string $provider
         */
        
        public function deleteImage($id, $provider = false) {
            
            if (!$provider) {
                $where = [ "id = ?" => $id ];
                $this->db->delete("image", $where); 
                
                return $this;
            }
            
            $where = [ 
                "photo_id = ?" => $id, 
                "provider" => $provider
            ]; 
            
            $this->db->delete("image", $where); 
            
            return $this;
            
        }
        
        /**
         * Get the screener of the selected image
         * @since Version 3.10.0
         * @param \Railpage\Images\Image $Image
         * @return array
         */
        
        public function getImageScreener(Image $Image) {
            
            $query = "SELECT f.*, f.screened_by AS user_id, u.username, u.user_avatar, CONCAT('/user/', f.screened_by) AS url FROM image_flags AS f LEFT JOIN nuke_users AS u ON f.screened_by = u.user_id WHERE image_id = ?";
            
            $row = $this->db->fetchRow($query, $Image->id); 
            
            $av = $row['user_avatar'];
            
            $row['user_avatar'] = [
                "tiny" => AvatarUtility::Format($av, 25, 25),
                "thumb" => AvatarUtility::Format($av, 50, 50),
                "small" => AvatarUtility::Format($av, 75, 75),
                "medium" => AvatarUtility::Format($av, 100, 100)
            ];
            
            return $row;
            
        }
        
        /**
         * Get the list of screeners
         * @since Version 3.10.0
         * @return array
         */
        
        public function getScreeners() {
            
            $query = "SELECT u.user_id, u.username, CONCAT('/user/', u.user_id) AS url, COUNT(*) AS num
                FROM image_flags AS f
                LEFT JOIN nuke_users AS u ON f.screened_by = u.user_id 
                WHERE u.user_id NOT IN (2)
                GROUP BY f.screened_by
                ORDER BY u.username";
            
            return $this->db->fetchAll($query); 
            
        }
        
        /**
         * Get rejected images
         * @since Version 3.10.0
         * @param int $page
         * @param int $items_per_page
         * @return array
         */
        
        public function getRejectedImages($page = 1, $items_per_page = 25) {
            
            $query = "SELECT SQL_CALC_FOUND_ROWS u.user_id, u.username, CONCAT('/user/', u.user_id) AS url, i.*
                FROM image_flags AS f
                LEFT JOIN nuke_users AS u ON f.screened_by = u.user_id
                LEFT JOIN image AS i ON f.image_id = i.id
                WHERE f.rejected = 1
                ORDER BY f.screened_on DESC
                LIMIT ?, ?";
            
            $params = [
                ($page - 1) * $items_per_page, 
                $items_per_page
            ];
            
            $return = array(
                "total" => 0,
                "page" => $page,
                "items_per_page" => $items_per_page,
                "photos" => array()
            ); 
            
            foreach ($this->db->fetchAll($query, $params) as $row) {
                $row['meta'] = json_decode($row['meta'], true);
                $row['meta']['sizes'] = Images::normaliseSizes($row['meta']['sizes']);
                $return['photos'][] = $row;
            }
            
            $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total");
            
            return $return;
            
        }
        
        /**
         * Get skipped images
         * @since Version 3.10.0
         * @param int $page
         * @param int $items_per_page
         * @return array
         */
        
        public function getSkippedImages($page = 1, $items_per_page = 25) {
            
            $query = "SELECT SQL_CALC_FOUND_ROWS i.*
                FROM image_flags_skip AS f
                LEFT JOIN image AS i ON f.image_id = i.id
                GROUP BY f.image_id
                ORDER BY f.date DESC
                LIMIT ?, ?";
            
            $params = [
                ($page - 1) * $items_per_page, 
                $items_per_page
            ];
            
            $return = array(
                "total" => 0,
                "page" => $page,
                "items_per_page" => $items_per_page,
                "photos" => array()
            ); 
            
            foreach ($this->db->fetchAll($query, $params) as $row) {
                $row['meta'] = json_decode($row['meta'], true);
                $row['meta']['sizes'] = Images::normaliseSizes($row['meta']['sizes']);
                $return['photos'][] = $row;
            }
            
            $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total");
            
            return $return;
            
        }
        
        /**
         * Get the last review date by a given user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return \DateTime|false Returns false if guest, or no review date found
         */
        
        public static function getLastReviewDate(User $User) {
            
            if (!filter_var($User->id, FILTER_VALIDATE_INT)) {
                return false;
            }
            
            $Database = AppCore::GetDatabase(); 
            
            $query = "SELECT screened_on FROM image_flags WHERE screened_by = ? ORDER BY screened_on DESC LIMIT 0, 1";
            
            $date = $Database->fetchOne($query, $User->id); 
            
            if (!$date) {
                
                $maintainers = [ 
                    11437,
                    13666
                ];
                
                if (in_array($User->id, $maintainers)) {
                    return new DateTime("jan 1 1970"); 
                }
                
                return false;
            }
            
            return new DateTime($date); 
            
        }
        
        /**
         * Get screeners who haven't done any work in the last seven days
         * @since Version 3.10.0
         * @return array
         */
        
        public static function getLazyScreeners() {
            
            $Database = AppCore::GetDatabase(); 
            
            $query = "SELECT * FROM ( 
                SELECT u.user_id, u.username, u.user_email, MAX(s.screened_on) AS last_screen 
                FROM image_flags AS s 
                LEFT JOIN nuke_users AS u ON u.user_id = s.screened_by 
                GROUP BY u.user_id 
            ) AS screeners 
            WHERE user_id NOT IN (2) 
            AND last_screen < DATE_SUB(NOW(), INTERVAL 7 DAY)";
            
            return $Database->fetchAll($query); 
            
        }
        
    }