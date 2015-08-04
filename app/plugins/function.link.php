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
	
	// get a valid apretaste email address
	$utils = new Utils();
	$validEmailAddress = $utils->getValidEmailAddress();

	// create and return button
	return "<a href='mailto:$validEmailAddress?subject=$href&amp;body=$body' target='_blank'>$caption</a>";
}
