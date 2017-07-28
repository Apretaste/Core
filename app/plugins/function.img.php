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
	$href = $params["src"];
	$alt = isset($params["alt"]) ? $params["alt"] : "";
	$width =  isset($params["width"]) ? "width='{$params["width"]}'" : "";
	$height =  isset($params["height"]) ? "height='{$params["height"]}'" : "";

	$file = basename($href);
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "sandbox")
	{
		$wwwroot = $di->get('path')['root'];
		$wwwhttp = $di->get('path')['http'];
		@copy($href, "$wwwroot/public/temp/$file");
		$destination = "$wwwhttp/temp/$file";
	}
	elseif($di->get('environment') == "app")
	{
		$destination = $file;
	}
	else
	{
		$destination = "cid:$file";
	}

	// create and return image
	return "<img src='$destination' alt='$alt' $width $height />";
}
