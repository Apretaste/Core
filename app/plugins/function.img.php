<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_img($params, $template)
{
	// get params
	$href = basename($params["src"]); 
	$alt = $params["alt"];
	$width = $params["width"];
	$height = $params["height"];

	// create with and height text if is passed
	if($width) $width = "width='$width'";
	if($height) $width = "height='$height'";

	// create and return button
	return "<img src='cid:$href' alt='$alt' $width $height />";
}
