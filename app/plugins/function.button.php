<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_button($params, $template)
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
	return "<nobr><a href='mailto:$validEmailAddress?subject=$href&amp;body=$body' style='white-space:nowrap;font-size:11pt;font-family:Arial,Helvetica,sans-serif;color:white;text-decoration:none;font-weight:bold;padding:10px;background-color:#5dbd00' target='_blank'>$caption</a></nobr>";
}
