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
	$color = isset($params["color"]) ? $params["color"] : "green";
	$size = isset($params["size"]) ? $params["size"] : "medium";
    $style = isset($params["style"]) ? $params["style"] : "";
    $icon = isset($params["icon"]) ? "<b style='font-size: 25px;'>{$params["icon"]}</b><br/>": "";

	// get the body if exist
	if (isset($params["body"])) $body = $params["body"];
	else $body = "Envie+el+correo+tal+y+como+esta,+ya+esta+preparado+para+usted";

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

	// create direct link for the sandbox
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "sandbox")
	{
		$wwwhttp = $di->get('path')['http'];
		$linkto = "$wwwhttp/run/display?subject=$href&amp;body=$body";
	}
	else
	{
		$utils = new Utils();
		$validEmailAddress = $utils->getValidEmailAddress();
		$linkto = "mailto:$validEmailAddress?subject=$href&amp;body=$body";
	}

	// create and return button
	return "<!--[if mso]>
		<v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' href='$linkto' style='height:{$height}px;v-text-anchor:middle;width:{$width}px;{$style}' arcsize='5%' strokecolor='$stroke' fillcolor='$fill'>
		<w:anchorlock/>
		<center style='color:$text;font-family:Helvetica, Arial,sans-serif;font-size:{$fontsize}px;'>{$icon}$caption</center>
		</v:roundrect>
	<![endif]-->
	<a href='$linkto' style='background-color:$fill;border:1px solid $stroke;border-radius:3px;color:$text;display:inline-block;font-family:sans-serif;font-size:{$fontsize}px;line-height:{$height}px;text-align:center;text-decoration:none;width:{$width}px;-webkit-text-size-adjust:none;mso-hide:all;{$style}'>{$icon}$caption</a>";
}
