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
	$desc = isset($params["desc"]) ? $params["desc"] : "Inserte una palabra o frase a buscar.";

	// create different type of links depending the environment
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "sandbox")
	{
		$wwwhttp = $di->get('path')['http'];
		$linkto = "$wwwhttp/run/display?subject=$href";
		return "<a href='$linkto'>$caption</a>";
	}
	elseif($di->get('environment') == "app")
	{
		$popup = empty($params["popup"]) ? "false" : $params["popup"];
		$wait = empty($params["wait"]) ? "true" : $params["wait"];
		if($popup == "false") $desc = "";
		return "<a onclick=\"apretaste.doaction('$href', $popup, '$desc', $wait); return false;\" href='#!'>$caption</a>";
	}
	else
	{
		$desc = str_replace("|", " y seguido ", $desc);
		$desc = "$desc\n Agregue el texto en el asunto a continuacion de $href";
		$linkto = "mailto:{APRETASTE_EMAIL}?subject=$href&amp;body=$desc";
		return "<a href='$linkto'>$caption</a>";
	}
}
