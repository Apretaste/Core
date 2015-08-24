<?php 
/* 
 * Smarty plugin 
 * ------------------------------------------------------------- 
 * Type: modifier 
 * Name: money_format 
 * File: modifier.money_format.php 
 * Purpose: format currency amount 
 * Input: string: money value 
 * decimals: number of decimal places 
 * dec_point: string for decimal 
 * thousands_sep: string for thousands separation 
 * Example: {$value|money_format:2:".":","} 
 * Author: Gabriel Birke <birke {at} kontor4.de> 
 * Modfied By: Desean [http://www.eighteencharacters.com] 
 * Modification: Check if string is numeric first 
 * Source URL: http://marc.theaimsgroup.com/?l=smarty-general&m=104972875929464&w=2 
 * Date: 2003-04-07 15:19:14 
 * Modfied on: 16 Aug 2003 
 */ 
function smarty_modifier_money_format($string, $decimals=2, $dec_point=".", $thousands_sep=",") 
{ 
	if (is_numeric($string)) // check if it's a number 
	{ 
		return number_format($string, $decimals, $dec_point, $thousands_sep); 
	} 
	else 
	{ 
		return $string; 
	} 
} 
