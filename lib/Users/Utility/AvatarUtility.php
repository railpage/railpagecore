<?php
	/**
	 * User avatar utility class
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users\Utility;
	
	use Exception;
	use DateTime;
	use Railpage\ContentUtility;
	use Railpage\Users\User;
	use Railpage\AppCore;
	use Railpage\Debug;
	
	class AvatarUtility {
		
		/**
		 * Default avatar
		 * @since Version 3.9.1
		 * @const string DEFAULT
		 */
		
		const DEFAULT_AVATAR = "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
		
		/**
		 * Format an avatar
		 * @since Version 3.9.1
		 * @return string
		 */

		public static function Format($user_avatar = false, $width = 100, $height = 100) {
			if (!$user_avatar) {
				return false;
			}
			
			
			$Memcached = AppCore::getMemcached(); 
			
			$timer = Debug::getTimer(); 
			
			if ($user_avatar == "http://www.railpage.com.au/modules/Forums/images/avatars/https://static.railpage.com.au/image_resize") {
				$user_avatar = self::DEFAULT_AVATAR;
			}
			
			if (empty($user_avatar) || stristr($user_avatar, "blank.gif") || stristr($user_avatar, "blank.png")) {
				$user_avatar = self::DEFAULT_AVATAR;
				return $user_avatar;
			}
			
			$parts = parse_url($user_avatar);
			
			if (isset($parts['host']) && $parts['host'] == "static.railpage.com.au" && isset($parts['query'])) {
				parse_str($parts['query'], $query);
			
				if (isset($query['w']) && isset($query['h']) && isset($query['image'])) {
					if ($query['w'] == $width && $query['h'] == $height) {
						return $user_avatar;
					}
					
					return sprintf("http://static.railpage.com.au/image_resize.php?w=%d&h=%d&image=%s", $width, $height, $query['image']); 
				}
			}
			
			if (isset($parts['host']) && $parts['host'] == "www.gravatar.com" && isset($parts['query'])) {
				parse_str($parts['query'], $query);
				
				$query['s'] = $width;
				$bits = array(); 
				foreach ($query as $key => $val) {
					$bits[] = sprintf("%s=%s", $key, $val); 
				}
				
				return sprintf("%s://%s%s?%s", $parts['scheme'], $parts['host'], $parts['path'], implode("&", $bits));
			}
			
			$mckey = sprintf("railpage.user:avatar=%s;width=%s;height=%s", $user_avatar, $width, $height);
			
			/**
			 * Check if this shit is in Memcache first
			 */
			
			if ($result = $Memcached->fetch($mckey)) {
				return $result;
			}
			
			/**
			 * It's not in Memcached, so let's process and cache it
			 */
			
			parse_str(parse_url($user_avatar, PHP_URL_QUERY), $args);
			
			if (isset($args['base64_args'])) {
				if (!@unserialize(base64_decode($args['base64_args']))) {
					// Malformed string!
					
					$user_avatar = self::DEFAULT_AVATAR;
				} else {
					// Do other stuff...
					
					$base64 = unserialize(base64_decode($args['base64_args']));
				}
			}
			
			if (preg_match("@modules/Forums/images/avatars/(http\:\/\/|https\:\/\/)@", $user_avatar)) {
				$user_avatar = self::DEFAULT_AVATAR;
			}
			
			if (!preg_match("@(http\:\/\/|https\:\/\/)@", $user_avatar)) {
				$user_avatar = "http://static.railpage.com.au/modules/Forums/images/avatars/".$user_avatar;
			}
			
			if (!ContentUtility::url_exists($user_avatar)) {
				$user_avatar = self::DEFAULT_AVATAR;
			}
			
			if ($width && !$height) {
				$height = $width;
			}
			
			// Is this an anigif?
			if (substr($user_avatar, -4, 4) == ".gif") {
				// Fetch the dimensions
				
				$mckey = "railpage:avatar.size=" . md5($user_avatar); 
				
				if ($dimensions = $Memcached->fetch($mckey)) {
					// Do nothing
				} else {
					$dimensions = @getimagesize($user_avatar); 
					
					$Memcached->save($mckey, $dimensions);
				}
				
				if (isset($dimensions['mime']) && $dimensions['mime'] == "image/gif") {
					// Great, it's a gif
					if ($width && $height) {
						if ($dimensions[0] <= $width && $dimensions[1] <= $height) {
							// It fits within the width and height - return it as-is
							return $user_avatar;
						}
					}
				}
			}
			
			// Assume that all avatars created on dev.railpage.com.au are shit and should be re-directed to static.railpage.com.au
			$user_avatar = str_replace("dev.railpage.com.au", "static.railpage.com.au", $user_avatar);
			
			if ($width && $height) {
				$args['width'] 	= $width;
				$args['height']	= $height;
				$args['url']	= $user_avatar;
				
				if (empty($user_avatar)) {
					$args['url'] = self::DEFAULT_AVATAR; 
				}
				
				$user_avatar = "https://static.railpage.com.au/image_resize.php?base64_args=".base64_encode(serialize($args)); 
				
				if ($width == $height) {
					$user_avatar .= "&square=true";
				}
			}
			
			$Memcached->save($mckey, $user_avatar, strtotime("+1 month"));
			
			Debug::logEvent(__METHOD__, $timer) ;
			
			return $user_avatar;
		}
	}
