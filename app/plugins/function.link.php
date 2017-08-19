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

	// create different type of links depending the environment
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "sandbox")
	{
		$wwwhttp = $di->get('path')['http'];
		$linkto = "$wwwhttp/run/display?subject=$href&amp;body=$body";
		return "<a href='$linkto'>$caption</a>";
	}
	elseif($di->get('environment') == "app")
	{
		$popup = empty($params["popup"]) ? "false" : $params["popup"];
		$wait = empty($params["wait"]) ? "true" : $params["wait"];
		$desc = isset($params["desc"]) ? $params["desc"] : ($popup == "true" ? "Inserte una palabra o frase a buscar" : "");
		return "<a onclick=\"apretaste.doaction('$href', $popup, '$desc', $wait); return false;\" href='#!'>$caption</a>";
	}
	else
	{
		$linkto = "mailto:{APRETASTE_EMAIL}?subject=$href&amp;body=$body";
		return "<a href='$linkto'>$caption</a>";
	}
}
