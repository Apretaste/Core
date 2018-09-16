<?php

/**
 * Smarty plugin
 *
 * @package	Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_button($params, $template)
{
	// get params
	$href = $params["href"];
	$caption = $params["caption"];
	$desc = isset($params["desc"]) ? $params["desc"] : "Inserte una palabra o frase a buscar.";
	$color = isset($params["color"]) ? $params["color"] : "green";
	$size = isset($params["size"]) ? $params["size"] : "medium";
	$style = isset($params["style"]) ? $params["style"] : "";
	$class = isset($params["class"]) ? "class='".$params["class"]."'" : "";
	$id = isset($params["id"]) ? "id='".$params["id"]."'" : "";
	$icon = isset($params["icon"]) ? "<b style='font-size: 25px;'>{$params["icon"]}</b><br/>": "";
	$onclick = "";

	// select the color scheema
	switch ($color)
	{
		case "grey":
			$stroke = '#CCCCCC';
			$fill = '#E6E6E6';
			$text = '#000000';
			break;
		case "blue":
			$stroke = '#2E6DA4';
			$fill = '#337AB7';
			$text = '#FFFFFF';
			break;
		case "red":
			$stroke = '#D43F3A';
			$fill = '#D9534F';
			$text = '#FFFFFF';
			break;
		default:
			$stroke = '#5dbd00';
			$fill = '#5EBB47';
			$text = '#FFFFFF';
	}

	// get the size of the button
	switch ($size)
	{
		case "icon":
			$width = 30;
			$fontsize = 12;
			$height = 20;
			break;
		case "small":
			$width = 80;
			$fontsize = 12;
			$height = 20;
			break;
		case "medium":
			$width = 150;
			$fontsize = 16;
			$height = 44;
			break;
		case "large":
			$width = 220;
			$fontsize = 24;
			$height = 48;
			break;
	}

	// create link for the web and app
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if(in_array($di->get('environment'), array("app", "appnet", "web")))
	{
		$type = isset($params["type"]) ? $params["type"] : "input";
		$popup = empty($params["popup"]) ? "false" : $params["popup"];
		$wait = empty($params["wait"]) ? "true" : $params["wait"];
		if($type != "input") $popup = "'$type'"; // set the type of popup
		if($popup == "false") $desc = "";

		$onclick = "onclick=\"apretaste.doaction('$href', $popup, '$desc', $wait); return false;\"";
		$linkto = "#!";
	}
	// create link for the email system
	else
	{
		$utils = new Utils();
		$apEmail = $utils->getValidEmailAddress();

		$desc = str_replace("|", " y seguido ", $desc);
		$desc = "$desc\n Agregue el texto en el asunto a continuacion de $href";
		$linkto = "mailto:$apEmail?subject=$href&amp;body=$desc";
	}

	$truestyle=($class=="" && $id=="")?"style='background-color:$fill;border:1px solid $stroke;
	border-radius:3px;color:$text;display:inline-block;
	font-family:sans-serif;font-size:{$fontsize}px;
	line-height:{$height}px;text-align:center;text-decoration:none;
	width:{$width}px;-webkit-text-size-adjust:none;mso-hide:all;{$style}'"
	:"style='display:inline-block;font-family:sans-serif;text-align:center;text-decoration:none;
	-webkit-text-size-adjust:none;mso-hide:all;{$style}'";
	
	// create and return button
	return "<!--[if mso]>
		<v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' href='$linkto' style='height:{$height}px;v-text-anchor:middle;width:{$width}px;{$style}' arcsize='5%' strokecolor='$stroke' fillcolor='$fill'>
		<w:anchorlock/>
		<center style='color:$text;font-family:Helvetica, Arial,sans-serif;font-size:{$fontsize}px;'>{$icon}$caption</center>
		</v:roundrect>
	<![endif]-->
	<a href='$linkto' $onclick $id $class $truestyle>{$icon}$caption</a>";
}
