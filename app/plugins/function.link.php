<?php

/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */
function smarty_function_link($params, $template)
{
	// get params
	$href = $params["href"];
	$caption = $params["caption"];
	$desc = isset($params["desc"]) ? $params["desc"] : "Inserte una palabra o frase a buscar.";
	$style = isset($params["style"]) ? "style='{$params["style"]}'" : "";
	$title = isset($params["title"]) ? "title='{$params["title"]}'" : "";

	// create link for the web and app
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if(in_array($di->get('environment'), ["app", "web"]))
	{
		$popup = empty($params["popup"]) ? "false" : $params["popup"];
		$wait = empty($params["wait"]) ? "true" : $params["wait"];
		if($popup == "false") $desc = "";
		$onclick = 'apretaste.doaction("'.$href.'", '.$popup.', "'.$desc.'", '.$wait.'); return false;';
		$href = "#!";
	}
	// create link for the email system
	else
	{
		$utils = new Utils();
		$apEmail = $utils->getValidEmailAddress();

		$desc = str_replace("|", " y seguido ", $desc);
		$desc = "$desc\n Agregue el texto en el asunto a continuacion de $href";
		$onclick = "";
		$href = "mailto:$apEmail?subject=$href&amp;body=$desc";
	}

	return "<a href='$href' onclick='$onclick' $title $style>$caption</a>";
}
