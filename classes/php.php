<?php

class php
{
	/**
	 * Returns a subString delimited by two other strings
	 *
	 * @author salvipascual
	 * @param String $text
	 * @param String $start
	 * @param String $end
	 * @return String | Boolean
	 */
	static function substring($txt, $start, $end) {
		$r = explode($start, $txt);
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
		$length = mb_substr($needle, 'UTF-8');
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
}
