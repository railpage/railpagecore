<?php
	/**
	 * Limited functions required by Railpage\RailpageCore
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	

	/** 
	 * Forums - format an IP address
	 * @todo Update for IPv6 addresses
	 * @since Version 2.0
	 * @version 2.0
	 * @param string $dotquad_ip
	 * @return string
	 */
	 
	if (!function_exists("encode_ip")) {
		function encode_ip($dotquad_ip) {
			$ip_sep = explode('.', $dotquad_ip);
			return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
		}
	}
	
	/** 
	 * Forums - Decode a formatted IP address
	 * @todo Update for IPv6 addresses
	 * @since Version 2.0
	 * @version 2.0
	 * @param string $int_ip
	 * @return string
	 */
	
	if (!function_exists("decode_ip")) {
		function decode_ip($int_ip) {
			$hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
			return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
		}
	}
?>