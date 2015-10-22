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
function smarty_modifier_cuba_phone_format($string, $charlist = null)
{
	$phone = preg_replace("/[^0-9]/", "", $string);

//	52-955-637
//	7-836-2499

	return $phone;
}
