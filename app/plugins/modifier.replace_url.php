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

	// get the environment
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	$environment = $di->get('environment');

	// check for URL in the code
	if(preg_match($regexp, $text, $url))
	{
		// get domain and URL
		$url = $url[0];
		$domain = str_replace("www.", "", parse_url($url, PHP_URL_HOST));

		// create the link
		if($environment == "web") $link = "<a href='$url' target='_blank'>$domain</a>";
		else $link = smarty_function_link(array("href"=>"WEB $url", "caption"=>"$domain"), null);

		// replace link by <a> element
		return preg_replace($regexp, $link, $text);
	}

	return $text;
}
