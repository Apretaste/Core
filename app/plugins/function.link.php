<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_link($params, $template)
{
	// get params
	$href = $params["href"];
	$caption = $params["caption"];

	// get the body if exist
	if(isset($params["body"])) $body = $params["body"];
	else $body = "Envie+el+correo+tal+y+como+esta,+ya+esta+preparado+para+usted";

	// create direct link for the sandbox
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "sandbox")
	{
		$wwwhttp = $di->get('path')['http'];
		$linkto = "$wwwhttp/run/display?subject=$href&amp;body=$body";
	}
	else
	{
		$linkto = "mailto:{APRETASTE_EMAIL}?subject=$href&amp;body=$body";
	}

	// create and return button
	return "<a href='$linkto'>$caption</a>";
}
