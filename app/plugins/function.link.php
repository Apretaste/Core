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
	$href = trim($params["href"]);
	$caption = $params["caption"];
	$desc = isset($params["desc"]) ? $params["desc"] : "Inserte una palabra o frase a buscar.";
	$style = isset($params["style"]) ? "style='{$params["style"]}'" : "";
	$title = isset($params["title"]) ? "title='{$params["title"]}'" : "";
	$onclick = "";

	// create link for the web and app
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	$environment = $di->get('environment');
	if(in_array($environment, ["app", "web"]))
	{
		// set emprty callback for new versions and the web
		$appversion = $di->get('appversion');
		$callback = ($environment=="web" || $appversion > 3.1) ? ',""' : '';

		// create onclick and href params
		$onclick = 'apretaste.doaction("'.$href.'", false, "", true '.$callback.'); return false;';
		$href = "#!";
	}
	// create link for the email system
	else
	{
		$apEmail = Utils::getValidEmailAddress();
		$href = "mailto:$apEmail?subject=$href&amp;body=Inserte una palabra o frase a buscar";
	}

	// create the link
	return "<a href='$href' onclick='$onclick' $title $style>$caption</a>";
}
