<?php
    /**
     * Curate a weekly newsletter
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Newsletters;
    
    use DateTime;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\ContentUtility;
    use Railpage\Newsletters\Utility\NewsletterUtility;
    use Railpage\Locations\Factory as LocationsFactory;
    use Railpage\Locations\Locations;
    use Railpage\Locations\Location;
    use Railpage\Locos\Factory as LocosFactory;
    use Railpage\Locos\Locomotive;
    use Railpage\Locos\LocoClass;
    use Railpage\News\Factory as NewsFactory;
    use Railpage\News\Article;
    use Railpage\News\Feed;
    use Railpage\News\News;
    use Railpage\Images\ImageFactory;
    use Railpage\Images\Images;
    use Railpage\Images\Image;
    use Railpage\Images\ImageCache;
    use Railpage\Images\MapImage;
    use Railpage\Images\PhotoOfTheWeek;
    use Railpage\Notifications\Notifications;
    use Railpage\Notifications\Notification;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Users\User;
    
    class Weekly extends AppCore {
        
        /**
         * Maximum number of elements to post in this article
         * @since Version 3.10.0
         * @var int $num_items
         */
        
        private $num_items = 10;
        
        /**
         * Internal list of recipients
         * @since Version 3.10.0
         * @var array $recipients
         */
        
        private $recipients;
        
        /**
         * Extra content to insert before the news articles
         * @since Version 3.10.0
         * @var array $prependedContent
         */
        
        private $prependedContent;
        
        /**
         * Decoration array
         * @since Version 3.10.0
         * @var array $replacements
         */
        
        private $replacements;
        
        /**
         * Newsletter HTML
         * @since Version 3.10.0
         * @var string $html
         */
        
        private $html;
        
        /**
         * User IDs to mark as sent to
         * @since Version 3.10.0
         * @param array $user_ids
         */
         
        private $user_ids = [];
        
        /**
         * Newsletter object
         * @since Version 3.10.0
         * @var \Railpage\Newsletters\Newsletter $Newsletter
         */
        
        public $Newsletter;
        
        /**
         * Notification object
         * @since Version 3.10.0
         * @var \Railpage\Notifications\Notification $Notification
         */
        
        public $Notification;
        
        /**
         * Hero image 
         * @since Version 3.10.0
         * @var \Railpage\Images\Image $HeroImage
         */
        
        private $HeroImage;
        
        /**
         * Curate the newsletter
         * @since Version 3.10.0
         * @return void
         */
        
        public static function curate() {
            
            $Weekly = new Weekly;
            
            $Weekly->createNewsletter()
                   ->createNotification()
                   ->getRecipients()
                   ->getHeroImage()
                   ->getOtherContent() // non-news content, eg new locations or popular forum posts
                   ->prepareTemplate()
                   ->personaliseContent()
                   ->queue();
            
            return $Weekly;
            
        }
        
        /**
         * Create the newsletter object
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function createNewsletter() {
            
            $Newsletter = new Newsletter;
            $Newsletters = new Newsletters;
            $Newsletter->subject = "Top stories this week on Railpage";
            $Newsletter->template = $Newsletters->getTemplate(2);
            
            $this->Newsletter = $Newsletter;
            
            return $this;
            
        }
        
        /**
         * Create the notification object
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function createNotification() {
            
            $this->Notification = new Notification;
            
            return $this;
            
        }
        
        /**
         * Find and set the hero image
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function getHeroImage() {
            
            $query = "SELECT f.image_id, i.meta FROM image_flags AS f LEFT JOIN image AS i ON f.image_id = i.id WHERE f.screened_pick = 1";
            $ids = [];
            
            foreach ($this->db->fetchAll($query) as $row) {
                $row['meta'] = json_decode($row['meta'], true); 
                $sizes = Images::normaliseSizes($row['meta']['sizes']); 
                
                if ($sizes['medium']['height'] > $sizes['medium']['width']) {
                    continue;
                }
                
                $ids[] = $row['image_id'];
            }
            
            $image_id = $ids[array_rand($ids)];
            
            if (filter_var($image_id, FILTER_VALIDATE_INT)) {
                Debug::LogCLI("Creating instance of Image for the hero photo");
                $this->HeroImage = ImageFactory::CreateImage($image_id);
                $this->Newsletter->setHeroImage($this->HeroImage);
            }
            
            return $this;
            
        }
        
        /**
         * Fetch our template contents and replace the smarty variables with decoration placeholders
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function prepareTemplate() {
            
            $start = 0; $num = 10;
            $replacements = []; 
            
            for ($i = $start; $i < $start + $this->num_items; $i++) {
                $replacements[$i] = [
                    "subtitle"      => "##block" . $i . ".subtitle##",
                    "featuredimage" => "##block" . $i . ".featuredimage##",
                    "text"          => "##block" . $i . ".text##",
                    "link"          => "##block" . $i . ".link##",
                    "alt_title"     => "##block" . $i . ".alt_title##",
                    "linktext"      => "##block" . $i . ".link_text##",
                ];
            }
            
            $template = $this->Newsletter->template;
            $params = $this->Newsletter->getArray();
            $params['content'] = $replacements;
            $params['unsubscribe'] = "##unsubscribe##";
            
            $Smarty = AppCore::GetSmarty(); 
            
            $Smarty->Assign("newsletter", $params);
            $html = $Smarty->Fetch("string:" . $template['html']);
            
            #$this->replacements = $replacements;
            $this->html = $html;
            
            return $this;
            
        }
        
        /**
         * Get our recipients
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function getRecipients() {
            
            $query = "SELECT u.user_id, u.username, u.user_email, n.topics, n.keywords
                        FROM nuke_users AS u 
                        LEFT JOIN nuke_users_flags AS f ON f.user_id = u.user_id 
                        LEFT JOIN news_feed AS n ON n.user_id = u.user_id
                        WHERE COALESCE(f.newsletter_weekly, 1) = 1 
                        AND u.user_lastvisit > 0
                        AND u.user_email IS NOT NULL
                        AND u.user_active = 1
                        AND u.user_id NOT IN (SELECT user_id FROM bancontrol WHERE ban_active = 1 AND user_id != 0 GROUP BY user_id)";
            
            #$query .= " AND COALESCE(f.newsletter_weekly_last, NULL) < SUBDATE(NOW(), INTERVAL 1 week)";
            
            #$query .= " AND u.user_id IN (45, 2, 13666, 28)"; // TEMPORARY!
            $query .= " AND u.user_id IN (45)"; // TEMPORARY!
            
            $query .= " ORDER BY u.user_id LIMIT 0, 250";
            
            Debug::LogCLI("Query: \n\n" . $query);
                        
            $users = $this->db->fetchAll($query); 
            
            Debug::LogCLI("Fetched " . count($users) . " recipients from the database"); 
            
            $this->recipients = $users;
            
            return $this;
            
        }
        
        /**
         * Site content other than news that we could feature in this newsletter
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function getOtherContent() {
            
            $query = "SELECT CONCAT('http://railpage.com.au/locations/', LOWER(country), '/', LOWER(region_slug), '/', slug) AS url, name AS title, `desc` AS text, 'Locations' AS subtitle, `lat`, `long` AS lon FROM location WHERE date_added >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 WEEK)) LIMIT 0, 2";
            
            $result = $this->db->fetchAll($query); 
            
            foreach ($result as $key => $row) {
                if (!empty($row['lat']) && !empty($row['lon'])) {
                    $row['featuredimage'] = MapImage::Image($row['lat'], $row['lon']);
                    $row['url'] = NewsletterUtility::CreateUTMParametersForLink($this->Newsletter, $row['url']);
                }
                
                $result[$key] = $row;
            }
            
            // Get the photo of the week
            $PhotoOfTheWeek = new PhotoOfTheWeek;
            $photo = $PhotoOfTheWeek->getImageOfTheWeek(); 
            
            if (!empty($photo)) {
                $result[] = [
                    "url" => NewsletterUtility::CreateUTMParametersForLink($this->Newsletter, $photo['url']['canonical']),
                    "title" => "Photo of the week",
                    "subtitle" => sprintf("%s by %s", $photo['title'], $photo['meta']['author']['realname']),
                    "text" => "Each week our site staff scour the photos submitted to Railpage, and showcase the best as Photo of the Week. We have thousands of photos on Railpage - dive in and take a look!",
                    "featuredimage" => $photo['meta']['sizes']['small']['source'],
                    "link_text" => "View photo",
                ];
            }
            
            $this->prependedContent = $result;
            $this->num_items += count($this->prependedContent); 
            
            return $this;
            
        }
        
        /**
         * Get and personalise the content for this newsletter
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function personaliseContent() {
            
            $replacements = array(); 
            
            Debug::LogCLI("Looping through " . count($this->recipients) . " users and preparing email decoration"); 
            
            $this->user_ids = array(); 
            
            $counter = 0; 
            
            /**
             * Loop through our list of users and start to curate the contents
             */
            
            foreach ($this->recipients as $row) {
                
                // Flag this user ID so that we can update the "last sent" timestamp later
                $user_ids[] = $row['user_id']; 
                
                // Sanity check : validate the email address first
                if (!filter_var($row['user_email'], FILTER_VALIDATE_EMAIL)) {
                    Debug::LogCLI("Skipping user ID " . $row['user_id'] . " - \"" . $row['user_email'] . "\" is not a valid email address");
                    continue;
                }
                
                // Add the recipient
                $this->Notification->addRecipient($row['user_id'], $row['username'], $row['user_email']);
                
                // Assign some decoration
                $replacements[$row['user_email']] = array(
                    "##username##" => $row['username'],
                    "##email##" => $row['user_email'],
                    "##email_encoded##" => urlencode($row['user_email']),
                    "##unsubscribe##" => sprintf("http://railpage.com.au/unsubscribe?email=%s&newsletter=weekly", urlencode($row['user_email']))
                );
                
                /**
                 * Get the custom news feed articles
                 */
                
                Debug::LogCLI("Preparing personalised news for user ID " . $row['user_id']); 
                
                // Try and create the user object. If it bombs out, we need to know about it but let the newsletter continue
                try {
                    $User = UserFactory::CreateUser($row['user_id']); 
                } catch (Exception $e) {
                    Debug::LogCLI("Skipped user due to exception: " . $e->getMessage());
                    continue;
                }
                
                // Create the custom news feed object
                $Feed = new Feed;
                $Feed->setUser($User); 
                
                $articles = $Feed->addFilter(Feed::FILTER_UNREAD)->addFilter(Feed::FILTER_LAST_30_DAYS)->findArticles(0, 10, "story_hits");
                
                // If the number of personalised articles is less than ten, drop the filter and simply find ten recent and unread articles
                if (count($articles) < 10) {
                    
                    Debug::LogCLI("Found " . count($articles) . " articles for user ID " . $User->id . " - dropping keyword and topic filter from feed"); 
                    
                    $Feed->filter_words = null;
                    $Feed->filter_topics = null;
                    $articles = $Feed->findArticles(0, 10, "story_hits");
                }
                
                // If we have less than six articles skip this user altogether. 
                if (count($articles) < 6) {
                    
                    Debug::LogCLI("Found " . count($articles) . " articles for user ID " . $User->id . " - skipping"); 
                    
                    continue;
                }
                
                Debug::LogCLI("Proceeding with newsletter for user ID " . $User->id);
                
                // Loop through each article and normalise the content
                foreach ($articles as $id => $article) {
                    
                    $article['sid'] = $article['story_id'];
                    $article['catid'] = $article['topic_id'];
                    $article['hometext'] = preg_replace("@(\[b\]|\[\/b\])@", "", $article['story_blurb']);
                    $article['informant'] = $article['username'];
                    $article['informant_id'] = $article['user_id'];
                    $article['ForumThreadId'] = $article['forum_topic_id'];
                    $article['topictext'] = ContentUtility::FormatTitle($article['topic_title']);
                    $article['topic'] = $article['topic_id'];
                    $article['featured_image'] = ImageCache::cache($article['story_image']);
                    $article['title'] = $article['story_title'];
                    $article['url'] = NewsletterUtility::CreateUTMParametersForLink($this->Newsletter, $article['url']);
                    
                    $articles[$id] = $article;
                }
                
                $articles = array_values($articles); 
                
                if (!isset($start)) {
                    $start = 0;
                }
                
                // Loop through the prepended content and assign it to the blocks
                foreach ($this->prependedContent as $i => $block) {
                        
                    $tmp = [
                        "##block" . $i . ".subtitle##" => $block['title'],
                        "##block" . $i . ".featuredimage##" => $block['featuredimage'],
                        "##block" . $i . ".text##" => strip_tags(wpautop(process_bbcode($block['text'])), "<br><br /><p>"),
                        "##block" . $i . ".link##" => (strpos($block['url'], "http") === false) ? "http://www.railpage.com.au" . $block['url'] : $block['url'],
                        "##block" . $i . ".alt_title##" => $block['subtitle'],
                        "##block" . $i . ".link_text##" => isset($block['link_text']) && !empty($block['link_text']) ? $block['link_text'] : "Continue reading",
                    ];
                    
                    $replacements[$row['user_email']] = array_merge($replacements[$row['user_email']], $tmp);
                    
                }
                
                // Loop through our content and assign to content blocks
                for ($i = count($this->prependedContent) + $start; $i < $start + $this->num_items; $i++) {
                        
                    $Date = new DateTime($articles[$i]['story_time']);
                        
                    $tmp = [
                        "##block" . $i . ".subtitle##" => $articles[$i]['story_title'],
                        "##block" . $i . ".featuredimage##" => $articles[$i]['story_image'],
                        "##block" . $i . ".text##" => strip_tags(wpautop(process_bbcode($articles[$i]['story_lead'])), "<br><br /><p>"),
                        "##block" . $i . ".link##" => (strpos($articles[$i]['url'], "http") === false) ? "http://www.railpage.com.au" . $articles[$i]['url'] : $articles[$i]['url'],
                        "##block" . $i . ".alt_title##" => sprintf("Published %s", $Date->format("F j, Y, g:i a")),
                        "##block" . $i . ".link_text##" => "Continue reading",
                    ];
                    
                    $replacements[$row['user_email']] = array_merge($replacements[$row['user_email']], $tmp);
                }
                
                Debug::LogCLI("Completed personalisation of newsletter for user ID " . $User->id); 
                
                // Increment our personalised newsletter counter
                $counter++;
                
                /**
                 * Break after 150 recipients. Don't want to be flagged as a spammer, or overload the MTA
                 */
                
                if ($counter == 150) {
                    break;
                }
                
            }
            
            $this->replacements = $replacements;
            
            return $this;
            
        }
        
        /**
         * Queue the newsletter for dispatch
         * @since Version 3.10.0
         * @return \Railpage\Newsletters\Weekly
         */
        
        private function queue() {
            
            $this->Notification->subject = "[News] " . $this->Newsletter->subject; 
            $this->Notification->body = $this->html;
            $this->Notification->meta['decoration'] = $this->replacements;
            
            $this->Notification->addHeader("List-Unsubscribe", "<http://railpage.com.au/unsubscribe?email=##email_encoded##&newsletter=weekly>");
            
            Debug::LogCLI("Queueing notification for dispatch"); 
            $this->Notification->commit();
            
            $this->Newsletter->status = Newsletter::STATUS_SENT;
            
            Debug::LogCLI("Commiting the newsletter to the database"); 
            $this->Newsletter->commit(); 
            
            /**
             * Update the last sent timestamp
             */
            
            foreach ($this->user_ids as $user_id) {
                $query = "INSERT INTO nuke_users_flags (user_id, newsletter_weekly_last) VALUES(" . $user_id . ", NOW()) ON DUPLICATE KEY UPDATE newsletter_weekly_last = NOW()";
                $ZendDB->query($query); 
            }
            
            return $this;
            
        }
        
        
    }