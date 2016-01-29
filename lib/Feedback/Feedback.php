<?php
    /**
     * Feedback module
     * @since Version 3.4
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Feedback;
    
    use Exception;
    use DateTime;
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Users\User;
    
    /**
     * Feedback class
     * @since Version 3.4
     */
    
    class Feedback extends \Railpage\AppCore {
        // Do something...anything...?
        
        /**
         * Get feedback items
         * @since Version 3.8.7
         * @yields new \Railpage\Feedback\FeedbackItem
         */
        
        public function getFeedbackItems() {
            $query = "SELECT f.*, fs.name AS status_text, fa.feedback_title AS area_text
                    FROM feedback AS f 
                    INNER JOIN feedback_status AS fs ON f.status = fs.id
                    INNER JOIN feedback_area AS fa ON f.area = fa.feedback_id
                    WHERE f.status = 1
                    ORDER BY f.time DESC";
            
            foreach ($this->db->fetchAll($query) as $row) {
                yield new FeedbackItem($row['id']);
            }
        }
        
        /**
         * Set the staff member associated to this feedback item
         * @since Version 3.9
         * @param \Railpage\Users\User $User
         * @return $this
         */
        
        public function setStaff(User $User) {
            $this->Staff = $User;
            
            return $this;
        }
        
        /**
         * Get feedback items assigned to this user
         * @since Version 3.9
         * @return array
         */
        
        public function getAssignedItems() {
            if (!$this->Staff instanceof User) {
                throw new Exception("You must assign a valid User object before fetching assigned feedback items");
            }
            
            $query = "SELECT f.*, fs.name AS status_text, fa.feedback_title AS area_text
                    FROM feedback AS f 
                    INNER JOIN feedback_status AS fs ON f.status = fs.id
                    INNER JOIN feedback_area AS fa ON f.area = fa.feedback_id
                    WHERE f.assigned_to = ?
                    ORDER BY f.time DESC";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $this->Staff->id) as $row) {
                $date = new DateTime(sprintf("@%s", $row['time']));
                
                $data = array(
                    "id" => $row['id'],
                    "message" => $row['message'],
                    "date" => array(
                        "absolute" => $date->format("Y-m-d H:i:s"),
                        "relative" => time2str($row['time']),
                    ),
                    "area" => array(
                        "id" => $row['area'],
                        "text" => $row['area_text'],
                    ),
                    "status" => array(
                        "id" => $row['status'],
                        "text" => $row['status_text'],
                    ),
                    "author" => array(
                        "id" => false,
                        "username" => false,
                        "realname" => false,
                        "email" => $row['email']
                    )
                );
                
                if (filter_var($row['user_id'], FILTER_VALIDATE_INT) && $row['user_id'] > 0) {
                    $Author = new User($row['user_id']);
                    $data['author']['id'] = $Author->id;
                    $data['author']['username'] = $Author->username;
                    $data['author']['realname'] = $Author->real_name;
                    $data['author']['url'] = $Author->url->url;
                    
                    $data['author']['avatar'] = array(
                        "large" => format_avatar($Author->avatar, 120),
                        "small" => format_avatar($Author->avatar, 40)
                    );
                }
                
                $return[] = $data;
            }
            
            return $return;
        }
    }
    