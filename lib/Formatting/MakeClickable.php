<?php

/**
 * Make URL-like text blocks clickable
 * @since Version 3.10.0
 * @package Railpage
 * @author Wordpress, Michael Greenhill
 */

namespace Railpage\Formatting; 

use Railpage\Debug;
use Exception;
use InvalidArgumentException;

class MakeClickable {
	
	/**
	 * Convert plaintext URI to HTML links.
	 *
	 * Converts URI, www and ftp, and email addresses. Finishes by fixing links
	 * within links.
	 *
	 * @since 0.71
	 *
	 * @param string $text Content to convert URIs.
	 * @return string Content with converted URIs.
	 */
	
	public static function Process( $text ) {
        
        $timer = Debug::GetTimer(); 
        
		$r = '';
		$textarr = preg_split( '/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // split out HTML tags
		foreach ( $textarr as $piece ) {
			if ( empty( $piece ) || ( $piece[0] == '<' && ! preg_match('|^<\s*[\w]{1,20}+://|', $piece) ) ) {
				$r .= $piece;
				continue;
			}
	
			// Long strings might contain expensive edge cases ...
			if ( 10000 < strlen( $piece ) ) {
				// ... break it up
				foreach ( self::_split_str_by_whitespace( $piece, 2100 ) as $chunk ) { // 2100: Extra room for scheme and leading and trailing paretheses
					if ( 2101 < strlen( $chunk ) ) {
						$r .= $chunk; // Too big, no whitespace: bail.
					} else {
						$r .= make_clickable( $chunk );
					}
				}
			} else {
				$ret = " $piece "; // Pad with whitespace to simplify the regexes
	
				$url_clickable = '~
					([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
					(                                                      # 2: URL
						[\\w]{1,20}+://                                # Scheme and hier-part prefix
						(?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
						[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
						(?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
							[\'.,;:!?)]                            # Punctuation URL character
							[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
						)*
					)
					(\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
				~xS'; // The regex is a non-anchored pattern and does not have a single fixed starting character.
					  // Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.
	
				$ret = preg_replace_callback( $url_clickable, 'self::_make_url_clickable_cb', $ret );
	
				$ret = preg_replace_callback( '#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', 'self::_make_web_ftp_clickable_cb', $ret );
				$ret = preg_replace_callback( '#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'self::_make_email_clickable_cb', $ret );
	
				$ret = substr( $ret, 1, -1 ); // Remove our whitespace padding.
				$r .= $ret;
			}
		}
	
		// Cleanup of accidental links within links
		$r = preg_replace( '#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', "$1$3</a>", $r );
        
        Debug::LogEvent(__METHOD__, $timer); 
        
		return $r;
	}
    
	/**
	 * Callback to convert URI match to HTML A element.
	 *
	 * This function was backported from 2.5.0 to 2.3.2. Regex callback for {@link
	 * make_clickable()}.
	 *
	 * @since 2.3.2
	 * @access private
	 *
	 * @param array $matches Single Regex Match.
	 * @return string HTML A element with URI address.
	 */
	
	public static function _make_url_clickable_cb($matches) {
		$url = $matches[2];
	
		if ( ')' == $matches[3] && strpos( $url, '(' ) ) {
			// If the trailing character is a closing parethesis, and the URL has an opening parenthesis in it, add the closing parenthesis to the URL.
			// Then we can let the parenthesis balancer do its thing below.
			$url .= $matches[3];
			$suffix = '';
		} else {
			$suffix = $matches[3];
		}
	
		// Include parentheses in the URL only if paired
		while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
			$suffix = strrchr( $url, ')' ) . $suffix;
			$url = substr( $url, 0, strrpos( $url, ')' ) );
		}
	
		$url = self::esc_url($url);
		if ( empty($url) )
			return $matches[0];
	
		return $matches[1] . "<a href=\"$url\" rel=\"nofollow\">$url</a>" . $suffix;
	}
	
	/**
	 * Callback to convert URL match to HTML A element.
	 *
	 * This function was backported from 2.5.0 to 2.3.2. Regex callback for {@link
	 * make_clickable()}.
	 *
	 * @since 2.3.2
	 * @access private
	 *
	 * @param array $matches Single Regex Match.
	 * @return string HTML A element with URL address.
	 */
	
	public static function _make_web_ftp_clickable_cb($matches) {
		$ret = '';
		$dest = $matches[2];
		$dest = 'http://' . $dest;
		$dest = self::esc_url($dest);
		if ( empty($dest) )
			return $matches[0];
	
		// removed trailing [.,;:)] from URL
		if ( in_array( substr($dest, -1), array('.', ',', ';', ':', ')') ) === true ) {
			$ret = substr($dest, -1);
			$dest = substr($dest, 0, strlen($dest)-1);
		}
		return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\">$dest</a>$ret";
	}
	
	/**
	 * Callback to convert email address match to HTML A element.
	 *
	 * This function was backported from 2.5.0 to 2.3.2. Regex callback for {@link
	 * make_clickable()}.
	 *
	 * @since 2.3.2
	 * @access private
	 *
	 * @param array $matches Single Regex Match.
	 * @return string HTML A element with email address.
	 */
	
	public static function _make_email_clickable_cb($matches) {
		$email = $matches[2] . '@' . $matches[3];
		return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
	}
	
	/**
	 * Breaks a string into chunks by splitting at whitespace characters.
	 * The length of each returned chunk is as close to the specified length goal as possible,
	 * with the caveat that each chunk includes its trailing delimiter.
	 * Chunks longer than the goal are guaranteed to not have any inner whitespace.
	 *
	 * Joining the returned chunks with empty delimiters reconstructs the input string losslessly.
	 *
	 * Input string must have no null characters (or eventual transformations on output chunks must not care about null characters)
	 *
	 * <code>
	 * _split_str_by_whitespace( "1234 67890 1234 67890a cd 1234   890 123456789 1234567890a    45678   1 3 5 7 90 ", 10 ) ==
	 * array (
	 *   0 => '1234 67890 ',  // 11 characters: Perfect split
	 *   1 => '1234 ',        //  5 characters: '1234 67890a' was too long
	 *   2 => '67890a cd ',   // 10 characters: '67890a cd 1234' was too long
	 *   3 => '1234   890 ',  // 11 characters: Perfect split
	 *   4 => '123456789 ',   // 10 characters: '123456789 1234567890a' was too long
	 *   5 => '1234567890a ', // 12 characters: Too long, but no inner whitespace on which to split
	 *   6 => '   45678   ',  // 11 characters: Perfect split
	 *   7 => '1 3 5 7 9',    //  9 characters: End of $string
	 * );
	 * </code>
	 *
	 * @since 3.4.0
	 * @access private
	 *
	 * @param string $string The string to split.
	 * @param int $goal The desired chunk length.
	 * @return array Numeric array of chunks.
	 */
	
	public static function _split_str_by_whitespace( $string, $goal ) {
		$chunks = array();
	
		$string_nullspace = strtr( $string, "\r\n\t\v\f ", "\000\000\000\000\000\000" );
	
		while ( $goal < strlen( $string_nullspace ) ) {
			$pos = strrpos( substr( $string_nullspace, 0, $goal + 1 ), "\000" );
	
			if ( false === $pos ) {
				$pos = strpos( $string_nullspace, "\000", $goal + 1 );
				if ( false === $pos ) {
					break;
				}
			}
	
			$chunks[] = substr( $string, 0, $pos + 1 );
			$string = substr( $string, $pos + 1 );
			$string_nullspace = substr( $string_nullspace, $pos + 1 );
		}
	
		if ( $string ) {
			$chunks[] = $string;
		}
	
		return $chunks;
	}
	
	/**
	 * Checks and cleans a URL.
	 *
	 * A number of characters are removed from the URL. If the URL is for displaying
	 * (the default behaviour) ampersands are also replaced. The 'clean_url' filter
	 * is applied to the returned cleaned URL.
	 *
	 * @since 2.8.0
	 * @uses wp_kses_bad_protocol() To only permit protocols in the URL set
	 *		via $protocols or the common ones set in the function.
	 *
	 * @param string $url The URL to be cleaned.
	 * @param array $protocols Optional. An array of acceptable protocols.
	 *		Defaults to 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn' if not set.
	 * @param string $_context Private. Use esc_url_raw() for database usage.
	 * @return string The cleaned $url after the 'clean_url' filter is applied.
	 */
	
	public static function esc_url( $url, $protocols = null, $_context = 'display' ) {
		$original_url = $url;
	
		if ( '' == $url )
			return $url;
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url = self::_deep_replace($strip, $url);
		$url = str_replace(';//', '://', $url);
		/* If the URL doesn't appear to contain a scheme, we
		 * presume it needs http:// appended (unless a relative
		 * link starting with /, # or ? or a php file).
		 */
		if ( strpos($url, ':') === false && ! in_array( $url[0], array( '/', '#', '?' ) ) &&
			! preg_match('/^[a-z0-9-]+?\.php/i', $url) )
			$url = 'http://' . $url;
	
		// Replace ampersands and single quotes only when displaying.
		if ( 'display' == $_context ) {
			if (function_exists("wp_kses_normalize_entities")) {
				$url = wp_kses_normalize_entities( $url );
			}
			$url = str_replace( '&amp;', '&#038;', $url );
			$url = str_replace( "'", '&#039;', $url );
		}
	
		if ( '/' === $url[0] ) {
			$good_protocol_url = $url;
		} else {
			/*
			if ( ! is_array( $protocols ) )
				$protocols = wp_allowed_protocols();
			$good_protocol_url = wp_kses_bad_protocol( $url, $protocols );
			if ( strtolower( $good_protocol_url ) != strtolower( $url ) )
				return '';*/
		}
		
		return $url;
		return apply_filters('clean_url', $good_protocol_url, $original_url, $_context);
	}
	
	/**
	 * Perform a deep string replace operation to ensure the values in $search are no longer present
	 *
	 * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
	 * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
	 * str_replace would return
	 *
	 * @since 2.8.1
	 * @access private
	 *
	 * @param string|array $search The value being searched for, otherwise known as the needle. An array may be used to designate multiple needles.
	 * @param string $subject The string being searched and replaced on, otherwise known as the haystack.
	 * @return string The string with the replaced svalues.
	 */
	
	public static function _deep_replace( $search, $subject ) {
		$subject = (string) $subject;
	
		$count = 1;
		while ( $count ) {
			$subject = str_replace( $search, '', $subject, $count );
		}
	
		return $subject;
	}
    
}