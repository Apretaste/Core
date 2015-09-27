<?php

/**
 * Smarty plugin "trim"
 * -------------------------------------------------------------
 * File:	modifier.trim.php
 * Type:	modifier
 * Name:	trim
 * Version: 1.0
 * Author:  Simon Tuck <stu@rtp.ch>, Rueegg Tuck Partner GmbH
 * Purpose: Replaces html br tags in a string with newlines
 * Example: {$someVar|trim:" \t\n\r\0\x0B"}
 * -------------------------------------------------------------
 *
 * @param $string
 * @param null $charlist
 * @return string
 */
//@codingStandardsIgnoreStart
function smarty_modifier_trim($string, $charlist = null)
{
	//@codingStandardsIgnoreEnd
	if ( ! is_null($charlist))
	{
		$string = trim($string, $charlist);
	}else{
		$string = trim($string);
	}
	return $string;
}