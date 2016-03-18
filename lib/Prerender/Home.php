<?php

/**
 * Pre-render the home page
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Prerender;

use Railpage\News\Base;
use Railpage\News\Feed;
use Railpage\News\News;
use Railpage\News\Article;
use Railpage\Jobs\Jobs;
use Railpage\Jobs\Job;
use Railpage\Railcams\Camera;
use Railpage\ContentUtility;
use Railpage\Chronicle\Chronicle;
use Railpage\Formatting\ColourUtility;
use Railpage\Images\Utility\Recent as RecentImages;
use Railpage\Images\Images;
use Railpage\Images\Image;
use Railpage\Images\ImageCache;
use Railpage\Events\Events;
use Railpage\Events\Event;
use Railpage\Events\EventDate;
use Railpage\Events\Factory as EventsFactory;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Users\User;
use Exception;
use DateTime;
use InvalidArgumentException;


class Home extends Prerender {
    
    /**
     * Render the page 
     * @since Version 3.10.0
     * @return string
     */
    
    public function render() {
        
        if (!$this->userObject instanceof User) {
            throw new InvalidArgumentException("No valid user object has been provided"); 
        }
        
        #$this->smarty->clearCache($this->template, $this->unique); 
        
	    if ($this->smarty->isCached($this->template, $this->unique)) {
            Debug::LogCLI("!! Template file " . $this->template . " is already cached for unique ID " . $this->unique); 
            return $this->smarty->fetch($this->template, $this->unique); 
        }
        
        Debug::LogCLI("Template file " . $this->template . " is NOT cached for unique ID \"" . $this->unique . "\""); 
        
        /**
         * Get user alerts
         */
        
        if (!$this->userObject->guest) {
            global $acl; 
            
            $alerts = $this->userObject->getAlerts($acl); 
            $this->smarty->Assign("alerts", $alerts, true);
        }
        
        /**
         * Get the latest jobs
         */
        
        $newjobs = array(); 
        
        foreach ((new Jobs)->yieldNewJobs(5) as $Job) {
            $newjobs[] = $Job->getArray();
        }
        
        $this->smarty->Assign("jobs", $newjobs, true);
	
		/**
		 * Upcoming events
		 */
		
		$Memcached = AppCore::GetMemcached(); 
		$cachekey = "railpage.home.upcomingevents"; 
        $upcoming = [];
		
		if (!$upcoming = $Memcached->fetch($cachekey)) {
			
			$Events = new Events;
			
			$upcoming = [];
			foreach ($Events->getUpcomingEvents(5) as $row) {
				//$Event = EventsFactory::CreateEvent($row['event_id']); 
				$EventDate = new EventDate($row['id']); 
				$data = $EventDate->getArray(); 
				
				$upcoming[] = $data;
			}
			
			$Memcached->save("railpage.home.upcomingevents", $upcoming, strtotime("+5 minutes")); 
			
		}
		
		$this->smarty->Assign("upcomingevents", $upcoming); 
		
		/**
		 * New photos
		 */
		
		$this->smarty->Assign("newphotos", RecentImages::getNewest(5)); 
		
		/**
		 * Chronicle
		 */
		
		$Chronicle = new Chronicle;
		$this->smarty->Assign("chronicle", $Chronicle->getEntriesForToday(10));
	
		/**
		 * Get the latest railcam photo
		 */
	
		$Camera = new Camera(1);
		
		$Photo = $Camera->getLatest(false); 
		
		$railcam = $Photo->getArray();
        
        $railcam['sizes']['small']['source'] = ImageCache::cache($railcam['sizes']['small']['source']);
		
		$this->smarty->Assign("railcam", $railcam); 
		$this->smarty->Assign("railcam_updated", ContentUtility::relativeTime($railcam['dates']['taken'])); 
		
		/**
		 * First check if this user has a personalised news feed
		 */
		
		if (filter_var($this->userObject->id, FILTER_VALIDATE_INT) && $this->userObject->id > 0) {
			$Feed = new Feed;
			$Feed->setUser($this->userObject)->getFilters(); 
			
			if (count($Feed->filter_words) || count($Feed->filter_topics)) {
				$latest = $Feed->findArticles(0, 20);
				
				foreach ($latest as $id => $article) {
					$article['sid'] = $article['story_id'];
					$article['catid'] = $article['topic_id'];
					$article['hometext'] = preg_replace("@(\[b\]|\[\/b\])@", "", $article['story_blurb']);
					$article['informant'] = $article['username'];
					$article['informant_id'] = $article['user_id'];
					$article['ForumThreadId'] = $article['forum_topic_id'];
					$article['topictext'] = $article['topic_title'];
					$article['topic'] = $article['topic_id'];
					$article['featured_image'] = $article['story_image'];
					$article['title'] = $article['story_title'];
					$article['time_relative'] = time2str($article['story_time_unix']);
					
					$latest[$id] = $article;
				}
			}
		}
		
		$this->smarty->Assign("personalfeed", isset($latest));
		
		/**
		 * No personal news feed - go ahead as normal
		 */
		
		if (!isset($latest)) {
			
			/**
			 * Instantiate the base News module
			 */
			 
			$News = new Base; 
			
			/**
			 * Get the latest 15 news articles
			 */
			
			$latest = $News->latest(20);
		}
		
		/**
		 * Format titles and tags for the latest news articles
		 */
		
		foreach ($latest as $id => $data) {
			
			/**
			 * Load the JSON for this article
			 */
			
			if (!isset($data['sid'])) {
				$data['sid'] = $data['story_id'];
			}
			
			$json = json_decode(News::getArticleJSON($data['sid']), true);
			
			$latest[$id]['hometext'] = isset($json['article']['blub']) ? wpautop(process_bbcode($json['article']['blub'])) : wpautop(process_bbcode($json['article']['blurb']));
			$latest[$id]['hometext'] = strip_tags($latest[$id]['hometext'], "<a><p><img><br><br /><strong><em>");
			$latest[$id]['title'] = format_topictitle($data['title']);
			$latest[$id]['topic'] = $json['article']['topic'];
			$latest[$id]['topic_highlight'] = ColourUtility::String2Hex($latest[$id]['topic_title']);
			$latest[$id]['url'] = $json['article']['url'];
			$latest[$id]['author'] = $json['article']['author'];
			$latest[$id]['staff'] = $json['article']['staff'];
            
            if (!empty($latest[$id]['featured_image'])) {
                $latest[$id]['featured_image'] = ImageCache::cache($latest[$id]['featured_image']); 
            }
			
			// Get the first paragraph from the home text
			preg_match("/<p>(.*)<\/p>/", $latest[$id]['hometext'], $matches);
			$latest[$id]['hometext'] = strip_tags($matches[1]);
			
			if (empty($json['article']['body']) && !empty($json['article']['source'])) {
				$latest[$id]['url'] = $json['article']['source'];
			}
		
			/**
			 * Pre-rendering
			 */
			
			$this->smarty->addHeadTag(sprintf("<link rel='prerender' href='%s'>", $json['article']['url']['url']));
			
		}
		
		/**
		 * Slice the first news article off
		 */
		
		$newsLatest = array_shift($latest);
		
		/**
		 * Send them to Smarty
		 */
		
		$this->smarty->assign("newsLatest", $newsLatest);
		$this->smarty->assign("news", $latest);
        $this->smarty->assign("pagecontrols", '<p style="background: #333; background: rgba(0, 0, 0, 0.6);margin: -20px;padding: 10px;margin-top: 20px; text-align: center;">Wasting time and bandwidth since 1992</p>');
		
		if ($this->params['handheld']) {
			$this->smarty->assign("pagecontrols", '<p style="background: #333; background: rgba(0, 0, 0, 0.6);margin: 0px -20px;padding: 0px;margin-top: 40px; text-align: center;font-size:1em;">Wasting time and bandwidth since 1992</p>');
		}
        
        return $this->smarty->fetch($this->template, $this->unique); 
        
    }
    
}