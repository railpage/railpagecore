<?php
	/**
	 * Utility class for user functions
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users;
	
	use stdClass;
	use Exception;
	use DateTime;
	use DateTimeZone;
	
	use Railpage\AppCore;
	use Railpage\BanControl\BanControl;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Index;
	
	class Utility {
		
		/**
		 * Extract a user's timeline 
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @param \DateTime|int $DateFrom
		 * @param \DateTime|int $DateTo
		 * @return array
		 */
		
		static public function GenerateTimeline($User, $DateFrom, $DateTo) {
			
			$db = (new AppCore)->getDatabaseConnection();
			
			if (filter_var($DateFrom, FILTER_VALIDATE_INT)) {
				$page = $DateFrom;
			}
			
			if (filter_var($DateTo, FILTER_VALIDATE_INT)) {
				$items_per_page = $DateTo;
			}
			
			/**
			 * Filter out forums this user doesn't have access to
			 */
			
			if (isset($User->Guest) && $User->Guest instanceof User) {
				$forum_post_filter_mckey = sprintf("forum.post.filter.user:%d", $User->Guest->id);
				
				if (!$forum_post_filter = $User->Memcached->fetch($forum_post_filter_mckey)) {
					$Forums = new Forums;
					$Index = new Index;
					
					$acl = $Forums->setUser($User->Guest)->getACL();
					
					$allowed_forums = array(); 
					
					foreach ($Index->forums() as $row) {
						$Forum = new Forum($row['forum_id']);
						
						if ($Forum->setUser($User->Guest)->isAllowed(Forums::AUTH_READ)) {
							$allowed_forums[] = $Forum->id;
						}
					}
					
					$forum_filter = "AND p.forum_id IN (" . implode(",", $allowed_forums) . ")";
					
					$forum_post_filter = "AND id NOT IN (SELECT l.id AS log_id
						FROM log_general AS l 
						LEFT JOIN nuke_bbposts AS p ON p.post_id = l.value
						WHERE l.key = 'post_id' 
						" . $forum_filter . ")";
					
					$User->Memcached->save($forum_post_filter_mckey, $forum_post_filter, strtotime("+1 week"));
				}
			} else {
				$forum_post_filter = "";
			}
			
			if (strpos($forum_post_filter, "p.forum_id IN ())")) {
				$forum_post_filter = "";
			}
			
			
			if ($page && $items_per_page) {
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " ORDER BY timestamp DESC LIMIT ?, ?";
				$offset = ($page - 1) * $items_per_page; 
				
				$params = array(
					$User->id, 
					$offset, 
					$items_per_page
				);
			} else {
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " AND timestamp >= ? AND timestamp <= ? ORDER BY timestamp DESC";
				
				$params = array(
					$User->id, 
					$DateFrom->format("Y-m-d H:i:s"), 
					$DateTo->format("Y-m-d H:i:s")
				);
			}
			
			$timeline = array(
				"total" => 0
			); 
			
			if ($result = $db->fetchAll($query, $params)) {
				if ($page && $items_per_page) {
					$timeline['page'] = $page;
					$timeline['perpage'] = $items_per_page;
				} else {
					$timeline['start'] = $DateFrom->format("Y-m-d H:i:s");
					$timeline['end'] = $DateTo->format("Y-m-d H:i:s");
				}
				
				$timeline['total'] = $db->fetchOne("SELECT FOUND_ROWS() AS total"); 
				
				foreach ($result as $row) {
					$row['args'] = json_decode($row['args'], true);
					$row['timestamp'] = new DateTime($row['timestamp']); 
					
					$timeline['timeline'][$row['id']] = $row;
				}
			}
			
			/**
			 * Process the timeline data
			 */
			
			if (isset($timeline['timeline'])) {
				foreach ($timeline['timeline'] as $key => $row) {
					// Set their timezone
					$row['timestamp']->setTimezone(new DateTimeZone($User->timezone));
					
					$relative_cutoff = new DateTime("12 hours ago", new DateTimeZone($User->timezone));
					
					$moments_ago = new DateTime("60 seconds ago", new DateTimeZone($User->timezone)); 
					$minutes_ago = new DateTime("60 minutes ago", new DateTimeZone($User->timezone));
					
					if (stristr($row['title'], "loco") && empty($row['module'])) {
						$row['module'] = "locos";
					}
					
					/**
					 * Check if the meta data array exists
					 */
					
					if (!isset($row['meta'])) {
						if (!isset($row['meta'])) {
							$row['meta'] = array(
								"id" => NULL,
								"namespace" => NULL
							); 
						}
					}
					
					/**
					 * Determine the action taken and on what kind of object
					 */
					
					$row['event']['action'] = ""; $row['event']['article'] = ""; $row['event']['object'] = ""; $row['event']['preposition'] = ""; 
					
					$row['title'] = str_ireplace(array("loco link created"), array("linked a locomotive"), $row['title']);
					
					if (preg_match("@(favourited|suggested|ignored|accepted|closed|commented|removed|re-ordered|edited|edit|added|add|sorted|sort|deleted|delete|rejected|reject|tagged|tag|changed|modified|linked|created|create)@Di", $row['title'], $matches)) {
						$row['event']['action'] = strtolower($matches[1]);
					}
					
					if (preg_match("@(idea|suggestion|correction|sighting|date|post|thread|digital asset|loco photo|loco class|loco|class|location|grouping|owners|owner|operators|operator|article|story|topic|railcam photo|photo|railcam|download|event|calendar|image)@Di", $row['title'], $matches)) {
						$row['event']['object'] = strtolower($matches[1]);
					}
					
					if ($row['title'] == "Loco link removed") {
						$row['event']['action'] = "removed";
						$row['event']['object'] = "linked locomotive";
						$row['event']['article'] = "a";
						$row['event']['preposition'] = "from";
					}
					
					/** 
					 * Preposition of this action
					 */
					
					if (preg_match("@(added|add|linked)@Di", $row['event']['action']) && preg_match("@(locos)@Di", $row['module'])) {
						$row['event']['preposition'] = "to";
					}
					
					if (preg_match("@(removed)@Di", $row['title'])) {
						$row['event']['preposition'] = "from";
					}
					
					if (preg_match("@(correction|re-ordered|sorted|sort|tagged|tag|changed|modified)@Di", $row['title'])) {
						$row['event']['preposition'] = "of";
					}
					
					if (preg_match("@(added|add|edited|edit|deleted|delete|rejected|reject|created|create)@Di", $row['title']) && preg_match("@(forums|news)@Di", $row['module'])) {
						$row['event']['preposition'] = "in";
					}
					
					if (preg_match("@(unlinked)@Di", $row['title']) && preg_match("@(locos)@Di", $row['module'])) {
						$row['event']['preposition'] = "from";
					}

					
					/**
					 * Article of this action
					 */
					
					if ($row['event']['preposition'] == "of") {
						$row['event']['article'] = "the";
					}
					
					if ($row['event']['preposition'] == "in") {
						$row['event']['article'] = "a";
					}
					
					if (preg_match("@(date)@Di", $row['event']['object'], $matches)) {
						if (preg_match("@(edited)@Di", $row['event']['action'], $matches)) {
							$row['event']['preposition'] = "for";
						}
					}
					
					if (preg_match("@(correction|date|post|thread|digital asset|loco|class|location|story|topic|railcam photo|photo|railcam|download)@Di", $row['event']['object'], $matches)) {
						if (!($matches[1] == "loco" && $row['event']['action'] == "edited")) {
							$row['event']['article'] = "a";
						}
					}
					
					if (preg_match("@(cover photo)@Di", $row['event']['object'], $matches)) {
						$row['event']['article'] = "the";
					}
					
					if (preg_match("@(operator)@Di", $row['event']['object'], $matches)) {
						$row['event']['article'] = "an";
					}
					
					if ($row['event']['action'] == "re-ordered" && preg_match("@(owners|owner|operators|operator)@Di", $row['title'], $matches)) {
						$row['event']['object'] = "owners/operators";
						$row['event']['article'] = "the";
					}
					
					/**
					 * Alter the object if needed
					 */
					
					if ($row['module'] == "locos" && $row['event']['object'] == "class") {
						$row['event']['object'] = "locomotive class";
						
						if ($row['event']['action'] == "modified") {
							unset($row['event']['preposition']);
							unset($row['event']['article']);
							unset($row['event']['object']);
						}
					}
					
					if (isset($row['event']['object']) && $row['module'] == "locos" && $row['event']['object'] == "loco photo") {
						$row['event']['object'] = "cover photo";
					}
					
					/**
					 * Set the module namespace
					 */
					
					$Module = new \Railpage\Module($row['module']);
					$row['meta']['namespace'] = $Module->namespace;
					
					/**
					 * Attempt to create a link to this object or action if none exists
					 */
					
					if (!isset($row['meta']['url'])) {
						
						switch ($row['key']) {
							
							/**
							 * Forum post
							 */
							
							case "post_id" : 
								
								$row['meta']['url'] = "/f-p" . $row['value'] . ".htm#" . $row['value'];
								
							break;
							
							/**
							 * Locomotive
							 */
							
							case "loco_id" : 
								
								$Loco = new \Railpage\Locos\Locomotive($row['value']); 
								$row['meta']['url'] = $Loco->url;
							
							break;
							
							/**
							 * Locomotive class
							 */
							
							case "class_id" : 
								
								$LocoClass = new \Railpage\Locos\LocoClass($row['value']); 
								$row['meta']['url'] = $LocoClass->url;
							
							break;
						}
						
					}
					
					/**
					 * Attempt to create a meta object title for this object or action if none exists
					 */
					
					if (!isset($row['meta']['object']['title'])) {
						
						switch ($row['key']) {
							
							/**
							 * Forum post
							 */
							
							case "post_id" : 
								
								$Post = new \Railpage\Forums\Post($row['value']);
								$row['meta']['object']['title'] = $Post->thread->title;
								
							break;
							
							/**
							 * Locomotive
							 */
							
							case "loco_id" : 
								
								$Loco = new \Railpage\Locos\Locomotive($row['value']); 
								
								$row['meta']['namespace'] = $Loco->namespace;
								$row['meta']['id'] = $Loco->id;
								
								if ($row['event']['action'] == "added" && $row['event']['object'] == "loco") {
									$row['meta']['object']['title'] = $Loco->class->name;
								} else {
									$row['meta']['object']['title'] = $Loco->number;
									$row['meta']['object']['subtitle'] = $Loco->class->name;
								}
							
							break;
							
							/**
							 * Locomotive class
							 */
							
							case "class_id" : 
								
								$LocoClass = new \Railpage\Locos\LocoClass($row['value']); 
								$row['meta']['object']['title'] = $LocoClass->name;
								
								$row['meta']['namespace'] = $LocoClass->namespace;
								$row['meta']['id'] = $LocoClass->id;
							
							break;
							
							/**
							 * Location
							 */
							
							case "id" :
								
								if ($row['module'] == "locations") {
									$Location = new \Railpage\Locations\Location($row['value']);
									$row['meta']['object']['title'] = $Location->name;
									$row['meta']['url'] = $Location->url;
									unset($row['event']['article']);
									unset($row['event']['object']);
									unset($row['event']['preposition']);
								}
								
							break;
							
							/**
							 * Photo
							 */
							
							case "photo_id" : 
								
								$row['meta']['object']['title'] = "photo";
								$row['meta']['url'] = "/flickr/" . $row['value'];
								
								if ($row['event']['action'] == "commented") {
									$row['event']['object'] = "";
									$row['event']['article'] = "on";
									$row['event']['preposition'] = "a";
								}
							
							break;
							
							/**
							 * Sighting
							 */
							
							case "sighting_id" : 
								
								if (empty($row['module']) || !isset($row['module'])) {
									$row['module'] = "sightings";
								}
								
								$row['event']['preposition'] = "of";
								$row['event']['article'] = "a";
								
								if (count($row['args']['locos']) === 1) {
									$row['meta']['object']['title'] = $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
								} elseif (count($row['args']['locos']) === 2) {
									$row['meta']['object']['title'] = $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
									next($row['args']['locos']);
									
									$row['meta']['object']['title'] .= " and " . $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
								} else {
									$locos = array();
									foreach ($row['args']['locos'] as $loco) {
										$locos[] = $loco['Locomotive'];
									}
									
									$last = array_pop($locos);
									
									$row['meta']['object']['title'] = implode(", ", $locos) . " and " . $last;
								}
							
							break;
							
							/**
							 * Idea
							 */
							
							case "idea_id" : 
							
								$Idea = new \Railpage\Ideas\Idea($row['value']);
								$row['meta']['object']['title'] = $Idea->title;
								$row['meta']['url'] = $Idea->url;
								$row['glyphicon'] = "thumbs-up";
								$row['event']['object'] = "idea:";
								$row['event']['article'] = "an";
							
							break;
						}
						
					}
					
					/**
					 * Compact it all together and create a succinct message
					 */
					
					foreach ($row['event'] as $k => $v) {
						if (empty($v)) {
							unset($row['event'][$k]);
						}
					}
					
					$row['action'] = implode(" ", $row['event']);
					
					
					if ($row['timestamp'] > $moments_ago) {
						$row['timestamp_nice'] = "moments ago"; 
					} elseif ($row['timestamp'] > $minutes_ago) {
						$diff = $row['timestamp']->diff($minutes_ago);
						$row['timestamp_nice'] = $diff->format("%s minutes ago");
					} elseif ($row['timestamp'] > $relative_cutoff) {
						$diff = $row['timestamp']->diff($relative_cutoff);
						$row['timestamp_nice'] = $diff->format("About %s hours ago");
					} else {
						$row['timestamp_nice'] = $row['timestamp']->format("d/m/Y H:i"); 
					}
					
					$row['timestamp_nice'] = relative_date($row['timestamp']->getTimestamp());
					
					/**
					 * Determine the icon
					 */
					
					if (!isset($row['glyphicon'])) {
						$row['glyphicon'] = "";
					}
					
					if (isset($row['event']['object'])) {
						switch (strtolower($row['event']['object'])) {
							case "photo" :
								$row['glyphicon'] = "picture";
								break;
								
							case "cover photo" :
								$row['glyphicon'] = "picture";
								break;
						}
					}
					
					switch (strtolower($row['event']['action'])) {
						case "edited" : 
							$row['glyphicon'] = "pencil";
							break;
						
						case "modified" : 
							$row['glyphicon'] = "pencil";
							break;
						
						case "added" : 
							$row['glyphicon'] = "plus";
							break;
						
						case "created" : 
							$row['glyphicon'] = "plus";
							break;
							
						case "tagged" : 
							$row['glyphicon'] = "tag";
							break;
							
						case "linked" : 
							$row['glyphicon'] = "link";
							break;
							
						case "re-ordered" : 
							$row['glyphicon'] = "random";
							break;
							
						case "removed" : 
							$row['glyphicon'] = "minus";
							break;
							
						case "commented" : 
							$row['glyphicon'] = "comment";
							break;
						
					}
					
					if (isset($row['event']['object'])) {
						switch (strtolower($row['event']['object'])) {
							case "sighting" :
								$row['glyphicon'] = "eye-open";
								break;
						}
					}
					
					$timeline['timeline'][$key] = $row;
				}
			}
			
			return $timeline;
			
		}
	}