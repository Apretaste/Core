<?php

class php
{
	/**
	 * Returns a subString delimited by two other strings
	 *
	 * @author salvipascual
	 * @param String $haystack
	 * @param String $start
	 * @param String $end
	 * @return String | Boolean
	 */
	static function substring($haystack, $start, $end) {
		$r = explode($start, $haystack);
		if (isset($r[1])) {
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return false;
	}

	/**
	 * Returns try if $needle is at the starts with $haystack
	 *
	 * @author salvipascual
	 * @param String $haystack
	 * @param String $needle
	 * @return Boolean
	 */
	static function startsWith($haystack, $needle)
	{
		$length = mb_strlen($needle, 'UTF-8');
		return (mb_substr($haystack, 0, $length, 'UTF-8') === $needle);
	}

	/**
	 * Returns try if $needle is at the ends of $haystack
	 *
	 * @author salvipascual
	 * @param String $haystack
	 * @param String $needle
	 * @return Boolean
	 */
	static function endsWith($haystack, $needle)
	{
		$length = mb_strlen($needle, 'UTF-8');
		return $length === 0 || (mb_substr($haystack, -$length, $length, 'UTF-8') === $needle);
	}

	/**
	 * Check if a text exist into a string
	 *
	 * @author salvipascual
	 * @param String $haystack
	 * @param String $needle
	 * @return Boolean
	 */
	static function exists($haystack, $needle)
	{
		return strpos($haystack, $needle) !== false;
	}

	/**
	 * Get the user's IP address
	 *
	 * @author salvipascual
	 * @return string | boolean
	 */
	static function getClientIP()
	{
		// get the IP
		$ip = false;
		if (getenv('HTTP_CLIENT_IP')) $ip = getenv('HTTP_CLIENT_IP');
		else if(getenv('HTTP_X_FORWARDED_FOR')) $ip = getenv('HTTP_X_FORWARDED_FOR');
		else if(getenv('HTTP_X_FORWARDED')) $ip = getenv('HTTP_X_FORWARDED');
		else if(getenv('HTTP_FORWARDED_FOR')) $ip = getenv('HTTP_FORWARDED_FOR');
		else if(getenv('HTTP_FORWARDED')) $ip = getenv('HTTP_FORWARDED');
		else if(getenv('REMOTE_ADDR')) $ip = getenv('REMOTE_ADDR');

		// make sure we return a clean IP
		if($ip) return Connection::escape($ip, 15);
		else return false;
	}
}
