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
function smarty_modifier_replace_url($text, $charlist = null)
{
	$regexp = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

	if(preg_match($regexp, $text, $url))
	{
		$link = smarty_function_link(array("href"=>"NAVEGAR {$url[0]}", "caption"=>"{$url[0]}"), null);
		return preg_replace($regexp, $link, $text);
	}

	return $text;
}
